<?php
require_once __DIR__ . '/../includes/functions.php';
session_start();

$action = $_POST['action'] ?? '';

// Every action below is a host action and requires the host to be logged in,
// EXCEPT submit_flag, which finalist teams call from their own device.
$hostOnlyActions = [
    'add_team', 'remove_team', 'start_game', 'select_question', 'reveal_question',
    'reveal_answer', 'judge', 'eliminate', 'reinstate', 'start_final', 'set_wager',
    'reveal_final_question', 'grade_final', 'start_ctf', 'start_cipher', 'declare_winner', 'reset_game',
];

if (in_array($action, $hostOnlyActions, true) && empty($_SESSION['host_auth'])) {
    json_response(['error' => 'Not authorized. Please log in as host.'], 401);
}

$pdo = get_db();

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
                    $pdo->prepare('UPDATE ctf_challenges SET is_used = 1 WHERE id = ?')->execute([$ctf['id']]);
                    update_state([
                        'phase'              => 'ctf',
                        'active_ctf_id'      => $ctf['id'],
                        'ctf_start_time'     => date('Y-m-d H:i:s'),
                        'ctf_prompt_visible' => 0,
                        'message'            => 'Final Jeopardy complete! ' . $winner['name'] . ' advances to CTF.',
                    ]);
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
                        $pdo->prepare('UPDATE ctf_challenges SET is_used = 1 WHERE id = ?')->execute([$ctf['id']]);
                        update_state([
                            'phase'              => 'ctf',
                            'active_ctf_id'      => $ctf['id'],
                            'ctf_start_time'     => date('Y-m-d H:i:s'),
                            'ctf_prompt_visible' => 0,
                            'message'            => 'Scores are tied! Resolve the winner with a live CTF challenge.',
                        ]);
                    } else {
                        update_state(['phase' => 'final_reveal', 'message' => 'Tied, but no CTF challenges remain. Host must break the tie manually.']);
                    }
                } else {
                    $loser = $finalists[1];
                    $pdo->prepare("UPDATE teams SET status = 'eliminated' WHERE id = ?")->execute([$loser['id']]);
                    $winner = $finalists[0];
                    $ctf = get_unused_ctf_challenge();
                    if ($ctf) {
                        $pdo->prepare('UPDATE ctf_challenges SET is_used = 1 WHERE id = ?')->execute([$ctf['id']]);
                        update_state([
                            'phase'              => 'ctf',
                            'active_ctf_id'      => $ctf['id'],
                            'ctf_start_time'     => date('Y-m-d H:i:s'),
                            'ctf_prompt_visible' => 0,
                            'message'            => 'Final Jeopardy complete! ' . $winner['name'] . ' advances to CTF.',
                        ]);
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
        $pdo->prepare('UPDATE ctf_challenges SET is_used = 1 WHERE id = ?')->execute([$ctf['id']]);
        update_state([
            'phase'              => 'ctf',
            'active_ctf_id'      => $ctf['id'],
            'ctf_start_time'     => date('Y-m-d H:i:s'),
            'ctf_prompt_visible' => 0,
        ]);
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

    // Called from the team submission page - no host login required
    case 'submit_flag': {
        $teamId = (int)($_POST['team_id'] ?? 0);
        $flag = trim($_POST['flag'] ?? '');
        $state = get_state();

        if ($state['phase'] !== 'ctf' || !$state['active_ctf_id']) {
            json_response(['error' => 'No CTF challenge is currently active'], 400);
        }
        if ($state['ctf_winner_team_id']) {
            json_response(['ok' => true, 'already_won' => true]);
        }

        $team = get_team($teamId);
        if (!$team || !in_array($team['status'], ['finalist', 'winner'], true)) {
            json_response(['error' => 'Team is not eligible to submit the flag'], 400);
        }

        $ctf = get_ctf_challenge((int)$state['active_ctf_id']);
        $isCorrect = hash('sha256', $flag) === $ctf['flag_hash'];

        $stmt = $pdo->prepare('INSERT INTO flag_submissions (ctf_id, team_id, is_correct) VALUES (?, ?, ?)');
        $stmt->execute([$ctf['id'], $teamId, $isCorrect ? 1 : 0]);

        if ($isCorrect) {
            update_state(['ctf_winner_team_id' => $teamId]);
            $pdo->prepare("UPDATE teams SET status = 'winner' WHERE id = ?")->execute([$teamId]);
            update_state([
                'phase'          => 'finished',
                'winner_team_id' => $teamId,
            ]);
            $team = get_team($teamId);
            update_state(['message' => $team['name'] . ' captured the flag and wins the game!']);
        }

        json_response(['ok' => true, 'correct' => $isCorrect]);
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
