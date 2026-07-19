<?php
require_once __DIR__ . '/../includes/functions.php';
board_require_player_or_host();

$isPlayerView = is_player_logged_in();
$viewerLabel = $isPlayerView
    ? ('Team: ' . ($_SESSION['player_team_name'] ?? 'Unknown') . ' | ' . ($_SESSION['player_name'] ?? ''))
    : 'Host board view';
$logoutHref = $isPlayerView ? 'logout.php' : '../host/logout.php';
$csrfToken = csrf_token();
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
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        padding: 20px 32px;
        background: #0d1b4c;
        border-bottom: 1px solid rgba(255, 215, 0, 0.4);
        gap: 12px;
    }

    .board-header .team-badge {
        justify-self: start;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 10px;
        justify-self: end;
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
        opacity: 0.9;
        margin: 0 6px;
        font-size: 1.3rem;
        font-weight: 900;
        color: #ffd700;
        vertical-align: middle;
    }

    .logout-link {
        color: #ffd700;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 700;
        padding: 8px 16px;
        border-radius: 999px;
        border: 1px solid rgba(255, 215, 0, 0.5);
        background: rgba(255, 215, 0, 0.1);
        transition: background 0.15s ease, color 0.15s ease, border-color 0.15s ease;
        white-space: nowrap;
    }

    .logout-link:hover {
        background: rgba(255, 215, 0, 0.22);
        border-color: #ffd700;
        color: #fff4b8;
    }

    .ctf-submit-link {
        display: none;
        color: #0d1b4c;
        text-decoration: none;
        font-size: 0.85rem;
        font-weight: 800;
        padding: 8px 16px;
        border-radius: 999px;
        border: 1px solid #ffd700;
        background: #ffd700;
        white-space: nowrap;
        box-shadow: 0 0 12px rgba(255, 215, 0, 0.6);
        animation: ctf-pulse 1.6s ease-in-out infinite;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .ctf-submit-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 0 18px rgba(255, 215, 0, 0.85);
    }

    .ctf-submit-link.show {
        display: inline-block;
    }

    @keyframes ctf-pulse {
        0%, 100% { box-shadow: 0 0 10px rgba(255, 215, 0, 0.5); }
        50% { box-shadow: 0 0 20px rgba(255, 215, 0, 0.9); }
    }

    .board-header-content {
        text-align: center;
        justify-self: center;
    }

    .board-header-content h1 {
        color: #ffd700;
        font-weight: 800;
        font-size: 1.6rem;
        margin: 4px 0;
        letter-spacing: 0.3px;
        white-space: nowrap;
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

    .raise-panel {
        background: #0d1b4c;
        border: 1px solid rgba(255, 215, 0, 0.4);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
        padding: 18px 22px;
        text-align: center;
        margin: -8px 0 24px;
        display: grid;
        grid-template-columns: minmax(170px, 1fr) minmax(180px, 260px) minmax(170px, 1fr);
        gap: 16px;
        align-items: center;
    }

    .raise-panel h2 {
        color: #ffd700;
        font-size: 1.1rem;
        margin: 0;
        text-align: left;
    }

    .raised-team-name {
        min-height: 46px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #f1f5ff;
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1.2;
        word-break: break-word;
    }

    .raise-btn {
        width: 100%;
        min-height: 54px;
        border: 1px solid #ffd700;
        border-radius: 12px;
        background: #ffd700;
        color: #0d1b4c;
        font-size: 1rem;
        font-weight: 900;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease, opacity 0.15s ease;
        box-shadow: 0 0 14px rgba(255, 215, 0, 0.45);
    }

    .raise-btn:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 0 20px rgba(255, 215, 0, 0.75);
    }

    .raise-btn:disabled {
        cursor: not-allowed;
        opacity: 0.55;
        transform: none;
        box-shadow: none;
    }

    .raise-status {
        color: #8a94b8;
        font-size: 0.85rem;
        min-height: 20px;
        text-align: right;
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
        margin: 2px 0 8px;
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

    .eliminated-banner {
        display: none;
        background: rgba(231, 76, 60, 0.12);
        border: 1px solid rgba(231, 76, 60, 0.45);
        color: #ff8a8a;
        border-radius: 12px;
        padding: 14px 20px;
        text-align: center;
        font-weight: 700;
        font-size: 0.95rem;
        margin-bottom: 20px;
        letter-spacing: 0.2px;
    }

    .eliminated-banner.show {
        display: block;
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

    @media (max-width: 700px) {
        .board-header {
            grid-template-columns: 1fr;
            justify-items: center;
            text-align: center;
        }

        .board-header .team-badge,
        .header-right {
            justify-self: center;
        }

        .raise-panel {
            grid-template-columns: 1fr;
            margin-top: 0;
        }

        .raise-panel h2,
        .raise-status {
            text-align: center;
        }
    }
</style>
</head>
<body class="board-page">
    <div class="board-header">
        <div class="team-badge"><?php echo htmlspecialchars($viewerLabel); ?></div>
        <div class="board-header-content">
            <span class="phase-pill" id="phasePill">Loading...</span>
            <h1>Web Feud: Information Security Edition</h1>
            <p class="muted" id="messageLine"></p>
        </div>
        <div class="header-right">
            <a href="team_submission.php" id="ctfSubmitBtn" class="ctf-submit-link">Submit CTF</a>
            <a href="<?php echo htmlspecialchars($logoutHref); ?>" class="logout-link">Log out</a>
        </div>
    </div>

    <div class="container">
        <div class="eliminated-banner" id="eliminatedBanner">📺 Your team has been eliminated — sit back and enjoy the rest of the show!</div>
        <div id="app"></div>
    </div>

<script>
const myTeamId = <?php echo json_encode($isPlayerView ? (int)$_SESSION['player_team_id'] : null); ?>;
const authMode = <?php echo json_encode($isPlayerView ? 'player' : 'host'); ?>;
const csrfToken = <?php echo json_encode($csrfToken); ?>;
const CATEGORY_COLORS = ['','',''];
let raisePending = false;
let raiseStatusText = '';
let raiseStatusQuestionKey = null;
let lastHostAuthSignal = localStorage.getItem('webFeudHostAuthChanged') || '';
let pollTimer = null;
let polling = false;
let consecutivePollFailures = 0;

function redirectToLogin(path) {
    window.location.replace(path || (authMode === 'host' ? '../host/login.php' : 'player_login.php'));
}

async function fetchJson(url, options = {}, timeoutMs = 8000) {
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), timeoutMs);
    try {
        const res = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            ...options,
            signal: controller.signal
        });
        const text = await res.text();
        let data = null;
        try {
            data = text ? JSON.parse(text) : null;
        } catch (e) {
            throw new Error('Invalid server response');
        }
        if (res.status === 401) {
            redirectToLogin(data && data.redirect);
            return null;
        }
        if (!res.ok) {
            throw new Error((data && data.error) || 'Request failed');
        }
        return data;
    } finally {
        clearTimeout(timer);
    }
}

async function fetchState() {
    return fetchJson(`../api/state.php?view=board&auth=${encodeURIComponent(authMode)}`);
}

function scoreboardHtml(teams) {
    return '<div class="scoreboard">' + teams.map(t => `
        <div class="score-card">
            <div class="name">${escapeHtml(t.name)}</div>
            <div><span class="status-tag status-${t.status}">${t.status}</span></div>
            <div class="pts">${t.score}</div>
        </div>
    `).join('') + '</div>';
}

function escapeHtml(s) {
    return (s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function render(state) {
    if (!state) return;
    const activeQuestionKey = state.current_question ? String(state.current_question.id) : null;
    if (activeQuestionKey !== raiseStatusQuestionKey) {
        raiseStatusQuestionKey = activeQuestionKey;
        raiseStatusText = '';
    }

    document.getElementById('phasePill').textContent = state.phase.replace('_', ' ');
    document.getElementById('messageLine').textContent = state.message || '';

    const myTeam = state.teams.find(t => t.id === myTeamId);
    const isEliminated = !!myTeam && myTeam.status === 'eliminated';
    document.getElementById('eliminatedBanner').classList.toggle('show', isEliminated && state.phase !== 'finished');

    // Only the two teams still in it (finalist, or winner after capturing
    // the flag) should ever see the Submit CTF button — an eliminated
    // team has nothing to submit and is just watching from here on.
    const canSubmitCtf = !!myTeam && (myTeam.status === 'finalist' || myTeam.status === 'winner');
    document.getElementById('ctfSubmitBtn').classList.toggle('show', state.phase === 'ctf' && canSubmitCtf);

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
        html += raisePanelHtml(state, myTeam);
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
            <h2>CTF Resolution: ${escapeHtml(c.title)}${c.round ? ` <span class="muted" style="font-size:1rem;">(Round ${c.round})</span>` : ''}</h2>
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

function raisePanelHtml(state, myTeam) {
    const firstTeamName = state.raised_hand_team_name || '';
    const raisedTeams = Array.isArray(state.raised_teams) ? state.raised_teams.map(Number) : [];
    const alreadyRaised = raisedTeams.includes(Number(myTeamId));
    const questionVisible = !!state.current_question && state.current_question.question_visible;
    const canRaise = !!myTeam
        && myTeam.status === 'active'
        && !state.raised_hand_team_id
        && !alreadyRaised
        && !raisePending
        && !questionVisible;
    const buttonText = raisePending ? 'Raising...' : alreadyRaised ? 'Raised' : state.raised_hand_team_id ? 'Locked' : 'Raise';
    const status = firstTeamName
        ? `${escapeHtml(firstTeamName)} raised first`
        : (raiseStatusText || 'First team to click appears here.');

    return `<div class="raise-panel">
        <h2>First Raise</h2>
        <div class="raised-team-name">${firstTeamName ? escapeHtml(firstTeamName) : '<span class="muted">Waiting...</span>'}</div>
        <button type="button" class="raise-btn" onclick="raiseHand()" ${canRaise ? '' : 'disabled'}>${buttonText}</button>
        <div class="raise-status">${status}</div>
    </div>`;
}

async function raiseHand() {
    if (raisePending) return;

    const state = await fetchState();
    if (!state || state.phase !== 'elimination' || (state.current_question && state.current_question.question_visible)) {
        raiseStatusText = 'No active raise window yet.';
        loop();
        return;
    }

    raisePending = true;
    raiseStatusText = 'Sending raise...';
    render(state);

    try {
        const data = await fetchJson('../api/raise_hand.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken },
            body: JSON.stringify({ question_key: 'elimination' })
        });
        if (!data) return;
        raiseStatusText = data.success
            ? 'You raised first!'
            : (data.first_team_name ? `${data.first_team_name} raised first.` : (data.error || 'Raise was not recorded.'));
    } catch (e) {
        raiseStatusText = 'Could not raise. Please try again.';
    } finally {
        raisePending = false;
        loop();
    }
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
    if (polling) return;
    polling = true;
    try {
        const state = await fetchState();
        if (state) {
            consecutivePollFailures = 0;
            render(state);
        }
    } catch (e) {
        consecutivePollFailures++;
    } finally {
        polling = false;
        clearTimeout(pollTimer);
        pollTimer = setTimeout(loop, consecutivePollFailures ? Math.min(10000, 1500 * consecutivePollFailures) : 1500);
    }
}

function hostAuthSignalChanged() {
    const current = localStorage.getItem('webFeudHostAuthChanged') || '';
    const changed = current && current !== lastHostAuthSignal;
    if (changed) {
        lastHostAuthSignal = current;
    }
    return changed;
}

window.addEventListener('storage', (event) => {
    if (event.key === 'webFeudHostAuthChanged') {
        lastHostAuthSignal = event.newValue || '';
        loop();
    }
});

window.addEventListener('focus', () => {
    if (hostAuthSignalChanged()) {
        loop();
    }
});

document.addEventListener('visibilitychange', () => {
    if (!document.hidden && hostAuthSignalChanged()) {
        loop();
    }
});

loop();
</script>
</body>
</html>
