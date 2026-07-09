<?php
require_once __DIR__ . '/../includes/functions.php';
host_require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Host Dashboard - Web Feud</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
    * {
        box-sizing: border-box;
    }

    html, body {
        margin: 0 !important;
        padding: 0 !important;
        min-height: 100vh;
    }

    body {
        font-family: 'Segoe UI', Arial, sans-serif;
        color: #e2e8f0;
        background: #0c1f5c !important;
        background-image:
            radial-gradient(circle at 50% 0%, #1a3a8f 0%, #0c1f5c 35%, #060f33 70%, #02071a 100%) !important;
        background-attachment: fixed;
        position: relative;
    }

    body::after {
        content: "";
        position: fixed;
        inset: 0;
        background: inherit;
        z-index: 0;
        pointer-events: none;
    }

    .topbar, .container {
        position: relative;
        z-index: 5;
    }

    .topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 28px;
        background: #0d1b4c;
        border-bottom: 1px solid rgba(255, 215, 0, 0.4);
    }

    .topbar strong {
        color: #ffd700;
        font-size: 1.05rem;
        letter-spacing: 0.3px;
    }

    .phase-pill {
        display: inline-block;
        margin-left: 10px;
        padding: 4px 12px;
        border-radius: 999px;
        background: rgba(255, 215, 0, 0.15);
        border: 1px solid rgba(255, 215, 0, 0.5);
        color: #ffd700;
        font-size: 0.72rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .topbar a {
        color: #b8c4e8;
        text-decoration: none;
        font-size: 0.9rem;
        margin-left: 18px;
        transition: filter 0.15s ease, color 0.15s ease, transform 0.05s ease;
    }

    .topbar a.btn {
        background: #ffd700;
        color: #1a1200;
        padding: 9px 14px;
    }

    .topbar a.btn:hover {
        filter: brightness(1.1);
    }

    .topbar a.btn:active {
        transform: translateY(1px);
    }

    .topbar a.logout {
        color: #fca5a5;
        padding: 6px 10px;
        border-radius: 8px;
    }

    .topbar a.logout:hover {
        background: rgba(231, 76, 60, 0.15);
        color: #ff8a8a;
    }

    .container {
        max-width: 1300px;
        margin: 0 auto;
        padding: 28px;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
    }

    .card {
        background: #0d1b4c;
        border: 1px solid rgba(255, 215, 0, 0.4);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
        padding: 24px;
        margin-bottom: 24px;
    }

    .card h2 {
        color: #ffd700;
        font-size: 1.3rem;
        margin: 0 0 14px;
    }

    .card h3 {
        color: #ffd700;
        font-size: 1.05rem;
        margin: 18px 0 10px;
    }

    .muted {
        color: #8a94b8;
    }

    .card p {
        line-height: 1.5;
    }

    input[type="text"],
    input[type="number"] {
        width: 100%;
        padding: 12px 14px;
        border-radius: 10px;
        border: 2px solid #2c4491;
        background: #0a1440;
        color: #f1f5ff;
        font-size: 1rem;
        outline: none;
        margin-bottom: 10px;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    input[type="text"]:focus,
    input[type="number"]:focus {
        border-color: #ffd700;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.25);
    }

    input::placeholder {
        color: #6b7ac0;
    }

    .btn,
    .btn-primary,
    .btn-warning,
    .btn-danger,
    .btn-success,
    .btn-sm {
        display: inline-block;
        padding: 11px 16px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.92rem;
        letter-spacing: 0.3px;
        text-decoration: none;
        border: none;
        cursor: pointer;
        transition: filter 0.15s ease, transform 0.05s ease;
    }

    .btn:hover,
    .btn-primary:hover,
    .btn-warning:hover,
    .btn-danger:hover,
    .btn-success:hover,
    .btn-sm:hover {
        filter: brightness(1.1);
    }

    .btn:active,
    .btn-primary:active,
    .btn-warning:active,
    .btn-danger:active,
    .btn-success:active,
    .btn-sm:active {
        transform: translateY(1px);
    }

    .btn,
    .btn-sm {
        background: #2c4491;
        color: #f1f5ff;
    }

    .btn-primary {
        background: #ffd700;
        color: #1a1200;
    }

    .btn-warning {
        background: #f5a623;
        color: #1a1200;
    }

    .btn-danger {
        background: #e74c3c;
        color: #fff;
    }

    .btn-success {
        background: #2ecc71;
        color: #06210f;
    }

    .btn-sm {
        padding: 7px 12px;
        font-size: 0.82rem;
    }

    .btn:disabled,
    .btn-primary:disabled,
    .btn-sm:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        filter: none;
    }

    /* Team list */
    .team-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #0a1440;
        border: 1px solid #22306b;
        border-radius: 10px;
        padding: 12px 14px;
        margin-bottom: 8px;
    }

    .team-name {
        color: #f1f5ff;
        font-weight: 700;
        margin-right: 10px;
    }

    .team-score {
        color: #ffd700;
        font-weight: 700;
    }

    .status-tag {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        vertical-align: middle;
    }

    .status-active {
        background: rgba(46, 204, 113, 0.15);
        color: #2ecc71;
        border: 1px solid rgba(46, 204, 113, 0.4);
    }

    .status-eliminated {
        background: rgba(231, 76, 60, 0.15);
        color: #e74c3c;
        border: 1px solid rgba(231, 76, 60, 0.4);
    }

    .status-finalist {
        background: rgba(245, 166, 35, 0.15);
        color: #f5a623;
        border: 1px solid rgba(245, 166, 35, 0.4);
    }

    .status-winner {
        background: rgba(255, 215, 0, 0.18);
        color: #ffd700;
        border: 1px solid rgba(255, 215, 0, 0.5);
    }

    /* Board grid */
    .board-grid {
        display: grid;
        gap: 10px;
    }

    .board-col-header {
        background: #0a1440;
        border: 1px solid rgba(255, 215, 0, 0.35);
        border-radius: 8px;
        color: #ffd700;
        font-weight: 700;
        text-align: center;
        padding: 12px 6px;
        font-size: 0.9rem;
        min-height: 58px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1.25;
    }

    .board-cell {
        background: #12235e;
        border: 1px solid #2c4491;
        border-radius: 8px;
        text-align: center;
        padding: 20px 6px;
        color: #ffd700;
        font-weight: 800;
        font-size: 1.05rem;
        cursor: pointer;
        transition: background 0.15s ease, transform 0.05s ease;
    }

    .board-cell:hover {
        background: #1a3a8f;
        transform: translateY(-2px);
    }

    .board-cell.used {
        background: transparent;
        border: 1px dashed #2c4491;
        cursor: default;
        color: transparent;
    }

    .board-cell.used:hover {
        transform: none;
    }

    /* Wagers */
    .wager-row {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #0a1440;
        border: 1px solid #22306b;
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 8px;
    }

    .wager-row .team-name {
        flex: 1;
    }

    .wager-row input[type="number"] {
        width: 110px;
        margin-bottom: 0;
    }

    /* CTF / timer */
    .timer {
        font-size: 2.4rem;
        font-weight: 800;
        color: #ffd700;
        text-align: center;
        margin: 6px 0 16px;
        letter-spacing: 1px;
    }

    .ctf-prompt {
        background: #0a1440;
        border: 1px solid rgba(255, 215, 0, 0.3);
        border-radius: 10px;
        padding: 14px;
        margin: 12px 0;
        font-family: 'Courier New', monospace;
        color: #ffd700;
        word-break: break-word;
    }
</style>
</head>
<body>
    <div class="topbar">
        <div><strong>Web Feud Host Dashboard</strong> <span class="phase-pill" id="phasePill">Loading...</span></div>
        <div>
            <a href="../board/main_board.php" target="_blank" class="btn btn-sm">Open Main Board</a>
            <a href="../team/submit.php" target="_blank" class="btn btn-sm">Open Team Submission</a>
            <a href="logout.php" class="logout">Log out</a>
        </div>
    </div>

    <div class="container" id="app">Loading...</div>

<script>
async function api(action, data = {}) {
    const form = new FormData();
    form.append('action', action);
    for (const k in data) form.append(k, data[k]);
    const res = await fetch('../api/action.php', { 
        method: 'POST', 
        body: form,
        credentials: 'same-origin'
    });
    return res.json();
}

async function fetchState() {
    const res = await fetch('../api/state.php?view=host');
    return res.json();
}

function escapeHtml(s) {
    return (s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

let currentState = null;

function teamListHtml(teams) {
    return teams.map(t => {
        const label = t.status === 'eliminated' ? 'Eliminated'
            : t.status === 'finalist' ? 'Finalist'
            : t.status === 'winner' ? 'Winner'
            : 'Active';
        return `
        <div class="team-row">
            <div><span class="team-name">${escapeHtml(t.name)}</span>
                <span class="status-tag status-${t.status}">${label}</span></div>
            <div>
                <span class="team-score">${t.score} pts</span>
            </div>
        </div>
    `}).join('');
}

function judgeButtonsHtml(teams) {
    return teams.filter(t => t.status === 'active').map(t => `
        <div class="team-row">
            <span class="team-name">${escapeHtml(t.name)}</span>
            <div>
                <button class="btn-sm btn-success" onclick="judge(${t.id}, 'correct')">Correct</button>
                <button class="btn-sm btn-danger" onclick="judge(${t.id}, 'wrong')">Wrong</button>
            </div>
        </div>
    `).join('');
}

function boardGridHtml(categories) {
    const rows = Math.max(...categories.map(c => c.questions.length));
    let html = `<div class="board-grid" style="grid-template-columns: repeat(${categories.length}, 1fr);">`;
    categories.forEach(c => html += `<div class="board-col-header">${escapeHtml(c.name)}</div>`);
    for (let r = 0; r < rows; r++) {
        categories.forEach(c => {
            const q = c.questions[r];
            if (!q) { html += '<div></div>'; return; }
            html += `<div class="board-cell ${q.is_used ? 'used' : ''}" onclick="${q.is_used ? '' : `selectQuestion(${q.id})`}">${q.is_used ? '' : '₱' + q.points}</div>`;
        });
    }
    html += '</div>';
    return html;
}

function render(state) {
    currentState = state;
    document.getElementById('phasePill').textContent = state.phase.replace('_', ' ');
    let html = '';

    // ---------- Team management (always visible) ----------
    html += `<div class="grid-2">`;
    html += `<div>`;

    if (state.phase === 'lobby') {
        html += `<div class="card">
            <h2>1. Register teams</h2>
            <form onsubmit="return addTeam(event)">
                <input type="text" id="newTeamName" placeholder="Team name" required>
                <button class="btn-primary" type="submit" id="addTeamBtn">Add team</button>
            </form>
            <div id="addTeamStatus" style="margin-top:10px;"></div>
        </div>`;
    }

    if (state.phase === 'elimination') {
        if (state.current_question) {
            const q = state.current_question;
            html += `<div class="card">
                <h2>Question for ₱${q.points}</h2>
                ${!q.question_visible ? `<button class="btn-primary" onclick="revealQuestion()">Reveal question on board</button>` : `
                    <p><strong>Q:</strong> ${escapeHtml(state._questionText || '')}</p>
                    ${!q.answer_visible ? `<button class="btn-warning" onclick="revealAnswer()">Reveal answer</button>` : `<p class="muted">Answer: ${escapeHtml(q.answer || '')}</p>`}
                    <h3>Which team answered correctly?</h3>
                    ${judgeButtonsHtml(state.teams)}
                    <button class="btn-sm" onclick="judge(0, 'close')">No correct answer / close question</button>
                `}
            </div>`;
        } else {
            html += `<div class="card"><h2>Elimination round board</h2>${boardGridHtml(state.board)}</div>`;
        }
    }

    if (state.phase === 'final_wager') {
        const finalists = state.teams.filter(t => t.status === 'finalist');
        html += `<div class="card"><h2>Last 2 standing: wagers</h2>
            <p class="muted">Enter each finalist's secret wager (kept off the Main Board).</p>`;
        finalists.forEach(t => {
            const w = (state.final.wagers.find(w => w.team_id === t.id) || {}).wager ?? 0;
            html += `<div class="wager-row">
                <span class="team-name">${escapeHtml(t.name)} (score: ${t.score})</span>
                <input type="number" min="0" max="${t.score}" value="${w}" id="wager_${t.id}">
                <button class="btn-sm" onclick="setWager(${t.id})">Save wager</button>
            </div>`;
        });
        html += `<button class="btn-primary" onclick="revealFinalQuestion()">Reveal final question on board</button></div>`;
    }

    if (state.phase === 'final_question' || state.phase === 'final_reveal') {
        const finalists = state.teams.filter(t => t.status === 'finalist');
        html += `<div class="card"><h2>Final Jeopardy question</h2>
            <p>${escapeHtml(state.final.question || '')}</p>
            <p class="muted">Answer: ${escapeHtml(state.final.answer || '(hidden until graded)')}</p>`;
        finalists.forEach(t => {
            const w = state.final.wagers.find(w => w.team_id === t.id) || {};
            html += `<div class="team-row">
                <span class="team-name">${escapeHtml(t.name)} (wagered ${w.wager})</span>
                <div>
                    <button class="btn-sm btn-success" onclick="gradeFinal(${t.id}, true)" ${w.answered_correct !== null ? 'disabled' : ''}>Correct</button>
                    <button class="btn-sm btn-danger" onclick="gradeFinal(${t.id}, false)" ${w.answered_correct !== null ? 'disabled' : ''}>Wrong</button>
                </div>
            </div>`;
        });
        html += `</div>`;
    }

    if (state.phase === 'ctf') {
        const c = state.ctf;
        html += `<div class="card">
            <h2>CTF resolution: ${escapeHtml(c.title)}</h2>
            <div class="timer">${formatTime(c.remaining)}</div>
            ${c.prompt_visible ? `
                <div class="ctf-prompt">${escapeHtml(c.prompt)}</div>
            ` : `
                <div class="ctf-prompt muted">The cipher is hidden. Click the button below to reveal it.</div>
                <button class="btn-primary" onclick="revealCipher()">Reveal cipher</button>
            `}
            <p class="muted"><strong>Host hint:</strong> ${escapeHtml(c.hint || '')}</p>
            <p class="muted">Waiting for a finalist to submit the correct flag from the Team Submission page...</p>
            <h3>Manual override</h3>
            ${state.teams.filter(t => t.status === 'finalist').map(t =>
                `<button class="btn-sm btn-warning" onclick="declareWinner(${t.id})">Declare ${escapeHtml(t.name)} winner</button>`
            ).join(' ')}
        </div>`;
    }

    if (state.phase === 'finished') {
        html += `<div class="card"><h2>🏆 ${escapeHtml(state.winner || '')} wins!</h2>
            <button class="btn-danger" onclick="resetGame()">Reset game for a new session</button></div>`;
    }

    html += `</div>`; // close left column

    // ---------- Right column: teams + phase controls ----------
    html += `<div>`;
    const activeCount = state.teams.filter(t => t.status === 'active').length;
    const eliminatedCount = state.teams.filter(t => t.status === 'eliminated').length;
    html += `<div class="card"><h2>Teams</h2>
        <p class="muted">Active: ${activeCount} · Eliminated: ${eliminatedCount}</p>
        ${teamListHtml(state.teams)}
    </div>`;

    html += `<div class="card"><h2>Game controls</h2>`;
    if (state.phase === 'lobby') {
        html += `<button class="btn-primary" ${state.teams.length < 3 ? 'disabled' : ''} onclick="startGame()">Start elimination round</button>`;
    }
    if (state.phase === 'elimination') {
        const activeCount = state.teams.filter(t => t.status === 'active').length;
        html += `<button class="btn-primary" ${activeCount < 2 ? 'disabled' : ''} onclick="startFinal()">Advance to Last 2 Standing</button>`;
    }
    html += `<br><br><button class="btn-danger btn-sm" onclick="resetGame()">Reset entire game</button>`;
    html += `</div>`;
    html += `</div>`; // close right column
    html += `</div>`; // close grid-2

    document.getElementById('app').innerHTML = html;
}

function formatTime(sec) {
    sec = Math.max(0, Math.floor(sec));
    const m = Math.floor(sec / 60), s = sec % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
}

async function addTeam(e) {
    e.preventDefault();
    const nameInput = document.getElementById('newTeamName');
    const name = nameInput.value.trim();
    const statusDiv = document.getElementById('addTeamStatus');
    const btn = document.getElementById('addTeamBtn');
    
    if (!name) {
        statusDiv.innerHTML = '<span style="color:#ef4444;">Team name cannot be empty</span>';
        return false;
    }
    
    btn.disabled = true;
    statusDiv.innerHTML = '<span style="color:#3b82f6;">Adding team...</span>';
    
    try {
        const result = await api('add_team', { name });
        if (result.error) {
            statusDiv.innerHTML = `<span style="color:#ef4444;">Error: ${escapeHtml(result.error)}</span>`;
            btn.disabled = false;
        } else if (result.ok) {
            statusDiv.innerHTML = `<span style="color:#22c55e;">✓ Team added successfully!</span>`;
            nameInput.value = '';
            await new Promise(r => setTimeout(r, 800));
            await loop();
        }
    } catch (error) {
        statusDiv.innerHTML = `<span style="color:#ef4444;">Error: ${escapeHtml(error.message)}</span>`;
        btn.disabled = false;
    }
    return false;
}
async function eliminateTeam(id) { await api('eliminate', { team_id: id }); loop(); }
async function reinstateTeam(id) { await api('reinstate', { team_id: id }); loop(); }
async function startGame() { 
    try {
        const result = await api('start_game');
        if (result.error) {
            alert('Error: ' + result.error);
        } else {
            await loop();
        }
    } catch (e) {
        alert('Failed to start game: ' + e.message);
    }
}
async function selectQuestion(id) { await api('select_question', { question_id: id }); loop(); }
async function revealQuestion() { await api('reveal_question'); loop(); }
async function revealAnswer() { await api('reveal_answer'); loop(); }
async function judge(teamId, result) { await api('judge', { team_id: teamId, result }); loop(); }
async function startFinal() { await api('start_final'); loop(); }
async function setWager(teamId) {
    const wager = document.getElementById('wager_' + teamId).value;
    const result = await api('set_wager', { team_id: teamId, wager });
    if (!result.ok) alert(result.error || 'Unable to save wager.');
    loop();
}
async function revealCipher() {
    const result = await api('start_cipher');
    if (!result.ok) alert(result.error || 'Unable to reveal the cipher.');
    loop();
}
async function revealFinalQuestion() { await api('reveal_final_question'); loop(); }
async function gradeFinal(teamId, correct) { await api('grade_final', { team_id: teamId, correct: correct ? 1 : 0 }); loop(); }
async function declareWinner(teamId) {
    if (!confirm('Declare this team the winner?')) return;
    await api('declare_winner', { team_id: teamId });
    loop();
}
async function resetGame() {
    if (!confirm('This resets ALL scores and progress. Continue?')) return;
    await api('reset_game');
    loop();
}

async function loop() {
    const state = await fetchState();
    if (state.current_question) state._questionText = state.current_question.question;
    render(state);
}
loop();
setInterval(loop, 100000);
</script>
</body>
</html>