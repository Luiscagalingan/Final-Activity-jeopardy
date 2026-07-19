<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

session_start();

$error = '';
$selectedTeamId = '';
$playerName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedTeamId = trim((string)($_POST['team_id'] ?? ''));
    $playerName = trim((string)($_POST['player_name'] ?? ''));

    if ($selectedTeamId === '') {
        $error = 'Please select a team.';
    } elseif ($playerName === '') {
        $error = 'Please enter your name.';
    } else {
        $pdo = get_db();
        $stmt = $pdo->prepare('SELECT id, name FROM teams WHERE id = ? LIMIT 1');
        $stmt->execute([(int)$selectedTeamId]);
        $team = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$team) {
            $error = 'Selected team was not found.';
        } else {
            $searchName = trim($playerName);
            $memberStmt = $pdo->prepare(
                'SELECT id, full_name, team_id FROM team_members WHERE team_id = ? AND (
                    LOWER(TRIM(full_name)) = LOWER(TRIM(?))
                    OR LOWER(TRIM(SUBSTRING_INDEX(full_name, " ", -1))) = LOWER(TRIM(?))
                    OR LOWER(TRIM(full_name)) LIKE LOWER(TRIM(?))
                ) LIMIT 1'
            );
            $memberStmt->execute([(int)$team['id'], $searchName, $searchName, '%' . $searchName . '%']);
            $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

            if (!$member) {
                $error = 'Wrong group or name.';
            } else {
                session_regenerate_id(true);
                $_SESSION['player_auth'] = true;
                $_SESSION['player_id'] = (int)$member['id'];
                $_SESSION['player_name'] = $member['full_name'];
                $_SESSION['player_team_id'] = (int)$member['team_id'];
                $_SESSION['player_team_name'] = $team['name'];
                $_SESSION['player_role'] = 'player';

                header('Location: main_board.php');
                exit;
            }
        }
    }
}

$pdo = get_db();
$teams = $pdo->query('SELECT id, name FROM teams ORDER BY display_order, id')->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Player Login - Web Feud</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
    * {
        box-sizing: border-box;
    }

    html, body.login-page {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: 100% !important;
    }

    body.login-page {
        min-height: 100vh;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-family: 'Segoe UI', Arial, sans-serif;
        background: #0c1f5c !important;
        background-image:
            radial-gradient(circle at 50% 35%, #1a3a8f 0%, #0c1f5c 35%, #060f33 70%, #02071a 100%) !important;
        overflow: hidden;
        position: relative;
    }

    /* Solid backdrop layer to cover any leftover content/text from the shared
       stylesheet so only the gradient shows behind the card */
    body.login-page::after {
        content: "";
        position: fixed;
        inset: 0;
        background: inherit;
        z-index: 0;
    }

    .login-card {
        position: relative;
        z-index: 5;
        width: 100%;
        max-width: 420px;
        margin: 24px;
        padding: 36px 34px 30px;
        text-align: center;
        background: #0d1b4c;
        border: 1px solid rgba(255, 215, 0, 0.4);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
    }

    .login-card h1 {
        margin: 4px 0 4px;
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: 0.5px;
        color: #ffd700;
    }

    .login-card .subtitle-logo {
        display: block;
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        margin: 0 auto 14px;
    }

    .login-card p.muted {
        color: #b8c4e8;
        font-size: 0.95rem;
        line-height: 1.5;
        margin: 0 0 26px;
    }

    .login-card .error {
        background: rgba(220, 38, 38, 0.15);
        border: 1px solid #dc2626;
        color: #fca5a5;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .login-card label {
        display: block;
        text-align: left;
        color: #ffd700;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 16px 0 6px;
    }

    .login-card select,
    .login-card input[type="text"] {
        width: 100%;
        padding: 13px 14px;
        border-radius: 10px;
        border: 2px solid #2c4491;
        background: #0a1440;
        color: #f1f5ff;
        font-size: 1rem;
        outline: none;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
        appearance: none;
        -webkit-appearance: none;
    }

    .login-card select {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='9' viewBox='0 0 14 9'><path d='M1 1l6 6 6-6' stroke='%23ffd700' stroke-width='2' fill='none' fill-rule='evenodd'/></svg>");
        background-repeat: no-repeat;
        background-position: right 14px center;
        padding-right: 36px;
    }

    .login-card select:focus,
    .login-card input[type="text"]:focus {
        border-color: #ffd700;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.25);
    }

    .login-card input[type="text"]::placeholder {
        color: #6b7ac0;
    }

    .login-card button {
        width: 100%;
        margin-top: 24px;
        padding: 13px 14px;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        color: #1a1200;
        background: #ffd700;
        transition: background 0.15s ease;
    }

</style>
</head>
<body class="login-page">
    <div class="login-card">
        <img src="../pictures/web feud.png" alt="Web Feud" class="subtitle-logo">
        <h1>Player Login</h1>
        <p class="muted">Choose your team and enter your name to join the board.</p>

        <?php if ($error !== ''): ?>
            <p class="error" style="margin: 12px 0;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post">
            <label for="team_id">Team</label>
            <select id="team_id" name="team_id" required>
                <option value="">Select your team</option>
                <?php foreach ($teams as $team): ?>
                    <option value="<?php echo (int)$team['id']; ?>" <?php echo ((string)$selectedTeamId === (string)$team['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($team['name'] ?? ''); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="player_name">Name</label>
            <input type="text" id="player_name" name="player_name" value="<?php echo htmlspecialchars($playerName); ?>" placeholder="Enter your full name or surname" required>

            <button type="submit">Continue to Main Board</button>
        </form>
    </div>
</body>
</html>
