<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Determine system title based on school level
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

$school_level = $settings['school_level'] ?? 'Junior High School';
if ($school_level === 'Elementary') {
    $system_title = "Supreme Elementary Learner Government Election System";
} else {
    $system_title = "Supreme Secondary Learner Government Election System";
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Check current password from file or default
    $password_file = 'admin_password.txt';
    $current_stored_password = file_exists($password_file) ? trim(file_get_contents($password_file)) : 'admin123';
    
    if ($current_password !== $current_stored_password) {
        $message = "Current password is incorrect.";
    } elseif (empty($new_password)) {
        $message = "New password cannot be empty.";
    } elseif (strlen($new_password) < 6) {
        $message = "New password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $message = "New password and confirmation do not match.";
    } else {
        // Update the password
        file_put_contents($password_file, $new_password);
        $message = "Password changed successfully!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($system_title) ?> - Change Admin Password</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background-color: #f5f5f5; 
        }
        .container { 
            background-color: white; 
            padding: 30px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
            max-width: 500px; 
            margin: 50px auto; 
        }
        .form-group { 
            margin: 15px 0; 
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
            color: #202124; 
        }
        input[type="password"] { 
            width: 100%; 
            padding: 10px; 
            border: 1px solid #dfe1e5; 
            border-radius: 4px; 
            font-size: 16px; 
        }
        button { 
            padding: 10px 20px; 
            background-color: #007bff; 
            color: white; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 16px; 
            margin-top: 10px; 
        }
        button:hover { 
            background-color: #0056b3; 
        }
        .back-link { 
            margin-top: 20px; 
            display: block; 
            color: #4285f4; 
            text-decoration: none; 
        }
        .back-link:hover { 
            text-decoration: underline; 
        }
        .message { 
            padding: 10px; 
            margin: 10px 0; 
            border-radius: 4px; 
        }
        .success { 
            background-color: #d4edda; 
            color: #155724; 
            border: 1px solid #c3e6cb; 
        }
        .error { 
            background-color: #f8d7da; 
            color: #721c24; 
            border: 1px solid #f5c6cb; 
        }
        h2 {
            color: #202124;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Change Admin Password</h2>
        
        <?php if ($message): ?>
            <div class="message <?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="current_password">Current Password:</label>
                <input type="password" name="current_password" id="current_password" required>
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required minlength="6">
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>
            </div>
            
            <button type="submit">Change Password</button>
        </form>
        
        <a href="school_settings.php" class="back-link">← Back to School Settings</a>
    </div>
</body>
</html>