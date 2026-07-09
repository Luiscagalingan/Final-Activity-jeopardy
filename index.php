@ -1,39 +1,39 @@
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Web Feud: Information Security Edition</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container" style="max-width: 1100px; text-align: center; display: flex; flex-direction: column; justify-content: center; min-height: 100vh; padding: 24px;">
        <div style="margin-bottom: 28px;">
            <img src="includes/pics/web%20feud%20logo.png" alt="Web Feud Logo" style="width: 180px; height: 180px; object-fit: cover; border-radius: 50%; border: 4px solid #f5c542; filter: drop-shadow(0 10px 20px rgba(0,0,0,0.5)); margin-bottom: 18px;">
            <h1 style="font-size: 36px; margin-bottom: 10px;">Web Feud: Information Security Edition</h1>
            <p class="muted" style="font-size: 18px; max-width: 680px; margin: 0 auto;">Jeopardy-style elimination round &rarr; Last 2 Standing &rarr; CTF resolution</p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; text-align: left;">
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

            <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column;">
                <h2>Team Flag Submission</h2>
                <p class="muted" style="flex-grow: 1;">Finalists open this on their own device during the CTF stage.</p>
                <a class="btn btn-success" href="team/submit.php" target="_blank" style="text-align: center; width: 100%;">Open Team Submission</a>
            </div>
        </div>
    </div>
</body>
</html>
