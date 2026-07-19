<?php
require_once __DIR__ . '/../config/db.php';

const HOST_PIN = '1234'; // change this before running your event

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    echo json_encode($data);
    exit;
}

function redirect_to(string $path): void {
    header('Location: ' . $path);
    exit;
}

function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_token_is_valid(?string $token): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    return is_string($token)
        && !empty($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function get_state(): array {
    $pdo = get_db();
    $state = $pdo->query('SELECT * FROM game_state WHERE id = 1')->fetch();
    return $state ?: [];
}

function update_state(array $fields): void {
    $pdo = get_db();
    $sets = [];
    $params = [];
    foreach ($fields as $key => $value) {
        $sets[] = "$key = ?";
        $params[] = $value;
    }
    $params[] = 1;
    $sql = 'UPDATE game_state SET ' . implode(', ', $sets) . ' WHERE id = ?';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function get_teams(): array {
    $pdo = get_db();
    return $pdo->query('SELECT * FROM teams ORDER BY display_order, id')->fetchAll();
}

function get_team(int $id): ?array {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM teams WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_board(): array {
    $pdo = get_db();
    $categories = $pdo->query(
        "SELECT * FROM categories WHERE round_type = 'elimination' ORDER BY display_order"
    )->fetchAll();

    foreach ($categories as &$cat) {
        $stmt = $pdo->prepare('SELECT id, points, is_used FROM questions WHERE category_id = ? ORDER BY points');
        $stmt->execute([$cat['id']]);
        $cat['questions'] = $stmt->fetchAll();
    }
    unset($cat);
    return $categories;
}

function get_question(int $id): ?array {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM questions WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_final_question(): ?array {
    $pdo = get_db();
    $row = $pdo->query(
        "SELECT q.* FROM questions q
         JOIN categories c ON c.id = q.category_id
         WHERE c.round_type = 'final' LIMIT 1"
    )->fetch();
    return $row ?: null;
}

function get_ctf_challenge(int $id): ?array {
    $pdo = get_db();
    $stmt = $pdo->prepare('SELECT * FROM ctf_challenges WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_unused_ctf_challenge(): ?array {
    $pdo = get_db();
    $row = $pdo->query('SELECT * FROM ctf_challenges WHERE is_used = 0 ORDER BY RAND() LIMIT 1')->fetch();
    return $row ?: null;
}

// How many CTF challenges have been played so far this game, used to show
// a "Round N" indicator during a sudden-death tiebreaker sequence.
function get_used_ctf_challenge_count(): int {
    $pdo = get_db();
    return (int)$pdo->query('SELECT COUNT(*) c FROM ctf_challenges WHERE is_used = 1')->fetch()['c'];
}

function get_ctf_competitors(): array {
    $pdo = get_db();
    $finalists = $pdo->query(
        "SELECT t.*
         FROM teams t
         JOIN final_wagers fw ON fw.team_id = t.id
         ORDER BY t.display_order, t.id
         LIMIT 2"
    )->fetchAll();

    if (count($finalists) >= 2) {
        return $finalists;
    }

    return $pdo->query(
        "SELECT * FROM teams WHERE status = 'finalist' ORDER BY display_order, id LIMIT 2"
    )->fetchAll();
}

function get_final_wagers(): array {
    $pdo = get_db();
    return $pdo->query('SELECT * FROM final_wagers')->fetchAll();
}

// All flag attempts for a given CTF challenge, newest first, joined with
// the submitting team's name. Host-only data — never expose this to the
// public board view, since it reveals in-progress guesses.
function get_flag_submissions(int $ctfId): array {
    $pdo = get_db();
    $stmt = $pdo->prepare(
        'SELECT fs.*, t.name AS team_name
         FROM flag_submissions fs
         JOIN teams t ON t.id = fs.team_id
         WHERE fs.ctf_id = ?
         ORDER BY fs.id DESC'
    );
    $stmt->execute([$ctfId]);
    return $stmt->fetchAll();
}

function get_latest_flag_submissions_by_team(int $ctfId): array {
    $pdo = get_db();
    $stmt = $pdo->prepare(
        'SELECT fs.*
         FROM flag_submissions fs
         JOIN (
             SELECT team_id, MAX(id) AS max_id
             FROM flag_submissions
             WHERE ctf_id = ?
             GROUP BY team_id
         ) latest ON latest.max_id = fs.id'
    );
    $stmt->execute([$ctfId]);

    $submissions = [];
    foreach ($stmt->fetchAll() as $row) {
        $submissions[(int)$row['team_id']] = $row;
    }
    return $submissions;
}

function get_team_member_by_name(string $fullName): ?array {
    $pdo = get_db();
    $stmt = $pdo->prepare(
        'SELECT tm.id, tm.full_name, tm.team_id, t.name AS team_name
         FROM team_members tm
         LEFT JOIN teams t ON t.id = tm.team_id
         WHERE LOWER(TRIM(tm.full_name)) = LOWER(TRIM(?))
         LIMIT 1'
    );
    $stmt->execute([$fullName]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_registered_team_names(): array {
    $pdo = get_db();
    $rows = $pdo->query('SELECT id, name FROM teams ORDER BY display_order, id')->fetchAll();
    return array_map(static fn($row) => ['id' => (int)$row['id'], 'name' => $row['name']], $rows);
}

function player_logout(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    unset(
        $_SESSION['player_auth'],
        $_SESSION['player_id'],
        $_SESSION['player_name'],
        $_SESSION['player_team_id'],
        $_SESSION['player_team_name'],
        $_SESSION['player_role']
    );

    if (empty($_SESSION)) {
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    } else {
        session_regenerate_id(true);
    }
}

function host_logout(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    unset($_SESSION['host_auth']);
    if (empty($_SESSION)) {
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    } else {
        session_regenerate_id(true);
    }
}

function player_require_login(?string $loginPath = null): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['player_auth']) || (empty($_SESSION['player_id']) && empty($_SESSION['player_team_id']))) {
        if ($loginPath === null) {
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            $loginPath = ($scriptDir && $scriptDir !== '/' ? $scriptDir : '') . '/player_login.php';
        }
        redirect_to($loginPath);
    }
}

function host_require_login(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['host_auth'])) {
        redirect_to('login.php');
    }
}

function board_require_player_or_host(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['player_auth']) && empty($_SESSION['host_auth'])) {
        redirect_to('player_login.php');
    }
}

function is_player_logged_in(): bool {
    return !empty($_SESSION['player_auth']) && !empty($_SESSION['player_team_id']);
}

function is_host_logged_in(): bool {
    return !empty($_SESSION['host_auth']);
}
