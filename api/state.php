<?php
require_once __DIR__ . '/../includes/functions.php';
set_exception_handler(function (Throwable $e): void {
    error_log($e);
    json_response(['error' => 'Server error. Please try again.'], 500);
});
session_start();

$view = $_GET['view'] ?? 'board'; // 'board' (public) or 'host' (includes answers)
$auth = $_GET['auth'] ?? '';
$isHost = $view === 'host' && !empty($_SESSION['host_auth']);

if ($view === 'host' && !$isHost) {
    json_response(['error' => 'Host session expired.', 'redirect' => '../host/login.php'], 401);
}

if ($view === 'board' && empty($_SESSION['player_auth']) && empty($_SESSION['host_auth'])) {
    json_response(['error' => 'Session expired.', 'redirect' => '../board/player_login.php'], 401);
}

if ($auth === 'player' && empty($_SESSION['player_auth'])) {
    json_response(['error' => 'Player session expired.', 'redirect' => '../board/player_login.php'], 401);
}

if ($auth === 'host' && empty($_SESSION['host_auth'])) {
    json_response(['error' => 'Host session expired.', 'redirect' => '../host/login.php'], 401);
}

session_write_close();

$state = get_state();
$teams = get_teams();
$board = get_board();

$payload = [
    'phase'   => $state['phase'],
    'message' => $state['message'],
    'feedback_type' => $state['feedback_type'] ?? null,
    'feedback_team_name' => !empty($state['feedback_team_id'])
        ? (get_team((int)$state['feedback_team_id'])['name'] ?? null)
        : null,
    'feedback_nonce' => (int)($state['feedback_nonce'] ?? 0),
    'teams'   => array_map(function ($t) {
        return [
            'id'     => (int)$t['id'],
            'name'   => $t['name'],
            'score'  => (int)$t['score'],
            'status' => $t['status'],
        ];
    }, $teams),
    'board' => $board,
];

// Current elimination-round question (never send the answer to the public board)
if ($state['current_question_id']) {
    $q = get_question((int)$state['current_question_id']);
    if ($q) {
        $payload['current_question'] = [
            'id'              => (int)$q['id'],
            'category_id'     => (int)$q['category_id'],
            'points'          => (int)$q['points'],
            'question'        => $state['question_visible'] ? $q['question'] : null,
            'answer'          => ($isHost || $state['answer_visible']) ? $q['answer'] : null,
            'question_visible'=> (bool)$state['question_visible'],
            'answer_visible'  => (bool)$state['answer_visible'],
        ];
    }
}

// Buzzer / raise-hand state for the elimination round.
// Reads the same locked JSON file that api/raise_hand.php writes to, and
// only returns it when it matches the currently active question — so a
// buzz from a previous question never bleeds into the next one.
$payload['raised_hand_team_id']   = null;
$payload['raised_hand_team_name'] = null;
$payload['raised_teams']          = [];
$payload['raised_order']          = [];

if (in_array($state['phase'], ['elimination', 'final_question', 'final_reveal'], true)) {
    $questionKey = $state['phase'] === 'elimination' ? 'elimination' : 'final_question';

    $raiseFile = __DIR__ . '/../data/raise_hand.json';
    if (file_exists($raiseFile)) {
        $raw  = file_get_contents($raiseFile);
        $buzz = $raw ? json_decode($raw, true) : null;

        if ($buzz && (string)($buzz['question_key'] ?? '') === $questionKey) {
            $payload['raised_hand_team_id']   = $buzz['first_team_id'] ?? null;
            $payload['raised_hand_team_name'] = $buzz['first_team_name'] ?? null;
            $payload['raised_teams']          = $buzz['raised_teams'] ?? [];
            $raiseOrder = $buzz['raised_order'] ?? [];
            // Backward-compatible fallback for a raise file created before
            // ordered rankings were added.
            if (!$raiseOrder && !empty($buzz['raised_teams'])) {
                foreach (array_slice($buzz['raised_teams'], 0, 6) as $raisedTeamId) {
                    $raisedTeam = get_team((int)$raisedTeamId);
                    if ($raisedTeam) {
                        $raiseOrder[] = [
                            'team_id' => (int)$raisedTeam['id'],
                            'team_name' => $raisedTeam['name'],
                        ];
                    }
                }
            }
            $payload['raised_order'] = array_slice($raiseOrder, 0, 6);
        }
    }
}

// Final Jeopardy (Last 2 Standing) data
if (in_array($state['phase'], ['final_question', 'final_reveal'], true)) {
    $fq = get_final_question();
    $wagers = get_final_wagers();
    $payload['final'] = [
        'question' => ($fq && in_array($state['phase'], ['final_question', 'final_reveal'], true)) ? $fq['question'] : null,
        'answer'   => ($fq && ($isHost || $state['phase'] === 'final_reveal')) ? $fq['answer'] : null,
        'wagers'   => $isHost ? $wagers : array_map(fn($w) => ['team_id' => (int)$w['team_id']], $wagers),
    ];
}

// CTF resolution data (the flag hash itself is NEVER sent to the client)
if ($state['phase'] === 'ctf' && $state['active_ctf_id']) {
    $ctf = get_ctf_challenge((int)$state['active_ctf_id']);
    if (!$ctf) {
        json_response(['error' => 'The active CTF challenge no longer exists.'], 500);
    }
    $competitors = get_ctf_competitors();
    $latestSubmissions = get_latest_flag_submissions_by_team((int)$state['active_ctf_id']);

    // Freeze the countdown at the exact time the second finalist submitted.
    // The CTF remains on screen while both teams wait for host review.
    $timerEnd = time();
    if (count($competitors) > 0 && count($latestSubmissions) >= count($competitors)) {
        $submissionTimes = array_map(
            static fn($submission) => strtotime($submission['submitted_at']),
            $latestSubmissions
        );
        $timerEnd = max($submissionTimes);
    }
    $elapsed = $state['ctf_start_time'] ? ($timerEnd - strtotime($state['ctf_start_time'])) : 0;
    $remaining = max(0, $ctf['duration_seconds'] - $elapsed);
    $myTeamId = !empty($_SESSION['player_team_id']) ? (int)$_SESSION['player_team_id'] : null;
    $payload['ctf'] = [
        'id'             => (int)$ctf['id'],
        'title'          => $ctf['title'],
        'prompt'         => $state['ctf_prompt_visible'] ? $ctf['prompt'] : null,
        'prompt_visible' => (bool)$state['ctf_prompt_visible'],
        'hint'           => $isHost ? $ctf['hint'] : null,
        'answer'         => $isHost ? ($ctf['flag_answer'] ?? null) : null,
        'remaining'      => $remaining,
        'winner_team_id' => $state['ctf_winner_team_id'] ? (int)$state['ctf_winner_team_id'] : null,
        'round'          => get_used_ctf_challenge_count(),
        'submissions_received' => count($latestSubmissions),
        'submissions_needed'   => count($competitors),
        'my_submitted'         => $myTeamId ? isset($latestSubmissions[$myTeamId]) : false,
    ];

    // Host-only: every flag attempt for this challenge, with the raw
    // submitted text and each team's name attached. Never sent to the
    // public board view — it would leak in-progress guesses to other teams.
    if ($isHost) {
        $ctfResultsVisible = $remaining <= 0
            || count($latestSubmissions) >= count($competitors)
            || !empty($state['ctf_winner_team_id']);
        $payload['ctf']['submissions'] = array_map(function ($s) {
            global $ctfResultsVisible;
            return [
                'team_name'  => $s['team_name'],
                'flag'       => $s['submitted_flag'],
                'is_correct' => $ctfResultsVisible ? (bool)$s['is_correct'] : null,
            ];
        }, get_flag_submissions((int)$state['active_ctf_id']));
    }
}

if ($state['winner_team_id']) {
    $winner = get_team((int)$state['winner_team_id']);
    $payload['winner'] = $winner['name'] ?? null;
}

json_response($payload);
