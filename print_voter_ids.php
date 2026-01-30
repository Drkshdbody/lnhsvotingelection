<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
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

$db = new SQLite3('election.db');
$students = $db->query("SELECT * FROM students ORDER BY grade_level, section, last_name, given_name");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Print Voter IDs - School Learner Government Election System</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 0; 
            padding: 0; 
            background-color: #f5f5f5; 
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
        }
        
        .controls {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
        }
        
        button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
        }
        
        button:hover {
            background: #0056b3;
        }
        
        .page-container {
            width: 210mm; /* A4 width */
            min-height: 297mm; /* A4 height */
            margin: 0 auto;
            padding: 2mm;
            box-sizing: border-box;
            position: relative;
        }
        
        .voter-id {
            width: 85mm; /* Standard ID width (credit card size) */
            height: 54mm; /* Standard ID height (credit card size) */
            border: 1px solid #000;
            margin: 2mm;
            padding: 2mm;
            display: inline-block;
            vertical-align: top;
            box-sizing: border-box;
            font-size: 8px;
            position: relative;
            background: white;
            overflow: hidden;
        }
        
        .id-header {
            text-align: center;
            margin-bottom: 1mm;
        }
        
        .id-logo {
            width: 12mm;
            height: 12mm;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 1mm;
            border: 1px solid #000;
            display: block;
        }
        
        .id-school-name {
            font-size: 9px;
            font-weight: bold;
            margin-bottom: 1mm;
        }
        
        .long-line {
            border-top: 1px solid #000;
            margin: 0 auto 1mm;
            width: 95%;
        }
        
        .voter-id-title {
            font-size: 10px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2mm;
        }
        
        .student-name {
            font-weight: bold;
            font-size: 10px;
            margin-bottom: 1mm;
            text-align: center;
            word-break: break-word;
        }
        
        .student-grade-section {
            font-size: 9px;
            margin-bottom: 2mm;
            text-align: center;
        }
        
        .student-token {
            background: #000;
            color: white;
            padding: 1mm 2mm;
            margin: 0 auto 1mm;
            font-family: monospace;
            font-size: 16px; /* 2x the original size (8px x 2 = 16px) */
            word-break: break-all;
            font-weight: bold;
            border-radius: 2px;
            text-align: center;
            display: block;
            width: fit-content;
        }
        
        .token-note {
            font-size: 6px;
            text-align: center;
            color: #666;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            
            .controls {
                display: none;
            }
            
            .page-container {
                margin: 0;
                padding: 0;
                width: 100%;
                min-height: 100%;
                box-shadow: none;
                border: none;
            }
            
            .voter-id {
                margin: 2mm;
                page-break-inside: avoid;
                box-shadow: none;
                border: 1px solid #000;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="controls">
            <button onclick="window.print()">Print All Voter IDs</button>
            <button onclick="location.href='admin_panel.php'">← Back to Admin Panel</button>
        </div>
        
        <div style="text-align: center;">
            <div class="page-container">
                <?php
                $count = 0;
                while ($student = $students->fetchArray()) {
                    $count++;
                    ?>
                    <div class="voter-id">
                        <div class="id-header">
                            <?php if (file_exists($settings['logo_path'])): ?>
                                <img src="<?= $settings['logo_path'] ?>" alt="Logo" class="id-logo">
                            <?php else: ?>
                                <div style="width: 12mm; height: 12mm; background: #f8f9fa; margin: 0 auto 1mm; border-radius: 50%; border: 1px solid #000; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px;">LOGO</div>
                            <?php endif; ?>
                            
                            <div class="id-school-name"><?= htmlspecialchars($settings['school_name']) ?></div>
                            <div class="long-line"></div>
                        </div>
                        
                        <div class="voter-id-title">VOTER'S ID</div>
                        
                        <div class="student-name"><?= htmlspecialchars($student['last_name'] . ', ' . $student['given_name'] . ' ' . $student['middle_name']) ?></div>
                        <div class="student-grade-section">Grade <?= $student['grade_level'] ?>-<?= htmlspecialchars($student['section']) ?></div>
                        
                        <div class="student-token">TOKEN: <?= htmlspecialchars($student['login_token']) ?></div>
                        <div class="token-note">This TOKEN is valid for 2026 Election ONLY</div>
                    </div>
                    
                    <?php
                    // Break page after every 12 cards (2 columns x 6 rows)
                    if ($count % 12 == 0) {
                        echo '</div><div class="page-container">';
                    }
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>