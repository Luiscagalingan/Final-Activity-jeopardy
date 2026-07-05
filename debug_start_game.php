<?php
/**
 * Game start debug tool
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h1>Game Start Debug</h1>";

try {
    // Check teams
    $teams = get_teams();
    echo "<h2>Teams Registered: " . count($teams) . "</h2>";
    
    if (empty($teams)) {
        echo "<p style='color:red;'>✗ No teams registered yet. Add at least 3 teams first!</p>";
    } else {
        echo "<ul>";
        foreach ($teams as $t) {
            echo "<li>ID: {$t['id']}, Name: {$t['name']}, Status: {$t['status']}</li>";
        }
        echo "</ul>";
    }
    
    // Check game state
    $state = get_state();
    echo "<h2>Current Game State</h2>";
    echo "<p><strong>Phase:</strong> " . htmlspecialchars($state['phase']) . "</p>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($state['message'] ?? 'None') . "</p>";
    
    // Try manually starting the game
    if ($_POST['test_start'] ?? false) {
        if (count($teams) < 3) {
            echo "<p style='color:red;'>✗ Cannot start: Need at least 3 teams, have " . count($teams) . "</p>";
        } else {
            try {
                $pdo = get_db();
                $stmt = $pdo->prepare("UPDATE game_state SET phase = ?, message = ? WHERE id = 1");
                $stmt->execute(['elimination', 'Elimination round has begun!']);
                echo "<p style='color:green;'>✓ Game state updated to 'elimination'</p>";
                echo "<p>Refresh your dashboard to see the changes</p>";
            } catch (Exception $e) {
                echo "<p style='color:red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        }
    }
    
    echo "<h2>Manual Test</h2>";
    echo "<form method='POST'>";
    echo "<button type='submit' name='test_start' style='padding:10px 20px; font-size:16px;'>Test Start Game</button>";
    echo "</form>";
    
} catch (Exception $e) {
    echo "<p style='color:red;'>ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='host/dashboard.php'>Back to Dashboard</a></p>";
?>
