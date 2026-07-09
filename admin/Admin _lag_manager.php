<?php
require_once __DIR__ . '/../includes/functions.php';
host_require_login();

$pdo = get_db();

$message = '';
$messageType = '';
$generatedHash = '';

// ---------------------------------------------------------------
// HANDLE FORM SUBMISSION
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $mode        = $_POST['mode'] ?? 'update';           // 'update' or 'create'
    $challengeId = $_POST['challenge_id'] ?? null;
    $title       = trim($_POST['title'] ?? '');
    $prompt      = trim($_POST['prompt'] ?? '');
    $hint        = trim($_POST['hint'] ?? '');
    $duration    = (int)($_POST['duration_seconds'] ?? 180);
    $plainFlag   = $_POST['plain_flag'] ?? '';

    if ($plainFlag === '') {
        $message = 'Flag text cannot be empty.';
        $messageType = 'error';
    } elseif ($title === '' || $prompt === '') {
        $message = 'Title and prompt are required.';
        $messageType = 'error';
    } else {
        // This is the ONLY place a hash should ever be generated.
        // Uses the exact same algorithm as the check in api/action.php's
        // submit_flag case: hash('sha256', $flag) === $ctf['flag_hash']
        // So a correct submission will always match.
        $generatedHash = hash('sha256', $plainFlag);

        try {
            if ($mode === 'create') {
                $stmt = $pdo->prepare(
                    "INSERT INTO ctf_challenges
                        (title, prompt, flag_hash, hint, duration_seconds, is_used)
                     VALUES (?, ?, ?, ?, ?, 0)"
                );
                $stmt->execute([$title, $prompt, $generatedHash, $hint, $duration]);
                $newId = $pdo->lastInsertId();
                $message = "New challenge created (id {$newId}) with a verified flag hash.";
                $messageType = 'success';

            } else { // update existing
                if (!$challengeId) {
                    $message = 'Please select a challenge to update.';
                    $messageType = 'error';
                } else {
                    $stmt = $pdo->prepare(
                        "UPDATE ctf_challenges
                         SET title = ?, prompt = ?, flag_hash = ?, hint = ?, duration_seconds = ?
                         WHERE id = ?"
                    );
                    $stmt->execute([$title, $prompt, $generatedHash, $hint, $duration, $challengeId]);
                    $message = "Challenge id {$challengeId} updated with a verified flag hash.";
                    $messageType = 'success';
                }
            }
        } catch (PDOException $e) {
            $message = 'Database error: ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// ---------------------------------------------------------------
// AJAX endpoint: fetch a single challenge's full data to auto-fill
// the form when the dropdown selection changes.
// ---------------------------------------------------------------
if (isset($_GET['fetch_challenge'])) {
    header('Content-Type: application/json');
    $stmt = $pdo->prepare('SELECT id, title, prompt, hint, duration_seconds FROM ctf_challenges WHERE id = ?');
    $stmt->execute([(int)$_GET['fetch_challenge']]);
    $row = $stmt->fetch();
    echo json_encode($row ?: null);
    exit;
}

// ---------------------------------------------------------------
// FETCH EXISTING CHALLENGES + ACTIVE CHALLENGE FOR THE DASHBOARD
// ---------------------------------------------------------------
$challenges = $pdo->query("SELECT id, title, flag_hash, is_used FROM ctf_challenges ORDER BY id")->fetchAll();

$stateRow = $pdo->query("SELECT active_ctf_id FROM game_state WHERE id = 1")->fetch();
$activeCtfId = $stateRow['active_ctf_id'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Flag Hash Manager</title>
<style>
    body { font-family: system-ui, sans-serif; max-width: 900px; margin: 30px auto; padding: 0 20px; background:#0f1117; color:#e6e6e6; }
    h1 { font-size: 1.4rem; }
    .box { background:#181b24; border:1px solid #2a2e3a; border-radius:8px; padding:20px; margin-bottom:24px; }
    label { display:block; margin-top:12px; font-size:0.9rem; color:#a8adba; }
    input[type=text], input[type=number], textarea, select {
        width:100%; padding:8px; margin-top:4px; background:#0f1117; border:1px solid #2a2e3a;
        border-radius:6px; color:#e6e6e6; font-family: inherit; box-sizing: border-box;
    }
    textarea { min-height: 70px; }
    button { margin-top:16px; padding:10px 18px; background:#4f7cff; border:none; border-radius:6px; color:#fff; cursor:pointer; font-weight:600; }
    button:hover { background:#3d63d9; }
    .msg { padding:10px 14px; border-radius:6px; margin-bottom:16px; }
    .success { background:#123a24; border:1px solid #1f7a4d; color:#7be3a8; }
    .error { background:#3a1212; border:1px solid #7a1f1f; color:#e37b7b; }
    .hash-out { font-family: monospace; background:#0f1117; padding:8px; border-radius:6px; word-break:break-all; margin-top:8px; border:1px solid #2a2e3a; }
    table { width:100%; border-collapse: collapse; font-size:0.85rem; }
    th, td { text-align:left; padding:8px; border-bottom:1px solid #2a2e3a; }
    .active-badge { background:#4f7cff; color:#fff; padding:2px 8px; border-radius:10px; font-size:0.75rem; }
    .mode-toggle { display:flex; gap:10px; margin-top:10px; }
    .mode-toggle label { display:flex; align-items:center; gap:6px; font-size:0.9rem; color:#e6e6e6; }
    a.top-link { color:#4f7cff; text-decoration:none; font-size:0.85rem; }
</style>
</head>
<body>

<a class="top-link" href="../host/dashboard.php">&larr; Back to host dashboard</a>
<h1>🔐 Flag Hash Manager</h1>
<p style="color:#a8adba;">Type the plaintext flag here — this page hashes it with the exact same <code>hash('sha256', $flag)</code> call used by <code>api/action.php</code>, so it will always match a correct submission.</p>

<?php if ($message): ?>
    <div class="msg <?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<?php if ($generatedHash && $messageType === 'success'): ?>
    <div class="box">
        <strong>Hash generated and saved:</strong>
        <div class="hash-out"><?= htmlspecialchars($generatedHash) ?></div>
    </div>
<?php endif; ?>

<div class="box">
    <form method="POST" id="flagManagerForm">
        <div class="mode-toggle">
            <label><input type="radio" name="mode" value="update" checked onclick="toggleMode('update')"> Update existing challenge</label>
            <label><input type="radio" name="mode" value="create" onclick="toggleMode('create')"> Create new challenge</label>
        </div>

        <div id="selectRow">
            <label for="challenge_id">Challenge to update</label>
            <select name="challenge_id" id="challenge_id" onchange="fillForm(this.value)">
                <option value="">-- select --</option>
                <?php foreach ($challenges as $c): ?>
                    <option value="<?= $c['id'] ?>">
                        #<?= $c['id'] ?> — <?= htmlspecialchars($c['title']) ?>
                        <?= $c['id'] == $activeCtfId ? ' (ACTIVE)' : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <label for="title">Title</label>
        <input type="text" name="title" id="title" required>

        <label for="prompt">Prompt (shown to players)</label>
        <textarea name="prompt" id="prompt" required></textarea>

        <label for="hint">Hint</label>
        <textarea name="hint" id="hint"></textarea>

        <label for="duration_seconds">Duration (seconds)</label>
        <input type="number" name="duration_seconds" id="duration_seconds" value="180">

        <label for="plain_flag">Plaintext flag (e.g. FLAG{SOMETHING})</label>
        <input type="text" name="plain_flag" id="plain_flag" required placeholder="FLAG{EXACT_ANSWER}">

        <button type="submit">Save &amp; Generate Hash</button>
    </form>
</div>

<div class="box">
    <h2 style="font-size:1.1rem;">Current challenges</h2>
    <table>
        <tr><th>ID</th><th>Title</th><th>Flag Hash</th><th>Used?</th><th>Active?</th></tr>
        <?php foreach ($challenges as $c): ?>
            <tr>
                <td><?= $c['id'] ?></td>
                <td><?= htmlspecialchars($c['title']) ?></td>
                <td style="font-family:monospace; font-size:0.75rem;"><?= substr($c['flag_hash'], 0, 16) ?>...</td>
                <td><?= $c['is_used'] ? 'Yes' : 'No' ?></td>
                <td><?= $c['id'] == $activeCtfId ? '<span class="active-badge">ACTIVE</span>' : '' ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
function toggleMode(mode) {
    document.getElementById('selectRow').style.display = (mode === 'update') ? 'block' : 'none';
    document.getElementById('challenge_id').required = (mode === 'update');
    if (mode === 'create') {
        document.getElementById('title').value = '';
        document.getElementById('prompt').value = '';
        document.getElementById('hint').value = '';
        document.getElementById('duration_seconds').value = 180;
        document.getElementById('plain_flag').value = '';
    }
}

// Auto-fills title/prompt/hint/duration when you pick a challenge,
// by fetching that row's real data from the DB via this same file.
async function fillForm(id) {
    if (!id) return;
    try {
        const res = await fetch(`${window.location.pathname}?fetch_challenge=${encodeURIComponent(id)}`);
        const data = await res.json();
        if (!data) return;
        document.getElementById('title').value = data.title || '';
        document.getElementById('prompt').value = data.prompt || '';
        document.getElementById('hint').value = data.hint || '';
        document.getElementById('duration_seconds').value = data.duration_seconds || 180;
        document.getElementById('plain_flag').value = ''; // never pre-fill a flag, always re-type it
    } catch (e) {
        console.error('Could not load challenge data', e);
    }
}
</script>

</body>
</html>
