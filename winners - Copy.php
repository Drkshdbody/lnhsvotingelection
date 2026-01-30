<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    $password = $_POST['password'];
    if (empty($password)) $password = '';
    if ($password !== 'admin123') {
        header("Location: admin_login.php");
        exit;
    }
    $_SESSION['admin_logged_in'] = true;
}

// Load school settings
$settings_file = 'school_settings.json';
$default_settings = [
    'school_name' => 'Sample High School',
    'school_id' => 'SHS-2026',
    'principal' => 'Dr. Juan Santos',
    'logo_path' => 'logo.png',
    'school_classification' => 'Small'
];

if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    $settings = array_merge($default_settings, $settings);
} else {
    $settings = $default_settings;
}

$school_class = $settings['school_classification'];
$db = new SQLite3('election.db');

// Load tie-break decisions from JSON file
$tie_break_file = 'tie_break_decisions.json';
$tie_break_decisions = [];
if (file_exists($tie_break_file)) {
    $tie_break_decisions = json_decode(file_get_contents($tie_break_file), true);
}

// Handle tie resolution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_tie'])) {
    $action_key = 'resolve_tie';
    $verified = isset($_SESSION["security_verified_$action_key"]) && 
               (time() - $_SESSION["security_verified_time"] < 300);
    
    if (!$verified) {
        header("Location: token_auth.php?action=$action_key&redirect=" . urlencode($_SERVER['PHP_SELF']));
        exit;
    }
    
    $position = $_POST['position'];
    $winner_id = $_POST['winner_id'];
    
    // Save tie-break decision to JSON file
    $tie_break_decisions[$position] = $winner_id;
    file_put_contents($tie_break_file, json_encode($tie_break_decisions, JSON_PRETTY_PRINT));
    
    // Clear verification
    unset($_SESSION["security_verified_$action_key"]);
    unset($_SESSION['security_verified_time']);
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Function to get winner with tie handling
function getWinnerWithTieHandling($db, $position, $tie_break_decisions) {
    // Get all candidates for this position ordered by votes
    $stmt = $db->prepare("SELECT id, name, party, vote_count FROM candidates WHERE position = ? ORDER BY vote_count DESC");
    $stmt->bindValue(1, $position);
    $result = $stmt->execute();
    
    $candidates = [];
    while ($row = $result->fetchArray()) {
        $candidates[] = $row;
    }
    
    if (empty($candidates)) {
        return null;
    }
    
    // Check if there's a tie at the top
    $top_vote_count = $candidates[0]['vote_count'];
    $tied_candidates = [];
    
    foreach ($candidates as $candidate) {
        if ($candidate['vote_count'] == $top_vote_count) {
            $tied_candidates[] = $candidate;
        } else {
            break;
        }
    }
    
    // If only one candidate has top votes, they win
    if (count($tied_candidates) == 1) {
        return $tied_candidates[0];
    }
    
    // If there's a tie, check if it's been resolved
    if (isset($tie_break_decisions[$position])) {
        $resolved_winner_id = $tie_break_decisions[$position];
        foreach ($tied_candidates as $candidate) {
            if ($candidate['id'] == $resolved_winner_id) {
                return $candidate;
            }
        }
    }
    
    // Return tie information
    return [
        'is_tie' => true,
        'tied_candidates' => $tied_candidates,
        'position' => $position
    ];
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System - Winners</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { color: #007bff; }
        .classification { background-color: #e7f3ff; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .winner-row { background-color: #d4edda; border: 2px solid #28a745; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .runnerup-row { background-color: #fff3cd; border: 2px solid #ffc107; padding: 10px; margin: 5px 0; border-radius: 5px; }
        .tie-alert { background-color: #f8d7da; border: 2px solid #dc3545; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .tie-resolution { background-color: #d1ecf1; border: 2px solid #17a2b8; padding: 15px; margin: 10px 0; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; }
        .back-link { margin-top: 10px; }
        .resolve-btn { background-color: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 4px; cursor: pointer; }
        .resolve-btn:hover { background-color: #c82333; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Official Election Winners</h2>
        
        <div class="classification">
            <strong>School:</strong> <?= htmlspecialchars($settings['school_name']) ?><br>
            <strong>Classification:</strong> <?= htmlspecialchars($school_class) ?><br>
            <strong>Winning Rule:</strong> 
            <?php if (in_array($school_class, ['Small', 'Medium'])): ?>
                Top 1 winner per grade level representative
            <?php else: ?>
                Top 2 winners per grade level representative
            <?php endif; ?>
        </div>

        <h3>Election Results Summary</h3>
        
        <?php
        // Define positions by category
        $general_positions = [
            'President', 'Vice President', 'Secretary', 'Treasurer', 
            'Auditor', 'Public Information Officer', 'Protocol Officer'
        ];

        $representative_positions = [];
        if (in_array($school_class, ['Small', 'Medium'])) {
            $representative_positions = [
                'Grade 10 Representative',
                'Grade 9 Representative', 
                'Grade 8 Representative'
            ];
        } else {
            $representative_positions = [
                'Grade 10 Representative 1', 'Grade 10 Representative 2',
                'Grade 9 Representative 1', 'Grade 9 Representative 2',
                'Grade 8 Representative 1', 'Grade 8 Representative 2'
            ];
        }

        // Show general positions
        foreach ($general_positions as $position) {
            $result = getWinnerWithTieHandling($db, $position, $tie_break_decisions);
            
            if ($result === null) {
                continue;
            }
            
            if (isset($result['is_tie']) && $result['is_tie']) {
                echo "<div class='tie-alert'>";
                echo "<strong>TIE DETECTED - $position:</strong><br>";
                echo "The following candidates are tied with {$result['tied_candidates'][0]['vote_count']} votes each:<br>";
                foreach ($result['tied_candidates'] as $candidate) {
                    echo "- {$candidate['name']} ({$candidate['party']})<br>";
                }
                
                // Show resolution form if not yet resolved
                if (!isset($tie_break_decisions[$position])) {
                    echo "<form method='POST' style='margin-top: 10px;'>";
                    echo "<input type='hidden' name='resolve_tie' value='1'>";
                    echo "<input type='hidden' name='position' value='" . htmlspecialchars($position) . "'>";
                    echo "<select name='winner_id' required>";
                    foreach ($result['tied_candidates'] as $candidate) {
                        echo "<option value='{$candidate['id']}'>{$candidate['name']}</option>";
                    }
                    echo "</select>";
                    echo "<button type='submit' class='resolve-btn'>Resolve Tie (3-Token Auth Required)</button>";
                    echo "</form>";
                } else {
                    echo "<div class='tie-resolution'>";
                    echo "<strong>Tie resolved by admin.</strong>";
                    echo "</div>";
                }
                echo "</div>";
            } else {
                echo "<div class='winner-row'>";
                echo "<strong>$position:</strong> {$result['name']} ({$result['party']}) - {$result['vote_count']} votes";
                echo "</div>";
            }
        }

        // Show representative positions
        foreach ($representative_positions as $position) {
            $result = getWinnerWithTieHandling($db, $position, $tie_break_decisions);
            
            if ($result === null) {
                continue;
            }
            
            if (isset($result['is_tie']) && $result['is_tie']) {
                echo "<div class='tie-alert'>";
                echo "<strong>TIE DETECTED - $position:</strong><br>";
                echo "The following candidates are tied with {$result['tied_candidates'][0]['vote_count']} votes each:<br>";
                foreach ($result['tied_candidates'] as $candidate) {
                    echo "- {$candidate['name']} ({$candidate['party']})<br>";
                }
                
                if (!isset($tie_break_decisions[$position])) {
                    echo "<form method='POST' style='margin-top: 10px;'>";
                    echo "<input type='hidden' name='resolve_tie' value='1'>";
                    echo "<input type='hidden' name='position' value='" . htmlspecialchars($position) . "'>";
                    echo "<select name='winner_id' required>";
                    foreach ($result['tied_candidates'] as $candidate) {
                        echo "<option value='{$candidate['id']}'>{$candidate['name']}</option>";
                    }
                    echo "</select>";
                    echo "<button type='submit' class='resolve-btn'>Resolve Tie (3-Token Auth Required)</button>";
                    echo "</form>";
                } else {
                    echo "<div class='tie-resolution'>";
                    echo "<strong>Tie resolved by admin.</strong>";
                    echo "</div>";
                }
                echo "</div>";
            } else {
                echo "<div class='winner-row'>";
                echo "<strong>$position:</strong> {$result['name']} ({$result['party']}) - {$result['vote_count']} votes";
                echo "</div>";
            }
        }

        // Show runner-ups for Large/Mega schools
        if (in_array($school_class, ['Large', 'Mega'])) {
            echo "<h3>Runner-ups (Top 2 Winners)</h3>";
            
            $runner_up_positions = [
                'Grade 10 Representative', 'Grade 9 Representative', 'Grade 8 Representative'
            ];
            
            foreach ($runner_up_positions as $base_position) {
                $stmt = $db->prepare("SELECT name, party, vote_count FROM candidates WHERE position LIKE ? ORDER BY vote_count DESC LIMIT 2 OFFSET 1");
                $stmt->bindValue(1, "$base_position%");
                $result = $stmt->execute();
                
                $rank = 2;
                while ($runner = $result->fetchArray()) {
                    echo "<div class='runnerup-row'>";
                    echo "<strong>{$base_position} - Rank $rank:</strong> {$runner['name']} ({$runner['party']}) - {$runner['vote_count']} votes";
                    echo "</div>";
                    $rank++;
                    if ($rank > 2) break; // Only show top 2
                }
            }
        }
        ?>

        <h3>Detailed Results</h3>
        <table>
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Name</th>
                    <th>Party</th>
                    <th>Votes</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sql = "SELECT id, position, name, party, vote_count FROM candidates ORDER BY 
                    CASE position
                        WHEN 'President' THEN 1
                        WHEN 'Vice President' THEN 2
                        WHEN 'Secretary' THEN 3
                        WHEN 'Treasurer' THEN 4
                        WHEN 'Auditor' THEN 5
                        WHEN 'Public Information Officer' THEN 6
                        WHEN 'Protocol Officer' THEN 7
                        WHEN 'Grade 10 Representative' THEN 8
                        WHEN 'Grade 10 Representative 1' THEN 8
                        WHEN 'Grade 10 Representative 2' THEN 9
                        WHEN 'Grade 9 Representative' THEN 10
                        WHEN 'Grade 9 Representative 1' THEN 10
                        WHEN 'Grade 9 Representative 2' THEN 11
                        WHEN 'Grade 8 Representative' THEN 12
                        WHEN 'Grade 8 Representative 1' THEN 12
                        WHEN 'Grade 8 Representative 2' THEN 13
                        ELSE 99
                    END ASC, vote_count DESC";

                $result = $db->query($sql);
                while ($row = $result->fetchArray()) {
                    $status = 'Participant';
                    
                    // Check if this candidate is a winner (considering tie breaks)
                    $winner_info = getWinnerWithTieHandling($db, $row['position'], $tie_break_decisions);
                    if ($winner_info && !isset($winner_info['is_tie'])) {
                        if ($row['id'] == $winner_info['id']) {
                            $status = '<span style="color: green; font-weight: bold;">WINNER</span>';
                        }
                    } elseif ($winner_info && isset($winner_info['is_tie'])) {
                        if (isset($tie_break_decisions[$row['position']]) && $row['id'] == $tie_break_decisions[$row['position']]) {
                            $status = '<span style="color: green; font-weight: bold;">WINNER (Tie Resolved)</span>';
                        } elseif (!isset($tie_break_decisions[$row['position']])) {
                            // Check if this candidate is in the tied group
                            foreach ($winner_info['tied_candidates'] as $tied_candidate) {
                                if ($row['id'] == $tied_candidate['id']) {
                                    $status = '<span style="color: red; font-weight: bold;">TIED - Requires Resolution</span>';
                                    break;
                                }
                            }
                        }
                    }
                    
                    echo "<tr>";
                    echo "<td>".htmlspecialchars($row['position'])."</td>";
                    echo "<td>".htmlspecialchars($row['name'])."</td>";
                    $party_display = !empty($row['party']) ? htmlspecialchars($row['party']) : 'N/A';
                    echo "<td>".$party_display."</td>";
                    echo "<td>".$row['vote_count']."</td>";
                    echo "<td>$status</td>";
                    echo "</tr>";
                }
                ?>
            </tbody>
        </table>

        <div class="back-link">
            <a href="admin_panel.php">← Back to Admin Panel</a>
        </div>
    </div>
</body>
</html>