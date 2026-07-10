<?php
require_once __DIR__ . '/../includes/functions.php';
set_exception_handler(function (Throwable $e): void {
    error_log($e);
    json_response(['error' => 'Server error. Please try again.'], 500);
});
session_start();

$action = $_POST['action'] ?? '';

// Every action below is a host action and requires the host to be logged in,
// EXCEPT submit_flag, which finalist teams call from their own device.
$hostOnlyActions = [
    'add_team', 'remove_team', 'start_game', 'select_question', 'reveal_question',
    'reveal_answer', 'judge', 'eliminate', 'reinstate', 'start_final', 'set_wager',
    'reveal_final_question', 'grade_final', 'start_ctf', 'start_cipher', 'next_ctf_round',
    'declare_winner', 'reset_game',
];

if (in_array($action, $hostOnlyActions, true) && empty($_SESSION['host_auth'])) {
    json_response(['error' => 'Host session expired. Please log in again.', 'redirect' => '../host/login.php'], 401);
}

// submit_flag is a player action - it must come from a logged-in player,
// and the team it's submitted for is always the player's own session team.
// The posted team_id (if any) is ignored on purpose: trusting a client-
// supplied team_id would let anyone submit, or vote, on behalf of a team
// that isn't theirs.
if ($action === 'submit_flag' && (empty($_SESSION['player_auth']) || empty($_SESSION['player_team_id']))) {
    json_response(['error' => 'Player session expired. Please log in again.', 'redirect' => '../board/player_login.php'], 401);
}

if (!csrf_token_is_valid($_POST['csrf_token'] ?? null)) {
    json_response(['error' => 'Session token expired. Refresh the page and try again.'], 403);
}

$sessionPlayerTeamId = (int)($_SESSION['player_team_id'] ?? 0);
session_write_close();

$pdo = get_db();

function clear_raise_hand_state(): void {
    $raiseFile = __DIR__ . '/../data/raise_hand.json';
    if (file_exists($raiseFile)) {
        @unlink($raiseFile);
    }
}

function begin_ctf_round(array $ctf, string $message): void {
    $pdo = get_db();
    $pdo->prepare('UPDATE ctf_challenges SET is_used = 1 WHERE id = ?')->execute([$ctf['id']]);
    $pdo->exec(
        "UPDATE teams
         SET status = 'finalist'
         WHERE id IN (SELECT team_id FROM final_wagers)"
    );
    update_state([
        'phase'              => 'ctf',
        'active_ctf_id'      => $ctf['id'],
        'ctf_start_time'     => date('Y-m-d H:i:s'),
        'ctf_prompt_visible' => 0,
        'ctf_winner_team_id' => null,
        'message'            => $message,
    ]);
}

function resolve_ctf_round_if_ready(int $ctfId): array {
    $pdo = get_db();
    $competitors = get_ctf_competitors();

    if (count($competitors) < 2) {
        return ['resolved' => false, 'waiting' => true];
    }

    $submissions = get_latest_flag_submissions_by_team($ctfId);
    foreach ($competitors as $team) {
        if (!isset($submissions[(int)$team['id']])) {
            return ['resolved' => false, 'waiting' => true];
        }
    }

    $teamA = $competitors[0];
    $teamB = $competitors[1];
    $aCorrect = (bool)$submissions[(int)$teamA['id']]['is_correct'];
    $bCorrect = (bool)$submissions[(int)$teamB['id']]['is_correct'];

    if ($aCorrect !== $bCorrect) {
        $winner = $aCorrect ? $teamA : $teamB;
        $loser = $aCorrect ? $teamB : $teamA;
        $pdo->prepare("UPDATE teams SET status = 'winner' WHERE id = ?")->execute([$winner['id']]);
        $pdo->prepare("UPDATE teams SET status = 'eliminated' WHERE id = ?")->execute([$loser['id']]);
        update_state([
            'phase'              => 'finished',
            'ctf_winner_team_id' => $winner['id'],
            'winner_team_id'     => $winner['id'],
            'message'            => $winner['name'] . ' submitted the correct flag and wins the game!',
        ]);
        return ['resolved' => true, 'winner_team_id' => (int)$winner['id']];
    }

    $ctf = get_unused_ctf_challenge();
    if (!$ctf) {
        update_state([
            'message' => $aCorrect
                ? 'Both finalists submitted the correct flag. No unused CTF challenges remain, so the host must break the tie manually.'
                : 'Both finalists missed the flag. No unused CTF challenges remain, so the host must break the tie manually.',
        ]);
        return ['resolved' => true, 'tied' => true, 'no_rounds_left' => true];
    }

    begin_ctf_round(
        $ctf,
        $aCorrect
            ? 'Both finalists captured the flag. Starting another CTF round to break the tie.'
            : 'Both finalists missed the flag. Starting another CTF round to break the tie.'
    );
    return ['resolved' => true, 'tied' => true, 'next_round' => true];
}

switch ($action) {

    case 'add_team': {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') json_response(['error' => 'Team name is required'], 400);
        $order = (int)$pdo->query('SELECT COUNT(*) c FROM teams')->fetch()['c'];
        $stmt = $pdo->prepare('INSERT INTO teams (name, display_order) VALUES (?, ?)');
        $stmt->execute([$name, $order]);
        json_response(['ok' => true, 'team_id' => $pdo->lastInsertId()]);
        break;
    }

    case 'remove_team': {
        $stmt = $pdo->prepare('DELETE FROM teams WHERE id = ?');
        $stmt->execute([(int)$_POST['team_id']]);
        json_response(['ok' => true]);
        break;
    }

    case 'start_game': {
        clear_raise_hand_state();
        update_state(['phase' => 'elimination', 'message' => 'Elimination round has begun!']);
        json_response(['ok' => true]);
        break;
    }

    case 'select_question': {
        $qid = (int)$_POST['question_id'];
        $q = get_question($qid);
        if (!$q || $q['is_used']) json_response(['error' => 'Question unavailable'], 400);
        update_state([
            'current_question_id' => $qid,
            'question_visible'    => 0,
            'answer_visible'      => 0,
        ]);
        json_response(['ok' => true]);
        break;
    }

    case 'reveal_question': {
        update_state(['question_visible' => 1]);
        json_response(['ok' => true]);
        break;
    }

    case 'reveal_answer': {
        update_state(['answer_visible' => 1]);
        json_response(['ok' => true]);
        break;
    }

    // Host judges the current question: 'correct', 'wrong', or 'close'
    case 'judge': {
        $teamId = (int)($_POST['team_id'] ?? 0);
        $result = $_POST['result'] ?? 'close';
        $state = get_state();
        $q = get_question((int)$state['current_question_id']);

        if (!$q) json_response(['error' => 'No active question'], 400);

        if ($teamId && in_array($result, ['correct', 'wrong'], true)) {
            $delta = $result === 'correct' ? $q['points'] : -$q['points'];
            $stmt = $pdo->prepare('UPDATE teams SET score = score + ? WHERE id = ?');
            $stmt->execute([$delta, $teamId]);
        }

        clear_raise_hand_state();

        if ($result === 'correct' || $result === 'close') {
            $stmt = $pdo->prepare('UPDATE questions SET is_used = 1 WHERE id = ?');
            $stmt->execute([$q['id']]);
            update_state([
                'current_question_id' => null,
                'question_visible'    => 0,
                'answer_visible'      => 0,
            ]);
        }
        json_response(['ok' => true]);
        break;
    }

    case 'eliminate': {
        $stmt = $pdo->prepare("UPDATE teams SET status = 'eliminated' WHERE id = ?");
        $stmt->execute([(int)$_POST['team_id']]);
        json_response(['ok' => true]);
        break;
    }

    case 'reinstate': {
        $stmt = $pdo->prepare("UPDATE teams SET status = 'active' WHERE id = ?");
        $stmt->execute([(int)$_POST['team_id']]);
        json_response(['ok' => true]);
        break;
    }

    // Move from Elimination round into Last 2 Standing: keep the two
    // highest-scoring active teams as finalists, eliminate the rest.
    case 'start_final': {
        $teams = $pdo->query(
            "SELECT * FROM teams WHERE status = 'active' ORDER BY score DESC, id ASC"
        )->fetchAll();

        if (count($teams) < 2) json_response(['error' => 'Need at least 2 active teams'], 400);

        $finalists = array_slice($teams, 0, 2);
        $rest = array_slice($teams, 2);

        foreach ($finalists as $t) {
            $stmt = $pdo->prepare("UPDATE teams SET status = 'finalist' WHERE id = ?");
            $stmt->execute([$t['id']]);
        }
        foreach ($rest as $t) {
            $stmt = $pdo->prepare("UPDATE teams SET status = 'eliminated' WHERE id = ?");
            $stmt->execute([$t['id']]);
        }

        $pdo->exec('DELETE FROM final_wagers');
        foreach ($finalists as $t) {
            $stmt = $pdo->prepare('INSERT INTO final_wagers (team_id, wager) VALUES (?, 0)');
            $stmt->execute([$t['id']]);
        }

        update_state([
            'phase'   => 'final_wager',
            'message' => 'Last 2 Standing: ' . $finalists[0]['name'] . ' vs ' . $finalists[1]['name'],
        ]);
        json_response(['ok' => true, 'finalists' => $finalists]);
        break;
    }

    case 'set_wager': {
        $teamId = (int)$_POST['team_id'];
        $wager = max(0, (int)$_POST['wager']);
        $team = get_team($teamId);
        if (!$team) json_response(['error' => 'Team not found'], 400);
        $wager = min($wager, max(0, (int)$team['score'])); // can't wager more than current score
        $stmt = $pdo->prepare('UPDATE final_wagers SET wager = ? WHERE team_id = ?');
        $stmt->execute([$wager, $teamId]);
        json_response(['ok' => true]);
        break;
    }

    case 'reveal_final_question': {
        update_state(['phase' => 'final_question']);
        json_response(['ok' => true]);
        break;
    }

    case 'grade_final': {
        $teamId = (int)$_POST['team_id'];
        $correct = $_POST['correct'] === '1';
        $stmt = $pdo->prepare('SELECT wager FROM final_wagers WHERE team_id = ?');
        $stmt->execute([$teamId]);
        $wager = (int)($stmt->fetch()['wager'] ?? 0);
        $delta = $correct ? $wager : -$wager;

        $pdo->prepare('UPDATE teams SET score = score + ? WHERE id = ?')->execute([$delta, $teamId]);
        $pdo->prepare('UPDATE final_wagers SET answered_correct = ? WHERE team_id = ?')
            ->execute([$correct ? 1 : 0, $teamId]);

        if (!$correct) {
            $pdo->prepare("UPDATE teams SET status = 'eliminated' WHERE id = ?")->execute([$teamId]);
        }

        // Once both finalists are graded, decide the outcome
        $ungraded = $pdo->query('SELECT COUNT(*) c FROM final_wagers WHERE answered_correct IS NULL')->fetch()['c'];
        if ((int)$ungraded === 0) {
            $finalists = $pdo->query("SELECT * FROM teams WHERE status = 'finalist' ORDER BY score DESC")->fetchAll();
            if (count($finalists) === 1) {
                $winner = $finalists[0];
                $ctf = get_unused_ctf_challenge();
                if ($ctf) {
                    begin_ctf_round($ctf, 'Final Jeopardy complete! ' . $winner['name'] . ' advances to CTF.');
                } else {
                    $pdo->prepare("UPDATE teams SET status = 'winner' WHERE id = ?")->execute([$winner['id']]);
                    update_state([
                        'phase'          => 'finished',
                        'winner_team_id' => $winner['id'],
                        'message'        => $winner['name'] . ' wins the game!',
                    ]);
                }
            } elseif (count($finalists) === 2) {
                if ($finalists[0]['score'] === $finalists[1]['score']) {
                    $ctf = get_unused_ctf_challenge();
                    if ($ctf) {
                        begin_ctf_round($ctf, 'Scores are tied! Resolve the winner with a live CTF challenge.');
                    } else {
                        update_state(['phase' => 'final_reveal', 'message' => 'Tied, but no CTF challenges remain. Host must break the tie manually.']);
                    }
                } else {
                    $loser = $finalists[1];
                    $pdo->prepare("UPDATE teams SET status = 'eliminated' WHERE id = ?")->execute([$loser['id']]);
                    $winner = $finalists[0];
                    $ctf = get_unused_ctf_challenge();
                    if ($ctf) {
                        begin_ctf_round($ctf, 'Final Jeopardy complete! ' . $winner['name'] . ' advances to CTF.');
                    } else {
                        $pdo->prepare("UPDATE teams SET status = 'winner' WHERE id = ?")->execute([$winner['id']]);
                        update_state([
                            'phase'          => 'finished',
                            'winner_team_id' => $winner['id'],
                            'message'        => $winner['name'] . ' wins the game!',
                        ]);
                    }
                }
            } else {
                update_state(['phase' => 'final_reveal', 'message' => 'Both finalists answered wrong. Host must decide the next step.']);
            }
        }
        json_response(['ok' => true]);
        break;
    }

    // Manually (re)start a CTF challenge, e.g. if the host wants a fresh timer
    case 'start_ctf': {
        $ctf = get_unused_ctf_challenge();
        if (!$ctf) json_response(['error' => 'No unused CTF challenges left'], 400);
        begin_ctf_round($ctf, 'CTF challenge started. Reveal the cipher when both finalists are ready.');
        json_response(['ok' => true]);
        break;
    }

    case 'start_cipher': {
        $state = get_state();
        if ($state['phase'] !== 'ctf' || !$state['active_ctf_id']) {
            json_response(['error' => 'No CTF challenge is currently active'], 400);
        }
        update_state(['ctf_prompt_visible' => 1]);
        json_response(['ok' => true]);
        break;
    }

    // Sudden-death tiebreaker: the timer ran out on the current CTF
    // challenge with neither team capturing the flag. Pull a fresh random
    // unused challenge and start another round. This can repeat
    // indefinitely - only a correct flag submission (handled in
    // submit_flag below) ever ends the game.
    case 'next_ctf_round': {
        $state = get_state();
        if ($state['phase'] !== 'ctf') {
            json_response(['error' => 'The game is not currently in the CTF stage'], 400);
        }
        if ($state['ctf_winner_team_id']) {
            json_response(['error' => 'A winner has already been decided for this CTF'], 400);
        }

        $ctf = get_unused_ctf_challenge();
        if (!$ctf) {
            json_response(['error' => 'No unused CTF challenges left. Add more to the ctf_challenges table, or declare a winner manually.'], 400);
        }

        begin_ctf_round($ctf, 'Still tied! Starting another CTF round to break the tie.');
        json_response(['ok' => true]);
        break;
    }

    // Called from the team submission page - requires a logged-in player
    // (enforced above). The team is always the player's own session team;
    // any team_id posted from the client is ignored so a player can never
    // submit on behalf of a team that isn't theirs.
    case 'submit_flag': {
        $teamId = $sessionPlayerTeamId;
        $flag = trim($_POST['flag'] ?? '');
        $state = get_state();

        if ($state['phase'] !== 'ctf' || !$state['active_ctf_id']) {
            json_response(['error' => 'No CTF challenge is currently active'], 400);
        }
        if ($state['ctf_winner_team_id']) {
            json_response(['ok' => true, 'already_won' => true]);
        }
        if (empty($state['ctf_prompt_visible'])) {
            json_response(['error' => 'The host has not revealed the cipher yet.'], 400);
        }

        $team = get_team($teamId);
        if (!$team || !in_array($team['status'], ['finalist', 'winner'], true)) {
            json_response(['error' => 'Your team is not eligible to submit the flag'], 400);
        }

        // Reject submissions after the timer has already run out, so a
        // slow network request can't sneak a flag in after time's up.
        $ctf = get_ctf_challenge((int)$state['active_ctf_id']);
        $elapsed = $state['ctf_start_time'] ? (time() - strtotime($state['ctf_start_time'])) : 0;
        $remaining = $ctf['duration_seconds'] - $elapsed;
        if ($remaining <= 0) {
            json_response(['error' => "Time's up for this round."], 400);
        }

        $existingStmt = $pdo->prepare('SELECT id FROM flag_submissions WHERE ctf_id = ? AND team_id = ? LIMIT 1');
        $existingStmt->execute([$ctf['id'], $teamId]);
        if ($existingStmt->fetch()) {
            json_response(['ok' => true, 'already_submitted' => true, 'pending' => true]);
        }

        $isCorrect = hash('sha256', $flag) === $ctf['flag_hash'];

        // Store the raw text the team typed (not just the verdict) so the
        // host can review every attempt on the dashboard, including near
        // misses and typos.
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO flag_submissions (ctf_id, team_id, submitted_flag, is_correct) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$ctf['id'], $teamId, $flag, $isCorrect ? 1 : 0]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                json_response(['ok' => true, 'already_submitted' => true, 'pending' => true]);
            }
            throw $e;
        }

        $resolution = resolve_ctf_round_if_ready((int)$ctf['id']);

        json_response([
            'ok'              => true,
            'submitted'       => true,
            'pending'         => !empty($resolution['waiting']),
            'round_resolved'  => !empty($resolution['resolved']),
            'winner_team_id'  => $resolution['winner_team_id'] ?? null,
            'next_round'      => !empty($resolution['next_round']),
            'no_rounds_left'  => !empty($resolution['no_rounds_left']),
        ]);
        break;
    }

    case 'declare_winner': {
        $teamId = (int)$_POST['team_id'];
        $team = get_team($teamId);
        $pdo->prepare("UPDATE teams SET status = 'winner' WHERE id = ?")->execute([$teamId]);
        update_state([
            'phase'          => 'finished',
            'winner_team_id' => $teamId,
            'message'        => ($team['name'] ?? 'A team') . ' wins the game!',
        ]);
        json_response(['ok' => true]);
        break;
    }

    case 'reset_game': {
        $pdo->exec('UPDATE questions SET is_used = 0');
        $pdo->exec('UPDATE ctf_challenges SET is_used = 0');
        $pdo->exec("UPDATE teams SET score = 0, status = 'active'");
        $pdo->exec('DELETE FROM final_wagers');
        $pdo->exec('DELETE FROM flag_submissions');
        clear_raise_hand_state();
        update_state([
            'phase' => 'lobby', 'current_question_id' => null, 'question_visible' => 0,
            'answer_visible' => 0, 'active_ctf_id' => null, 'ctf_start_time' => null,
            'ctf_prompt_visible' => 0, 'ctf_winner_team_id' => null, 'winner_team_id' => null, 'message' => null,
        ]);
        json_response(['ok' => true]);
        break;
    }

    default:
        json_response(['error' => 'Unknown action'], 400);
}
