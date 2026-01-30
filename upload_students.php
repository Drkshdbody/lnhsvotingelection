<?php
// Increase execution time and memory limits for large files
ini_set('max_execution_time', 300); // 5 minutes
ini_set('memory_limit', '256M');

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

// Check if security is verified for this action
$action_key = 'upload_students';
$verified = isset($_SESSION["security_verified_$action_key"]) && 
           (time() - $_SESSION["security_verified_time"] < 300); // 5 minutes timeout

if (!$verified) {
    header("Location: token_auth.php?action=$action_key&redirect=" . urlencode($_SERVER['PHP_SELF']));
    exit;
}

// Load token settings
$token_settings_file = 'token_settings.json';
$default_token_settings = [
    'token_length' => 8,
    'token_characters' => 'alphanumeric',
    'custom_characters' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
];

if (file_exists($token_settings_file)) {
    $token_settings = json_decode(file_get_contents($token_settings_file), true);
    $token_settings = array_merge($default_token_settings, $token_settings);
} else {
    $token_settings = $default_token_settings;
}

/**
 * Generate custom token based on settings
 */
function generateCustomToken($settings) {
    $length = $settings['token_length'];
    $characters = '';
    
    switch ($settings['token_characters']) {
        case 'numbers':
            $characters = '0123456789';
            break;
        case 'letters':
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            break;
        case 'alphanumeric':
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            break;
        case 'custom':
            $characters = $settings['custom_characters'];
            break;
        default:
            $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    }
    
    // Remove duplicate characters and ensure at least one character
    $characters = implode('', array_unique(str_split($characters)));
    if (empty($characters)) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    }
    
    $token = '';
    $max_index = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $random_index = random_int(0, $max_index);
        $token .= $characters[$random_index];
    }
    
    return $token;
}

$message = '';
$failed_students = []; // Track failed students

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_token_settings'])) {
        // Save token settings
        $new_settings = [
            'token_length' => (int)$_POST['token_length'],
            'token_characters' => $_POST['token_characters'],
            'custom_characters' => trim($_POST['custom_characters'])
        ];
        
        // Validate settings
        if ($new_settings['token_length'] < 4 || $new_settings['token_length'] > 20) {
            $message = "Error: Token length must be between 4 and 20 characters.";
        } else {
            file_put_contents($token_settings_file, json_encode($new_settings, JSON_PRETTY_PRINT));
            $token_settings = $new_settings;
            $message = "Token settings updated successfully!";
        }
    } elseif (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        // Handle CSV upload - OPTIMIZED VERSION WITH DETAILED ERROR REPORTING
        $file = $_FILES['csv_file']['tmp_name'];

        if (empty($file)) {
            $message = "Error: No file selected.";
        } else {
            // Set longer time limit for processing
            set_time_limit(300);
            
            $handle = fopen($file, "r");
            if (!$handle) {
                $message = "Error opening file.";
            } else {
                $db = new SQLite3('election.db');
                $db->exec("BEGIN TRANSACTION"); // Single transaction for better performance

                $success_count = 0;
                $error_count = 0;
                $invalid_sex_count = 0;
                $duplicate_lrns = [];
                $existing_duplicate_lrns = [];
                $valid_rows = [];
                $lrn_map = []; // Track LRNs in current file
                $existing_lrns = []; // Track LRNs already in database
                $failed_students = []; // Reset failed students

                // Get existing LRNs from database (single query)
                $existing_result = $db->query("SELECT lrn FROM students");
                while ($row = $existing_result->fetchArray()) {
                    $existing_lrns[$row['lrn']] = true;
                }

                // Skip header row
                fgetcsv($handle);

                // Read and validate all rows
                while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (count($row) < 7) {
                        $error_count++;
                        $failed_students[] = [
                            'lrn' => $row[0] ?? 'N/A',
                            'name' => ($row[1] ?? '') . ', ' . ($row[3] ?? ''),
                            'reason' => 'Incomplete data (less than 7 columns)'
                        ];
                        continue;
                    }

                    // Fix the LRN format - convert from scientific notation to full number
                    $lrn_raw = trim($row[0]);
                    
                    // If LRN is in scientific notation, convert it to full number
                    if (strpos($lrn_raw, 'E+') !== false || strpos($lrn_raw, 'e+') !== false) {
                        $lrn = number_format((float)$lrn_raw, 0, '', '');
                    } else {
                        $lrn = $lrn_raw;
                    }
                    
                    $last_name = trim($row[1]);
                    $middle_name = trim($row[2]);
                    $given_name = trim($row[3]);
                    $sex = trim($row[4]);
                    $grade = (int)$row[5];
                    $section = trim($row[6]);

                    // Validate sex - must be exactly "Male" or "Female"
                    if ($sex !== 'Male' && $sex !== 'Female') {
                        $invalid_sex_count++;
                        $error_count++;
                        $failed_students[] = [
                            'lrn' => $lrn,
                            'name' => $last_name . ', ' . $given_name,
                            'reason' => 'Invalid sex value: "' . $sex . '" (must be "Male" or "Female")'
                        ];
                        continue;
                    }

                    // Check for duplicates in current file
                    if (isset($lrn_map[$lrn])) {
                        if (!in_array($lrn, $duplicate_lrns)) {
                            $duplicate_lrns[] = $lrn;
                        }
                        $error_count++;
                        $failed_students[] = [
                            'lrn' => $lrn,
                            'name' => $last_name . ', ' . $given_name,
                            'reason' => 'Duplicate LRN in CSV file'
                        ];
                        continue;
                    }
                    $lrn_map[$lrn] = true;

                    // Check if LRN already exists in database
                    if (isset($existing_lrns[$lrn])) {
                        if (!in_array($lrn, $existing_duplicate_lrns)) {
                            $existing_duplicate_lrns[] = $lrn;
                        }
                        $error_count++;
                        $failed_students[] = [
                            'lrn' => $lrn,
                            'name' => $last_name . ', ' . $given_name,
                            'reason' => 'LRN already exists in database'
                        ];
                        continue;
                    }

                    // Add to valid rows
                    $valid_rows[] = $row;
                }

                fclose($handle);

                // Show duplicate LRNs if any
                if (!empty($duplicate_lrns) || !empty($existing_duplicate_lrns)) {
                    $db->exec("ROLLBACK");
                    $message = "Upload stopped - duplicate LRNs found!";
                } else {
                    // Insert all valid students
                    foreach ($valid_rows as $row) {
                        // Process LRN to ensure it's a full number
                        $lrn_raw = trim($row[0]);
                        if (strpos($lrn_raw, 'E+') !== false || strpos($lrn_raw, 'e+') !== false) {
                            $lrn = number_format((float)$lrn_raw, 0, '', '');
                        } else {
                            $lrn = $lrn_raw;
                        }
                        
                        $last_name = trim($row[1]);
                        $middle_name = trim($row[2]);
                        $given_name = trim($row[3]);
                        $sex = trim($row[4]);
                        $grade = (int)$row[5];
                        $section = trim($row[6]);

                        // Generate custom token based on settings
                        $token = generateCustomToken($token_settings);

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

                        try {
                            if ($stmt->execute()) {
                                $success_count++;
                            } else {
                                $error_count++;
                                $failed_students[] = [
                                    'lrn' => $lrn,
                                    'name' => $last_name . ', ' . $given_name,
                                    'reason' => 'Database insertion error'
                                ];
                            }
                        } catch (Exception $e) {
                            $error_count++;
                            $failed_students[] = [
                                'lrn' => $lrn,
                                'name' => $last_name . ', ' . $given_name,
                                'reason' => 'Database error: ' . $e->getMessage()
                            ];
                        }
                    }

                    $db->exec("COMMIT");
                    $message = "Upload Complete!\nSuccess: $success_count students added\nErrors: $error_count failed";
                    if ($invalid_sex_count > 0) {
                        $message .= "\nInvalid Sex Values: $invalid_sex_count records had invalid sex values (must be 'Male' or 'Female')";
                    }
                    
                    // Clear verification after successful operation
                    unset($_SESSION["security_verified_$action_key"]);
                    unset($_SESSION['security_verified_time']);
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System - Upload Students</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        input, button, select { padding: 8px 12px; margin: 5px; }
        button { background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .instructions { background-color: #e7f3ff; padding: 10px; border-left: 4px solid #007bff; margin: 10px 0; }
        .result { margin-top: 20px; }
        .back-link { display: inline-block; margin-top: 10px; }
        .download-csv { display: inline-block; background-color: #007bff; color: white; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin: 10px 0; }
        .error { color: red; }
        .duplicate-lrn { background-color: #f8d7da; padding: 10px; border-radius: 4px; margin: 5px 0; border: 1px solid #f5c6cb; }
        .duplicate-details { background-color: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; border: 1px solid #ffeaa7; }
        .student-info { margin: 5px 0; }
        .student-field { font-weight: bold; }
        .existing-student { background-color: #f1f3f4; padding: 5px; margin: 5px 0; border-radius: 4px; }
        .uploaded-student { background-color: #e3f2fd; padding: 5px; margin: 5px 0; border-radius: 4px; }
        .message { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .token-settings { background-color: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #dee2e6; }
        .token-settings h3 { margin-top: 0; color: #495057; }
        .form-group { margin: 10px 0; }
        label { display: inline-block; width: 200px; font-weight: bold; }
        .sample-token { background-color: #e9ecef; padding: 5px 10px; border-radius: 4px; font-family: monospace; margin-left: 10px; }
        .preview-section { background-color: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; border: 1px solid #bee5eb; }
        .failed-students { background-color: #f8d7da; padding: 15px; border-radius: 6px; margin: 15px 0; border: 1px solid #f5c6cb; }
        .failed-student-item { 
            background-color: #ffebee; 
            padding: 8px; 
            margin: 5px 0; 
            border-radius: 4px; 
            border-left: 3px solid #dc3545;
        }
        .failed-lrn { font-weight: bold; color: #dc3545; }
        .failed-reason { color: #666; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Upload Students (CSV)</h2>
        
        <!-- Token Settings Section -->
        <div class="token-settings">
            <h3>Student Token Settings</h3>
            <form method="POST">
                <input type="hidden" name="save_token_settings" value="1">
                
                <div class="form-group">
                    <label>Token Length:</label>
                    <select name="token_length" required>
                        <?php for ($i = 4; $i <= 20; $i++): ?>
                            <option value="<?= $i ?>" <?= $token_settings['token_length'] == $i ? 'selected' : '' ?>><?= $i ?> characters</option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Character Type:</label>
                    <select name="token_characters" id="token_characters" required onchange="toggleCustomField()">
                        <option value="numbers" <?= $token_settings['token_characters'] == 'numbers' ? 'selected' : '' ?>>Numbers Only (0-9)</option>
                        <option value="letters" <?= $token_settings['token_characters'] == 'letters' ? 'selected' : '' ?>>Letters Only (A-Z)</option>
                        <option value="alphanumeric" <?= $token_settings['token_characters'] == 'alphanumeric' ? 'selected' : '' ?>>Alphanumeric (A-Z, 0-9)</option>
                        <option value="custom" <?= $token_settings['token_characters'] == 'custom' ? 'selected' : '' ?>>Custom Characters</option>
                    </select>
                </div>
                
                <div class="form-group" id="custom_characters_field" style="<?= $token_settings['token_characters'] == 'custom' ? 'display:block;' : 'display:none;' ?>">
                    <label>Custom Characters:</label>
                    <input type="text" name="custom_characters" value="<?= htmlspecialchars($token_settings['custom_characters']) ?>" placeholder="Enter custom characters (e.g., ABC123XYZ)" maxlength="50">
                </div>
                
                <button type="submit" style="background-color: #17a2b8;">Save Token Settings</button>
            </form>
            
            <div class="preview-section">
                <strong>Sample Tokens:</strong>
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <span class="sample-token"><?= htmlspecialchars(generateCustomToken($token_settings)) ?></span>
                <?php endfor; ?>
            </div>
        </div>

        <div class="instructions">
            <p><strong>Format:</strong> LRN,Last Name,Middle Name,Given Name,Sex,Grade Level,Section</p>
            <p><strong>Example:</strong> 123456789012,Dela Cruz,Juan,Johnny,<span class="error">Male</span>,9,9-A</p>
            <p><strong>Requirements:</strong></p>
            <ul>
                <li>LRN must be unique</li>
                <li>Sex must be exactly "<span class="error">Male</span>" or "<span class="error">Female</span>" (case-sensitive)</li>
                <li>Grade Level must be a number (7, 8, 9, 10)</li>
                <li>Section can be any text</li>
            </ul>
        </div>

        <a href="sample_students.csv" class="download-csv">Download Sample CSV</a>

        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit">Upload Students</button>
        </form>

        <?php if ($message): ?>
            <?php if (strpos($message, 'Upload stopped') !== false): ?>
                <div class="message error-message">
                    <h3>Upload Stopped - Duplicate LRNs Found!</h3>
                    
                    <?php if (!empty($duplicate_lrns)): ?>
                        <div class="duplicate-lrn">
                            <p class="error"><strong>Duplicate LRNs in CSV file:</strong></p>
                            <?php foreach (array_unique($duplicate_lrns) as $duplicate_lrn): ?>
                                <p>• <?= htmlspecialchars($duplicate_lrn) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($existing_duplicate_lrns)): ?>
                        <div class="duplicate-lrn">
                            <p class="error"><strong>LRNs already exist in database:</strong></p>
                            <?php foreach (array_unique($existing_duplicate_lrns) as $existing_duplicate): ?>
                                <p>• <?= htmlspecialchars($existing_duplicate) ?></p>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <p class="error">No students were added to the database. Please fix the duplicate LRNs and try again.</p>
                </div>
            <?php else: ?>
                <div class="message success">
                    <h3>Upload Complete!</h3>
                    <pre><?= htmlspecialchars($message) ?></pre>
                    
                    <?php if (!empty($failed_students)): ?>
                        <div class="failed-students">
                            <h4>Failed Students Details:</h4>
                            <?php foreach ($failed_students as $failed): ?>
                                <div class="failed-student-item">
                                    <div><span class="failed-lrn">LRN: <?= htmlspecialchars($failed['lrn']) ?></span> - <?= htmlspecialchars($failed['name']) ?></div>
                                    <div class="failed-reason">Reason: <?= htmlspecialchars($failed['reason']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="back-link">
            <a href="admin_panel.php">← Back to Admin Panel</a>
        </div>
    </div>
    
    <script>
        function toggleCustomField() {
            var tokenType = document.getElementById('token_characters').value;
            var customField = document.getElementById('custom_characters_field');
            if (tokenType === 'custom') {
                customField.style.display = 'block';
            } else {
                customField.style.display = 'none';
            }
        }
        // Initialize on page load
        toggleCustomField();
    </script>
</body>
</html>