<?php
session_start();

if (!isset($_SESSION['lrn']) || !isset($_SESSION['token'])) {
    header("Location: index.php");
    exit;
}

$lrn = $_SESSION['lrn'];
$token = $_SESSION['token'];

// Validate session
$db = new SQLite3('election.db');
$stmt = $db->prepare("SELECT * FROM students WHERE lrn = ? AND login_token = ?");
$stmt->bindValue(1, $lrn);
$stmt->bindValue(2, $token);
$result = $stmt->execute();
$student = $result->fetchArray(SQLITE3_ASSOC);

if (!$student || $student['has_voted'] == 1) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Load school settings
$settings_file = 'school_settings.json';
$default_settings = [
    'school_name' => 'Sample High School',
    'school_id' => 'SHS-2026',
    'principal' => 'Dr. Juan Santos',
    'logo_path' => 'logo.png',
    'school_classification' => 'Small',
    'custom_system_title' => '' // Add custom title field
];

if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    $settings = array_merge($default_settings, $settings);
    $school_level = $settings['school_level'] ?? 'Junior High School';
    $school_class = $settings['school_classification'] ?? 'Small';
    
    // Determine system title - prioritize custom title
    if (!empty($settings['custom_system_title'])) {
        $system_title = $settings['custom_system_title'];
    } else {
        // Auto-generate based on school level
        if ($school_level === 'Elementary') {
            $system_title = "Supreme Elementary Learner Government Election System";
        } else {
            $system_title = "Supreme Secondary Learner Government Election System";
        }
    }
} else {
    $settings = $default_settings;
    $school_level = 'Junior High School';
    $school_class = 'Small';
    $system_title = "Supreme Secondary Learner Government Election System";
}

// Get student grade level for position filtering
$student_grade = $student['grade_level'];

// Determine representative position name based on grade and school class
function getRepresentativePosition($grade, $school_level, $school_class) {
    switch ($school_level) {
        case 'Elementary':
            $grade_map = [2 => 'Grade 3', 3 => 'Grade 4', 4 => 'Grade 5', 5 => 'Grade 6'];
            break;
        case 'Integrated School':
            $grade_map = [7 => 'Grade 8', 8 => 'Grade 9', 9 => 'Grade 10', 10 => 'Grade 11', 11 => 'Grade 12'];
            break;
        case 'Senior High School':
            $grade_map = [11 => 'Grade 12'];
            break;
        case 'Junior High School':
        default:
            $grade_map = [7 => 'Grade 8', 8 => 'Grade 9', 9 => 'Grade 10'];
            break;
    }
    
    if (isset($grade_map[$grade])) {
        return $grade_map[$grade] . ' Representative';
    }
    return null;
}

// Get current position from URL or set first position
$positions = [
    'President', 'Vice President', 'Secretary', 'Treasurer', 'Auditor',
    'Public Information Officer', 'Protocol Officer'
];

// Add representative position if applicable
$rep_position = getRepresentativePosition($student_grade, $school_level, $school_class);
if ($rep_position) {
    $positions[] = $rep_position;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_position = $_POST['current_position'] ?? '';
    $selected_candidates = $_POST['candidate_ids'] ?? [];
    $abstain = $_POST['abstain'] ?? '';
    $confirm_vote = $_POST['confirm_vote'] ?? '';
    
    if (!empty($confirm_vote)) {
        // Final confirmation - process the vote
        $votes = $_SESSION['votes'] ?? [];
        
        if (!empty($votes)) {
            $db->exec("BEGIN TRANSACTION");
            
            try {
                // Record all votes
                foreach ($votes as $position => $vote_values) {
                    if (is_array($vote_values)) {
                        // Multiple votes for this position (representatives)
                        foreach ($vote_values as $vote_value) {
                            if ($vote_value !== 'ABSTAIN') {
                                $stmt = $db->prepare("INSERT INTO votes (student_lrn, candidate_id, position) VALUES (?, ?, ?)");
                                $stmt->bindValue(1, $lrn);
                                $stmt->bindValue(2, $vote_value);
                                $stmt->bindValue(3, $position);
                                $stmt->execute();
                                
                                $stmt = $db->prepare("UPDATE candidates SET vote_count = vote_count + 1 WHERE id = ?");
                                $stmt->bindValue(1, $vote_value);
                                $stmt->execute();
                            }
                        }
                    } else {
                        // Single vote for this position
                        if ($vote_values !== 'ABSTAIN') {
                            $stmt = $db->prepare("INSERT INTO votes (student_lrn, candidate_id, position) VALUES (?, ?, ?)");
                            $stmt->bindValue(1, $lrn);
                            $stmt->bindValue(2, $vote_values);
                            $stmt->bindValue(3, $position);
                            $stmt->execute();
                            
                            $stmt = $db->prepare("UPDATE candidates SET vote_count = vote_count + 1 WHERE id = ?");
                            $stmt->bindValue(1, $vote_values);
                            $stmt->execute();
                        }
                    }
                }
                
                // Mark student as voted
                $stmt = $db->prepare("UPDATE students SET has_voted = 1 WHERE lrn = ?");
                $stmt->bindValue(1, $lrn);
                $stmt->execute();
                
                $db->exec("COMMIT");
                
                // Show thank you page
                $show_thank_you = true;
                
            } catch (Exception $e) {
                $db->exec("ROLLBACK");
                $error_message = "Error processing your vote. Please try again.";
            }
        }
    } elseif (!empty($current_position)) {
        if (!empty($abstain)) {
            // Store abstain vote in session
            if ($current_position === $rep_position && in_array($school_class, ['Large', 'Mega'])) {
                // For large/mega schools, store array of abstains
                $_SESSION['votes'][$current_position] = ['ABSTAIN', 'ABSTAIN'];
            } else {
                $_SESSION['votes'][$current_position] = 'ABSTAIN';
            }
        } elseif (!empty($selected_candidates)) {
            // Store candidate votes in session
            if ($current_position === $rep_position && in_array($school_class, ['Large', 'Mega'])) {
                // For large/mega schools, allow up to 2 selections
                $_SESSION['votes'][$current_position] = array_slice($selected_candidates, 0, 2);
            } else {
                $_SESSION['votes'][$current_position] = $selected_candidates[0];
            }
        }
        
        // Find next position
        $current_index = array_search($current_position, $positions);
        if ($current_index !== false && $current_index < count($positions) - 1) {
            // Go to next position
            $next_position = $positions[$current_index + 1];
            header("Location: vote.php?position=" . urlencode($next_position));
            exit;
        } else {
            // Show confirmation page
            $show_confirmation = true;
        }
    }
}

// Thank you page display
if (isset($show_thank_you)) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Thank You - <?= htmlspecialchars($system_title) ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
            .container { background-color: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; max-width: 600px; margin: 50px auto; }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e0e0e0;
            }
            .logo {
                width: 80px;
                height: 80px;
                margin: 0 auto 15px;
                display: block;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid #dfe1e5;
            }
            .system-title {
                font-size: 1.8em;
                color: #202124;
                margin-bottom: 5px;
                font-weight: bold;
            }
            .school-name {
                font-size: 1.2em;
                color: #5f6368;
            }
            .success-icon { font-size: 4em; color: #28a745; margin-bottom: 20px; }
            .thank-you-title { font-size: 2em; color: #28a745; margin-bottom: 20px; }
            .message { font-size: 1.2em; margin-bottom: 30px; color: #2c3e50; }
            .back-btn {
                background-color: #4285f4;
                color: white;
                padding: 12px 24px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-size: 16px;
                text-decoration: none;
                display: inline-block;
            }
            .back-btn:hover {
                background-color: #3367d6;
            }
            .footer {
                text-align: center;
                padding: 20px;
                margin-top: 30px;
                color: #70757a;
                font-size: 0.9em;
                border-top: 1px solid #e0e0e0;
            }
            .footer a {
                color: #4285f4;
                text-decoration: none;
            }
            .footer a:hover {
                text-decoration: underline;
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
                
                <div class="system-title"><?= htmlspecialchars($system_title) ?></div>
                <div class="school-name"><?= htmlspecialchars($settings['school_name']) ?></div>
            </div>
            
            <div class="success-icon">✓</div>
            <h1 class="thank-you-title">Thank You for Voting!</h1>
            <p class="message">Your vote has been successfully recorded. You have participated in making our school government better!</p>
            <a href="index.php" class="back-btn">Back to Login Page</a>
        </div>
        
        <div class="footer">
            <p>Powered by <?= htmlspecialchars($system_title) ?></p>
            <p>Developed by: <a href="https://www.facebook.com/sirtopet" target="_blank">Cristopher Duro</a></p>
        </div>
    </body>
    </html>
    <?php
    session_destroy();
    exit;
}

// Confirmation page display
if (isset($show_confirmation)) {
    $votes = $_SESSION['votes'] ?? [];
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Confirm Your vote - <?= htmlspecialchars($system_title) ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
            .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 800px; margin: 50px auto; }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e0e0e0;
            }
            .logo {
                width: 80px;
                height: 80px;
                margin: 0 auto 15px;
                display: block;
                border-radius: 50%;
                object-fit: cover;
                border: 2px solid #dfe1e5;
            }
            .system-title {
                font-size: 1.8em;
                color: #202124;
                margin-bottom: 5px;
                font-weight: bold;
            }
            .school-name {
                font-size: 1.2em;
                color: #5f6368;
            }
            .title { font-size: 1.8em; color: #2c3e50; margin-bottom: 20px; }
            .instructions { background-color: #fff3cd; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #ffeaa7; color: #856404; text-align: center; }
            .votes-summary { margin-bottom: 30px; }
            .vote-item { 
                display: flex; 
                justify-content: space-between; 
                padding: 10px; 
                border-bottom: 1px solid #eee; 
                align-items: center;
            }
            .vote-position { font-weight: bold; color: #2c3e50; }
            .vote-candidate { color: #666; }
            .abstain-text { color: #ffc107; font-weight: bold; }
            .action-buttons { text-align: center; margin-top: 30px; }
            .btn {
                padding: 12px 24px;
                border: none;
                border-radius: 6px;
                cursor: pointer;
                font-size: 16px;
                margin: 0 10px;
                text-decoration: none;
                display: inline-block;
            }
            .confirm-btn {
                background-color: #28a745;
                color: white;
            }
            .confirm-btn:hover {
                background-color: #218838;
            }
            .back-btn {
                background-color: #6c757d;
                color: white;
            }
            .back-btn:hover {
                background-color: #5a6268;
            }
            .student-info { 
                background-color: #f8f9fa; 
                padding: 10px; 
                border-radius: 4px; 
                margin-bottom: 20px; 
                text-align: center;
            }
            .footer {
                text-align: center;
                padding: 20px;
                margin-top: 30px;
                color: #70757a;
                font-size: 0.9em;
                border-top: 1px solid #e0e0e0;
            }
            .footer a {
                color: #4285f4;
                text-decoration: none;
            }
            .footer a:hover {
                text-decoration: underline;
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
                
                <div class="system-title"><?= htmlspecialchars($system_title) ?></div>
                <div class="school-name"><?= htmlspecialchars($settings['school_name']) ?></div>
            </div>
            
            <div class="title">Confirm Your Vote</div>
            
            <div class="student-info">
                <strong><?= htmlspecialchars($student['last_name'] . ', ' . $student['given_name'] . ' ' . $student['middle_name']) ?></strong><br>
                Grade <?= $student['grade_level'] ?>-<?= htmlspecialchars($student['section']) ?>
            </div>
            
            <div class="instructions">
                Please review your selections below. Once confirmed, your vote cannot be changed.
            </div>
            
            <div class="votes-summary">
                <?php foreach ($votes as $position => $vote_values): ?>
                    <?php if (is_array($vote_values)): ?>
                        <!-- Multiple votes (representatives) -->
                        <div class="vote-item">
                            <span class="vote-position"><?= htmlspecialchars($position) ?>:</span>
                            <span class="vote-candidate">
                                <?php 
                                $candidates_text = [];
                                foreach ($vote_values as $vote_value) {
                                    if ($vote_value === 'ABSTAIN') {
                                        $candidates_text[] = '<span class="abstain-text">ABSTAIN</span>';
                                    } else {
                                        $stmt = $db->prepare("SELECT name FROM candidates WHERE id = ?");
                                        $stmt->bindValue(1, $vote_value);
                                        $result = $stmt->execute();
                                        $candidate = $result->fetchArray(SQLITE3_ASSOC);
                                        $candidates_text[] = htmlspecialchars($candidate['name'] ?? 'Unknown Candidate');
                                    }
                                }
                                echo implode(', ', $candidates_text);
                                ?>
                            </span>
                        </div>
                    <?php else: ?>
                        <!-- Single vote -->
                        <div class="vote-item">
                            <span class="vote-position"><?= htmlspecialchars($position) ?>:</span>
                            <span class="vote-candidate">
                                <?php if ($vote_values === 'ABSTAIN'): ?>
                                    <span class="abstain-text">ABSTAIN</span>
                                <?php else:
                                    $stmt = $db->prepare("SELECT name FROM candidates WHERE id = ?");
                                    $stmt->bindValue(1, $vote_values);
                                    $result = $stmt->execute();
                                    $candidate = $result->fetchArray(SQLITE3_ASSOC);
                                    echo htmlspecialchars($candidate['name'] ?? 'Unknown Candidate');
                                endif; ?>
                            </span>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            
            <div class="action-buttons">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="confirm_vote" value="1">
                    <button type="submit" class="btn confirm-btn">Confirm and Submit Vote</button>
                </form>
                <a href="vote.php?position=<?= urlencode($positions[0]) ?>" class="btn back-btn">Edit My Votes</a>
            </div>
        </div>
        
        <div class="footer">
            <p>Powered by <?= htmlspecialchars($system_title) ?></p>
            <p>Developed by: <a href="https://www.facebook.com/sirtopet" target="_blank">Cristopher Duro</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Regular voting page
$current_position = $_GET['position'] ?? ($positions[0] ?? '');

// Get candidates for current position - FIXED LOGIC
$candidates = [];
if (!empty($current_position)) {
    if ($current_position === $rep_position) {
        // For representative positions, get all candidates for this grade level
        $grade_map = [
            'Elementary' => [2 => 'Grade 3', 3 => 'Grade 4', 4 => 'Grade 5', 5 => 'Grade 6'],
            'Integrated School' => [7 => 'Grade 8', 8 => 'Grade 9', 9 => 'Grade 10', 10 => 'Grade 11', 11 => 'Grade 12'],
            'Senior High School' => [11 => 'Grade 12'],
            'Junior High School' => [7 => 'Grade 8', 8 => 'Grade 9', 9 => 'Grade 10']
        ];
        
        $target_positions = [];
        if (isset($grade_map[$school_level][$student_grade])) {
            $base_position = $grade_map[$school_level][$student_grade] . ' Representative';
            
            if (in_array($school_class, ['Small', 'Medium'])) {
                // Small/Medium: look for exact match
                $target_positions = [$base_position];
            } else {
                // Large/Mega: look for both variations
                $target_positions = [$base_position . ' 1', $base_position . ' 2'];
            }
            
            // Build query with multiple positions
            $placeholders = str_repeat('?,', count($target_positions) - 1) . '?';
            $stmt = $db->prepare("SELECT * FROM candidates WHERE position IN ($placeholders) ORDER BY name");
            foreach ($target_positions as $index => $pos) {
                $stmt->bindValue($index + 1, $pos);
            }
            $result = $stmt->execute();
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $candidates[] = $row;
            }
        }
    } else {
        // Regular positions
        $stmt = $db->prepare("SELECT * FROM candidates WHERE position = ? ORDER BY name");
        $stmt->bindValue(1, $current_position);
        $result = $stmt->execute();
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $candidates[] = $row;
        }
    }
}

$current_index = array_search($current_position, $positions);
$prev_position = ($current_index > 0) ? $positions[$current_index - 1] : null;
$next_position = ($current_index < count($positions) - 1) ? $positions[$current_index + 1] : null;

// Check if current position is representative and school is Large/Mega
$is_representative = ($current_position === $rep_position);
$allow_multiple = ($is_representative && in_array($school_class, ['Large', 'Mega']));
$max_selections = $allow_multiple ? 2 : 1;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Vote - <?= htmlspecialchars($current_position) ?> - <?= htmlspecialchars($system_title) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            display: block;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dfe1e5;
        }
        .system-title {
            font-size: 1.8em;
            color: #202124;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .school-name {
            font-size: 1.2em;
            color: #5f6368;
        }
        .student-info { background-color: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 20px; text-align: center; }
        .progress-bar { 
            background-color: #e9ecef; 
            padding: 10px; 
            border-radius: 4px; 
            margin-bottom: 20px; 
            text-align: center;
        }
        .progress-text { 
            font-size: 0.9em; 
            color: #6c757d; 
        }
        .position-title { 
            font-size: 1.5em; 
            font-weight: bold; 
            margin-bottom: 20px; 
            color: #2c3e50; 
            text-align: center;
        }
        .candidates-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
            margin-bottom: 20px;
        }
        .candidate-card {
            border: 2px solid #ddd;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background-color: #fff;
        }
        .candidate-card:hover {
            border-color: #4285f4;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .candidate-card.selected {
            border-color: #4285f4;
            background-color: #e8f0fe;
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.2);
        }
        .candidate-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 15px;
            border: 3px solid #ddd;
        }
        .candidate-name { 
            font-weight: bold; 
            margin-bottom: 8px; 
            font-size: 1.1em;
            color: #2c3e50;
        }
        .candidate-party { 
            color: #666; 
            font-size: 0.9em;
            background-color: #f8f9fa;
            padding: 4px 8px;
            border-radius: 12px;
            display: inline-block;
            margin-top: 5px;
        }
        .no-photo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-weight: bold;
            font-size: 24px;
            margin: 0 auto 15px;
            border: 3px solid #ddd;
        }
        .nav-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        .nav-btn {
            background-color: #4285f4;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.2s ease;
        }
        .nav-btn:hover {
            background-color: #3367d6;
        }
        .back-btn {
            background-color: #6c757d;
        }
        .back-btn:hover {
            background-color: #5a6268;
        }
        .submit-btn {
            background-color: #28a745;
        }
        .submit-btn:hover {
            background-color: #218838;
        }
        .abstain-btn {
            background-color: #ffc107;
            color: #212529;
        }
        .abstain-btn:hover {
            background-color: #e0a800;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            text-align: center;
        }
        .instructions {
            background-color: #fff3cd;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
            text-align: center;
            color: #856404;
        }
        .selection-counter {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
            text-align: center;
        }
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 30px;
            color: #70757a;
            font-size: 0.9em;
            border-top: 1px solid #e0e0e0;
        }
        .footer a {
            color: #4285f4;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
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
            
            <div class="system-title"><?= htmlspecialchars($system_title) ?></div>
            <div class="school-name"><?= htmlspecialchars($settings['school_name']) ?></div>
        </div>
        
        <div class="student-info">
            <strong><?= htmlspecialchars($student['last_name'] . ', ' . $student['given_name'] . ' ' . $student['middle_name']) ?></strong><br>
            Grade <?= $student['grade_level'] ?>-<?= htmlspecialchars($student['section']) ?>
        </div>
        
        <div class="progress-bar">
            <div class="progress-text">
                Position <?= ($current_index + 1) ?> of <?= count($positions) ?>
            </div>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <div class="position-title"><?= htmlspecialchars($current_position) ?></div>
        
        <div class="instructions">
            <?php if ($allow_multiple): ?>
                Please select up to <?= $max_selections ?> candidates for this position, or choose ABSTAIN.
            <?php else: ?>
                Please select one candidate for this position, or choose ABSTAIN.
            <?php endif; ?>
        </div>
        
        <form method="POST" id="voteForm">
            <input type="hidden" name="current_position" value="<?= htmlspecialchars($current_position) ?>">
            
            <div class="candidates-grid">
                <?php foreach ($candidates as $candidate): ?>
                    <div class="candidate-card" onclick="selectCandidate(this, <?= $candidate['id'] ?>)">
                        <?php if (!empty($candidate['photo_path']) && file_exists($candidate['photo_path'])): ?>
                            <img src="<?= $candidate['photo_path'] ?>" alt="<?= $candidate['name'] ?>" class="candidate-photo">
                        <?php else: ?>
                            <div class="no-photo"><?= strtoupper(substr($candidate['name'], 0, 1)) ?></div>
                        <?php endif; ?>
                        <div class="candidate-name"><?= htmlspecialchars($candidate['name']) ?></div>
                        <?php if (!empty($candidate['party'])): ?>
                            <div class="candidate-party"><?= htmlspecialchars($candidate['party']) ?></div>
                        <?php endif; ?>
                        <?php if ($allow_multiple): ?>
                            <input type="checkbox" name="candidate_ids[]" value="<?= $candidate['id'] ?>" style="display: none;" class="candidate-checkbox">
                        <?php else: ?>
                            <input type="radio" name="candidate_ids[]" value="<?= $candidate['id'] ?>" style="display: none;" class="candidate-radio">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($allow_multiple): ?>
                <div class="selection-counter" id="selectionCounter">Selected: 0 / <?= $max_selections ?></div>
            <?php endif; ?>
            
            <div class="nav-buttons">
                <?php if ($prev_position): ?>
                    <a href="vote.php?position=<?= urlencode($prev_position) ?>" class="nav-btn back-btn">← Previous</a>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>
                
                <?php if ($next_position): ?>
                    <button type="submit" name="abstain" value="1" class="nav-btn abstain-btn">ABSTAIN</button>
                    <button type="submit" class="nav-btn" id="nextBtn" disabled>Next →</button>
                <?php else: ?>
                    <button type="submit" name="abstain" value="1" class="nav-btn abstain-btn">ABSTAIN</button>
                    <button type="submit" class="nav-btn submit-btn" id="submitBtn" disabled>Review My Votes</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <div class="footer">
        <p>Powered by <?= htmlspecialchars($system_title) ?></p>
        <p>Developed by: <a href="https://www.facebook.com/sirtopet" target="_blank">Cristopher Duro</a></p>
    </div>

    <script>
        let selectedCount = 0;
        const maxSelections = <?= $max_selections ?>;
        const allowMultiple = <?= $allow_multiple ? 'true' : 'false' ?>;
        
        function selectCandidate(card, candidateId) {
            if (allowMultiple) {
                const checkbox = card.querySelector('.candidate-checkbox');
                checkbox.checked = !checkbox.checked;
                
                if (checkbox.checked) {
                    card.classList.add('selected');
                    selectedCount++;
                } else {
                    card.classList.remove('selected');
                    selectedCount--;
                }
                
                // Update selection counter
                document.getElementById('selectionCounter').textContent = 'Selected: ' + selectedCount + ' / ' + maxSelections;
                
                // Enable/disable next button based on selection
                const nextBtn = document.getElementById('nextBtn');
                const submitBtn = document.getElementById('submitBtn');
                if (nextBtn) nextBtn.disabled = (selectedCount === 0);
                if (submitBtn) submitBtn.disabled = (selectedCount === 0);
                
                // Limit selections to maxSelections
                if (selectedCount > maxSelections) {
                    // Uncheck the last checked item
                    checkbox.checked = false;
                    card.classList.remove('selected');
                    selectedCount--;
                    document.getElementById('selectionCounter').textContent = 'Selected: ' + selectedCount + ' / ' + maxSelections;
                }
            } else {
                // Single selection (radio button behavior)
                const allCards = document.querySelectorAll('.candidate-card');
                allCards.forEach(c => c.classList.remove('selected'));
                
                card.classList.add('selected');
                const radio = card.querySelector('.candidate-radio');
                radio.checked = true;
                
                const nextBtn = document.getElementById('nextBtn');
                const submitBtn = document.getElementById('submitBtn');
                if (nextBtn) nextBtn.disabled = false;
                if (submitBtn) submitBtn.disabled = false;
            }
        }
        
        // Form validation
        document.getElementById('voteForm').addEventListener('submit', function(e) {
            let hasSelection = false;
            const abstainClicked = e.submitter && e.submitter.name === 'abstain';
            
            if (!abstainClicked) {
                if (allowMultiple) {
                    const checkboxes = document.querySelectorAll('.candidate-checkbox:checked');
                    hasSelection = checkboxes.length > 0;
                } else {
                    const radio = document.querySelector('.candidate-radio:checked');
                    hasSelection = radio !== null;
                }
                
                if (!hasSelection) {
                    e.preventDefault();
                    alert('Please select <?= $allow_multiple ? "at least one candidate" : "one candidate" ?> for this position or choose ABSTAIN.');
                }
            }
        });
    </script>
</body>
</html>