<?php
require_once __DIR__ . '/../includes/functions.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['pin'] ?? '') === HOST_PIN) {
        $_SESSION['host_auth'] = true;
        header('Location: dashboard.php');
        exit;
    }
    $error = 'Incorrect PIN.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Host Login - Web Feud</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-card">
        <h1>Host Login</h1>
        <p class="muted">Enter the host PIN to control the game.</p>
        <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <form method="post">
            <input type="password" name="pin" placeholder="Host PIN" autofocus required>
            <button type="submit">Log in</button>
        </form>
        <p class="muted small">Default PIN is set in includes/functions.php</p>
    </div>
</body>
</html>
