<?php
session_start();
if (empty($_SESSION['player_auth']) || empty($_SESSION['player_name']) || empty($_SESSION['player_team_id'])) {
    header('Location: player_login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Web Feud - Main Board</title>
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

    body.board-page {
        font-family: 'Segoe UI', Arial, sans-serif;
        color: #e2e8f0;
        background: #0c1f5c !important;
        background-image:
            radial-gradient(circle at 50% 0%, #1a3a8f 0%, #0c1f5c 35%, #060f33 70%, #02071a 100%) !important;
        background-attachment: fixed;
        position: relative;
    }

    body.board-page::after {
        content: "";
        position: fixed;
        inset: 0;
        background: inherit;
        z-index: 0;
        pointer-events: none;
    }

    .board-header, .container {
        position: relative;
        z-index: 5;
    }

    .board-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 32px;
        background: #0d1b4c;
        border-bottom: 1px solid rgba(255, 215, 0, 0.4);
        flex-wrap: wrap;
        gap: 12px;
    }

    .team-badge {
        background: rgba(255, 215, 0, 0.12);
        border: 1px solid rgba(255, 215, 0, 0.5);
        color: #ffd700;
        font-weight: 700;
        font-size: 0.9rem;
        padding: 8px 16px;
        border-radius: 999px;
        white-space: nowrap;
    }

    .player-name-sep {
        opacity: 0.5;
        margin: 0 2px;
    }

    .board-header-content {
        text-align: center;
        flex: 1;
    }

    .board-header-content h1 {
        color: #ffd700;
        font-weight: 800;
        font-size: 1.6rem;
        margin: 4px 0;
        letter-spacing: 0.3px;
    }

    .phase-pill {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 999px;
        background: rgba(255, 215, 0, 0.15);
        border: 1px solid rgba(255, 215, 0, 0.5);
        color: #ffd700;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    .board-header-content .muted {
        color: #b8c4e8;
        margin: 6px 0 0;
        font-size: 0.95rem;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 32px;
    }

    .card {
        background: #0d1b4c;
        border: 1px solid rgba(255, 215, 0, 0.4);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
        padding: 28px;
        text-align: center;
        margin-bottom: 24px;
    }

    .card h2 {
        color: #ffd700;
        font-size: 1.4rem;
        margin: 0;
    }

    /* Circular logo shown during lobby */
    .main-board-center-image {
        text-align: center;
        margin: 8px 0 28px;
    }

    .main-board-center-image img {
        width: 180px;
        height: 180px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #ffd700;
        box-shadow: 0 10px 24px rgba(0, 0, 0, 0.5);
    }

    /* Question / answer panels */
    .question-panel {
        background: #0d1b4c;
        border: 1px solid rgba(255, 215, 0, 0.4);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
        padding: 32px;
        text-align: center;
        font-size: 1.5rem;
        font-weight: 700;
        color: #f1f5ff;
        margin-bottom: 20px;
    }

    .question-panel.answer-panel {
        background: #12235e;
        color: #ffd700;
        font-size: 1.3rem;
    }

    /* Board grid (elimination round) */
    .board-grid {
        display: grid;
        gap: 12px;
        margin-bottom: 28px;
    }

    .board-col-header {
        background: #0a1440;
        border: 1px solid rgba(255, 215, 0, 0.35);
        border-radius: 8px;
        color: #ffd700;
        font-weight: 700;
        text-align: center;
        padding: 14px 8px;
        font-size: 1rem;
        min-height: 64px;
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
        padding: 26px 8px;
        color: #ffd700;
        font-weight: 800;
        font-size: 1.2rem;
    }

    .board-cell.used {
        background: transparent;
        border: 1px dashed #2c4491;
        color: transparent;
    }

    /* Scoreboard */
    .scoreboard {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 14px;
    }

    .score-card {
        background: #0d1b4c;
        border: 1px solid rgba(255, 215, 0, 0.35);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
    }

    .score-card .name {
        color: #f1f5ff;
        font-weight: 700;
        margin-bottom: 8px;
        font-size: 0.95rem;
    }

    .score-card .pts {
        color: #ffd700;
        font-weight: 800;
        font-size: 1.6rem;
    }

    .status-tag {
        display: inline-block;
        margin-left: 6px;
        padding: 2px 9px;
        border-radius: 999px;
        font-size: 0.62rem;
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

    /* CTF panel */
    .ctf-panel {
        background: #0d1b4c;
        border: 1px solid rgba(255, 215, 0, 0.4);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
        padding: 28px;
        text-align: center;
        margin-bottom: 24px;
    }

    .ctf-panel h2 {
        color: #ffd700;
        margin: 0 0 10px;
    }

    .timer {
        font-size: 2.6rem;
        font-weight: 800;
        color: #ffd700;
        margin: 6px 0 16px;
        letter-spacing: 1px;
    }

    .ctf-prompt {
        background: #0a1440;
        border: 1px solid rgba(255, 215, 0, 0.3);
        border-radius: 10px;
        padding: 16px;
        margin: 12px 0;
        font-family: 'Courier New', monospace;
        color: #ffd700;
        word-break: break-word;
    }

    .muted {
        color: #8a94b8;
    }

    /* Winner banner */
    .winner-banner {
        background: linear-gradient(160deg, #12235e 0%, #0d1b4c 100%);
        border: 1px solid #ffd700;
        border-radius: 14px;
        box-shadow: 0 0 30px rgba(255, 215, 0, 0.25), 0 12px 30px rgba(0, 0, 0, 0.5);
        padding: 40px;
        text-align: center;
        font-size: 2rem;
        font-weight: 800;
        color: #ffd700;
        margin-bottom: 24px;
    }
</style>
</head>
<body class="board-page">
    <div class="board-header">
        <div class="team-badge">Team: <?php echo htmlspecialchars($_SESSION['player_team_name'] ?? 'Unknown'); ?> <span class="player-name-sep">·</span> <?php echo htmlspecialchars($_SESSION['player_name'] ?? ''); ?></div>
        <div class="board-header-content">
            <span class="phase-pill" id="phasePill">Loading...</span>
            <h1>Web Feud: Information Security Edition</h1>
            <p class="muted" id="messageLine"></p>
        </div>
    </div>

    <div class="container" id="app"></div>

<script>
const CATEGORY_COLORS = ['','',''];

async function fetchState() {
    try {
        const res = await fetch('../api/state.php?view=board');
        return await res.json();
    } catch (e) {
        return null;
    }
}

function scoreboardHtml(teams) {
    return '<div class="scoreboard">' + teams.map(t => `
        <div class="score-card">
            <div class="name">${escapeHtml(t.name)}
                <span class="status-tag status-${t.status}">${t.status}</span>
            </div>
            <div class="pts">${t.score}</div>
        </div>
    `).join('') + '</div>';
}

function escapeHtml(s) {
    return (s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function render(state) {
    if (!state) return;
    document.getElementById('phasePill').textContent = state.phase.replace('_', ' ');
    document.getElementById('messageLine').textContent = state.message || '';

    let html = '';

    if (state.phase === 'lobby') {
        html += `<div class="card"><h2>Waiting for the host to start the game...</h2></div>`;
        html += `<div class="main-board-center-image"><img src="../pictures/web feud.png" alt="Web Feud" /></div>`;
        html += scoreboardHtml(state.teams);
    }

    if (state.phase === 'elimination') {
        if (state.current_question && state.current_question.question_visible) {
            const q = state.current_question;
            html += `<div class="question-panel">${escapeHtml(q.question)}</div>`;
            if (q.answer_visible) {
                html += `<div class="question-panel answer-panel">Answer: ${escapeHtml(q.answer)}</div>`;
            }
        } else {
            html += boardGridHtml(state.board);
        }
        html += scoreboardHtml(state.teams);
    }

    if (state.phase === 'final_wager') {
        html += `<div class="card"><h2>Last 2 Standing</h2>
            <p class="muted">Finalists are secretly wagering points on the Final Jeopardy question.</p></div>`;
        html += scoreboardHtml(state.teams.filter(t => t.status === 'finalist'));
    }

    if (state.phase === 'final_question') {
        html += `<div class="question-panel">${escapeHtml(state.final.question || '')}</div>`;
        html += scoreboardHtml(state.teams.filter(t => t.status === 'finalist' || t.status === 'winner'));
    }

    if (state.phase === 'final_reveal') {
        html += `<div class="question-panel">${escapeHtml(state.final.question || '')}</div>`;
        html += `<div class="question-panel answer-panel">Answer: ${escapeHtml(state.final.answer || '')}</div>`;
        html += scoreboardHtml(state.teams.filter(t => t.status === 'finalist' || t.status === 'winner'));
    }

    if (state.phase === 'ctf') {
        const c = state.ctf;
        html += `<div class="ctf-panel">
            <h2>CTF Resolution: ${escapeHtml(c.title)}</h2>
            <div class="timer">${formatTime(c.remaining)}</div>
            ${c.prompt_visible ? `<div class="ctf-prompt">${escapeHtml(c.prompt)}</div>` : `<div class="ctf-prompt muted">The cipher is hidden until the host reveals it.</div>`}
            <p class="muted">Finalists: submit your flag from the Team Submission page on your device.</p>
        </div>`;
        html += scoreboardHtml(state.teams.filter(t => t.status === 'finalist' || t.status === 'winner'));
    }

    if (state.phase === 'finished') {
        html += `<div class="winner-banner">🏆 ${escapeHtml(state.winner || '')} wins! 🏆</div>`;
        html += scoreboardHtml(state.teams);
    }

    document.getElementById('app').innerHTML = html;
}

function formatTime(sec) {
    sec = Math.max(0, Math.floor(sec));
    const m = Math.floor(sec / 60), s = sec % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
}

function boardGridHtml(categories) {
    if (!categories || !categories.length) return '';
    const rows = Math.max(...categories.map(c => c.questions.length));
    let html = `<div class="board-grid" style="grid-template-columns: repeat(${categories.length}, 1fr);">`;
    categories.forEach(c => html += `<div class="board-col-header">${escapeHtml(c.name)}</div>`);
    for (let r = 0; r < rows; r++) {
        categories.forEach(c => {
            const q = c.questions[r];
            if (!q) { html += '<div></div>'; return; }
            html += `<div class="board-cell ${q.is_used ? 'used' : ''}">${q.is_used ? '' : '₱' + q.points}</div>`;
        });
    }
    html += '</div>';
    return html;
}

async function loop() {
    const state = await fetchState();
    render(state);
}
loop();
setInterval(loop, 10000);
</script>
</body>
</html>