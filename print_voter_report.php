<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    $password = $_POST['password'] ?? '';
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

// Get all students from database
$db = new SQLite3('election.db');

// Get totals
$total_count = $db->querySingle("SELECT COUNT(*) FROM students");
$voted_count = $db->querySingle("SELECT COUNT(*) FROM students WHERE has_voted = 1");
$not_voted_count = $db->querySingle("SELECT COUNT(*) FROM students WHERE has_voted = 0");

// Get grade-level breakdown
$grade_breakdown = [];
$grades = $db->query("SELECT DISTINCT grade_level FROM students ORDER BY grade_level");
while ($grade = $grades->fetchArray()) {
    $grade_level = $grade['grade_level'];
    
    // Total students per grade
    $total_grade = $db->querySingle("SELECT COUNT(*) FROM students WHERE grade_level = $grade_level");
    
    // Voted students per grade
    $voted_grade = $db->querySingle("SELECT COUNT(*) FROM students WHERE grade_level = $grade_level AND has_voted = 1");
    
    // Not voted students per grade
    $not_voted_grade = $db->querySingle("SELECT COUNT(*) FROM students WHERE grade_level = $grade_level AND has_voted = 0");
    
    // Sections per grade
    $sections = [];
    $section_query = $db->query("SELECT DISTINCT section FROM students WHERE grade_level = $grade_level ORDER BY section");
    while ($section_row = $section_query->fetchArray()) {
        $section = $section_row['section'];
        $total_section = $db->querySingle("SELECT COUNT(*) FROM students WHERE grade_level = $grade_level AND section = '$section'");
        $voted_section = $db->querySingle("SELECT COUNT(*) FROM students WHERE grade_level = $grade_level AND section = '$section' AND has_voted = 1");
        $not_voted_section = $db->querySingle("SELECT COUNT(*) FROM students WHERE grade_level = $grade_level AND section = '$section' AND has_voted = 0");
        
        $sections[$section] = [
            'total' => $total_section,
            'voted' => $voted_section,
            'not_voted' => $not_voted_section,
            'turnout' => $total_section > 0 ? round(($voted_section / $total_section) * 100, 2) : 0
        ];
    }
    
    $grade_breakdown[$grade_level] = [
        'total' => $total_grade,
        'voted' => $voted_grade,
        'not_voted' => $not_voted_grade,
        'turnout' => $total_grade > 0 ? round(($voted_grade / $total_grade) * 100, 2) : 0,
        'sections' => $sections
    ];
}

// Get all students for detailed lists
$all_students_result = $db->query("SELECT * FROM students ORDER BY grade_level, section, last_name");
$voted_students_result = $db->query("SELECT * FROM students WHERE has_voted = 1 ORDER BY grade_level, section, last_name");
$not_voted_students_result = $db->query("SELECT * FROM students WHERE has_voted = 0 ORDER BY grade_level, section, last_name");
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($system_title) ?> - Voter Report</title>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #000;
        }
        
        .logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
            display: block;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #000;
        }
        
        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .report-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #000;
        }
        
        .report-subtitle {
            font-size: 16px;
            margin-bottom: 20px;
            color: #333;
        }
        
        .summary-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
        }
        
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }
        
        .summary-item {
            text-align: center;
        }
        
        .summary-value {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        
        .summary-label {
            font-size: 14px;
            color: #666;
        }
        
        .grade-breakdown {
            margin: 20px 0;
        }
        
        .grade-section {
            margin: 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .grade-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #000;
        }
        
        .section-details {
            margin-left: 20px;
        }
        
        .section-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        
        .section-name {
            font-weight: bold;
        }
        
        .section-stats {
            display: flex;
            gap: 15px;
        }
        
        .stat-item {
            text-align: right;
        }
        
        .section {
            margin: 30px 0;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #ccc;
            color: #000;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            font-size: 12px;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .signatories {
            margin-top: 50px;
            text-align: center;
        }
        
        .signatory-row {
            display: flex;
            justify-content: space-around;
            margin: 30px 0;
        }
        
        .signatory {
            text-align: center;
            width: 200px;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin: 40px 0 5px 0;
            padding-top: 5px;
        }
        
        .signatory-title {
            font-size: 12px;
            color: #333;
            margin-top: 5px;
        }
        
        .signatory-name {
            font-size: 12px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <?php if (file_exists($settings['logo_path'])): ?>
            <img src="<?= $settings['logo_path'] ?>" alt="School Logo" class="logo">
        <?php else: ?>
            <div class="logo" style="background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #000; font-weight: bold; font-size: 24px;">LOGO</div>
        <?php endif; ?>
        
        <div class="school-name"><?= htmlspecialchars($settings['school_name']) ?></div>
        <div class="report-title">VOTER PARTICIPATION REPORT</div>
        <div class="report-subtitle"><?= htmlspecialchars($system_title) ?></div>
    </div>
    
    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-value"><?= $total_count ?></div>
                <div class="summary-label">Total Registered Voters</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?= $voted_count ?></div>
                <div class="summary-label">Students Who Voted</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?= $not_voted_count ?></div>
                <div class="summary-label">Students Not Yet Voted</div>
            </div>
            <div class="summary-item">
                <div class="summary-value"><?= $total_count > 0 ? round(($voted_count / $total_count) * 100, 2) : 0 ?>%</div>
                <div class="summary-label">Overall Voter Turnout</div>
            </div>
        </div>
    </div>
    
    <!-- Grade Level Breakdown -->
    <div class="section">
        <div class="section-title">GRADE LEVEL AND SECTION BREAKDOWN</div>
        <div class="grade-breakdown">
            <?php foreach ($grade_breakdown as $grade_level => $grade_data): ?>
                <div class="grade-section">
                    <div class="grade-title">Grade <?= $grade_level ?></div>
                    <div class="section-details">
                        <div class="section-row">
                            <div class="section-name"><strong>Total:</strong></div>
                            <div class="section-stats">
                                <div class="stat-item"><strong><?= $grade_data['total'] ?></strong> students</div>
                                <div class="stat-item"><strong><?= $grade_data['voted'] ?></strong> voted</div>
                                <div class="stat-item"><strong><?= $grade_data['not_voted'] ?></strong> not voted</div>
                                <div class="stat-item"><strong><?= $grade_data['turnout'] ?>%</strong> turnout</div>
                            </div>
                        </div>
                        
                        <?php foreach ($grade_data['sections'] as $section => $section_data): ?>
                            <div class="section-row">
                                <div class="section-name">Section <?= htmlspecialchars($section) ?>:</div>
                                <div class="section-stats">
                                    <div class="stat-item"><?= $section_data['total'] ?> students</div>
                                    <div class="stat-item"><?= $section_data['voted'] ?> voted</div>
                                    <div class="stat-item"><?= $section_data['not_voted'] ?> not voted</div>
                                    <div class="stat-item"><?= $section_data['turnout'] ?>% turnout</div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- All Registered Students -->
    <div class="section">
        <div class="section-title">ALL REGISTERED STUDENTS</div>
        <table>
            <thead>
                <tr>
                    <th>LRN</th>
                    <th>Name</th>
                    <th>Grade & Section</th>
                    <th>Sex</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($student = $all_students_result->fetchArray()): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['lrn']) ?></td>
                        <td><?= htmlspecialchars($student['last_name'] . ', ' . $student['given_name'] . ' ' . $student['middle_name']) ?></td>
                        <td><?= htmlspecialchars($student['grade_level'] . '-' . $student['section']) ?></td>
                        <td><?= htmlspecialchars($student['sex']) ?></td>
                        <td><?= $student['has_voted'] ? 'VOTED' : 'NOT VOTED' ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Students Who Have Voted -->
    <div class="section">
        <div class="section-title">STUDENTS WHO HAVE VOTED</div>
        <?php if ($voted_count > 0): ?>
            <table>
                <thead>
                    <tr>
                    <th>LRN</th>
                    <th>Name</th>
                    <th>Grade & Section</th>
                    <th>Sex</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $voted_students_result->fetchArray()): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['lrn']) ?></td>
                            <td><?= htmlspecialchars($student['last_name'] . ', ' . $student['given_name'] . ' ' . $student['middle_name']) ?></td>
                            <td><?= htmlspecialchars($student['grade_level'] . '-' . $student['section']) ?></td>
                            <td><?= htmlspecialchars($student['sex']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666;">No students have voted yet.</p>
        <?php endif; ?>
    </div>
    
    <!-- Students Who Haven't Voted -->
    <div class="section">
        <div class="section-title">STUDENTS WHO HAVEN'T VOTED YET</div>
        <?php if ($not_voted_count > 0): ?>
            <table>
                <thead>
                    <tr>
                    <th>LRN</th>
                    <th>Name</th>
                    <th>Grade & Section</th>
                    <th>Sex</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($student = $not_voted_students_result->fetchArray()): ?>
                        <tr>
                            <td><?= htmlspecialchars($student['lrn']) ?></td>
                            <td><?= htmlspecialchars($student['last_name'] . ', ' . $student['given_name'] . ' ' . $student['middle_name']) ?></td>
                            <td><?= htmlspecialchars($student['grade_level'] . '-' . $student['section']) ?></td>
                            <td><?= htmlspecialchars($student['sex']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="text-align: center; color: #666;">All students have voted.</p>
        <?php endif; ?>
    </div>
    
    <!-- Signatories -->
    <div class="signatories">
        <div class="signatory-row">
            <div class="signatory">
		<div class="signatory-title">Prepared by:</div>	
                <div class="signature-line"></div>
                <div class="signatory-name">Commissioner on Screening and Validation</div>
            </div>
            <div class="signatory">
 		<div class="signatory-title">Attested by:</div>                
		<div class="signature-line"></div>
                <div class="signatory-name">Commissioner on Electoral Board</div>
            </div>
            <div class="signatory">
		<div class="signatory-title">Approved by:</div>                
		<div class="signature-line"></div>
                <div class="signatory-name">LG COMEA Chief Commissioner</div>
            </div>
        </div>
    </div>
    
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">Print Report</button>
        <a href="admin_panel.php" style="display: inline-block; margin-left: 10px; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px;">Back to Admin Panel</a>
    </div>
</body>
</html>