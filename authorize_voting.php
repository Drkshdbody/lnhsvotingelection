<?php
session_start();

// Check if student is logged in
if (!isset($_SESSION['lrn'])) {
    header("Location: index.php");
    exit;
}

// Check if already authorized
if (isset($_SESSION['voting_authorized']) && $_SESSION['voting_authorized']) {
    header("Location: vote.php");
    exit;
}

// Load stored tokens
$tokens_file = 'election_tokens.json';
if (file_exists($tokens_file)) {
    $stored_tokens = json_decode(file_get_contents($tokens_file), true);
} else {
    header("Location: index.php?error=voting_not_started");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chief_token = $_POST['chief_commissioner_token'] ?? '';
    $screening_token = $_POST['screening_validation_token'] ?? '';
    $electoral_token = $_POST['electoral_board_token'] ?? '';

    // Check if all tokens are correct
    if ($chief_token === $stored_tokens['chief_commissioner'] &&
        $screening_token === $stored_tokens['screening_validation'] &&
        $electoral_token === $stored_tokens['electoral_board']) {
        
        // Set voting as authorized
        $_SESSION['voting_authorized'] = true;
        
        // Redirect to vote page
        header("Location: vote.php");
        exit;
    } else {
        $message = "Invalid tokens. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Authorize Voting - School Learner Government Election System</title>
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
        
        .token-inputs {
            margin-bottom: 30px;
        }
        
        .token-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #202124;
            font-weight: 500;
            text-align: left;
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
        
        .start-btn {
            background: #4285f4;
            color: white;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }
        
        .start-btn:hover {
            background: #3367d6;
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
        
        .footer {
            margin-top: 30px;
            color: #70757a;
            font-size: 0.9em;
            text-align: center;
        }
        
        .message {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">SLGES</div>
        <h1 class="title">Authorize Voting</h1>
        <p class="subtitle">Contact election committee to enter tokens</p>
        
        <div class="instructions">
            <h3>Authorization Required:</h3>
            <ul>
                <li>Chief Commissioner Token</li>
                <li>Commission on Screening and Validation Token</li>
                <li>Commission of Electoral Board Token</li>
            </ul>
        </div>
        
        <?php if ($message): ?>
            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="token-inputs">
                <div class="token-group">
                    <label for="chief_commissioner_token">Chief Commissioner Token</label>
                    <input type="text" name="chief_commissioner_token" id="chief_commissioner_token" placeholder="Enter Chief Commissioner token" required autocomplete="off">
                </div>
                
                <div class="token-group">
                    <label for="screening_validation_token">Screening & Validation Token</label>
                    <input type="text" name="screening_validation_token" id="screening_validation_token" placeholder="Enter S&V token" required autocomplete="off">
                </div>
                
                <div class="token-group">
                    <label for="electoral_board_token">Electoral Board Token</label>
                    <input type="text" name="electoral_board_token" id="electoral_board_token" placeholder="Enter EB token" required autocomplete="off">
                </div>
            </div>
            
            <button type="submit" class="start-btn">Start Voting</button>
        </form>
        
        <div class="back-link">
            <a href="index.php">← Back to Login</a>
        </div>
    </div>
    
    <div class="footer">
        &copy; 2026 School Learner Government Election System
    </div>
</body>
</html>