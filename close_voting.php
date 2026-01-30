<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Check if voting is currently active
$tokens_file = 'election_tokens.json';
$voting_active = false;
$stored_tokens = null;

if (file_exists($tokens_file)) {
    $stored_tokens = json_decode(file_get_contents($tokens_file), true);
    if (isset($stored_tokens['enabled']) && $stored_tokens['enabled'] === true) {
        $voting_active = true;
    }
}

if (!$voting_active) {
    header("Location: admin_panel.php?error=not_voting");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chief_token = $_POST['chief_commissioner_token'] ?? '';
    $screening_token = $_POST['screening_validation_token'] ?? '';
    $electoral_token = $_POST['electoral_board_token'] ?? '';

    // Validate tokens
    if ($chief_token === $stored_tokens['chief_commissioner'] &&
        $screening_token === $stored_tokens['screening_validation'] &&
        $electoral_token === $stored_tokens['electoral_board']) {
        
        // Disable voting
        $stored_tokens['enabled'] = false;
        file_put_contents($tokens_file, json_encode($stored_tokens, JSON_PRETTY_PRINT));
        
        $message = "Voting has been closed successfully!";
    } else {
        $message = "Invalid tokens. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Close Voting - School Learner Government Election System</title>
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
            max-width: 500px;
            text-align: center;
        }
        
        .logo {
            font-size: 4em;
            color: #4285f4;
            margin-bottom: 10px;
        }
        
        .title {
            font-size: 2em;
            color: #202124;
            font-weight: 400;
            margin-bottom: 5px;
        }
        
        .subtitle {
            font-size: 1.2em;
            color: #5f6368;
            margin-bottom: 30px;
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
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
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
        
        .footer {
            margin-top: 30px;
            color: #70757a;
            font-size: 0.9em;
            text-align: center;
        }
        
        .back-link {
            margin-top: 20px;
            text-align: center;
        }
        
        .back-link a {
            color: #4285f4;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 600px) {
            .container {
                padding: 30px 20px;
            }
            
            .title {
                font-size: 1.8em;
            }
            
            .subtitle {
                font-size: 1em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">SLGES</div>
        <h1 class="title">Close Voting</h1>
        <p class="subtitle">School Learner Government Election System</p>
        
        <?php if ($message): ?>
            <?php if (strpos($message, 'successfully') !== false): ?>
                <div class="success"><?= htmlspecialchars($message) ?></div>
                <div class="back-link">
                    <a href="admin_panel.php">← Back to Admin Panel</a>
                </div>
            <?php else: ?>
                <div class="error"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
        <?php else: ?>
            <div class="instructions">
                <h3>Close Voting Instructions:</h3>
                <ul>
                    <li>Enter the same tokens used to start voting</li>
                    <li>Chief Commissioner Token</li>
                    <li>Commission on Screening and Validation Token</li>
                    <li>Commission of Electoral Board Token</li>
                    <li>This will end the voting process for all students</li>
                </ul>
            </div>
            
            <form method="POST">
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
            
            <div class="back-link">
                <a href="admin_panel.php">← Back to Admin Panel</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="footer">
        &copy; 2026 School Learner Government Election System
    </div>
</body>
</html>