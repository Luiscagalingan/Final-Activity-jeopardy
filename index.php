<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Web Feud: Information Security Edition</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width:700px; text-align:center;">
        <h1 style="margin-top:60px;">Web Feud: Information Security Edition</h1>
        <p class="muted">Jeopardy-style elimination round &rarr; Last 2 Standing &rarr; CTF resolution</p>

        <div class="card">
            <h2>Main Board</h2>
            <p class="muted">Players enter their name here first, then they can access the board.</p>
            <a class="btn btn-primary" href="board/player_login.php?force=1" target="_blank">Open Player Login</a>
        </div>

        <div class="card">
            <h2>Host Dashboard</h2>
            <p class="muted">The host uses this screen to run the game (PIN protected).</p>
            <a class="btn btn-primary" href="host/login.php" target="_blank">Open Host Dashboard</a>
        </div>

        <div class="card">
            <h2>Team Flag Submission</h2>
            <p class="muted">Finalists open this on their own device during the CTF stage.</p>
            <a class="btn btn-primary" href="team/submit.php" target="_blank">Open Team Submission</a>
        </div>
    </div>
</body>
</html>
