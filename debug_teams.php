<?php
/**
 * Simple debug endpoint to test adding teams
 * This bypasses auth for testing purposes
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h1>Team Add Debug Tool</h1>";

if ($_POST) {
    $name = trim($_POST['name'] ?? '');
    
    if ($name === '') {
        echo "<p style='color:red;'>ERROR: Team name is empty</p>";
    } else {
        try {
            $pdo = get_db();
            $order = (int)$pdo->query('SELECT COUNT(*) c FROM teams')->fetch()['c'];
            $stmt = $pdo->prepare('INSERT INTO teams (name, display_order) VALUES (?, ?)');
            $stmt->execute([$name, $order]);
            $teamId = $pdo->lastInsertId();
            echo "<p style='color:green;font-weight:bold;'>✓ SUCCESS: Team '$name' added with ID: $teamId</p>";
        } catch (Exception $e) {
            echo "<p style='color:red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
}

echo "<h2>Add Team (No Auth Required)</h2>";
echo "<form method='POST'>
    <input type='text' name='name' placeholder='Team name' required>
    <button type='submit'>Add Team</button>
</form>";

echo "<h2>Current Teams</h2>";
try {
    $teams = get_teams();
    if (empty($teams)) {
        echo "<p style='color:orange;'>No teams yet</p>";
    } else {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Name</th><th>Status</th><th>Score</th></tr>";
        foreach ($teams as $t) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($t['id']) . "</td>";
            echo "<td>" . htmlspecialchars($t['name']) . "</td>";
            echo "<td>" . htmlspecialchars($t['status']) . "</td>";
            echo "<td>" . htmlspecialchars($t['score']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='test_team_add.php'>Back to Diagnostic Test</a> | 
        <a href='host/login.php'>Host Dashboard</a></p>";
?>
