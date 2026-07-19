<?php
/**
 * api/raise_hand.php
 *
 * Records buzzer state for the currently active question:
 *  - `first`: the one team that buzzed in first (decided via an exclusive
 *    file lock, so simultaneous clicks still resolve to exactly one winner)
 *  - `raised`: every team_id that has clicked at all, so each team's own
 *    button can lock after their single click even if they weren't first
 *
 * Expects POST JSON: { "question_key": "<unique id of current question>" }
 *
 * Returns JSON:
 *   {
 *     success: true|false,        // true only if THIS request was first
 *     already_raised: true|false, // true if this team had already clicked before
 *     first_team_id, first_team_name, raised_at,
 *     raised_teams: [team_id, ...]
 *   }
 */

require_once __DIR__ . '/../includes/functions.php';
set_exception_handler(function (Throwable $e): void {
    error_log($e);
    json_response(['success' => false, 'error' => 'Server error. Please try again.'], 500);
});

session_start();

if (empty($_SESSION['player_auth']) || empty($_SESSION['player_team_id'])) {
    json_response(['success' => false, 'error' => 'Player session expired.', 'redirect' => '../board/player_login.php'], 401);
}

$sessionTeamId = (int)$_SESSION['player_team_id'];
$sessionTeamName = $_SESSION['player_team_name'] ?? 'Unknown Team';
if (!csrf_token_is_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
    json_response(['success' => false, 'error' => 'Session token expired. Refresh the page and try again.'], 403);
}
session_write_close();

$input = json_decode(file_get_contents('php://input'), true);
$questionKey = isset($input['question_key']) ? (string)$input['question_key'] : null;

if (!$questionKey) {
    json_response(['success' => false, 'error' => 'Missing question_key'], 400);
}

$teamId   = $sessionTeamId;
$team = get_team($teamId);
$teamName = $team['name'] ?? $sessionTeamName;

$gameState = get_state();
$phase = $gameState['phase'] ?? '';
$isEliminationRaise = $phase === 'elimination' && $questionKey === 'elimination';
$isFinalRaise = in_array($phase, ['final_question', 'final_reveal'], true) && $questionKey === 'final_question';
if (!$isEliminationRaise && !$isFinalRaise) {
    json_response(['success' => false, 'error' => 'This is not the active raise window.'], 400);
}
if ($isEliminationRaise && !empty($gameState['question_visible'])) {
    json_response(['success' => false, 'error' => 'Raising is closed for this question.'], 400);
}
$eligibleStatus = $isFinalRaise ? 'finalist' : 'active';
if (!$team || $team['status'] !== $eligibleStatus) {
    json_response(['success' => false, 'error' => 'Your team is not eligible to raise.'], 400);
}

$dataDir  = __DIR__ . '/../data';
$dataFile = $dataDir . '/raise_hand.json';

if (!is_dir($dataDir)) {
    mkdir($dataDir, 0755, true);
}

$fp = fopen($dataFile, 'c+');
if (!$fp) {
    json_response(['success' => false, 'error' => 'Could not open buzzer state file'], 500);
}

flock($fp, LOCK_EX);

$raw   = stream_get_contents($fp);
$state = $raw ? json_decode($raw, true) : null;

$isNewQuestion = !$state || ($state['question_key'] ?? null) !== $questionKey;

if ($isNewQuestion) {
    $state = [
        'question_key' => $questionKey,
        'first_team_id'   => null,
        'first_team_name' => null,
        'raised_at'       => null,
        'raised_teams'    => [],
        'raised_order'    => [],
    ];
}

$alreadyRaisedByThisTeam = in_array($teamId, $state['raised_teams'], true);

if (!$alreadyRaisedByThisTeam) {
    $state['raised_teams'][] = $teamId;
    $state['raised_order'][] = [
        'team_id' => $teamId,
        'team_name' => $teamName,
        'raised_at' => microtime(true),
    ];
}

$wonTheBuzz = false;
if ($state['first_team_id'] === null) {
    // Nobody has buzzed in yet for this question -> this click wins.
    $state['first_team_id']   = $teamId;
    $state['first_team_name'] = $teamName;
    $state['raised_at']       = microtime(true);
    $wonTheBuzz = true;
}

ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($state));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

json_response([
    'success'         => $wonTheBuzz,
    'already_raised'  => $alreadyRaisedByThisTeam,
    'first_team_id'   => $state['first_team_id'],
    'first_team_name' => $state['first_team_name'],
    'raised_at'       => $state['raised_at'],
    'raised_teams'    => $state['raised_teams'],
    'raised_order'    => array_slice($state['raised_order'] ?? [], 0, 6),
    'position'        => array_search($teamId, $state['raised_teams'], true) + 1,
]);
