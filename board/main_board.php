<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Web Feud - Main Board</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="board-page">
    <div class="board-header">
        <span class="phase-pill" id="phasePill">Loading...</span>
        <h1>Web Feud: Information Security Edition</h1>
        <p class="muted" id="messageLine"></p>
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
    let html = '<div class="board-grid">';
    categories.forEach(c => html += `<div class="board-col-header">${escapeHtml(c.name)}</div>`);
    for (let r = 0; r < rows; r++) {
        categories.forEach(c => {
            const q = c.questions[r];
            if (!q) { html += '<div></div>'; return; }
            html += `<div class="board-cell ${q.is_used ? 'used' : ''}">${q.is_used ? '' : '$' + q.points}</div>`;
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
setInterval(loop, 1500);
</script>
</body>
</html>
