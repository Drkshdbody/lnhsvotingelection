<?php
session_start();

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
    
    // Determine system title based on school level
    $school_level = $settings['school_level'] ?? 'Junior High School';
    if ($school_level === 'Elementary') {
        $system_title = "Supreme Elementary Learner Government Election System";
    } else {
        $system_title = "Supreme Secondary Learner Government Election System";
    }
} else {
    $settings = $default_settings;
    $system_title = "Supreme Secondary Learner Government Election System";
}

// Load results from database
$db = new SQLite3('election.db');

// Load tie-break decisions
$tie_break_file = 'tie_break_decisions.json';
$tie_break_decisions = [];
if (file_exists($tie_break_file)) {
    $tie_break_decisions = json_decode(file_get_contents($tie_break_file), true);
}

// Enhanced function to get actual winners considering tie breaks
function getActualWinnersForResults($db, $position, $tie_break_decisions, $school_class) {
    // Determine number of winners needed
    $num_winners = 1;
    if (strpos($position, 'Representative') !== false && in_array($school_class, ['Large', 'Mega'])) {
        if (!preg_match('/\d$/', $position)) {
            $num_winners = 2;
        }
    }
    
    // Get all candidates ordered by votes
    $stmt = $db->prepare("SELECT * FROM candidates WHERE position = ? ORDER BY vote_count DESC");
    $stmt->bindValue(1, $position);
    $result = $stmt->execute();
    
    $candidates = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $candidates[] = $row;
    }
    
    if (empty($candidates)) {
        return ['winners' => [], 'all_candidates' => []];
    }
    
    $winners = [];
    $has_tie = false;
    
    if ($num_winners == 1) {
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
            $winners = [$tied_candidates[0]];
        } elseif (isset($tie_break_decisions[$position])) {
            $resolved_winner_id = $tie_break_decisions[$position];
            foreach ($tied_candidates as $candidate) {
                if ($candidate['id'] == $resolved_winner_id) {
                    $winners = [$candidate];
                    break;
                }
            }
        } else {
            $winners = $tied_candidates;
            $has_tie = true;
        }
    } else {
        $remaining_slots = $num_winners;
        $current_vote_count = null;
        $candidates_at_current_level = [];
        
        foreach ($candidates as $candidate) {
            if ($remaining_slots <= 0) break;
            
            if ($current_vote_count === null || $candidate['vote_count'] == $current_vote_count) {
                $candidates_at_current_level[] = $candidate;
                $current_vote_count = $candidate['vote_count'];
            } else {
                if (count($candidates_at_current_level) <= $remaining_slots) {
                    $winners = array_merge($winners, $candidates_at_current_level);
                    $remaining_slots -= count($candidates_at_current_level);
                } else {
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
                        $winners = array_merge($winners, $candidates_at_current_level);
                        $has_tie = true;
                        break;
                    }
                }
                
                $candidates_at_current_level = [$candidate];
                $current_vote_count = $candidate['vote_count'];
            }
        }
        
        if (!empty($candidates_at_current_level) && $remaining_slots > 0) {
            if (count($candidates_at_current_level) <= $remaining_slots) {
                $winners = array_merge($winners, $candidates_at_current_level);
            } else {
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
                    $winners = array_merge($winners, $candidates_at_current_level);
                    $has_tie = true;
                }
            }
        }
    }
    
    return ['winners' => $winners, 'all_candidates' => $candidates, 'has_tie' => $has_tie];
}

// Determine positions based on school classification
$schoolClass = $settings['school_classification'];
$order = [
    'President',
    'Vice President',
    'Secretary',
    'Treasurer',
    'Auditor',
    'Public Information Officer',
    'Protocol Officer'
];

if (in_array($schoolClass, ['Small', 'Medium'])) {
    $order = array_merge($order, [
        'Grade 10 Representative',
        'Grade 9 Representative',
        'Grade 8 Representative'
    ]);
} else {
    $order = array_merge($order, [
        'Grade 10 Representative 1',
        'Grade 10 Representative 2',
        'Grade 9 Representative 1',
        'Grade 9 Representative 2',
        'Grade 8 Representative 1',
        'Grade 8 Representative 2'
    ]);
}

// Get results with tie handling
$results_with_winners = [];
foreach ($order as $position) {
    $result_data = getActualWinnersForResults($db, $position, $tie_break_decisions, $schoolClass);
    if (!empty($result_data['all_candidates'])) {
        $results_with_winners[$position] = $result_data;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Election Results - <?= htmlspecialchars($system_title) ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: white;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
        }
        
        .header {
            text-align: center;
            padding: 40px 20px;
            border-bottom: 1px solid #e0e0e0;
            background: #f8f9fa;
        }
        
        .logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            display: block;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dfe1e5;
        }
        
        .system-title {
            font-size: 2.5em;
            color: #202124;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .school-info {
            font-size: 1.2em;
            color: #5f6368;
            margin-bottom: 10px;
        }
        
        .school-details {
            font-size: 1em;
            color: #5f6368;
        }
        
        .results-section {
            padding: 30px;
        }
        
        .position-group {
            margin-bottom: 40px;
            border: 1px solid #dfe1e5;
            border-radius: 8px;
            padding: 20px;
        }
        
        .position-title {
            font-size: 1.5em;
            color: #202124;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #4285f4;
        }
        
        .tie-status {
            background: #f8d7da;
            color: #721c24;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9em;
            margin-left: 10px;
        }
        
        .tie-resolved {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .candidate-row {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #dfe1e5;
            border-radius: 8px;
            margin-bottom: 10px;
            background: white;
        }
        
        .candidate-rank {
            font-size: 1.2em;
            font-weight: bold;
            color: #4285f4;
            margin-right: 15px;
            min-width: 30px;
            text-align: center;
        }
        
        .candidate-photo {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #dfe1e5;
        }
        
        .candidate-info {
            flex: 1;
        }
        
        .candidate-name {
            font-size: 1.1em;
            font-weight: bold;
            color: #202124;
        }
        
        .candidate-party {
            font-size: 0.9em;
            color: #5f6368;
            margin-top: 2px;
        }
        
        .candidate-votes {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
            margin-left: 20px;
        }
        
        .winner-badge {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8em;
            margin-left: 10px;
        }
        
        .tie-candidate {
            background: #fff3cd;
            border-color: #ffeaa7;
        }
        
        .stats-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #dfe1e5;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: #4285f4;
            display: block;
        }
        
        .stat-label {
            font-size: 0.9em;
            color: #5f6368;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 30px;
            color: #70757a;
            font-size: 0.9em;
            border-top: 1px solid #e0e0e0;
        }
        
        .back-link {
            margin-top: 20px;
            text-align: center;
        }
        
        .back-link a {
            color: #4285f4;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 768px) {
            .candidate-row {
                flex-direction: column;
                text-align: center;
            }
            
            .candidate-rank {
                margin-bottom: 10px;
            }
            
            .candidate-votes {
                margin-left: 0;
                margin-top: 10px;
            }
            
            .system-title {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if (file_exists($settings['logo_path'])): ?>
                <img src="<?= $settings['logo_path'] ?>" alt="School Logo" class="logo">
            <?php else: ?>
                <div class="logo" style="background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #5f6368; font-weight: bold; font-size: 1.5em;">LOGO</div>
            <?php endif; ?>
            
            <h1 class="system-title"><?= htmlspecialchars($system_title) ?></h1>
            <div class="school-info">
                <h2><?= htmlspecialchars($settings['school_name']) ?></h2>
            </div>
            <div class="school-details">
                School ID: <?= htmlspecialchars($settings['school_id']) ?> | 
                Principal: <?= htmlspecialchars($settings['principal']) ?> | 
                Classification: <?= htmlspecialchars($settings['school_classification']) ?>
            </div>
        </div>

        <div class="stats-section">
            <div class="stats-grid">
                <?php
                $total_students = $db->querySingle("SELECT COUNT(*) FROM students");
                $voted_students = $db->querySingle("SELECT COUNT(*) FROM students WHERE has_voted = 1");
                $turnout = $total_students > 0 ? round(($voted_students / $total_students) * 100, 2) : 0;
                $total_candidates = $db->querySingle("SELECT COUNT(*) FROM candidates");
                $total_votes = $db->querySingle("SELECT COUNT(*) FROM votes");
                ?>
                
                <div class="stat-card">
                    <span class="stat-value"><?= $total_students ?></span>
                    <span class="stat-label">Total Students</span>
                </div>
                
                <div class="stat-card">
                    <span class="stat-value"><?= $voted_students ?></span>
                    <span class="stat-label">Voted Students</span>
                </div>
                
                <div class="stat-card">
                    <span class="stat-value"><?= $turnout ?>%</span>
                    <span class="stat-label">Voter Turnout</span>
                </div>
                
                <div class="stat-card">
                    <span class="stat-value"><?= $total_votes ?></span>
                    <span class="stat-label">Total Votes</span>
                </div>
            </div>
        </div>

        <div class="results-section">
            <?php foreach ($order as $position): ?>
                <?php if (isset($results_with_winners[$position])): ?>
                    <?php 
                    $result_data = $results_with_winners[$position];
                    $winners = $result_data['winners'];
                    $all_candidates = $result_data['all_candidates'];
                    $has_tie = $result_data['has_tie'];
                    $is_resolved = (isset($tie_break_decisions[$position]) || isset($tie_break_decisions[$position . '_multi']));
                    ?>
                    <div class="position-group">
                        <h3 class="position-title">
                            <?= htmlspecialchars($position) ?>
                            <?php if ($has_tie): ?>
                                <span class="tie-status <?= $is_resolved ? 'tie-resolved' : '' ?>">
                                    <?= $is_resolved ? 'TIE RESOLVED' : 'TIE DETECTED' ?>
                                </span>
                            <?php endif; ?>
                        </h3>
                        <?php foreach ($all_candidates as $rank => $candidate): ?>
                            <?php 
                            $is_winner = false;
                            foreach ($winners as $winner) {
                                if ($winner['id'] == $candidate['id']) {
                                    $is_winner = true;
                                    break;
                                }
                            }
                            $is_tied = $has_tie && !$is_resolved;
                            ?>
                            <div class="candidate-row <?= $is_tied ? 'tie-candidate' : '' ?>">
                                <div class="candidate-rank">#<?= $rank + 1 ?></div>
                                <?php if (!empty($candidate['photo_path']) && file_exists($candidate['photo_path'])): ?>
                                    <img src="<?= $candidate['photo_path'] ?>" alt="<?= $candidate['name'] ?>" class="candidate-photo">
                                <?php else: ?>
                                    <div class="candidate-photo" style="background-color: #f1f3f4; display: flex; align-items: center; justify-content: center; color: #6c757d; font-weight: bold; font-size: 1.5em;">
                                        <?= strtoupper(substr($candidate['name'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="candidate-info">
                                    <div class="candidate-name"><?= htmlspecialchars($candidate['name']) ?></div>
                                    <div class="candidate-party"><?= htmlspecialchars($candidate['party'] ?? 'N/A') ?></div>
                                </div>
                                <div class="candidate-votes"><?= $candidate['vote_count'] ?> votes</div>
                                <?php if ($is_winner): ?>
                                    <div class="winner-badge">WINNER</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        
        <div class="back-link">
            <a href="index.php">← Back to Home</a>
        </div>
        
        <div class="footer">
            <p>Final Election Results - <?= date('Y-m-d H:i:s') ?></p>
            <p>Powered by <?= htmlspecialchars($system_title) ?></p>
            <p>Developed by: <a href="https://www.facebook.com/sirtopet" target="_blank">Cristopher Duro</a></p>
        </div>
    </div>
</body>
</html>