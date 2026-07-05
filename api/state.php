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
    $payload['ctf'] = [
        'title'     => $ctf['title'],
        'prompt'    => $ctf['prompt'],
        'hint'      => $isHost ? $ctf['hint'] : null,
        'remaining' => $remaining,
        'winner_team_id' => $state['ctf_winner_team_id'] ? (int)$state['ctf_winner_team_id'] : null,
    ];
}

if ($state['winner_team_id']) {
    $winner = get_team((int)$state['winner_team_id']);
    $payload['winner'] = $winner['name'] ?? null;
}

json_response($payload);
