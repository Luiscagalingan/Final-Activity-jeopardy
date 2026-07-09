<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$error = '';
$selectedTeamId = '';
$playerName = '';

$requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($requestMethod === 'POST') {
    $selectedTeamId = trim((string)($_POST['team_id'] ?? ''));
    $playerName = trim((string)($_POST['player_name'] ?? ''));

    if ($selectedTeamId === '') {
        $error = 'Please select a team.';
    } elseif ($playerName === '') {
        $error = 'Please enter your name.';
    } else {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT id, name FROM teams WHERE id = ? LIMIT 1');
        $stmt->execute([$selectedTeamId]);
        $team = $stmt->fetch();

        if (!$team) {
            $error = 'Selected team was not found.';
        } else {
            $memberStmt = $pdo->prepare(
                'SELECT id, full_name, team_id FROM team_members WHERE team_id = ? AND LOWER(TRIM(full_name)) = LOWER(TRIM(?)) LIMIT 1'
            );
            $memberStmt->execute([(int)$team['id'], $playerName]);
            $member = $memberStmt->fetch();

            if (!$member) {
                $error = 'Wrong group or name.';
            } else {
                $_SESSION['player_auth'] = true;
                $_SESSION['player_id'] = (int)$member['id'];
                $_SESSION['player_name'] = $member['full_name'];
                $_SESSION['player_team_id'] = (int)$member['team_id'];
                $_SESSION['player_team_name'] = $team['name'];
                $_SESSION['player_role'] = 'player';

                session_regenerate_id(true);
                header('Location: main_board.php');
                exit;
            }
        }
    }
}

$pdo = get_db();
$teams = $pdo->query('SELECT id, name FROM teams ORDER BY display_order, id')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Player Login - Web Feud</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h1>Player Login</h1>
        <p class="muted">Choose your team and enter your name to join the board.</p>

        <?php if ($error !== ''): ?>
            <p class="error" style="margin: 12px 0;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post">
            <label for="team_id" style="display:block; text-align:left; margin-top:8px;">Team</label>
            <select id="team_id" name="team_id" required style="width:100%; padding:10px; margin:8px 0 12px; border-radius:8px; border:1px solid #334155; background:#0f172a; color:#e2e8f0;">
                <option value="">Select your team</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?php echo (int)$team['id']; ?>" <?php echo ((string)$selectedTeamId === (string)$team['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($team['name'] ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="player_name" style="display:block; text-align:left;">Name</label>
            <input type="text" id="player_name" name="player_name" value="<?php echo htmlspecialchars($playerName); ?>" placeholder="Enter your name" required>

            <button type="submit">Continue to Main Board</button>
        </form>
    </div>
</body>
</html>
