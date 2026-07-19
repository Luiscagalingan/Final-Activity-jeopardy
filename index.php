<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Web Feud: Information Security Edition</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
    * {
        box-sizing: border-box;
    }

    html, body {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: 100% !important;
    }

    body {
        min-height: 100vh;
        font-family: 'Segoe UI', Arial, sans-serif;
        color: #e2e8f0;
        background: #0c1f5c !important;
        background-image:
            radial-gradient(circle at 50% 35%, #1a3a8f 0%, #0c1f5c 35%, #060f33 70%, #02071a 100%) !important;
        position: relative;
    }

    /* Solid backdrop layer to cover any leftover content/text from the shared
       stylesheet so only the gradient shows behind the page */
    body::after {
        content: "";
        position: fixed;
        inset: 0;
        background: inherit;
        z-index: 0;
    }

    .container {
        position: relative;
        z-index: 5;
    }

    .container h1 {
        color: #ffd700;
        font-weight: 800;
        letter-spacing: 0.5px;
    }

    .container p.muted {
        color: #b8c4e8;
    }

    .card {
        background: #0d1b4c;
        border: 1px solid rgba(255, 215, 0, 0.4);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
        padding: 26px 24px;
    }

    .card h2 {
        color: #ffd700;
        font-size: 1.25rem;
        margin: 0 0 10px;
    }

    .card p.muted {
        color: #b8c4e8;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .btn {
        display: inline-block;
        padding: 13px 14px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1rem;
        letter-spacing: 0.5px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: filter 0.15s ease, transform 0.05s ease;
    }

    .btn:hover {
        filter: brightness(1.08);
    }

    .btn:active {
        transform: translateY(1px);
    }

    .btn-primary {
        background: #ffd700;
        color: #1a1200;
    }

    .btn-warning {
        background: #f5a623;
        color: #1a1200;
    }

    .btn-success {
        background: #2ecc71;
        color: #06210f;
    }
</style>
</head>
<body>
    <div class="container" style="max-width: 1100px; text-align: center; display: flex; flex-direction: column; justify-content: center; min-height: 100vh; padding: 24px;">
        <div style="margin-bottom: 28px;">
            <img src="pictures/web%20feud.png" alt="Web Feud Logo" style="width: 180px; height: 180px; object-fit: cover; border-radius: 50%; border: 4px solid #f5c542; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.5)); margin-bottom: 18px;">
            <h1 style="font-size: 36px; margin-bottom: 10px;">Web Feud: Information Security Edition</h1>
            <p class="muted" style="font-size: 18px; max-width: 680px; margin: 0 auto;">Jeopardy-style elimination round &rarr; Last 2 Standing &rarr; CTF resolution</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px; text-align: left; max-width: 760px; margin: 0 auto;">
            <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column;">
                <h2>Main Board</h2>
                <p class="muted" style="flex-grow: 1;">Players enter their name here first, then they can access the board.</p>
                <a class="btn btn-primary" href="board/player_login.php" target="_blank" style="text-align: center; width: 100%;">Open Main Dashboard</a>
            </div>

            <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column;">
                <h2>Host Dashboard</h2>
                <p class="muted" style="flex-grow: 1;">The host uses this screen to run the game (PIN protected).</p>
                <a class="btn btn-warning" href="host/login.php" target="_blank" style="text-align: center; width: 100%;">Open Host Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>