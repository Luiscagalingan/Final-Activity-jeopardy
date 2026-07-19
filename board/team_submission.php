<?php
require_once __DIR__ . '/../includes/functions.php';
player_require_login();
$csrfToken = csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Web Feud - Submit Flag</title>
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

    .logout-link, .back-link {
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

    .logout-link:hover, .back-link:hover {
        background: rgba(255, 215, 0, 0.22);
        border-color: #ffd700;
        color: #fff4b8;
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

    .container {
        max-width: 640px;
        margin: 0 auto;
        padding: 32px;
    }

    .ctf-panel {
        background: #0d1b4c;
        border: 1px solid rgba(255, 215, 0, 0.4);
        border-radius: 14px;
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.45);
        padding: 32px;
        text-align: center;
        margin-bottom: 24px;
    }

    .ctf-panel h2 {
        color: #ffd700;
        margin: 0 0 6px;
    }

    .muted {
        color: #8a94b8;
    }

    .timer {
        font-size: 2.2rem;
        font-weight: 800;
        color: #ffd700;
        margin: 6px 0 18px;
        letter-spacing: 1px;
    }

    .ctf-prompt {
        background: #0a1440;
        border: 1px solid rgba(255, 215, 0, 0.3);
        border-radius: 10px;
        padding: 16px;
        margin: 12px 0 24px;
        font-family: 'Courier New', monospace;
        color: #ffd700;
        word-break: break-word;
        text-align: left;
        white-space: pre-wrap;
    }

    .flag-form label {
        display: block;
        text-align: left;
        color: #b8c4e8;
        font-weight: 700;
        font-size: 0.85rem;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .flag-input {
        width: 100%;
        padding: 14px 16px;
        border-radius: 10px;
        border: 1px solid rgba(255, 215, 0, 0.4);
        background: #0a1440;
        color: #f1f5ff;
        font-family: 'Courier New', monospace;
        font-size: 1.05rem;
        margin-bottom: 16px;
        outline: none;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .flag-input:focus {
        border-color: #ffd700;
        box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.15);
    }

    .submit-btn {
        width: 100%;
        padding: 14px 16px;
        border-radius: 10px;
        border: none;
        background: #ffd700;
        color: #0d1b4c;
        font-weight: 800;
        font-size: 1rem;
        cursor: pointer;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .submit-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 0 18px rgba(255, 215, 0, 0.5);
    }

    .submit-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .result-msg {
        margin-top: 16px;
        padding: 12px 16px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 0.95rem;
        display: none;
    }

    .result-msg.show {
        display: block;
    }

    .result-msg.success {
        background: rgba(46, 204, 113, 0.15);
        color: #2ecc71;
        border: 1px solid rgba(46, 204, 113, 0.4);
    }

    .result-msg.error {
        background: rgba(231, 76, 60, 0.15);
        color: #e74c3c;
        border: 1px solid rgba(231, 76, 60, 0.4);
    }

    .result-msg.info {
        background: rgba(52, 152, 219, 0.15);
        color: #5dade2;
        border: 1px solid rgba(52, 152, 219, 0.4);
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
    }
</style>
</head>
<body class="board-page">
    <div class="board-header">
        <div class="team-badge">Team: <?php echo htmlspecialchars($_SESSION['player_team_name'] ?? 'Unknown'); ?> <span class="player-name-sep">·</span> <?php echo htmlspecialchars($_SESSION['player_name'] ?? ''); ?></div>
        <div class="board-header-content">
            <span class="phase-pill" id="phasePill">Loading...</span>
            <h1>Submit Flag</h1>
        </div>
        <div class="header-right">
            <a href="main_board.php" class="back-link">Back to Board</a>
            <a href="logout.php" class="logout-link">Log out</a>
        </div>
    </div>

    <div class="container">
        <div class="ctf-panel">
            <h2 id="ctfTitle">CTF Resolution</h2>
            <p class="muted" id="ctfSubtitle">Enter the flag you decoded from the cipher below.</p>
            <div class="timer" id="ctfTimer">--:--</div>
            <div class="ctf-prompt" id="ctfPrompt">Waiting for the host to reveal the cipher...</div>

            <form class="flag-form" id="flagForm" autocomplete="off">
                <label for="flagInput">Submitted Cipher / Flag</label>
                <input type="text" id="flagInput" class="flag-input" placeholder="e.g. FLAG{...}" required>
                <button type="submit" class="submit-btn" id="submitBtn">Submit Flag</button>
            </form>

            <div class="result-msg" id="resultMsg"></div>
        </div>
    </div>

<script>
const teamId = <?php echo json_encode($_SESSION['player_team_id']); ?>;
const csrfToken = <?php echo json_encode($csrfToken); ?>;
const form = document.getElementById('flagForm');
const resultMsg = document.getElementById('resultMsg');
const submitBtn = document.getElementById('submitBtn');
const flagInput = document.getElementById('flagInput');
let formLocked = false;
let activeCtfId = null;
let pollTimer = null;
let polling = false;
let consecutivePollFailures = 0;

function redirectToPlayerLogin(path) {
    window.location.replace(path || 'player_login.php');
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
            redirectToPlayerLogin(data && data.redirect);
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
    return fetchJson('../api/state.php?view=board&auth=player');
}

function formatTime(sec) {
    sec = Math.max(0, Math.floor(sec));
    const m = Math.floor(sec / 60), s = sec % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
}

function escapeHtml(s) {
    return (s || '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function preserveFlagInputState(update) {
    const wasFocused = document.activeElement === flagInput;
    const value = flagInput.value;
    const selectionStart = flagInput.selectionStart;
    const selectionEnd = flagInput.selectionEnd;

    update();

    if (wasFocused) {
        flagInput.focus();
        const shouldRestoreValue = flagInput.dataset.preserveValue !== '0';
        delete flagInput.dataset.preserveValue;
        if (typeof selectionStart === 'number' && typeof selectionEnd === 'number' && shouldRestoreValue) {
            flagInput.setSelectionRange(selectionStart, selectionEnd);
        }
        if (shouldRestoreValue && flagInput.value !== value) {
            flagInput.value = value;
        }
    }
}

function renderState(state) {
    if (!state) return;

    const phasePill = document.getElementById('phasePill');
    const ctfTitle = document.getElementById('ctfTitle');
    const ctfTimer = document.getElementById('ctfTimer');
    const ctfPrompt = document.getElementById('ctfPrompt');
    const phaseText = (state.phase || '').replace('_', ' ');

    preserveFlagInputState(() => {
        if (phasePill.textContent !== phaseText) {
            phasePill.textContent = phaseText;
        }

        if (state.phase === 'ctf' && state.ctf) {
            const c = state.ctf;
            const nextTitle = (c.title || 'CTF Resolution') + (c.round ? ` (Round ${c.round})` : '');
            const nextTimer = formatTime(c.remaining);
            const nextPrompt = c.prompt_visible
                ? escapeHtml(c.prompt)
                : '<span class="muted">Waiting for the host to reveal the cipher...</span>';
            const ctfChanged = activeCtfId !== c.id;

            if (ctfChanged) {
                activeCtfId = c.id;
                formLocked = false;
                flagInput.dataset.preserveValue = '0';
                flagInput.value = '';
                resultMsg.className = 'result-msg';
                resultMsg.textContent = '';
            }

            if (ctfTitle.textContent !== nextTitle) {
                ctfTitle.textContent = nextTitle;
            }
            if (ctfTimer.textContent !== nextTimer) {
                ctfTimer.textContent = nextTimer;
            }
            if (ctfPrompt.innerHTML !== nextPrompt) {
                ctfPrompt.innerHTML = nextPrompt;
            }

            if (c.winner_team_id) {
                submitBtn.disabled = true;
                if (!formLocked) {
                    formLocked = true;
                    showResult('info', 'The CTF result has been decided.');
                }
            } else if (c.my_submitted) {
                submitBtn.disabled = true;
                showResult('info', `Submitted. Waiting for the other finalist (${c.submissions_received}/${c.submissions_needed}).`);
            } else if (c.remaining <= 0) {
                submitBtn.disabled = true;
            } else {
                submitBtn.disabled = !c.prompt_visible;
            }
        } else if (state.phase === 'finished') {
            if (ctfTimer.textContent !== '--:--') {
                ctfTimer.textContent = '--:--';
            }
            submitBtn.disabled = true;
            if (!formLocked) {
                formLocked = true;
                showResult('info', state.winner ? `${state.winner} has already won the game!` : 'The game has ended.');
            }
        } else {
            activeCtfId = null;
            if (ctfTimer.textContent !== '--:--') {
                ctfTimer.textContent = '--:--';
            }
            if (ctfPrompt.innerHTML !== '<span class="muted">The flag submission stage is not active right now.</span>') {
                ctfPrompt.innerHTML = '<span class="muted">The flag submission stage is not active right now.</span>';
            }
            submitBtn.disabled = true;
        }
    });
}

function showResult(type, text) {
    resultMsg.className = 'result-msg show ' + type;
    resultMsg.textContent = text;
}

async function loop() {
    if (polling) return;
    polling = true;
    try {
        const state = await fetchState();
        if (state) {
            consecutivePollFailures = 0;
            renderState(state);
        }
    } catch (e) {
        consecutivePollFailures++;
    } finally {
        polling = false;
        clearTimeout(pollTimer);
        pollTimer = setTimeout(loop, consecutivePollFailures ? Math.min(10000, 1500 * consecutivePollFailures) : 1500);
    }
}
loop();

form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (formLocked) return;

    const flag = flagInput.value.trim();
    if (!flag) return;

    submitBtn.disabled = true;
    showResult('info', 'Checking flag...');

    try {
        const body = new URLSearchParams();
        body.set('action', 'submit_flag');
        body.set('csrf_token', csrfToken);
        body.set('team_id', teamId);
        body.set('flag', flag);

        const data = await fetchJson('../api/action.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
        if (!data) return;

        if (data.error) {
            showResult('error', data.error);
        } else if (data.already_won) {
            formLocked = true;
            showResult('info', 'The CTF result has already been decided.');
        } else if (data.already_submitted) {
            showResult('info', 'Your team already submitted for this round. Waiting for the other finalist.');
        } else if (data.winner_team_id) {
            formLocked = true;
            showResult('success', data.winner_team_id === teamId ? 'Your team wins the CTF challenge!' : 'The other finalist wins this CTF challenge.');
            flagInput.value = '';
        } else if (data.next_round) {
            showResult('info', 'Both teams had the same result. A new CTF round is starting.');
            flagInput.value = '';
        } else if (data.no_rounds_left) {
            showResult('info', 'Both teams had the same result, but no unused CTF rounds remain. Please wait for the host.');
        } else if (data.pending || data.submitted) {
            showResult('info', 'Submitted. Waiting for the other finalist before results are decided.');
            flagInput.value = '';
        } else {
            showResult('info', 'Submission recorded.');
        }
    } catch (err) {
        showResult('error', 'Something went wrong submitting your flag. Please try again.');
    } finally {
        if (!formLocked) submitBtn.disabled = false;
    }
});
</script>
</body>
</html>
