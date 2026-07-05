<?php
/**
 * Test file to verify team registration is working
 * Access this via: http://localhost/Final-Activity-jeopardy/test_team_add.php
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h1>Web Feud - Diagnostic Test</h1>";

// Test database connection
echo "<h2>1. Database Connection</h2>";
try {
    $pdo = get_db();
    echo "<p style='color:green'>✓ Database connection OK</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Database connection FAILED: " . $e->getMessage() . "</p>";
    echo "<p style='color:orange'>Make sure MySQL is running and the database 'web_feud_ctf' exists.</p>";
    exit;
}

// Test that teams table exists and check current teams
echo "<h2>2. Database Tables</h2>";
try {
    $tables = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('teams', $tables)) {
        echo "<p style='color:green'>✓ 'teams' table exists</p>";
    } else {
        echo "<p style='color:red'>✗ 'teams' table NOT found</p>";
        echo "<p>You need to import the database schema. Check the database/ folder.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error checking tables: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Current Teams in Database</h2>";
try {
    $teams = get_teams();
    if (empty($teams)) {
        echo "<p style='color:orange'>No teams registered yet</p>";
    } else {
        echo "<ul>";
        foreach ($teams as $team) {
            echo "<li>ID: {$team['id']}, Name: {$team['name']}, Status: {$team['status']}, Score: {$team['score']}</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error reading teams: " . $e->getMessage() . "</p>";
}

// Test adding a new team
echo "<h2>4. Test Adding a Team</h2>";
echo "<form method='POST'>
    <input type='text' name='test_team_name' placeholder='Enter team name' required>
    <button type='submit' name='test_add'>Add Test Team</button>
</form>";

if ($_POST['test_add'] ?? false) {
    $name = trim($_POST['test_team_name'] ?? '');
    if ($name === '') {
        echo "<p style='color:red'>✗ Team name is required</p>";
    } else {
        try {
            $order = (int)$pdo->query('SELECT COUNT(*) c FROM teams')->fetch()['c'];
            $stmt = $pdo->prepare('INSERT INTO teams (name, display_order) VALUES (?, ?)');
            $result = $stmt->execute([$name, $order]);
            if ($result) {
                echo "<p style='color:green'>✓ Team '$name' added successfully (ID: " . $pdo->lastInsertId() . ")</p>";
                echo "<p>If this worked, the Dashboard team registration should work too!</p>";
            } else {
                echo "<p style='color:red'>✗ Insert failed: " . json_encode($stmt->errorInfo()) . "</p>";
            }
        } catch (Exception $e) {
            echo "<p style='color:red'>✗ Error adding team: " . $e->getMessage() . "</p>";
        }
    }
}

// Check game state
echo "<h2>5. Game State</h2>";
try {
    $state = get_state();
    echo "<p>Phase: <strong>" . htmlspecialchars($state['phase'] ?? 'unknown') . "</strong></p>";
    if ($state['phase'] === 'lobby') {
        echo "<p style='color:green'>✓ Game is in LOBBY phase - you can add teams in the Dashboard</p>";
    } else {
        echo "<p style='color:orange'>⚠ Game is in '" . htmlspecialchars($state['phase']) . "' phase</p>";
        echo "<p>You need to reset the game to add teams. Go to Host Dashboard > Reset entire game</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>✗ Error reading game state: " . $e->getMessage() . "</p>";
}

// Summary
echo "<h2>Summary & Next Steps</h2>";
echo "<ul>";
echo "<li>If all tests pass, go to <a href='host/login.php'><strong>Host Dashboard</strong></a> (PIN: 1234)</li>";
echo "<li>If Database Connection fails, make sure MySQL is running</li>";
echo "<li>If tables don't exist, import the schema from <code>database/schema.sql</code></li>";
echo "<li>If the game phase is not 'lobby', click 'Reset entire game' in the Dashboard</li>";
echo "</ul>";

echo "<hr><p><a href='host/dashboard.php'>Back to Dashboard</a> | <a href='host/login.php'>Host Login</a></p>";
?>
