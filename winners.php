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
    'school_classification' => 'Small',
    'school_level' => 'Junior High School'
];

if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    $settings = array_merge($default_settings, $settings);
} else {
    $settings = $default_settings;
}

$school_class = $settings['school_classification'];
$school_level = $settings['school_level'];
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
    $winner_ids = $_POST['winner_ids'] ?? [];
    $single_winner_id = $_POST['winner_id'] ?? null;
    
    if (!empty($winner_ids)) {
        // Multi-winner tie resolution
        $tie_key = $position . '_multi';
        $tie_break_decisions[$tie_key] = $winner_ids;
    } elseif ($single_winner_id) {
        // Single winner tie resolution
        $tie_break_decisions[$position] = $single_winner_id;
    }
    
    // Save to JSON file
    file_put_contents($tie_break_file, json_encode($tie_break_decisions, JSON_PRETTY_PRINT));
    
    // Clear verification
    unset($_SESSION["security_verified_$action_key"]);
    unset($_SESSION['security_verified_time']);
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Enhanced function to handle all types of ties
function getWinnersWithTieHandling($db, $position, $tie_break_decisions, $school_class, $school_level) {
    // Determine number of winners needed
    $num_winners = 1;
    $is_representative = false;
    
    // Check if this is a representative position
    if (strpos($position, 'Representative') !== false) {
        $is_representative = true;
        
        // Determine base position (remove 1/2 suffix if present)
        $base_position = preg_replace('/\s+\d+$/', '', $position);
        
        // For Large/Mega schools, representatives need 2 winners
        if (in_array($school_class, ['Large', 'Mega'])) {
            $num_winners = 2;
        }
    }
    
    // Get all candidates for this position ordered by votes
    $stmt = $db->prepare("SELECT id, name, party, vote_count FROM candidates WHERE position = ? ORDER BY vote_count DESC");
    $stmt->bindValue(1, $position);
    $result = $stmt->execute();
    
    $candidates = [];
    while ($row = $result->fetchArray()) {
        $candidates[] = $row;
    }
    
    if (empty($candidates)) {
        return ['winners' => [], 'has_tie' => false];
    }
    
    if ($num_winners == 1) {
        // Single winner logic
        $top_vote_count = $candidates[0]['vote_count'];
        $tied_candidates = [];
        
        foreach ($candidates as $candidate) {
            if ($candidate['vote_count'] == $top_vote_count) {
                $tied_candidates[] = $candidate;
            } else {
                break;
            }
        }
        
        if (count($tied_candidates) == 1) {
            return ['winners' => [$tied_candidates[0]], 'has_tie' => false];
        }
        
        // Check if tie is resolved
        if (isset($tie_break_decisions[$position])) {
            $resolved_winner_id = $tie_break_decisions[$position];
            foreach ($tied_candidates as $candidate) {
                if ($candidate['id'] == $resolved_winner_id) {
                    return ['winners' => [$candidate], 'has_tie' => false];
                }
            }
        }
        
        return [
            'winners' => [],
            'has_tie' => true,
            'tied_candidates' => $tied_candidates,
            'position' => $position,
            'tie_type' => 'single'
        ];
    } else {
        // Multi-winner logic (for Large/Mega representative positions)
        $winners = [];
        $remaining_slots = $num_winners;
        $current_vote_count = null;
        $candidates_at_current_level = [];
        
        foreach ($candidates as $candidate) {
            if ($remaining_slots <= 0) break;
            
            if ($current_vote_count === null || $candidate['vote_count'] == $current_vote_count) {
                $candidates_at_current_level[] = $candidate;
                $current_vote_count = $candidate['vote_count'];
            } else {
                // Process the previous group
                if (count($candidates_at_current_level) <= $remaining_slots) {
                    // Can fit all candidates from this group
                    $winners = array_merge($winners, $candidates_at_current_level);
                    $remaining_slots -= count($candidates_at_current_level);
                } else {
                    // Tie detected - not enough slots
                    $tie_key = $position . '_multi';
                    if (isset($tie_break_decisions[$tie_key])) {
                        $resolved_winners = $tie_break_decisions[$tie_key];
                        foreach ($candidates_at_current_level as $candidate) {
                            if (in_array($candidate['id'], $resolved_winners) && $remaining_slots > 0) {
                                $winners[] = $candidate;
                                $remaining_slots--;
                            }
                        }
                    } else {
                        return [
                            'winners' => $winners,
                            'has_tie' => true,
                            'tied_candidates' => $candidates_at_current_level,
                            'position' => $position,
                            'slots_needed' => $remaining_slots,
                            'tie_type' => 'multi'
                        ];
                    }
                }
                
                // Start new group
                $candidates_at_current_level = [$candidate];
                $current_vote_count = $candidate['vote_count'];
            }
        }
        
        // Process the last group
        if (!empty($candidates_at_current_level) && $remaining_slots > 0) {
            if (count($candidates_at_current_level) <= $remaining_slots) {
                $winners = array_merge($winners, $candidates_at_current_level);
            } else {
                // Tie in the final group
                $tie_key = $position . '_multi';
                if (isset($tie_break_decisions[$tie_key])) {
                    $resolved_winners = $tie_break_decisions[$tie_key];
                    foreach ($candidates_at_current_level as $candidate) {
                        if (in_array($candidate['id'], $resolved_winners) && $remaining_slots > 0) {
                            $winners[] = $candidate;
                            $remaining_slots--;
                        }
                    }
                } else {
                    return [
                        'winners' => $winners,
                        'has_tie' => true,
                        'tied_candidates' => $candidates_at_current_level,
                        'position' => $position,
                        'slots_needed' => $remaining_slots,
                        'tie_type' => 'multi'
                    ];
                }
            }
        }
        
        return ['winners' => $winners, 'has_tie' => false];
    }
}

// Define the correct order of positions based on school level
$ordered_positions = [];

// Add general positions
$general_positions = [
    'President',
    'Vice President',
    'Secretary',
    'Treasurer',
    'Auditor',
    'Public Information Officer',
    'Protocol Officer'
];

$ordered_positions = array_merge($ordered_positions, $general_positions);

// Add representative positions based on school level and classification
if ($school_level === 'Elementary') {
    // Elementary: Grade 3-6 Representatives
    if (in_array($school_class, ['Small', 'Medium'])) {
        $rep_positions = [
            'Grade 6 Representative',
            'Grade 5 Representative',
            'Grade 4 Representative',
            'Grade 3 Representative'
        ];
    } else {
        $rep_positions = [
            'Grade 6 Representative 1',
            'Grade 6 Representative 2',
            'Grade 5 Representative 1',
            'Grade 5 Representative 2',
            'Grade 4 Representative 1',
            'Grade 4 Representative 2',
            'Grade 3 Representative 1',
            'Grade 3 Representative 2'
        ];
    }
} elseif ($school_level === 'Integrated School') {
    // Integrated: Grade 8-12 Representatives
    if (in_array($school_class, ['Small', 'Medium'])) {
        $rep_positions = [
            'Grade 12 Representative',
            'Grade 11 Representative',
            'Grade 10 Representative',
            'Grade 9 Representative',
            'Grade 8 Representative'
        ];
    } else {
        $rep_positions = [
            'Grade 12 Representative 1',
            'Grade 12 Representative 2',
            'Grade 11 Representative 1',
            'Grade 11 Representative 2',
            'Grade 10 Representative 1',
            'Grade 10 Representative 2',
            'Grade 9 Representative 1',
            'Grade 9 Representative 2',
            'Grade 8 Representative 1',
            'Grade 8 Representative 2'
        ];
    }
} elseif ($school_level === 'Senior High School') {
    // Senior High: Grade 12 Representatives only
    if (in_array($school_class, ['Small', 'Medium'])) {
        $rep_positions = [
            'Grade 12 Representative'
        ];
    } else {
        $rep_positions = [
            'Grade 12 Representative 1',
            'Grade 12 Representative 2'
        ];
    }
} else {
    // Junior High School: Grade 8-10 Representatives
    if (in_array($school_class, ['Small', 'Medium'])) {
        $rep_positions = [
            'Grade 10 Representative',
            'Grade 9 Representative',
            'Grade 8 Representative'
        ];
    } else {
        $rep_positions = [
            'Grade 10 Representative 1',
            'Grade 10 Representative 2',
            'Grade 9 Representative 1',
            'Grade 9 Representative 2',
            'Grade 8 Representative 1',
            'Grade 8 Representative 2'
        ];
    }
}

$ordered_positions = array_merge($ordered_positions, $rep_positions);
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
        .multi-select { width: 100%; padding: 5px; margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Official Election Winners</h2>
        
        <div class="classification">
            <strong>School:</strong> <?= htmlspecialchars($settings['school_name']) ?><br>
            <strong>Classification:</strong> <?= htmlspecialchars($school_class) ?><br>
            <strong>Level:</strong> <?= htmlspecialchars($school_level) ?><br>
            <strong>Winning Rule:</strong> 
            <?php if (in_array($school_class, ['Small', 'Medium'])): ?>
                Top 1 winner per grade level representative
            <?php else: ?>
                Top 2 winners per grade level representative
            <?php endif; ?>
        </div>

        <h3>Election Results Summary</h3>
        
        <?php
        // Process positions in the correct order
        foreach ($ordered_positions as $position) {
            // Check if this position exists in the database
            $check_stmt = $db->prepare("SELECT COUNT(*) FROM candidates WHERE position = ?");
            $check_stmt->bindValue(1, $position);
            $exists = $check_stmt->execute()->fetchArray()[0];
            
            if ($exists > 0) {
                $result = getWinnersWithTieHandling($db, $position, $tie_break_decisions, $school_class, $school_level);
                
                if ($result['has_tie']) {
                    echo "<div class='tie-alert'>";
                    if ($result['tie_type'] == 'single') {
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
                    } else {
                        // Multi-winner tie
                        echo "<strong>TIE DETECTED - $position (Need {$result['slots_needed']} more winner(s)):</strong><br>";
                        echo "The following candidates are tied with {$result['tied_candidates'][0]['vote_count']} votes each:<br>";
                        foreach ($result['tied_candidates'] as $candidate) {
                            echo "- {$candidate['name']} ({$candidate['party']})<br>";
                        }
                        
                        $tie_key = $position . '_multi';
                        if (!isset($tie_break_decisions[$tie_key])) {
                            echo "<form method='POST' style='margin-top: 10px;'>";
                            echo "<input type='hidden' name='resolve_tie' value='1'>";
                            echo "<input type='hidden' name='position' value='" . htmlspecialchars($position) . "'>";
                            echo "<p>Select {$result['slots_needed']} candidate(s) to fill remaining slot(s):</p>";
                            echo "<select name='winner_ids[]' class='multi-select' multiple required size='" . count($result['tied_candidates']) . "'>";
                            foreach ($result['tied_candidates'] as $candidate) {
                                echo "<option value='{$candidate['id']}'>{$candidate['name']} ({$candidate['vote_count']} votes)</option>";
                            }
                            echo "</select>";
                            echo "<button type='submit' class='resolve-btn'>Resolve Tie (3-Token Auth Required)</button>";
                            echo "</form>";
                        } else {
                            echo "<div class='tie-resolution'>";
                            echo "<strong>Tie resolved by admin.</strong>";
                            echo "</div>";
                        }
                    }
                    echo "</div>";
                } else {
                    // Display winners
                    foreach ($result['winners'] as $winner) {
                        echo "<div class='winner-row'>";
                        echo "<strong>$position:</strong> {$winner['name']} ({$winner['party']}) - {$winner['vote_count']} votes";
                        echo "</div>";
                    }
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
                // Use the same ordered positions for detailed results
                foreach ($ordered_positions as $position) {
                    $check_stmt = $db->prepare("SELECT COUNT(*) FROM candidates WHERE position = ?");
                    $check_stmt->bindValue(1, $position);
                    $exists = $check_stmt->execute()->fetchArray()[0];
                    
                    if ($exists > 0) {
                        $sql = "SELECT id, position, name, party, vote_count FROM candidates WHERE position = ? ORDER BY vote_count DESC";
                        $stmt = $db->prepare($sql);
                        $stmt->bindValue(1, $position);
                        $result = $stmt->execute();
                        
                        while ($row = $result->fetchArray()) {
                            $status = 'Participant';
                            
                            // Check status based on tie-breaking results
                            $position_result = getWinnersWithTieHandling($db, $row['position'], $tie_break_decisions, $school_class, $school_level);
                            
                            if ($position_result['has_tie']) {
                                // Check if this candidate is in the tied group
                                foreach ($position_result['tied_candidates'] as $tied_candidate) {
                                    if ($row['id'] == $tied_candidate['id']) {
                                        if ($position_result['tie_type'] == 'single') {
                                            $status = '<span style="color: red; font-weight: bold;">TIED - Requires Resolution</span>';
                                        } else {
                                            $status = '<span style="color: orange; font-weight: bold;">TIED FOR SLOT - Requires Resolution</span>';
                                        }
                                        break;
                                    }
                                }
                            } else {
                                // Check if this candidate is a winner
                                foreach ($position_result['winners'] as $winner) {
                                    if ($row['id'] == $winner['id']) {
                                        $status = '<span style="color: green; font-weight: bold;">WINNER</span>';
                                        break;
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
                    }
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