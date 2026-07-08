<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$notice = '';
$noticeClass = '';
$forceLogin = !empty($_GET['force']) || !empty($_POST['force']);

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

    $registeredTeams = get_registered_team_names();
    $teamList = implode(', ', array_map(static fn($team) => $team['name'], $registeredTeams));
    $notice = 'Your name was not found in the registered team list. Please enter your exact full name as listed in the database, or ask the host to add your team.';
    $noticeClass = 'error';
}

if (!empty($_SESSION['player_auth']) && !empty($_SESSION['player_team_id']) && empty($forceLogin)) {
    header('Location: main_board.php');
    exit;
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

            <?php if ($notice !== ''): ?>
                <div class="alert <?php echo htmlspecialchars($noticeClass); ?>" style="margin-bottom:16px;">
                    <?php echo htmlspecialchars($notice); ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <input type="hidden" name="force" value="1">
                <label for="player_name">Full name</label>
                <input type="text" id="player_name" name="player_name" placeholder="e.g. Abalos, Kathleen Anne R" required style="width:100%; margin-top:8px; margin-bottom:12px;">
                <button class="btn btn-primary" type="submit">Continue to Main Board</button>
            </form>

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
