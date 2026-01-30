<?php
session_start();

// Get student token
$token = $_POST['token'] ?? '';

if (!empty($token)) {
    $db = new SQLite3('election.db');
    
    // Find student by token
    $stmt = $db->prepare("SELECT * FROM students WHERE login_token = ?");
    $stmt->bindValue(1, $token);
    $result = $stmt->execute();
    $student = $result->fetchArray();
    
    if ($student) {
        if ($student['has_voted'] == 1) {
            header("Location: index.php?error=already_voted");
            exit;
        }
        
        // Set session variables
        $_SESSION['lrn'] = $student['lrn'];
        $_SESSION['grade_level'] = $student['grade_level'];
        
        // Check if voting is authorized (tokens entered by admin)
        $tokens_file = 'election_tokens.json';
        if (file_exists($tokens_file)) {
            $stored_tokens = json_decode(file_get_contents($tokens_file), true);
            
            // Check if admin has entered all 3 tokens in session
            if (isset($_SESSION['voting_authorized']) && $_SESSION['voting_authorized']) {
                header("Location: vote.php");
                exit;
            } else {
                // Redirect to token authorization page
                header("Location: authorize_voting.php");
                exit;
            }
        } else {
            // No tokens file, assume voting is not started
            header("Location: index.php?error=voting_not_started");
            exit;
        }
    } else {
        header("Location: index.php?error=invalid_token");
        exit;
    }
} else {
    header("Location: index.php?error=missing_token");
    exit;
}
?>