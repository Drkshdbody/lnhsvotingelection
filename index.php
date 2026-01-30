<?php
session_start();

// If already logged in as voter, go directly to vote.php
if (isset($_SESSION['lrn'])) {
    header("Location: vote.php");
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
} else {
    $settings = $default_settings;
}

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

// Check voting status
$voting_started = false;
$voting_closed = false;
$tokens_file = 'election_tokens.json';

if (file_exists($tokens_file)) {
    $tokens = json_decode(file_get_contents($tokens_file), true);
    if (isset($tokens['enabled']) && $tokens['enabled'] === true) {
        $voting_started = true;
    } elseif (isset($tokens['enabled']) && $tokens['enabled'] === false) {
        $voting_closed = true;
    }
}

// Handle token submission during active voting
if ($voting_started && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token']);
    
    if (!empty($token)) {
        $db = new SQLite3('election.db');
        
        // Direct query without prepare (simpler approach)
        $query = "SELECT lrn, has_voted FROM students WHERE login_token = '" . $db->escapeString($token) . "'";
        $result = $db->query($query);
        $student = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($student && isset($student['lrn'])) {
            if ($student['has_voted'] == 0) {
                // Valid token → proceed to vote
                $_SESSION['lrn'] = $student['lrn'];
                $_SESSION['token'] = $token;
                header("Location: vote.php");
                exit;
            } else {
                $error_message = "This token has already been used for voting.";
            }
        } else {
            $error_message = "Invalid token. Please check your token and try again.";
        }
    } else {
        $error_message = "Please enter a valid token.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($system_title) ?></title>
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
            max-width: 800px;
            width: 100%;
            margin: 0 auto;
            text-align: center;
        }
        
        .header {
            text-align: center;
            margin-bottom: 50px;
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
        }
        
        .school-info {
            font-size: 1em;
            color: #5f6368;
            margin-bottom: 20px;
        }
        
        .status-indicator {
            background: #d4edda;
            color: #155724;
            padding: 10px 20px;
            border-radius: 4px;
            margin-bottom: 30px;
            border: 1px solid #c3e6cb;
            font-weight: bold;
            font-size: 1.1em;
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
        
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            text-align: center;
            margin: 0 auto 30px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #202124;
            font-weight: 500;
        }
        
        input[type="text"] {
            width: 100%;
            padding: 15px;
            border: 1px solid #dfe1e5;
            border-radius: 4px;
            font-size: 16px;
            outline: none;
        }
        
        input[type="text"]:focus {
            border-color: #4285f4;
            box-shadow: 0 0 0 1px #4285f4;
        }
        
        .login-btn {
            background: #4285f4;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        
        .login-btn:hover {
            background: #3367d6;
        }
        
        .admin-link {
            margin-top: 20px;
            text-align: center;
        }
        
        .admin-link a {
            color: #4285f4;
            text-decoration: none;
            font-size: 14px;
        }
        
        .admin-link a:hover {
            text-decoration: underline;
        }
        
        .message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .footer {
            margin-top: 30px;
            color: #70757a;
            font-size: 0.9em;
            text-align: center;
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .footer a {
            color: #4285f4;
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        .results-link,
        .navigation-links {
            margin: 20px 0;
            text-align: center;
        }
        
        .results-link a,
        .navigation-links a {
            color: #4285f4;
            text-decoration: none;
            font-size: 14px;
            margin: 0 10px;
        }
        
        .results-link a {
            font-size: 16px;
            font-weight: bold;
        }
        
        .results-link a:hover,
        .navigation-links a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            .login-box { padding: 30px 20px; }
            .title { font-size: 2em; }
            .subtitle { font-size: 1em; }
            .navigation-links { display: flex; flex-direction: column; gap: 10px; }
            .navigation-links a { margin: 5px 0; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <?php if (file_exists($settings['logo_path'])): ?>
                <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="School Logo" class="logo">
            <?php else: ?>
                <div class="logo" style="background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; color: #5f6368; font-weight: bold; font-size: 1.5em;">LOGO</div>
            <?php endif; ?>
            
            <h1 class="title"><?= htmlspecialchars($system_title) ?></h1>
            <p class="school-info"><?= htmlspecialchars($settings['school_name']) ?></p>
            <p class="subtitle">Enter your login token to vote</p>
        </div>
        
        <?php if ($voting_closed): ?>
            <div class="status-indicator closed">
                Election has ended. Results are now available.
            </div>
            
            <div class="success-message">
                Thank you for participating in the election! The voting period has ended.
            </div>
            
            <div class="results-link">
                <a href="election_results.php">View Final Election Results</a>
            </div>
            
            <div class="navigation-links">
                <a href="admin_login.php">Admin Login</a>
                <a href="index.php">Refresh Page</a>
            </div>
            
        <?php elseif ($voting_started): ?>
            <div class="status-indicator">
                Election is currently active. Voting in progress.
            </div>
            
            <?php if (isset($error_message)): ?>
                <div class="message"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            
            <div class="login-box">
                <form method="POST">
                    <div class="form-group">
                        <label for="token">Login Token</label>
                        <input type="text" name="token" id="token" placeholder="Enter your token" required autocomplete="off" autofocus>
                    </div>
                    <button type="submit" class="login-btn">Sign In</button>
                </form>
                
                <div class="admin-link">
                    <a href="admin_login.php">Admin Login</a>
                </div>
            </div>
            
        <?php else: ?>
            <div class="status-indicator inactive">
                Election has not started yet.
            </div>
            
            <div class="message">
                The election has not yet started. Please wait for the election committee to begin the voting process.
            </div>
            
            <div class="navigation-links">
                <a href="admin_login.php">Admin Login</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        <p>Powered by <?= htmlspecialchars($system_title) ?></p>
        <p>Developed by: <a href="https://www.facebook.com/sirtopet" target="_blank">Cristopher Duro</a></p>
    </div>
</body>
</html>