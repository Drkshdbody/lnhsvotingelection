<?php
session_start();

function validateThreeTokens($token1, $token2, $token3) {
    // Load the stored tokens from file
    $tokens_file = 'election_tokens.json';
    if (!file_exists($tokens_file)) {
        return false;
    }
    
    $stored_tokens = json_decode(file_get_contents($tokens_file), true);
    
    if (!$stored_tokens) {
        return false;
    }
    
    // Validate all three tokens
    $correct_tokens = [
        'chief_commissioner' => $stored_tokens['chief_commissioner'] ?? '',
        'screening_validation' => $stored_tokens['screening_validation'] ?? '',
        'electoral_board' => $stored_tokens['electoral_board'] ?? ''
    ];
    
    return (
        $token1 === $correct_tokens['chief_commissioner'] &&
        $token2 === $correct_tokens['screening_validation'] &&
        $token3 === $correct_tokens['electoral_board']
    );
}

function showTokenForm($action, $redirect_url) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Security Verification - School Learner Government Election System</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; background-color: #f5f5f5; }
            .container { background-color: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 500px; margin: 50px auto; text-align: center; }
            .form-group { margin: 15px 0; }
            label { display: block; margin-bottom: 5px; font-weight: bold; }
            input[type="text"] { width: 100%; padding: 10px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px; }
            button { background-color: #dc3545; color: white; padding: 12px 24px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
            button:hover { background-color: #c82333; }
            .back-link { margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class="container">
            <h2>Security Verification Required</h2>
            <p>You are about to perform: <strong><?= htmlspecialchars($action) ?></strong></p>
            <p>Please enter the three tokens to proceed:</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="<?= htmlspecialchars($action) ?>">
                <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect_url) ?>">
                
                <div class="form-group">
                    <label for="token1">Chief Commissioner Token:</label>
                    <input type="text" name="token1" id="token1" required>
                </div>
                
                <div class="form-group">
                    <label for="token2">Screening & Validation Token:</label>
                    <input type="text" name="token2" id="token2" required>
                </div>
                
                <div class="form-group">
                    <label for="token3">Electoral Board Token:</label>
                    <input type="text" name="token3" id="token3" required>
                </div>
                
                <button type="submit">Verify and Proceed</button>
            </form>
            
            <div class="back-link">
                <a href="admin_panel.php">← Back to Admin Panel</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Handle token validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token1 = $_POST['token1'] ?? '';
    $token2 = $_POST['token2'] ?? '';
    $token3 = $_POST['token3'] ?? '';
    $action = $_POST['action'] ?? '';
    $redirect = $_POST['redirect'] ?? 'admin_panel.php';
    
    if (validateThreeTokens($token1, $token2, $token3)) {
        // Store verification in session
        $_SESSION['security_verified_' . $action] = true;
        $_SESSION['security_verified_time'] = time();
        header("Location: $redirect");
        exit;
    } else {
        showTokenForm($action, $redirect);
    }
}

// If accessed directly without POST, show the form
$action = $_GET['action'] ?? 'unknown';
$redirect = $_GET['redirect'] ?? 'admin_panel.php';
showTokenForm($action, $redirect);
?>