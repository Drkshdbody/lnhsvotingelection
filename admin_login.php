<?php
session_start();
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: admin_panel.php');
    exit;
}

// Load school settings to get logo
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
    
    // Determine system title - prioritize custom title
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

// Load tokens from file
$tokens_file = 'election_tokens.json';
$stored_tokens = null;
$voting_enabled = false;

if (file_exists($tokens_file)) {
    $stored_tokens = json_decode(file_get_contents($tokens_file), true);
    if (isset($stored_tokens['enabled']) && $stored_tokens['enabled'] === true) {
        $voting_enabled = true;
    }
}

$error = '';
$success_message = '';

if ($_POST) {
    $password = $_POST['password'] ?? '';
    $action = $_POST['action'] ?? 'login';
    
    if ($action === 'start_voting') {
        // Handle START VOTING tokens
        $chief_token = $_POST['chief_commissioner_token'] ?? '';
        $screening_token = $_POST['screening_validation_token'] ?? '';
        $electoral_token = $_POST['electoral_board_token'] ?? '';
        
        // Validate tokens from file
        if ($stored_tokens && 
            $chief_token === $stored_tokens['chief_commissioner'] &&
            $screening_token === $stored_tokens['screening_validation'] &&
            $electoral_token === $stored_tokens['electoral_board']) {
            
            // Enable voting
            $stored_tokens['enabled'] = true;
            file_put_contents($tokens_file, json_encode($stored_tokens, JSON_PRETTY_PRINT));
            
            $success_message = "Voting has been started successfully! Students can now vote using their tokens.";
            $voting_enabled = true; // Update local variable
        } else {
            $error = "Invalid tokens. Please try again.";
        }
    } elseif ($action === 'close_voting') {
        // Handle CLOSE VOTING tokens
        $chief_token = $_POST['chief_commissioner_token'] ?? '';
        $screening_token = $_POST['screening_validation_token'] ?? '';
        $electoral_token = $_POST['electoral_board_token'] ?? '';
        
        // Validate tokens from file
        if ($stored_tokens && 
            $chief_token === $stored_tokens['chief_commissioner'] &&
            $screening_token === $stored_tokens['screening_validation'] &&
            $electoral_token === $stored_tokens['electoral_board']) {
            
            // Disable voting
            $stored_tokens['enabled'] = false;
            file_put_contents($tokens_file, json_encode($stored_tokens, JSON_PRETTY_PRINT));
            
            $success_message = "Voting has been closed successfully! Students can no longer vote.";
            $voting_enabled = false; // Update local variable
        } else {
            $error = "Invalid tokens. Please try again.";
        }
    } else {
        // Handle ADMIN LOGIN
        if ($password === 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            header('Location: admin_panel.php');
            exit;
        } else {
            $error = "Invalid credentials.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - <?= htmlspecialchars($system_title) ?></title>
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            text-align: center;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
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
        
        .title {
            font-size: 2.5em;
            color: #202124;
            font-weight: 400;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 1.2em;
            color: #5f6368;
            margin-bottom: 30px;
        }
        
        .school-info {
            font-size: 1em;
            color: #5f6368;
            margin-bottom: 20px;
        }
        
        .status-indicator {
            background: #d4edda;
            color: #155724;
            padding: 8px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            font-weight: bold;
        }
        
        .status-indicator.inactive {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #202124;
            font-weight: 500;
            text-align: left;
        }
        
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #dfe1e5;
            border-radius: 4px;
            font-size: 16px;
            outline: none;
        }
        
        input[type="password"]:focus,
        input[type="text"]:focus {
            border-color: #4285f4;
            box-shadow: 0 0 0 1px #4285f4;
        }
        
        .btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-primary {
            background: #4285f4;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3367d6;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .home-link {
            background: #f8f9fa;
            color: #202124;
            border: 1px solid #dfe1e5;
            padding: 8px 16px;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
            transition: all 0.2s ease;
        }
        
        .home-link:hover {
            background: #e8eaed;
            border-color: #4285f4;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .token-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 1px solid #dfe1e5;
        }
        
        .token-group {
            margin-bottom: 15px;
        }
        
        .section-toggle {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .section-btn {
            background: #f8f9fa;
            color: #202124;
            border: 1px solid #dfe1e5;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }
        
        .section-btn:hover {
            background: #e8eaed;
        }
        
        .section-btn.active {
            background: #4285f4;
            color: white;
            border-color: #4285f4;
        }
        
        .footer {
            margin-top: 30px;
            color: #70757a;
            font-size: 0.9em;
            text-align: center;
        }
        
        .instructions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: left;
            font-size: 0.9em;
        }
        
        .instructions h3 {
            color: #202124;
            margin-bottom: 10px;
        }
        
        .instructions ul {
            padding-left: 20px;
        }
        
        .instructions li {
            margin: 5px 0;
            color: #5f6368;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }
            
            .title {
                font-size: 2em;
            }
            
            .subtitle {
                font-size: 1em;
            }
            
            .section-toggle {
                flex-direction: column;
            }
            
            .section-btn {
                width: 100%;
                text-align: center;
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
            
            <h1 class="title"><?= htmlspecialchars($system_title) ?></h1>
            <p class="school-info"><?= htmlspecialchars($settings['school_name']) ?></p>
            <p class="subtitle">Admin Login</p>
        </div>
        
        <div class="status-indicator <?= $voting_enabled ? '' : 'inactive' ?>">
            <?= $voting_enabled ? 'Voting is ACTIVE' : 'Voting is INACTIVE' ?>
        </div>
        
        <div class="section-toggle">
            <button class="section-btn active" onclick="showSection('login')">Admin Login</button>
            <button class="section-btn" onclick="showSection('voting_control')">Voting Control</button>
        </div>
        
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success"><?= htmlspecialchars($success_message) ?></div>
            <?php if (strpos($success_message, 'started') !== false || strpos($success_message, 'closed') !== false): ?>
                <div style="text-align: center; margin: 20px 0;">
                    <a href="admin_panel.php" class="btn btn-primary">Go to Admin Panel</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- Admin Login Section -->
        <div id="login-section">
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" name="password" id="password" placeholder="Enter Password" required>
                </div>
                <button type="submit" class="btn btn-primary">Login</button>
            </form>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="index.php" class="home-link">← Back to Home Page</a>
            </div>
        </div>
        
        <!-- Voting Control Section -->
        <div id="voting-control-section" style="display: none;">
            <div class="instructions">
                <h3>Voting Control Instructions:</h3>
                <ul>
                    <li>Use this section to start or close voting</li>
                    <li>You need all 3 tokens from the commissions</li>
                    <li>Chief Commissioner Token</li>
                    <li>Commission on Screening and Validation Token</li>
                    <li>Commission of Electoral Board Token</li>
                </ul>
            </div>
            
            <?php if (!$voting_enabled): ?>
                <!-- Start Voting Form -->
                <?php if (!$stored_tokens): ?>
                    <div class="error">
                        No tokens found. Please generate tokens first in the admin panel.
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="start_voting">
                        <div class="token-section">
                            <div class="token-group">
                                <label for="chief_commissioner_token">Chief Commissioner Token</label>
                                <input type="text" name="chief_commissioner_token" id="chief_commissioner_token" placeholder="Enter Chief Commissioner token" required>
                            </div>
                            
                            <div class="token-group">
                                <label for="screening_validation_token">Screening & Validation Token</label>
                                <input type="text" name="screening_validation_token" id="screening_validation_token" placeholder="Enter S&V token" required>
                            </div>
                            
                            <div class="token-group">
                                <label for="electoral_board_token">Electoral Board Token</label>
                                <input type="text" name="electoral_board_token" id="electoral_board_token" placeholder="Enter EB token" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">Start Voting</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <!-- Close Voting Form -->
                <?php if (!$stored_tokens): ?>
                    <div class="error">
                        No tokens found. Please generate tokens first in the admin panel.
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="close_voting">
                        <div class="token-section">
                            <div class="token-group">
                                <label for="chief_commissioner_token">Chief Commissioner Token</label>
                                <input type="text" name="chief_commissioner_token" id="chief_commissioner_token" placeholder="Enter Chief Commissioner token" required>
                            </div>
                            
                            <div class="token-group">
                                <label for="screening_validation_token">Screening & Validation Token</label>
                                <input type="text" name="screening_validation_token" id="screening_validation_token" placeholder="Enter S&V token" required>
                            </div>
                            
                            <div class="token-group">
                                <label for="electoral_board_token">Electoral Board Token</label>
                                <input type="text" name="electoral_board_token" id="electoral_board_token" placeholder="Enter EB token" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-danger">Close Voting</button>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="index.php" class="home-link">← Back to Home Page</a>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>Powered by <?= htmlspecialchars($system_title) ?></p>
        <p>Developed by: <a href="https://www.facebook.com/sirtopet" target="_blank">Cristopher Duro</a></p>
    </div>

    <script>
        function showSection(section) {
            // Hide all sections
            document.getElementById('login-section').style.display = 'none';
            document.getElementById('voting-control-section').style.display = 'none';
            
            // Show selected section
            if (section === 'login') {
                document.getElementById('login-section').style.display = 'block';
                // Update button states
                document.querySelector('.section-btn:nth-child(1)').classList.add('active');
                document.querySelector('.section-btn:nth-child(2)').classList.remove('active');
            } else if (section === 'voting_control') {
                document.getElementById('voting-control-section').style.display = 'block';
                // Update button states
                document.querySelector('.section-btn:nth-child(1)').classList.remove('active');
                document.querySelector('.section-btn:nth-child(2)').classList.add('active');
            }
        }
    </script>
</body>
</html>