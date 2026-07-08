<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$notice = '';
$noticeClass = '';
$activePlayerName = $_SESSION['player_name'] ?? '';
$activeTeamName = $_SESSION['player_team_name'] ?? '';

if (!empty($_POST['player_name'])) {
    $fullName = trim($_POST['player_name']);
    $member = get_team_member_by_name($fullName);

    if ($member && !empty($member['team_id'])) {
        $_SESSION['player_auth'] = true;
        $_SESSION['player_name'] = $member['full_name'];
        $_SESSION['player_team_id'] = (int)$member['team_id'];
        $_SESSION['player_team_name'] = $member['team_name'] ?? 'Unknown team';
        header('Location: main_board.php');
        exit;
    }

    $notice = 'Your name was not found in the registered team list. Please enter your exact full name as listed in the database, or ask the host to add your team.';
    $noticeClass = 'error';
}

if (!empty($_SESSION['player_auth']) && !empty($_SESSION['player_team_id'])) {
    $notice = 'You already have an active player session. Enter a different name if you want to switch players, or use the button below to continue to the main board.';
    $noticeClass = 'info';
}

$registeredTeams = get_registered_team_names();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Player Login - Web Feud</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width:700px; margin-top:60px;">
        <div class="card">
            <h1>Player Login</h1>
            <p class="muted">Enter your full name exactly as it is stored in the database. If your name is registered to a team, you will be redirected to the main board.</p>
            <p class="muted" style="margin-top:8px;"><strong>File location:</strong> this login page is saved in the board folder as player_login.php.</p>

            <?php if ($notice !== ''): ?>
                <div class="alert <?php echo htmlspecialchars($noticeClass); ?>" style="margin-bottom:16px;">
                    <?php echo htmlspecialchars($notice); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($activePlayerName)): ?>
                <p class="muted" style="margin-bottom:12px;">Current player: <strong><?php echo htmlspecialchars($activePlayerName); ?></strong><?php if (!empty($activeTeamName)): ?> · Team: <strong><?php echo htmlspecialchars($activeTeamName); ?></strong><?php endif; ?></p>
            <?php endif; ?>

            <form method="post">
                <label for="player_name">Full name</label>
                <input type="text" id="player_name" name="player_name" placeholder="e.g. Abalos, Kathleen Anne R" required style="width:100%; margin-top:8px; margin-bottom:12px;">
                <button class="btn btn-primary" type="submit">Continue to Main Board</button>
            </form>

            <?php if (!empty($_SESSION['player_auth']) && !empty($_SESSION['player_team_id'])): ?>
                <p style="margin-top:12px;"><a class="btn btn-primary" href="main_board.php">Open Main Board</a></p>
            <?php endif; ?>

            <div style="margin-top:20px;">
                <strong>Registered teams:</strong>
                <ul>
                    <?php foreach ($registeredTeams as $team): ?>
                        <li><?php echo htmlspecialchars($team['name']); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
