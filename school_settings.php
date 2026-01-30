<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Check if serial key is authenticated
if (!isset($_SESSION['serial_key_authenticated']) || $_SESSION['serial_key_authenticated'] !== true) {
    header("Location: serial_key_auth.php");
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
    'school_level' => 'Junior High School',
    'custom_system_title' => '' // Add custom title field
];

if (file_exists($settings_file)) {
    $settings = json_decode(file_get_contents($settings_file), true);
    $settings = array_merge($default_settings, $settings);
    
    // Determine system title - prioritize custom title if set
    if (!empty($settings['custom_system_title'])) {
        $system_title = $settings['custom_system_title'];
    } else {
        // Auto-generate based on school level
        $school_level = $settings['school_level'] ?? 'Junior High School';
        if ($school_level === 'Elementary') {
            $system_title = "Supreme Elementary Learner Government Election System";
        } else {
            $system_title = "Supreme Secondary Learner Government Election System";
        }
    }
} else {
    $settings = $default_settings;
    $system_title = "Supreme Secondary Learner Government Election System";
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    // Always update these fields
    $settings['school_name'] = trim($_POST['school_name']);
    $settings['school_id'] = trim($_POST['school_id']);
    $settings['principal'] = trim($_POST['principal']);
    $settings['school_classification'] = trim($_POST['school_classification']);
    $settings['school_level'] = trim($_POST['school_level']);
    $settings['custom_system_title'] = trim($_POST['custom_system_title']);
    
    // Handle logo upload ONLY if a file is provided
    $logo_updated = false;
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $target_dir = "";
        $imageFileType = strtolower(pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION));
        
        // Validate image file
        $check = getimagesize($_FILES["logo"]["tmp_name"]);
        if ($check !== false) {
            $new_filename = 'logo.' . $imageFileType;
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $new_filename)) {
                $settings['logo_path'] = $new_filename;
                $logo_updated = true;
            }
        }
    }
    
    // Save settings to file
    if (file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT))) {
        if ($logo_updated) {
            $message = "Settings and logo updated successfully!";
        } else {
            $message = "Settings updated successfully!";
        }
    } else {
        $message = "Error saving settings.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Settings - <?= htmlspecialchars($system_title) ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
        .container { 
            max-width: 800px; 
            margin: 0 auto; 
            background: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .form-group { 
            margin: 15px 0; 
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
        }
        input, select { 
            padding: 8px; 
            width: 100%; 
            border: 1px solid #dfe1e5; 
            border-radius: 4px; 
        }
        button { 
            padding: 10px 20px; 
            background: #007bff; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin: 5px 0;
        }
        .preview { 
            margin-top: 30px; 
        }
        .current-logo { 
            max-width: 150px; 
            max-height: 150px; 
            border: 1px solid #ddd; 
            padding: 5px; 
        }
        .info-box {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
        }
        .instructions {
            background-color: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #007bff;
        }
        .instructions h3 {
            margin-top: 0;
        }
        .instructions ul {
            margin: 5px 0;
            padding-left: 20px;
        }
        .custom-title-section {
            background-color: #e7f3ff;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #007bff;
        }
        .custom-title-section h3 {
            margin-top: 0;
            color: #0c5460;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #c3e6cb;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            border: 1px solid #f5c6cb;
        }
        .password-section {
            background-color: #e2e3e5;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #6c757d;
        }
        .password-section h3 {
            margin-top: 0;
            color: #383d41;
        }
        .password-btn {
            background-color: #6c757d;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        .password-btn:hover {
            background-color: #5a6268;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #4285f4;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><?= htmlspecialchars($system_title) ?> - School Settings</h2>
        <?php if ($message): ?>
            <?php if (strpos($message, 'successfully') !== false): ?>
                <div class="success-message"><?= htmlspecialchars($message) ?></div>
            <?php else: ?>
                <div class="error-message"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="instructions">
            <h3>Instructions:</h3>
            <ul>
                <li><strong>School Level</strong> determines which grades can participate</li>
                <li><strong>School Classification</strong> affects number of representatives</li>
                <li><strong>Custom System Title</strong> allows custom branding for your election</li>
            </ul>
        </div>

        <div class="info-box">
            <strong>School Level Details:</strong>
            <ul>
                <li><strong>Elementary (Grade 3-6):</strong> President, VP (Grade 4-5), Secretary/Treasurer/Auditor/PIO/PO (Grade 2-5), Reps for Grades 2-6</li>
                <li><strong>Junior High School (Grade 7-10):</strong> Standard positions, Reps for Grades 7-10</li>
                <li><strong>Integrated School (Grade 7-12):</strong> President, VP (Grade 11-12), other positions (Grade 7-11), Reps for Grades 7-12</li>
                <li><strong>Senior High School (Grade 11-12):</strong> All positions for Grade 11, Rep for Grade 12</li>
            </ul>
        </div>

        <div class="info-box">
            <strong>Classification Details:</strong>
            <ul>
                <li><strong>Small/Medium:</strong> 1 representative per grade level</li>
                <li><strong>Large/Mega:</strong> 2 representatives per grade level</li>
            </ul>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="update_settings" value="1">
            
            <div class="form-group">
                <label>School Name:</label>
                <input type="text" name="school_name" value="<?= htmlspecialchars($settings['school_name']) ?>" required>
            </div>

            <div class="form-group">
                <label>School ID:</label>
                <input type="text" name="school_id" value="<?= htmlspecialchars($settings['school_id']) ?>" required>
            </div>

            <div class="form-group">
                <label>Principal:</label>
                <input type="text" name="principal" value="<?= htmlspecialchars($settings['principal']) ?>" required>
            </div>

            <div class="form-group">
                <label>School Level:</label>
                <select name="school_level" required>
                    <option value="Elementary" <?= $settings['school_level'] === 'Elementary' ? 'selected' : '' ?>>Elementary (Grade 3-6)</option>
                    <option value="Junior High School" <?= $settings['school_level'] === 'Junior High School' ? 'selected' : '' ?>>Junior High School (Grade 7-10)</option>
                    <option value="Integrated School" <?= $settings['school_level'] === 'Integrated School' ? 'selected' : '' ?>>Integrated School (Grade 7-12)</option>
                    <option value="Senior High School" <?= $settings['school_level'] === 'Senior High School' ? 'selected' : '' ?>>Senior High School (Grade 11-12)</option>
                </select>
            </div>

            <div class="form-group">
                <label>School Classification:</label>
                <select name="school_classification" required>
                    <option value="Small" <?= $settings['school_classification'] === 'Small' ? 'selected' : '' ?>>Small</option>
                    <option value="Medium" <?= $settings['school_classification'] === 'Medium' ? 'selected' : '' ?>>Medium</option>
                    <option value="Large" <?= $settings['school_classification'] === 'Large' ? 'selected' : '' ?>>Large</option>
                    <option value="Mega" <?= $settings['school_classification'] === 'Mega' ? 'selected' : '' ?>>Mega</option>
                </select>
            </div>

            <!-- Custom Title Section -->
            <div class="custom-title-section">
                <h3>Custom System Title (Optional)</h3>
                <p>Leave blank to use auto-generated title based on school level.</p>
                <p><strong>Examples:</strong> "Barangay Election 2026", "SK Council Election", "Student Organization Elections"</p>
                
                <div class="form-group">
                    <label>Custom Title:</label>
                    <input type="text" name="custom_system_title" value="<?= htmlspecialchars($settings['custom_system_title']) ?>" placeholder="Enter custom system title (optional)">
                    <small>Leave blank to use auto-generated title based on school level</small>
                </div>
            </div>

            <div class="form-group">
                <label>New Logo:</label>
                <input type="file" name="logo" accept="image/*">
                <small>(Leave blank to keep current logo)</small>
            </div>

            <button type="submit">Update Settings</button>
        </form>

        <div class="preview">
            <h3>Current Settings Preview:</h3>
            <p><strong>School Name:</strong> <?= htmlspecialchars($settings['school_name']) ?></p>
            <p><strong>School ID:</strong> <?= htmlspecialchars($settings['school_id']) ?></p>
            <p><strong>Principal:</strong> <?= htmlspecialchars($settings['principal']) ?></p>
            <p><strong>School Level:</strong> <?= htmlspecialchars($settings['school_level']) ?></p>
            <p><strong>School Classification:</strong> <?= htmlspecialchars($settings['school_classification']) ?></p>
            <p><strong>System Title:</strong> <?= htmlspecialchars($system_title) ?></p>
            
            <?php if (file_exists($settings['logo_path'])): ?>
                <p><strong>Current Logo:</strong></p>
                <img src="<?= $settings['logo_path'] ?>" alt="Current Logo" class="current-logo">
            <?php else: ?>
                <p><strong>No logo uploaded yet.</strong></p>
            <?php endif; ?>
        </div>

        <div class="password-section">
            <h3>Admin Password Management</h3>
            <p>Click the button below to change your admin password.</p>
            <a href="change_password.php" class="password-btn">Change Admin Password</a>
        </div>

        <br><a href="admin_panel.php" class="back-link">Back to Admin Panel</a>
    </div>
</body>
</html>