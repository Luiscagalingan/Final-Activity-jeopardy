<?php
require_once __DIR__ . '/../includes/functions.php';
host_require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Host Dashboard - Web Feud</title>
<link rel="stylesheet" href="../assets/css/style.css">
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

    <div id="confirmModal" style="display:none; position:fixed; inset:0; background:rgba(2,6,23,.75); align-items:center; justify-content:center; z-index:9999;">
        <div style="background:linear-gradient(135deg,#1e293b,#0f172a); border:1px solid #334155; border-radius:16px; padding:24px; max-width:420px; width:90%; text-align:center; box-shadow:0 20px 50px rgba(0,0,0,.35);">
            <div style="font-size:28px; margin-bottom:8px;">⚠️</div>
            <h3 id="confirmTitle" style="margin-bottom:8px;">Confirm action</h3>
            <p id="confirmMessage" class="muted" style="margin-bottom:18px;">Please confirm this action.</p>
            <div style="display:flex; justify-content:center; gap:10px;">
                <button class="btn btn-sm" onclick="closeConfirmModal(false)">Cancel</button>
                <button class="btn-danger btn-sm" onclick="closeConfirmModal(true)">Continue</button>
            </div>
        </div>
    </div>

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
let teamDraftName = '';
let pendingResetAction = null;

function openConfirmModal(title, message, action) {
    document.getElementById('confirmTitle').textContent = title;
    document.getElementById('confirmMessage').textContent = message;
    document.getElementById('confirmModal').style.display = 'flex';
    pendingResetAction = action;
}

function closeConfirmModal(confirmed) {
    document.getElementById('confirmModal').style.display = 'none';
    if (confirmed && pendingResetAction) {
        pendingResetAction();
    }
    pendingResetAction = null;
}

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
    let html = '<div class="board-grid">';
    categories.forEach(c => html += `<div class="board-col-header">${escapeHtml(c.name)}</div>`);
    for (let r = 0; r < rows; r++) {
        categories.forEach(c => {
            const q = c.questions[r];
            if (!q) { html += '<div></div>'; return; }
            html += `<div class="board-cell ${q.is_used ? 'used' : ''}" onclick="${q.is_used ? '' : `selectQuestion(${q.id})`}">${q.is_used ? '' : '$' + q.points}</div>`;
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
            <h2>1. Add teams for the game</h2>
            <p class="muted">Only the host can add teams here. Players will log in by their full name and be matched to their team automatically.</p>
            <form onsubmit="return addTeam(event)">
                <input type="text" id="newTeamName" placeholder="Team name" required value="${escapeHtml(teamDraftName)}" oninput="teamDraftName = this.value">
                <button class="btn-primary" type="submit" id="addTeamBtn">Add team</button>
            </form>
            <div id="addTeamStatus" style="margin-top:10px;"></div>
        </div>`;
    }

    if (state.phase === 'elimination') {
        if (state.current_question) {
            const q = state.current_question;
            html += `<div class="card">
                <h2>Question for $${q.points}</h2>
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
        html += `<p class="muted">The main board will show “Waiting for the host to start the game...” until you press the button below.</p>`;
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
            teamDraftName = '';
            if (nameInput) {
                nameInput.value = '';
                nameInput.focus();
            }
            await new Promise(r => setTimeout(r, 700));
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
    openConfirmModal('Declare winner', 'Declare this team the winner?', async () => {
        await api('declare_winner', { team_id: teamId });
        loop();
    });
}
async function resetGame() {
    openConfirmModal('Reset entire game', 'This will clear all scores, teams, and progress for the current session.', async () => {
        await api('reset_game');
        loop();
    });
}

async function loop() {
    const currentInput = document.getElementById('newTeamName');
    if (currentInput && currentInput.value) {
        teamDraftName = currentInput.value;
    }
    const state = await fetchState();
    if (state.current_question) state._questionText = state.current_question.question;
    render(state);
}
loop();
setInterval(loop, 1500);
</script>
</body>
</html>
