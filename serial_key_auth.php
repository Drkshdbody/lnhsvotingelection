<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Check if already authenticated (persistent check)
$serial_auth_file = 'serial_authenticated.txt';
if (file_exists($serial_auth_file)) {
    $_SESSION['serial_key_authenticated'] = true;
    header("Location: school_settings.php");
    exit;
}

// Load school settings for system title
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

$message = '';

// ✅ PLAIN TEXT SERIAL KEY AUTHENTICATION (PERSISTENT)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_serial = trim($_POST['serial_key'] ?? '');
    
    // 🔑 SET YOUR SERIAL KEY HERE
    $valid_serial = "SCH-f9770349-a1528dbf-2026"; // ← YOUR ACTUAL SERIAL KEY
    
    if ($entered_serial === $valid_serial) {
        // Save authentication permanently
        file_put_contents($serial_auth_file, 'authenticated');
        $_SESSION['serial_key_authenticated'] = true;
        header("Location: school_settings.php");
        exit;
    } else {
        $message = "Invalid serial key. Please check your serial key and try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Serial Key Authentication - <?= htmlspecialchars($system_title) ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: Arial, sans-serif;
            background: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
        }
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            display: block;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #dfe1e5;
        }
        .title {
            font-size: 2.5em;
            color: #202124;
            margin-bottom: 10px;
            font-weight: 400;
        }
        .subtitle {
            font-size: 1.2em;
            color: #5f6368;
            margin-bottom: 30px;
        }
        .instructions {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border: 1px solid #ffeaa7;
            text-align: center;
        }
        .payment-info {
            background: #e7f3ff;
            color: #0c5460;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #b8daff;
            text-align: left;
        }
        .payment-info h3 {
            text-align: center;
            margin-top: 0;
            margin-bottom: 15px;
            color: #0c5460;
        }
        .payment-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 10px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 4px;
        }
        .payment-label {
            font-weight: bold;
            color: #0c5460;
            flex: 1;
        }
        .payment-value {
            color: #0c5460;
            flex: 2;
            text-align: right;
            font-weight: bold;
        }
        .form-group {
            margin: 20px 0;
            text-align: left;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #202124;
            font-weight: 500;
        }
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #dfe1e5;
            border-radius: 4px;
            font-size: 16px;
            outline: none;
        }
        input[type="text"]:focus {
            border-color: #4285f4;
            box-shadow: 0 0 0 1px #4285f4;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #4285f4;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
        }
        .btn:hover {
            background: #3367d6;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
            text-align: center;
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
        .footer {
            text-align: center;
            padding: 20px;
            margin-top: auto;
            color: #70757a;
            font-size: 0.9em;
            border-top: 1px solid #e0e0e0;
            background: white;
        }
        .footer a {
            color: #4285f4;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .highlight {
            background: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: bold;
        }
        .step-list {
            text-align: left;
            margin: 15px 0;
            padding-left: 20px;
        }
        .step-list li {
            margin: 8px 0;
            line-height: 1.4;
        }
        .contact-box {
            margin: 20px 0;
            padding: 15px;
            background: #d4edda;
            border-radius: 8px;
            border: 1px solid #c3e6cb;
        }
        .contact-box h4 {
            color: #155724;
            margin: 0 0 10px 0;
        }
        .contact-box p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container">
            <?php if (file_exists($settings['logo_path'])): ?>
                <img src="<?= $settings['logo_path'] ?>" alt="School Logo" class="logo">
            <?php else: ?>
                <div class="logo" style="background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #5f6368; font-weight: bold; font-size: 1.5em;">LOGO</div>
            <?php endif; ?>
            
            <h1 class="title"><?= htmlspecialchars($system_title) ?></h1>
            <p class="subtitle">Serial Key Authentication Required</p>
            
            <div class="instructions">
                <p><strong>Payment Required for Serial Key Access</strong></p>
            </div>
            
            <div class="payment-info">
                <h3>Payment Information</h3>
                <div class="payment-row">
                    <span class="payment-label">GCash Number:</span>
                    <span class="payment-value highlight">09685001152</span>
                </div>
                <div class="payment-row">
                    <span class="payment-label">Name:</span>
                    <span class="payment-value highlight">Cristopher D</span>
                </div>
                <div class="payment-row">
                    <span class="payment-label">Amount:</span>
                    <span class="payment-value highlight">₱200</span>
                </div>
            </div>
            
            <div class="instructions">
                <h3>How to Get Your Serial Key:</h3>
                <ol class="step-list">
                    <li>Make payment via GCash to the number above</li>
                    <li>Complete the Google Form: <a href="https://forms.gle/dfKrcWnPSjrmvyT98" target="_blank" style="color: #007bff; text-decoration: underline;">https://forms.gle/dfKrcWnPSjrmvyT98</a></li>
                    <li>Provide proof of payment in the form</li>
                    <li>Your serial key will be sent to your email within 24 hours</li>
                    <li>Enter the serial key below to access school settings</li>
                </ol>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label for="serial_key">Enter Serial Key:</label>
                    <input type="text" name="serial_key" id="serial_key" placeholder="Enter your serial key" required autocomplete="off" autofocus>
                </div>
                <button type="submit" class="btn">Verify Serial Key</button>
            </form>
            
            <div class="contact-box">
                <h4>Need Help?</h4>
                <p>Contact: <strong>Sir Topet</strong></p>
                <p>Facebook: <a href="https://facebook.com/sirtopet" target="_blank" style="color: #4285f4;">https://facebook.com/sirtopet</a></p>
            </div>
            
            <div class="back-link">
                <a href="admin_panel.php">← Back to Admin Panel</a>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>Powered by <?= htmlspecialchars($system_title) ?></p>
        <p>Developed by: <a href="https://www.facebook.com/sirtopet" target="_blank">Cristopher Duro</a></p>
    </div>
</body>
</html>