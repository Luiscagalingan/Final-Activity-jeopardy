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
    'reveal_answer', 'judge', 'eliminate', 'reinstate', 'start_final',
    'reveal_final_question', 'reveal_final_answer', 'grade_final', 'start_ctf', 'start_cipher', 'next_ctf_round',
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
        // Keep the full duration available while the cipher is hidden.
        // The timer starts only when the host reveals the cipher.
        'ctf_start_time'     => null,
        'ctf_prompt_visible' => 0,
        'ctf_winner_team_id' => null,
        'message'            => $message,
    ]);
}

function resolve_ctf_round_if_ready(int $ctfId): array {
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

    // Keep the CTF round visible after both submissions. The host reviews
    // the revealed verdicts and manually declares the winner.
    update_state([
        'message' => 'Both finalists have submitted. Waiting for the host to review the answers and declare the winner.',
    ]);
    return ['resolved' => false, 'awaiting_host' => true];
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
            // Wrong answers no longer deduct points; scores never go negative.
            $delta = $result === 'correct' ? (int)$q['points'] : 0;
            $stmt = $pdo->prepare('UPDATE teams SET score = score + ? WHERE id = ?');
            $stmt->execute([$delta, $teamId]);
        }

        if ($result === 'wrong') {
            update_state([
                'feedback_type'  => 'wrong',
                'feedback_team_id' => $teamId ?: null,
                'feedback_nonce' => (int)round(microtime(true) * 1000),
            ]);
        }

        if ($result === 'correct' || $result === 'close') {
            // Preserve the 1st-6th raise order after a wrong answer so the
            // host knows which team should answer next. Clear it only when
            // the question is finished.
            clear_raise_hand_state();
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

        if (count($teams) < 2) json_response(['error' => 'At least 2 active teams are needed for Last 2 Standing'], 400);

        $finalists = array_slice($teams, 0, 2);

        foreach ($finalists as $t) {
            $stmt = $pdo->prepare("UPDATE teams SET status = 'finalist' WHERE id = ?");
            $stmt->execute([$t['id']]);
        }
        $stmt = $pdo->prepare("UPDATE teams SET status = 'eliminated' WHERE status = 'active' AND id NOT IN (?, ?)");
        $stmt->execute([(int)$finalists[0]['id'], (int)$finalists[1]['id']]);

        $pdo->exec('DELETE FROM final_wagers');
        foreach ($finalists as $t) {
            $stmt = $pdo->prepare('INSERT INTO final_wagers (team_id, wager) VALUES (?, 0)');
            $stmt->execute([$t['id']]);
        }

        clear_raise_hand_state();

        update_state([
            'phase'   => 'final_ready',
            'message' => 'Last 2 Standing: Review the instructions and wait for the host to reveal the question.',
        ]);
        json_response(['ok' => true, 'finalists' => $finalists]);
        break;
    }

    case 'reveal_final_question': {
        $state = get_state();
        if ($state['phase'] !== 'final_ready') {
            json_response(['error' => 'The Last 2 Standing question is not waiting to be revealed'], 400);
        }
        clear_raise_hand_state();
        update_state([
            'phase' => 'final_question',
            'message' => 'Last 2 Standing question revealed. Finalists may now raise their hands.',
        ]);
        json_response(['ok' => true]);
        break;
    }

    case 'reveal_final_answer': {
        $state = get_state();
        if (!in_array($state['phase'], ['final_question', 'final_reveal'], true)) {
            json_response(['error' => 'Last 2 Standing is not active'], 400);
        }
        update_state(['phase' => 'final_reveal']);
        json_response(['ok' => true]);
        break;
    }

    case 'grade_final': {
        $teamId = (int)$_POST['team_id'];
        $correct = $_POST['correct'] === '1';
        $state = get_state();
        if ($state['phase'] !== 'final_reveal') {
            json_response(['error' => 'Show the final answer on the Main Dashboard before grading finalists'], 400);
        }

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'SELECT fw.team_id, fw.wager, fw.answered_correct, t.name, t.score, t.status
                 FROM final_wagers fw
                 JOIN teams t ON t.id = fw.team_id
                 WHERE fw.team_id = ?
                 FOR UPDATE'
            );
            $stmt->execute([$teamId]);
            $gradedTeam = $stmt->fetch();
            if (!$gradedTeam) {
                $pdo->rollBack();
                json_response(['error' => 'Finalist not found'], 400);
            }
            if ($gradedTeam['answered_correct'] !== null) {
                $pdo->rollBack();
                json_response(['ok' => true, 'already_graded' => true]);
            }

            // Store this team's verdict only. Do not reveal the result,
            // change points, eliminate anyone, or declare a winner until
            // both finalists have been graded.
            $pdo->prepare('UPDATE final_wagers SET answered_correct = ? WHERE team_id = ?')
                ->execute([$correct ? 1 : 0, $teamId]);

            $finalistsStmt = $pdo->query(
                'SELECT fw.team_id, fw.wager, fw.answered_correct, t.name, t.score
                 FROM final_wagers fw
                 JOIN teams t ON t.id = fw.team_id
                 ORDER BY t.score DESC, t.id ASC
                 FOR UPDATE'
            );
            $finalists = $finalistsStmt->fetchAll();
            $ungraded = array_filter($finalists, static fn($f) => $f['answered_correct'] === null);

            if (count($ungraded) > 0) {
                update_state(['message' => 'Final answer recorded. Waiting for the other finalist before revealing results.']);
                $pdo->commit();
                json_response(['ok' => true, 'pending' => true]);
            }

            // Regardless of points or how many finalists answered correctly,
            // both remaining finalists always advance to a CTF decider.
            $ctf = get_unused_ctf_challenge();
            if (!$ctf) {
                update_state(['phase' => 'final_reveal', 'message' => 'Final answers recorded, but no unused CTF challenge remains.']);
                $pdo->commit();
                json_response(['ok' => true, 'resolved' => true, 'no_rounds_left' => true]);
            }
            $pdo->commit();
            begin_ctf_round($ctf, 'Final answers recorded. The two finalists now proceed to the CTF challenge.');
            json_response(['ok' => true, 'resolved' => true, 'next_round' => true]);
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
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

        // Do not let a repeated request restart an already-running timer.
        if (empty($state['ctf_prompt_visible']) || empty($state['ctf_start_time'])) {
            update_state([
                'ctf_prompt_visible' => 1,
                'ctf_start_time'     => date('Y-m-d H:i:s'),
            ]);
        }
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
        if (empty($state['ctf_start_time'])) {
            json_response(['error' => 'The CTF timer has not started yet.'], 400);
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
            'awaiting_host'   => !empty($resolution['awaiting_host']),
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
            'ctf_prompt_visible' => 0, 'ctf_winner_team_id' => null, 'winner_team_id' => null,
            'feedback_type' => null, 'feedback_team_id' => null, 'feedback_nonce' => 0, 'message' => null,
        ]);
        json_response(['ok' => true]);
        break;
    }

    default:
        json_response(['error' => 'Unknown action'], 400);
}
