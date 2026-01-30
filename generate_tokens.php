<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

// Generate random tokens
function generateRandomToken() {
    return bin2hex(random_bytes(8)); // 16-character hex token
}

// Load existing tokens or generate new ones
$tokens_file = 'election_tokens.json';
$existing_tokens = null;

if (file_exists($tokens_file)) {
    $existing_tokens = json_decode(file_get_contents($tokens_file), true);
}

if (!$existing_tokens) {
    // Generate new tokens
    $tokens = [
        'chief_commissioner' => generateRandomToken(),
        'screening_validation' => generateRandomToken(),
        'electoral_board' => generateRandomToken(),
        'enabled' => false
    ];
    
    file_put_contents($tokens_file, json_encode($tokens, JSON_PRETTY_PRINT));
    $tokens = json_decode(file_get_contents($tokens_file), true);
} else {
    $tokens = $existing_tokens;
}

$message = '';

if (isset($_POST['generate_new_tokens'])) {
    // Generate new tokens
    $tokens['chief_commissioner'] = generateRandomToken();
    $tokens['screening_validation'] = generateRandomToken();
    $tokens['electoral_board'] = generateRandomToken();
    $tokens['enabled'] = false; // Reset to disabled
    
    file_put_contents($tokens_file, json_encode($tokens, JSON_PRETTY_PRINT));
    $message = "New tokens generated successfully!";
    
    // Refresh tokens
    $tokens = json_decode(file_get_contents($tokens_file), true);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Generate Election Tokens</title>
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
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .title {
            font-size: 2em;
            color: #202124;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #5f6368;
            margin-bottom: 20px;
        }
        
        .token-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #dfe1e5;
        }
        
        .token-title {
            font-size: 1.2em;
            color: #202124;
            margin-bottom: 10px;
        }
        
        .token-value {
            font-size: 1.1em;
            font-weight: bold;
            color: #4285f4;
            background: white;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #dfe1e5;
            word-break: break-all;
        }
        
        .print-btn {
            background: #4285f4;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 5px;
        }
        
        .print-btn:hover {
            background: #3367d6;
        }
        
        .generate-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 24px;
            font-size: 14px;
            border-radius: 4px;
            cursor: pointer;
            margin: 10px 5px;
        }
        
        .generate-btn:hover {
            background: #218838;
        }
        
        .back-link {
            margin-top: 20px;
            text-align: center;
        }
        
        .back-link a {
            color: #4285f4;
            text-decoration: none;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .message {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }
        
        .status {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #dfe1e5;
        }
        
        .status.active {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        
        .status.inactive {
            background: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1 class="title">Election Token Generator</h1>
            <p class="subtitle">Generate and print tokens for voting access</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <div class="status <?= $tokens['enabled'] ? 'active' : 'inactive' ?>">
            <strong>Voting Status:</strong> <?= $tokens['enabled'] ? 'ACTIVE' : 'INACTIVE' ?>
        </div>
        
        <div class="token-card">
            <div class="token-title">Chief Commissioner Token</div>
            <div class="token-value"><?= htmlspecialchars($tokens['chief_commissioner']) ?></div>
        </div>
        
        <div class="token-card">
            <div class="token-title">Screening & Validation Token</div>
            <div class="token-value"><?= htmlspecialchars($tokens['screening_validation']) ?></div>
        </div>
        
        <div class="token-card">
            <div class="token-title">Electoral Board Token</div>
            <div class="token-value"><?= htmlspecialchars($tokens['electoral_board']) ?></div>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <button class="print-btn" onclick="window.print()">Print Tokens</button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="generate_new_tokens" value="1">
                <button type="submit" class="generate-btn" onclick="return confirm('Are you sure you want to generate new tokens? This will invalidate existing tokens.')">Generate New Tokens</button>
            </form>
        </div>
        
        <div class="back-link">
            <a href="admin_panel.php">← Back to Admin Panel</a>
        </div>
    </div>
</body>
</html>