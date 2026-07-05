<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Submit Flag - Web Feud</title>
<link rel="stylesheet" href="../assets/css/style.css">
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
