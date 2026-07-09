@ -1,105 +1,153 @@
<?php
require_once __DIR__ . '/../config/db.php';

const HOST_PIN = '1234'; // change this before running your event

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
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
    $row = $pdo->query('SELECT * FROM ctf_challenges WHERE is_used = 0 ORDER BY id LIMIT 1')->fetch();
    return $row ?: null;
}

function get_final_wagers(): array {
    $pdo = get_db();
    return $pdo->query('SELECT * FROM final_wagers')->fetchAll();
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

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function player_require_login(): void {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['player_auth']) || (empty($_SESSION['player_id']) && empty($_SESSION['player_team_id']))) {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $loginPath = ($scriptDir && $scriptDir !== '/' ? $scriptDir : '') . '/player_login.php';
        header('Location: ' . $loginPath);
        exit;
    }
}

function host_require_login(): void {
    session_start();
    if (empty($_SESSION['host_auth'])) {
        header('Location: login.php');
        exit;
    }
}
