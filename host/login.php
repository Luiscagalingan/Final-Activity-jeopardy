<?php
require_once __DIR__ . '/../includes/functions.php';
session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['pin'] ?? '') === HOST_PIN) {
        session_regenerate_id(true);
        $_SESSION['host_auth'] = true;
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="UTF-8">
        <title>Logging in...</title>
        </head>
        <body>
        <script>
            localStorage.setItem('webFeudHostAuthChanged', String(Date.now()));
            window.location.replace('dashboard.php');
        </script>
        </body>
        </html>
        <?php
        exit;
    }
    $error = 'Incorrect PIN.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Host Login - Web Feud</title>
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

    .login-card .subtitle-logo {
        display: block;
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 50%;
        margin: 0 auto 14px;
    }

    .login-card h1 {
        margin: 4px 0 4px;
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: 0.5px;
        color: #ffd700;
    }

    .login-card p.muted {
        color: #b8c4e8;
        font-size: 0.95rem;
        line-height: 1.5;
        margin: 0 0 26px;
    }

    .login-card p.muted.small {
        font-size: 0.78rem;
        margin: 16px 0 0;
        color: #6b7ac0;
    }

    .login-card .error {
        background: rgba(220, 38, 38, 0.15);
        border: 1px solid #dc2626;
        color: #fca5a5;
        border-radius: 8px;
        padding: 10px 14px;
        font-size: 0.9rem;
        font-weight: 600;
        margin: 12px 0;
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
    .login-card input[type="text"],
    .login-card input[type="password"] {
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
    .login-card input[type="text"]:focus,
    .login-card input[type="password"]:focus {
        border-color: #ffd700;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.25);
    }

    .login-card input::placeholder {
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

    .login-card button:hover {
        background: #e6c200;
    }
</style>
</head>
<body class="login-page">
    <div class="login-card">
        <img src="../pictures/web feud.png" alt="Web Feud" class="subtitle-logo">
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
