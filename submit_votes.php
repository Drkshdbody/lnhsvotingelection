<?php
session_start();
if (!isset($_SESSION['lrn']) || (!isset($_POST['vote']) && !isset($_SESSION['votes']))) {
    header("Location: index.php");
    exit;
}

$lrn = $_SESSION['lrn'];
$db = new SQLite3('election.db');

// Check if we're using the old system (POST data) or new system (session data)
if (isset($_POST['vote']) && is_array($_POST['vote'])) {
    // Old system - direct POST data
    foreach ($_POST['vote'] as $position => $candidate_id) {
        if (!empty($candidate_id)) {
            $stmt = $db->prepare("INSERT INTO votes (student_lrn, candidate_id, position) VALUES (?, ?, ?)");
            $stmt->bindValue(1, $lrn);
            $stmt->bindValue(2, $candidate_id);
            $stmt->bindValue(3, $position);
            $stmt->execute();

            // Update vote count
            $stmt = $db->prepare("UPDATE candidates SET vote_count = vote_count + 1 WHERE id = ?");
            $stmt->bindValue(1, $candidate_id);
            $stmt->execute();
        }
    }
} elseif (isset($_SESSION['votes']) && is_array($_SESSION['votes'])) {
    // New system - session data
    foreach ($_SESSION['votes'] as $position => $candidate_ids) {
        if (is_array($candidate_ids)) {
            foreach ($candidate_ids as $candidate_id) {
                if (!empty($candidate_id)) {
                    $stmt = $db->prepare("INSERT INTO votes (student_lrn, candidate_id, position) VALUES (?, ?, ?)");
                    $stmt->bindValue(1, $lrn);
                    $stmt->bindValue(2, $candidate_id);
                    $stmt->bindValue(3, $position);
                    $stmt->execute();

                    // Update vote count
                    $stmt = $db->prepare("UPDATE candidates SET vote_count = vote_count + 1 WHERE id = ?");
                    $stmt->bindValue(1, $candidate_id);
                    $stmt->execute();
                }
            }
        }
    }
}

// Mark as voted
$stmt = $db->prepare("UPDATE students SET has_voted = 1 WHERE lrn = ?");
$stmt->bindValue(1, $lrn);
$stmt->execute();

// Clear session
session_destroy();
?>
<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 50px; background-color: #f5f5f5; }
        .success-box { background-color: white; padding: 30px; border-radius: 8px; width: 400px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .success-icon { font-size: 48px; color: #28a745; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="success-box">
        <div class="success-icon">✓</div>
        <h2>Thank You for Voting!</h2>
        <p>Your vote has been recorded successfully.</p>
        <p>You can now close this window.</p>
        <a href="index.php">Back to Login</a>
    </div>
</body>
</html>