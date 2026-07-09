<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Submit Flag - Web Feud</title>
<link rel="stylesheet" href="../assets/css/style.css">
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
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
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
        width: 100%;
    }

    .card {
        background: #0d1b4c;
        border: 1px solid rgba(255, 215, 0, 0.4);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
        padding: 32px 30px;
        text-align: center;
    }

    .card h1 {
        color: #ffd700;
        font-weight: 800;
        letter-spacing: 0.5px;
        font-size: 1.9rem;
        margin: 0 0 14px;
    }

    .card .muted {
        color: #b8c4e8;
        font-size: 0.95rem;
        line-height: 1.5;
    }

    .card label {
        display: block;
        text-align: left;
        color: #ffd700;
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin: 16px 0 6px;
    }

    .card select,
    .card input[type="text"] {
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

    .card select {
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='14' height='9' viewBox='0 0 14 9'><path d='M1 1l6 6 6-6' stroke='%23ffd700' stroke-width='2' fill='none' fill-rule='evenodd'/></svg>");
        background-repeat: no-repeat;
        background-position: right 14px center;
        padding-right: 36px;
    }

    .card select:focus,
    .card input[type="text"]:focus {
        border-color: #ffd700;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.25);
    }

    .card input::placeholder {
        color: #6b7ac0;
    }

    .card .ctf-prompt {
        background: #0a1440;
        border: 1px solid rgba(255, 215, 0, 0.3);
        border-radius: 10px;
        padding: 14px;
        margin: 12px 0;
        font-family: 'Courier New', monospace;
        color: #ffd700;
        word-break: break-word;
    }

    .card .btn-primary {
        width: 100%;
        margin-top: 8px;
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

    .card .btn-primary:hover {
        background: #e6c200;
    }

    .card #result {
        margin-top: 12px;
        font-size: 0.95rem;
    }
</style>
</head>
<body>
    <div class="container" style="max-width:600px;">
        <div class="card">
            <h1>CTF flag submission</h1>
            <div id="status" class="muted">Loading game state...</div>
            <div id="formArea"></div>
        </div>
    </div>

<script>
async function fetchState() {
    const res = await fetch('../api/state.php?view=board');
    return res.json();
}

function escapeHtml(s) {
    return (s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

async function render() {
    const state = await fetchState();
    const statusEl = document.getElementById('status');
    const formEl = document.getElementById('formArea');

    if (state.phase !== 'ctf') {
        statusEl.textContent = 'No CTF challenge is active right now. Check the Main Board for the current phase.';
        formEl.innerHTML = '';
        return;
    }

    const finalists = state.teams.filter(t => t.status === 'finalist' || t.status === 'winner');
    statusEl.textContent = state.ctf.winner_team_id
        ? 'A flag has already been captured. The game has ended.'
        : (state.ctf.prompt_visible ? 'Select your team and submit the flag as fast as you can.' : 'Waiting for the host to reveal the cipher.');

    if (state.ctf.winner_team_id) { formEl.innerHTML = ''; return; }

    if (!finalists.length) {
        formEl.innerHTML = `<p class="muted">No eligible finalists are available to submit the flag right now.</p>`;
        return;
    }

    if (!state.ctf.prompt_visible) {
        formEl.innerHTML = `
            <p><strong>${escapeHtml(state.ctf.title)}</strong></p>
            <div class="ctf-prompt muted">The cipher will appear once the host reveals it.</div>
        `;
        return;
    }

    formEl.innerHTML = `
        <p><strong>${escapeHtml(state.ctf.title)}</strong></p>
        <div class="ctf-prompt">${escapeHtml(state.ctf.prompt)}</div>
        <p>Time remaining: <strong>${Math.max(0, Math.floor(state.ctf.remaining))}s</strong></p>
        <label>Your team</label><br>
        <select id="teamSelect">
            ${finalists.map(t => `<option value="${t.id}">${escapeHtml(t.name)}</option>`).join('')}
        </select><br><br>
        <label>Flag</label><br>
        <input type="text" id="flagInput" placeholder="FLAG{...}" style="width:100%">
        <br><br>
        <button class="btn-primary" onclick="submitFlag()">Submit flag</button>
        <p id="result"></p>
    `;
}

async function submitFlag() {
    const teamId = document.getElementById('teamSelect').value;
    const flag = document.getElementById('flagInput').value.trim();
    const form = new FormData();
    form.append('action', 'submit_flag');
    form.append('team_id', teamId);
    form.append('flag', flag);
    const res = await fetch('../api/action.php', { method: 'POST', body: form });
    const data = await res.json();
    const resultEl = document.getElementById('result');
    if (data.correct) {
        resultEl.innerHTML = '<span style="color:#22c55e;font-weight:700;">Correct! You captured the flag!</span>';
    } else {
        resultEl.innerHTML = '<span style="color:#ef4444;">Incorrect flag, try again.</span>';
    }
}

render();
setInterval(render, 2000);
</script>
</body>
</html>