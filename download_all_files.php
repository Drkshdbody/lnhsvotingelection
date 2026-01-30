<?php
$files = [
    'index.php' => '<?php
session_start();
if (isset($_SESSION[\'lrn\'])) {
    header("Location: vote.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 50px; background-color: #f5f5f5; }
        .login-box { background-color: white; padding: 30px; border-radius: 8px; width: 300px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { width: 100%; padding: 10px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background-color: #218838; }
        .header { margin-bottom: 20px; }
        .logo { width: 80px; height: 80px; background-color: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin: 0 auto 10px; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="header">
            <div class="logo">SLGES</div>
            <h3>Student Login</h3>
        </div>
        <form method="POST" action="authenticate.php">
            <input type="text" name="token" placeholder="Enter Login Token" required>
            <button type="submit">Login to Vote</button>
        </form>
    </div>
</body>
</html>',

    'authenticate.php' => '<?php
session_start();

$token = $_POST[\'token\'] ?? \'\';

if (!$token) {
    header("Location: index.php");
    exit;
}

$db = new SQLite3(\'election.db\');

$stmt = $db->prepare("SELECT lrn, grade_level, has_voted FROM students WHERE login_token = ? AND has_voted = 0");
$stmt->bindValue(1, $token);
$result = $stmt->execute();
$row = $result->fetchArray();

if ($row) {
    $_SESSION[\'lrn\'] = $row[\'lrn\'];
    $_SESSION[\'grade_level\'] = $row[\'grade_level\'];
    header("Location: vote.php");
} else {
    echo "<h2>Invalid or already used token.</h2>";
    echo "<p><a href=\'index.php\'>Try Again</a></p>";
}
?>',

    'vote.php' => '<?php
session_start();
if (!isset($_SESSION[\'lrn\'])) {
    header("Location: index.php");
    exit;
}

$grade = $_SESSION[\'grade_level\'];

// Load school settings
$settings_file = \'school_settings.json\';
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    $default_settings = [
        \'school_name\' => \'Sample High School\',
        \'school_id\' => \'SHS-2026\',
        \'principal\' => \'Dr. Juan Santos\',
        \'logo_path\' => \'logo.png\',
        \'school_classification\' => \'Small\'
    ];
    $settings = array_merge($default_settings, $settings);
    $school_class = $settings[\'school_classification\'];
} else {
    $school_class = \'Small\';
}

$db = new SQLite3(\'election.db\');
?>
<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System - Voting</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #007bff; }
        h3 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        label { display: block; margin: 8px 0; }
        input[type="radio"] { margin-right: 10px; }
        .candidate { padding: 5px 10px; margin: 2px 0; background-color: #f8f9fa; border-radius: 4px; }
        button { background-color: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background-color: #218838; }
        hr { margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Your Vote Matters!</h2>
        <p><strong>School:</strong> <?= htmlspecialchars($settings[\'school_name\']) ?></p>
        <p><strong>Classification:</strong> <?= htmlspecialchars($school_class) ?></p>
        
        <form method="POST" action="submit_votes.php">
        <?php
        // All positions in order
        $all_positions = [
            \'President\',
            \'Vice President\',
            \'Secretary\',
            \'Treasurer\',
            \'Auditor\',
            \'Public Information Officer\',
            \'Protocol Officer\'
        ];

        // Determine number of representatives based on classification
        if (in_array($school_class, [\'Small\', \'Medium\'])) {
            // 1 representative per grade
            if ($grade == 7) {
                $all_positions[] = \'Grade 8 Representative\';
            } elseif ($grade == 8) {
                $all_positions[] = \'Grade 9 Representative\';
            } elseif ($grade == 9) {
                $all_positions[] = \'Grade 10 Representative\';
            }
        } else {
            // 2 representatives per grade (Large/Mega)
            if ($grade == 7) {
                $all_positions[] = \'Grade 8 Representative 1\';
                $all_positions[] = \'Grade 8 Representative 2\';
            } elseif ($grade == 8) {
                $all_positions[] = \'Grade 9 Representative 1\';
                $all_positions[] = \'Grade 9 Representative 2\';
            } elseif ($grade == 9) {
                $all_positions[] = \'Grade 10 Representative 1\';
                $all_positions[] = \'Grade 10 Representative 2\';
            }
        }

        foreach ($all_positions as $pos) {
            echo "<h3>$pos</h3>";
            $stmt = $db->prepare("SELECT id, name, party FROM candidates WHERE position = ? ORDER BY name");
            $stmt->bindValue(1, $pos);
            $result = $stmt->execute();

            while ($cand = $result->fetchArray()) {
                echo "<div class=\"candidate\">";
                echo "<label><input type=\"radio\" name=\"vote[$pos]\" value=\"{$cand[\'id\']}\" required> {$cand[\'name\']} ({$cand[\'party\']})</label>";
                echo "</div>";
            }
            echo "<hr>";
        }
        ?>
        
        <div style="text-align: center; margin-top: 20px;">
            <button type="submit">Submit Votes</button>
        </div>
        </form>
    </div>
</body>
</html>',

    'submit_votes.php' => '<?php
session_start();
if (!isset($_SESSION[\'lrn\'])) {
    header("Location: index.php");
    exit;
}

$lrn = $_SESSION[\'lrn\'];
$db = new SQLite3(\'election.db\');

foreach ($_POST[\'vote\'] as $position => $candidate_id) {
    $stmt = $db->prepare("INSERT INTO votes (student_lrn, candidate_id, position) VALUES (?, ?, ?)");
    $stmt->bindValue(1, $lrn);
    $stmt->bindValue(2, $candidate_id);
    $stmt->bindValue(3, $position);
    $stmt->execute();

    // Update vote count
    $stmt = $db->prepare("UPDATE candidates SET vote_count = vote_count + 1 WHERE id = ?");
    $stmt->bindValue(1, $candidate_id);
    $stmt->execute();
}

// Mark as voted
$stmt = $db->prepare("UPDATE students SET has_voted = 1 WHERE lrn = ?");
$stmt->bindValue(1, $lrn);
$stmt->execute();

session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 50px; background-color: #f5f5f5; }
        .success-box { background-color: white; padding: 30px; border-radius: 8px; width: 400px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .success-icon { font-size: 48px; color: #28a745; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="success-box">
        <div class="success-icon">✓</div>
        <h2>Thank You for Voting!</h2>
        <p>Your vote has been recorded successfully.</p>
        <p>You can now close this window.</p>
        <a href="index.php">Back to Login</a>
    </div>
</body>
</html>',

    'admin_login.php' => '<?php
session_start();
if (isset($_SESSION[\'admin_logged_in\'])) {
    header(\'Location: admin_panel.php\');
    exit;
}

if ($_POST) {
    $password = $_POST[\'password\'] ?? \'\';
    if ($password === \'admin123\') {
        $_SESSION[\'admin_logged_in\'] = true;
        header(\'Location: admin_panel.php\');
        exit;
    } else {
        $error = "Invalid credentials.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - School Learner Government Election System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 50px; background-color: #f5f5f5; }
        .login-box { background-color: white; padding: 30px; border-radius: 8px; width: 300px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; }
        button { width: 100%; padding: 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .error { color: red; text-align: center; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { width: 60px; height: 60px; background-color: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin: 0 auto 10px; }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="header">
            <div class="logo">SLGES</div>
            <h3>Admin Login</h3>
        </div>
        <?php if (isset($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="password" name="password" placeholder="Enter Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>',

    'upload_students.php' => '<?php
session_start();

if (!isset($_SESSION[\'admin_logged_in\'])) {
    $password = $_POST[\'password\'] ?? \'\';
    if ($password !== \'admin123\') {
        header("Location: admin_login.php");
        exit;
    }
    $_SESSION[\'admin_logged_in\'] = true;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System - Upload Students</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input, button { padding: 8px 12px; margin: 5px; }
        button { background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .instructions { background-color: #e7f3ff; padding: 10px; border-left: 4px solid #007bff; margin: 10px 0; }
        .result { margin-top: 20px; }
        .back-link { display: inline-block; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Upload Students (CSV)</h2>
        
        <div class="instructions">
            <p><strong>Format:</strong> LRN,Last Name,Middle Name,Given Name,Sex,Grade Level,Section</p>
            <p><strong>Example:</strong> 123456789012,Dela Cruz,Juan,Johnny,Male,9,9-A</p>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit">Upload Students</button>
        </form>

        <?php
        if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
            $file = $_FILES[\'csv_file\'][\'tmp_name\'] ?? \'\';

            if (!$file) {
                echo "<p style=\'color: red;\'>Error: No file selected.</p>";
                exit;
            }

            $handle = fopen($file, "r");
            if (!$handle) {
                echo "<p style=\'color: red;\'>Error opening file.</p>";
                exit;
            }

            $db = new SQLite3(\'election.db\');

            $success_count = 0;
            $error_count = 0;

            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($row) < 7) {
                    $error_count++;
                    continue;
                }

                $lrn = trim($row[0]);
                $last_name = trim($row[1]);
                $middle_name = trim($row[2]);
                $given_name = trim($row[3]);
                $sex = trim($row[4]);
                $grade = (int)$row[5];
                $section = trim($row[6]);

                // Generate unique token
                $token = bin2hex(random_bytes(8));

                // Check if LRN already exists
                $check_stmt = $db->prepare("SELECT lrn FROM students WHERE lrn = ?");
                $check_stmt->bindValue(1, $lrn);
                $check_result = $check_stmt->execute();
                $existing = $check_result->fetchArray();

                if ($existing) {
                    $error_count++;
                    continue;
                }

                // Insert student
                $stmt = $db->prepare("
                    INSERT INTO students (lrn, last_name, middle_name, given_name, sex, grade_level, section, login_token)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->bindValue(1, $lrn);
                $stmt->bindValue(2, $last_name);
                $stmt->bindValue(3, $middle_name);
                $stmt->bindValue(4, $given_name);
                $stmt->bindValue(5, $sex);
                $stmt->bindValue(6, $grade);
                $stmt->bindValue(7, $section);
                $stmt->bindValue(8, $token);

                if ($stmt->execute()) {
                    $success_count++;
                } else {
                    $error_count++;
                }
            }

            fclose($handle);

            echo "<div class=\'result\'>";
            echo "<h3>Upload Complete!</h3>";
            echo "<p><strong>Success:</strong> $success_count students added</p>";
            echo "<p><strong>Errors:</strong> $error_count failed</p>";
            echo "</div>";
        }
        ?>

        <div class="back-link">
            <a href="admin_panel.php">← Back to Admin Panel</a>
        </div>
    </div>
</body>
</html>',

    'view_students.php' => '<?php
session_start();

if (!isset($_SESSION[\'admin_logged_in\'])) {
    $password = $_POST[\'password\'] ?? \'\';
    if ($password !== \'admin123\') {
        header("Location: admin_login.php");
        exit;
    }
    $_SESSION[\'admin_logged_in\'] = true;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System - View Students</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f8f9fa; }
        .back-link { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Students List</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>LRN</th>
                    <th>Name</th>
                    <th>Sex</th>
                    <th>Grade</th>
                    <th>Section</th>
                    <th>Has Voted?</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $db = new SQLite3(\'election.db\');
                $result = $db->query("SELECT * FROM students ORDER BY grade_level, section, last_name");

                while ($row = $result->fetchArray()) {
                    echo "<tr>";
                    echo "<td>{$row[\'id\']}</td>";
                    echo "<td>{$row[\'lrn\']}</td>";
                    echo "<td>{$row[\'last_name\']}, {$row[\'given_name\']} {$row[\'middle_name\']}</td>";
                    echo "<td>{$row[\'sex\']}</td>";
                    echo "<td>{$row[\'grade_level\']}</td>";
                    echo "<td>{$row[\'section\']}</td>";
                    echo "<td>" . ($row[\'has_voted\'] ? '<span style="color: green;">Yes</span>' : '<span style="color: red;">No</span>') . "</td>";
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
</html>',

    'add_candidates.php' => '<?php
session_start();

if (!isset($_SESSION[\'admin_logged_in\'])) {
    $password = $_POST[\'password\'] ?? \'\';
    if ($password !== \'admin123\') {
        header("Location: admin_login.php");
        exit;
    }
    $_SESSION[\'admin_logged_in\'] = true;
}

// Load school settings to determine available positions
$settings_file = \'school_settings.json\';
if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    $default_settings = [
        \'school_classification\' => \'Small\'
    ];
    $settings = array_merge($default_settings, $settings);
    $size_class = $settings[\'school_classification\'];
} else {
    $size_class = \'Small\';
}

$message = \'\';

if ($_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    $position = $_POST[\'position\'];
    $name = $_POST[\'name\'];
    $party = $_POST[\'party\'];

    $db = new SQLite3(\'election.db\');
    $stmt = $db->prepare("INSERT INTO candidates (position, name, party) VALUES (?, ?, ?)");
    $stmt->bindValue(1, $position);
    $stmt->bindValue(2, $name);
    $stmt->bindValue(3, $party);

    if ($stmt->execute()) {
        $message = "Candidate added successfully!";
    } else {
        $message = "Error adding candidate.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System - Add Candidate</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .form-group { margin: 10px 0; }
        label { display: inline-block; width: 100px; }
        input, select { padding: 5px; width: 200px; }
        button { padding: 8px 15px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .info-box { background-color: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .back-link { margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Add Candidate</h2>
        
        <div class="info-box">
            <strong>Current School Classification:</strong> <?= htmlspecialchars($size_class) ?><br>
            <?php if (in_array($size_class, [\'Small\', \'Medium\'])): ?>
                <strong>Representatives:</strong> 1 per grade (Grades 8, 9, 10)
            <?php else: ?>
                <strong>Representatives:</strong> 2 per grade (Grades 8, 9, 10)
            <?php endif; ?>
        </div>
        
        <?php if ($message) echo "<p style=\'color: green;\'>$message</p>"; ?>

        <form method="POST">
            <div class="form-group">
                <label>Position:</label>
                <select name="position" required>
                    <option value="">Select Position</option>
                    <option value="President">President</option>
                    <option value="Vice President">Vice President</option>
                    <option value="Secretary">Secretary</option>
                    <option value="Treasurer">Treasurer</option>
                    <option value="Auditor">Auditor</option>
                    <option value="Public Information Officer">Public Information Officer</option>
                    <option value="Protocol Officer">Protocol Officer</option>
                    <?php if (in_array($size_class, [\'Small\', \'Medium\'])): ?>
                        <option value="Grade 10 Representative">Grade 10 Representative</option>
                        <option value="Grade 9 Representative">Grade 9 Representative</option>
                        <option value="Grade 8 Representative">Grade 8 Representative</option>
                    <?php else: ?>
                        <option value="Grade 10 Representative 1">Grade 10 Representative 1</option>
                        <option value="Grade 10 Representative 2">Grade 10 Representative 2</option>
                        <option value="Grade 9 Representative 1">Grade 9 Representative 1</option>
                        <option value="Grade 9 Representative 2">Grade 9 Representative 2</option>
                        <option value="Grade 8 Representative 1">Grade 8 Representative 1</option>
                        <option value="Grade 8 Representative 2">Grade 8 Representative 2</option>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Name:</label>
                <input type="text" name="name" required>
            </div>

            <div class="form-group">
                <label>Party:</label>
                <input type="text" name="party">
            </div>

            <button type="submit">Add Candidate</button>
        </form>

        <div class="back-link">
            <a href="admin_panel.php">← Back to Admin Panel</a>
        </div>
    </div>
</body>
</html>',

    'download_csv.php' => '<?php
session_start();

if (!isset($_SESSION[\'admin_logged_in\'])) {
    $password = $_POST[\'password\'] ?? \'\';
    if ($password !== \'admin123\') {
        header("Location: admin_login.php");
        exit;
    }
    $_SESSION[\'admin_logged_in\'] = true;
}

$db = new SQLite3(\'election.db\');

$sql = "SELECT position, name, party, vote_count FROM candidates ORDER BY 
    CASE position
        WHEN \'President\' THEN 1
        WHEN \'Vice President\' THEN 2
        WHEN \'Secretary\' THEN 3
        WHEN \'Treasurer\' THEN 4
        WHEN \'Auditor\' THEN 5
        WHEN \'Public Information Officer\' THEN 6
        WHEN \'Protocol Officer\' THEN 7
        WHEN \'Grade 10 Representative\' THEN 8
        WHEN \'Grade 10 Representative 1\' THEN 8
        WHEN \'Grade 10 Representative 2\' THEN 9
        WHEN \'Grade 9 Representative\' THEN 10
        WHEN \'Grade 9 Representative 1\' THEN 10
        WHEN \'Grade 9 Representative 2\' THEN 11
        WHEN \'Grade 8 Representative\' THEN 12
        WHEN \'Grade 8 Representative 1\' THEN 12
        WHEN \'Grade 8 Representative 2\' THEN 13
        ELSE 99
    END ASC, vote_count DESC";

$result = $db->query($sql);

header(\'Content-Type: text/csv\');
header(\'Content-Disposition: attachment; filename="election_results_\' . date(\'Y-m-d_H-i-s\') . \'.csv"\');

$output = fopen(\'php://output\', \'w\');
fputcsv($output, [\'Position\', \'Name\', \'Party\', \'Votes\']);

while ($row = $result->fetchArray()) {
    fputcsv($output, [$row[\'position\'], $row[\'name\'], $row[\'party\'] ?? \'N/A\', $row[\'vote_count\']]);
}

fclose($output);
?>',

    'download_results.php' => '<?php
session_start();

if (!isset($_SESSION[\'admin_logged_in\'])) {
    $password = $_POST[\'password\'] ?? \'\';
    if ($password !== \'admin123\') {
        header("Location: admin_login.php");
        exit;
    }
    $_SESSION[\'admin_logged_in\'] = true;
}

// Load school settings
$settings_file = \'school_settings.json\';
$default_settings = [
    \'school_name\' => \'Sample High School\',
    \'school_id\' => \'SHS-2026\',
    \'principal\' => \'Dr. Juan Santos\',
    \'logo_path\' => \'logo.png\',
    \'school_classification\' => \'Small\'
];

if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    $settings = array_merge($default_settings, $settings);
} else {
    $settings = $default_settings;
}

$db = new SQLite3(\'election.db\');

$sql = "SELECT position, name, party, vote_count FROM candidates ORDER BY 
    CASE position
        WHEN \'President\' THEN 1
        WHEN \'Vice President\' THEN 2
        WHEN \'Secretary\' THEN 3
        WHEN \'Treasurer\' THEN 4
        WHEN \'Auditor\' THEN 5
        WHEN \'Public Information Officer\' THEN 6
        WHEN \'Protocol Officer\' THEN 7
        WHEN \'Grade 10 Representative\' THEN 8
        WHEN \'Grade 10 Representative 1\' THEN 8
        WHEN \'Grade 10 Representative 2\' THEN 9
        WHEN \'Grade 9 Representative\' THEN 10
        WHEN \'Grade 9 Representative 1\' THEN 10
        WHEN \'Grade 9 Representative 2\' THEN 11
        WHEN \'Grade 8 Representative\' THEN 12
        WHEN \'Grade 8 Representative 1\' THEN 12
        WHEN \'Grade 8 Representative 2\' THEN 13
        ELSE 99
    END ASC, vote_count DESC";

$result = $db->query($sql);

$html = "
<!DOCTYPE html>
<html>
<head>
    <title>Election Results - School Learner Government Election System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1, h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .school-info { text-align: center; margin-bottom: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .logo { width: 80px; height: 80px; background-color: #007bff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; margin: 0 auto 10px; }
    </style>
</head>
<body>
    <div class=\'header\'>
        <div class=\'logo\'>SLGES</div>
        <h1>" . htmlspecialchars($settings[\'school_name\']) . "</h1>
        <p>School ID: " . htmlspecialchars($settings[\'school_id\']) . " | Principal: " . htmlspecialchars($settings[\'principal\']) . " | Classification: " . htmlspecialchars($settings[\'school_classification\']) . "</p>
        <h2>Student Election Results</h2>
        <p>Generated on: " . date(\'Y-m-d H:i:s\') . "</p>
    </div>
    <table>
        <thead>
            <tr>
                <th>Position</th>
                <th>Name</th>
                <th>Party</th>
                <th>Votes</th>
            </tr>
        </thead>
        <tbody>
";

while ($row = $result->fetchArray()) {
    $html .= "<tr>
        <td>" . htmlspecialchars($row[\'position\']) . "</td>
        <td>" . htmlspecialchars($row[\'name\']) . "</td>
        <td>" . htmlspecialchars($row[\'party\'] ?? \'N/A\') . "</td>
        <td>" . $row[\'vote_count\'] . "</td>
    </tr>";
}

$html .= "
        </tbody>
    </table>
</body>
</html>
";

header(\'Content-Type: application/html\');
header(\'Content-Disposition: attachment; filename="election_results_\' . date(\'Y-m-d_H-i-s\') . \'.html"\');
echo $html;
?>'
];

// Create all files
foreach ($files as $filename => $content) {
    file_put_contents($filename, $content);
}

echo "<h2>Complete School Learner Government Election System Files Created!</h2>";
echo "<p>Files created in current directory:</p><ul>";
foreach (array_keys($files) as $filename) {
    echo "<li>$filename</li>";
}
echo "</ul>";
echo "<p><strong>Make sure you also have:</strong></p>";
echo "<ul>";
echo "<li>election.db (database file)</li>";
echo "<li>school_settings.json (configuration file)</li>";
echo "</ul>";
echo "<p><a href='admin_panel.php'>Go to Admin Panel</a> | <a href='index.php'>Go to Student Login</a></p>";
?>