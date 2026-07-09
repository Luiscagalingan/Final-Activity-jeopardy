<?php
require_once __DIR__ . '/../includes/functions.php';
session_start();

$view = $_GET['view'] ?? 'board'; // 'board' (public) or 'host' (includes answers)
$isHost = $view === 'host' && !empty($_SESSION['host_auth']);

$state = get_state();
$teams = get_teams();
$board = get_board();

$payload = [
    'phase'   => $state['phase'],
    'message' => $state['message'],
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

if ($state['phase'] === 'elimination') {
    // Must mirror the front end's shared elimination-turn raise key.
    $questionKey = 'elimination';

    $raiseFile = __DIR__ . '/../data/raise_hand.json';
    if (file_exists($raiseFile)) {
        $raw  = file_get_contents($raiseFile);
        $buzz = $raw ? json_decode($raw, true) : null;

        if ($buzz && (string)($buzz['question_key'] ?? '') === $questionKey) {
            $payload['raised_hand_team_id']   = $buzz['first_team_id'] ?? null;
            $payload['raised_hand_team_name'] = $buzz['first_team_name'] ?? null;
            $payload['raised_teams']          = $buzz['raised_teams'] ?? [];
        }
    }
}

// Final Jeopardy (Last 2 Standing) data
if (in_array($state['phase'], ['final_wager', 'final_question', 'final_reveal'], true)) {
    $fq = get_final_question();
    $wagers = get_final_wagers();
    $payload['final'] = [
        'question' => (in_array($state['phase'], ['final_question', 'final_reveal'], true)) ? $fq['question'] : null,
        'answer'   => ($isHost || $state['phase'] === 'final_reveal') ? $fq['answer'] : null,
        'wagers'   => $isHost ? $wagers : array_map(fn($w) => ['team_id' => (int)$w['team_id']], $wagers),
    ];
}

// CTF resolution data (the flag hash itself is NEVER sent to the client)
if ($state['phase'] === 'ctf' && $state['active_ctf_id']) {
    $ctf = get_ctf_challenge((int)$state['active_ctf_id']);
    $elapsed = $state['ctf_start_time'] ? (time() - strtotime($state['ctf_start_time'])) : 0;
    $remaining = max(0, $ctf['duration_seconds'] - $elapsed);
    $competitors = get_ctf_competitors();
    $latestSubmissions = get_latest_flag_submissions_by_team((int)$state['active_ctf_id']);
    $myTeamId = !empty($_SESSION['player_team_id']) ? (int)$_SESSION['player_team_id'] : null;
    $payload['ctf'] = [
        'id'             => (int)$ctf['id'],
        'title'          => $ctf['title'],
        'prompt'         => $state['ctf_prompt_visible'] ? $ctf['prompt'] : null,
        'prompt_visible' => (bool)$state['ctf_prompt_visible'],
        'hint'           => $isHost ? $ctf['hint'] : null,
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
        $payload['ctf']['submissions'] = array_map(function ($s) {
            return [
                'team_name'  => $s['team_name'],
                'flag'       => $s['submitted_flag'],
                'is_correct' => (bool)$s['is_correct'],
            ];
        }, get_flag_submissions((int)$state['active_ctf_id']));
    }
}

if ($state['winner_team_id']) {
    $winner = get_team((int)$state['winner_team_id']);
    $payload['winner'] = $winner['name'] ?? null;
}

json_response($payload);
