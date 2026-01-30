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

// Handle logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: admin_login.php");
    exit;
}

// Check if voting is active
$tokens_file = 'election_tokens.json';
$voting_active = false;
$voting_closed = false;
if (file_exists($tokens_file)) {
    $tokens = json_decode(file_get_contents($tokens_file), true);
    if (isset($tokens['enabled']) && $tokens['enabled'] === true) {
        $voting_active = true;
    } elseif (isset($tokens['enabled']) && $tokens['enabled'] === false) {
        $voting_closed = true;
    }
}

// Load school settings
$settings_file = 'school_settings.json';
$default_settings = [
    'school_name' => 'Sample High School',
    'school_id' => 'SHS-2026',
    'principal' => 'Dr. Juan Santos',
    'logo_path' => 'logo.png',
    'school_classification' => 'Small',
    'custom_system_title' => '' // NEW: Custom system title field
];

if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    $settings = array_merge($default_settings, $settings);
} else {
    $settings = $default_settings;
}

// Determine system title - prioritize custom title, then auto-generate
if (!empty($settings['custom_system_title'])) {
    $system_title = $settings['custom_system_title'];
} else {
    // Auto-generate based on school level (existing logic)
    $school_level = $settings['school_level'] ?? 'Junior High School';
    if ($school_level === 'Elementary') {
        $system_title = "Supreme Elementary Learner Government Election System";
    } else {
        $system_title = "Supreme Secondary Learner Government Election System";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($system_title) ?> - Admin Panel</title>
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
        
        .nav {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }
        
        .nav a {
            color: #202124;
            text-decoration: none;
            padding: 8px 16px;
            border-radius: 4px;
            background: white;
            border: 1px solid #dfe1e5;
            display: inline-block;
            transition: all 0.2s ease;
        }
        
        .nav a:hover {
            background: #e8eaed;
            border-color: #4285f4;
        }
        
        /* Gold button for Winners */
        .nav a.winners-btn {
            background: #FFD700; /* Gold color */
            color: #000;
            font-weight: bold;
            border-color: #FFA500;
        }
        
        .nav a.winners-btn:hover {
            background: #FFC400;
            border-color: #FF8C00;
        }
        
        .content {
            display: flex;
            gap: 20px;
        }
        
        .left-panel {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dfe1e5;
        }
        
        .right-panel {
            flex: 2;
            background: white;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #dfe1e5;
        }
        
        .panel-title {
            color: #202124;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #dfe1e5;
        }
        
        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: #202124;
            display: block;
        }
        
        .stat-label {
            font-size: 0.8em;
            color: #5f6368;
        }
        
        .btn-group {
            margin: 15px 0;
        }
        
        .btn {
            display: block;
            width: 100%;
            padding: 10px 15px;
            margin: 8px 0;
            border: 1px solid #dfe1e5;
            border-radius: 4px;
            text-decoration: none;
            text-align: center;
            font-size: 14px;
            color: #202124;
            background: white;
            transition: all 0.2s ease;
        }
        
        .btn:hover {
            background: #f8f9fa;
            border-color: #4285f4;
        }
        
        .btn-success {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .btn-success:hover {
            background: #c3e6cb;
        }
        
        .btn-danger {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
        
        .btn-danger:hover {
            background: #f5c6cb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        
        th, td {
            border: 1px solid #dfe1e5;
            padding: 12px;
            text-align: left;
        }
        
        th {
            background-color: #f8f9fa;
            font-weight: 500;
        }
        
        .candidate-card {
            display: flex;
            align-items: center;
            padding: 10px;
            border: 1px solid #dfe1e5;
            border-radius: 4px;
            margin: 5px 0;
        }
        
        .candidate-photo-small {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
            border: 1px solid #dfe1e5;
        }
        
        .candidate-info-small {
            flex: 1;
        }
        
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: 20px;
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
        
        .logout-section {
            text-align: center;
            margin: 20px 0;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        
        .logout-btn:hover {
            background: #c82333;
        }
        
        .status-indicator {
            background: #d4edda;
            color: #155724;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            margin-bottom: 10px;
            border: 1px solid #c3e6cb;
        }
        
        .status-indicator.inactive {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .status-indicator.closed {
            background: #e2e3e5;
            color: #383d41;
            border-color: #d6d8db;
        }
        
        @media (max-width: 768px) {
            .content {
                flex-direction: column;
            }
            
            .nav {
                flex-direction: column;
                align-items: center;
            }
            
            .nav a {
                width: 100%;
                text-align: center;
                margin: 5px 0;
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

        <div class="nav">
            <a href="school_settings.php">Edit School Info</a>
            <a href="upload_students.php">Upload Students</a>
            <a href="view_students.php">View Students</a>
            <a href="print_voter_ids.php">Print Voter IDs</a>
            <a href="add_candidates.php">Add Candidates</a>
            <a href="view_candidates.php">View Candidates</a>
            <?php if ($voting_closed): ?>
                <a href="winners.php" class="winners-btn">View Winners</a> <!-- GOLD WINNERS BUTTON (ONLY WHEN VOTING CLOSED) -->
                <a href="print_winners_report.php">Print Winners Report</a>
                <a href="print_candidates_report.php">Print Candidates Report</a>
                <a href="print_voter_report.php">Print Voter Report</a>
            <?php endif; ?>
            <a href="generate_tokens.php" target="_blank">Generate Election Tokens</a>
        </div>

        <div class="content">
            <div class="left-panel">
                <h3 class="panel-title">Quick Stats</h3>
                
                <div class="stats-grid">
                    <?php
                    $db = new SQLite3('election.db');
                    
                    $total_students = $db->querySingle("SELECT COUNT(*) FROM students");
                    $voted_students = $db->querySingle("SELECT COUNT(*) FROM students WHERE has_voted = 1");
                    $turnout = $total_students > 0 ? round(($voted_students / $total_students) * 100, 2) : 0;
                    $total_candidates = $db->querySingle("SELECT COUNT(*) FROM candidates");
                    $total_votes = $db->querySingle("SELECT COUNT(*) FROM votes");
                    ?>
                    
                    <div class="stat-card">
                        <span class="stat-value"><?= $total_students ?></span>
                        <span class="stat-label">Students</span>
                    </div>
                    
                    <div class="stat-card">
                        <span class="stat-value"><?= $voted_students ?></span>
                        <span class="stat-label">Voted</span>
                    </div>
                    
                    <div class="stat-card">
                        <span class="stat-value"><?= $turnout ?>%</span>
                        <span class="stat-label">Turnout</span>
                    </div>
                    
                    <div class="stat-card">
                        <span class="stat-value"><?= $total_candidates ?></span>
                        <span class="stat-label">Candidates</span>
                    </div>
                    
                    <div class="stat-card">
                        <span class="stat-value"><?= $total_votes ?></span>
                        <span class="stat-label">Votes</span>
                    </div>
                </div>

                <?php if ($voting_closed): ?>
                    <div class="status-indicator closed">
                        Voting is CLOSED
                    </div>
                <?php else: ?>
                    <div class="status-indicator <?= $voting_active ? '' : 'inactive' ?>">
                        <?= $voting_active ? 'Voting is ACTIVE' : 'Voting is INACTIVE' ?>
                    </div>
                <?php endif; ?>

                <div class="btn-group">
                    <h3 class="panel-title">Voting Control</h3>
                    <p style="color: #5f6368; margin: 10px 0; font-size: 0.9em;">To start or close voting, use the Voting Control tab in the admin login page.</p>
                    <a href="admin_login.php" class="btn">Go to Admin Login</a>
                </div>

                <?php if ($voting_closed): ?>
                    <div class="btn-group">
                        <h3 class="panel-title">Election Reports</h3>
                        <a href="print_winners_report.php" class="btn">Print Winners Report</a>
                        <a href="print_candidates_report.php" class="btn">Print Candidates Report</a>
                        <a href="print_voter_report.php" class="btn">Print Voter Report</a>
                    </div>
                <?php endif; ?>

                <div class="btn-group">
                    <h3 class="panel-title">Student Management</h3>
                    <a href="view_students.php" class="btn">View All Students</a>
                    <a href="delete_all_students.php" class="btn btn-danger">Delete All Students</a>
                    <a href="reset_student_vote.php" class="btn btn-warning">Reset Student Vote</a>
                </div>

                <div class="btn-group">
                    <h3 class="panel-title">Candidate Management</h3>
                    <a href="add_candidates.php" class="btn">Add Candidates</a>
                    <a href="view_candidates.php" class="btn">View All Candidates</a>
                    <a href="delete_all_candidates.php" class="btn btn-danger">Delete All Candidates</a>
                    <a href="edit_candidate.php" class="btn btn-success">Edit Candidate</a>
                </div>

                <div class="btn-group">
                    <h3 class="panel-title">System Management</h3>
                    <a href="reset_system.php" class="btn btn-danger">Reset Entire System</a>
                </div>
                
                <div class="logout-section">
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="logout" value="1">
                        <button type="submit" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">Logout</button>
                    </form>
                </div>
            </div>

            <div class="right-panel">
                <h3 class="panel-title">Live Election Results</h3>
                <div id="results"></div>
            </div>
        </div>
        
        <div class="footer">
            <p>Powered by <?= htmlspecialchars($system_title) ?></p>
            <p>Developed by: <a href="https://www.facebook.com/sirtopet" target="_blank">Cristopher Duro</a></p>
        </div>
    </div>

    <script>
        function loadResults() {
            fetch('refresh_results.php')
                .then(response => response.json())
                .then(data => {
                    // Determine positions based on school classification
                    const schoolClass = "<?= $settings['school_classification'] ?>";
                    let order = [
                        'President',
                        'Vice President',
                        'Secretary',
                        'Treasurer',
                        'Auditor',
                        'Public Information Officer',
                        'Protocol Officer'
                    ];

                    if (['Small', 'Medium'].includes(schoolClass)) {
                        order.push(
                            'Grade 10 Representative',
                            'Grade 9 Representative',
                            'Grade 8 Representative'
                        );
                    } else {
                        order.push(
                            'Grade 10 Representative 1',
                            'Grade 10 Representative 2',
                            'Grade 9 Representative 1',
                            'Grade 9 Representative 2',
                            'Grade 8 Representative 1',
                            'Grade 8 Representative 2'
                        );
                    }

                    let grouped = {};
                    data.forEach(row => {
                        if (!grouped[row.position]) grouped[row.position] = [];
                        grouped[row.position].push(row);
                    });

                    let html = '<table><thead><tr><th>Position</th><th>Candidate</th><th>Party</th><th>Votes</th></tr></thead><tbody>';

                    order.forEach(pos => {
                        if (grouped[pos]) {
                            grouped[pos].sort((a, b) => b.vote_count - a.vote_count);
                            grouped[pos].forEach((row, index) => {
                                // Create candidate card with photo if available
                                let photoUrl = row.photo_path ? row.photo_path : 'image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48Y2lyY2xlIGN4PSIyMCIgY3k9IjIwIiByPSIyMCIgZmlsbD0iI2RkZCIvPjx0ZXh0IHg9IjIwIiB5PSIyNSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE2IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjNjY2Ij4ke3Jvdy5uYW1lLmNoYXJBdCgwKS50b0xvd2VyQ2FzZSgpLnVwcGVyQ2FzZX08L3RleHQ+PC9zdmc+';
                                let candidateDisplay = `
                                    <div class="candidate-card">
                                        <img src="${photoUrl}" 
                                             alt="${row.name}" class="candidate-photo-small">
                                        <div class="candidate-info-small">
                                            <div>${row.name}</div>
                                        </div>
                                    </div>
                                `;
                                
                                html += `<tr>
                                    ${index === 0 ? `<td rowspan="${grouped[pos].length}"><strong>${row.position}</strong></td>` : ''}
                                    <td>${candidateDisplay}</td>
                                    <td>${row.party || 'N/A'}</td>
                                    <td><strong>${row.vote_count}</strong></td>
                                </tr>`;
                            });
                        }
                    });

                    // Add any remaining positions that weren't in our predefined order
                    Object.keys(grouped).forEach(pos => {
                        if (!order.includes(pos)) {
                            grouped[pos].sort((a, b) => b.vote_count - a.vote_count);
                            grouped[pos].forEach((row, index) => {
                                let photoUrl = row.photo_path ? row.photo_path : 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48Y2lyY2xlIGN4PSIyMCIgY3k9IjIwIiByPSIyMCIgZmlsbD0iI2RkZCIvPjx0ZXh0IHg9IjIwIiB5PSIyNSIgZm9udC1mYW1pbHk9IkFyaWFsIiBmb250LXNpemU9IjE2IiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmaWxsPSIjNjY2Ij4ke3Jvdy5uYW1lLmNoYXJBdCgwKS50b0xvd2VyQ2FzZSgpLnVwcGVyQ2FzZX08L3RleHQ+PC9zdmc+';
                                let candidateDisplay = `
                                    <div class="candidate-card">
                                        <img src="${photoUrl}" 
                                             alt="${row.name}" class="candidate-photo-small">
                                        <div class="candidate-info-small">
                                            <div>${row.name}</div>
                                        </div>
                                    </div>
                                `;
                                
                                html += `<tr>
                                    ${index === 0 ? `<td rowspan="${grouped[pos].length}"><strong>${row.position}</strong></td>` : ''}
                                    <td>${candidateDisplay}</td>
                                    <td>${row.party || 'N/A'}</td>
                                    <td><strong>${row.vote_count}</strong></td>
                                </tr>`;
                            });
                        }
                    });

                    html += '</tbody></table>';
                    document.getElementById('results').innerHTML = html;
                })
                .catch(err => console.error(err));
        }

        setInterval(loadResults, 2000);
        loadResults();
    </script>
</body>
</html>