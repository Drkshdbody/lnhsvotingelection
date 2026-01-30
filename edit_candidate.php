<?php
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: admin_login.php");
    exit;
}

$candidate_id = $_GET['id'];

if (empty($candidate_id)) {
    header("Location: view_candidates.php");
    exit;
}

$db = new SQLite3('election.db');

// Get candidate data
$stmt = $db->prepare("SELECT * FROM candidates WHERE id = ?");
$stmt->bindValue(1, $candidate_id);
$result = $stmt->execute();
$candidate = $result->fetchArray();

if (!$candidate) {
    header("Location: view_candidates.php");
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_basic'])) {
        // Update basic info
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        $party = trim($_POST['party']);

        // Update candidate
        $stmt = $db->prepare("UPDATE candidates SET name = ?, position = ?, party = ? WHERE id = ?");
        $stmt->bindValue(1, $name);
        $stmt->bindValue(2, $position);
        $stmt->bindValue(3, $party);
        $stmt->bindValue(4, $candidate_id);

        if ($stmt->execute()) {
            $message = "Candidate updated successfully!";
            
            // Refresh candidate data
            $stmt = $db->prepare("SELECT * FROM candidates WHERE id = ?");
            $stmt->bindValue(1, $candidate_id);
            $result = $stmt->execute();
            $candidate = $result->fetchArray();
        } else {
            $message = "Error updating candidate.";
        }
    } elseif (isset($_POST['upload_photo'])) {
        // Handle photo upload
        $photo = $_FILES['photo'];

        if ($photo['error'] === UPLOAD_ERR_OK) {
            $target_dir = "candidates_photos/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $imageFileType = strtolower(pathinfo($photo['name'], PATHINFO_EXTENSION));
            $target_file = $target_dir . "candidate_" . $candidate_id . "." . $imageFileType;

            // Check if image file is actual image
            $check = getimagesize($photo['tmp_name']);
            if ($check !== false) {
                if (move_uploaded_file($photo['tmp_name'], $target_file)) {
                    // Update candidate record to indicate photo exists
                    $stmt = $db->prepare("UPDATE candidates SET photo_path = ? WHERE id = ?");
                    $stmt->bindValue(1, $target_file);
                    $stmt->bindValue(2, $candidate_id);
                    $stmt->execute();
                    
                    $message = "Photo uploaded successfully!";
                    
                    // Refresh candidate data
                    $stmt = $db->prepare("SELECT * FROM candidates WHERE id = ?");
                    $stmt->bindValue(1, $candidate_id);
                    $result = $stmt->execute();
                    $candidate = $result->fetchArray();
                } else {
                    $message = "Error uploading photo.";
                }
            } else {
                $message = "File is not an image.";
            }
        } else {
            $message = "Error uploading photo.";
        }
    } elseif (isset($_POST['delete_photo'])) {
        // Handle photo deletion
        if (!empty($candidate['photo_path']) && file_exists($candidate['photo_path'])) {
            unlink($candidate['photo_path']);
            
            // Remove photo path from database
            $stmt = $db->prepare("UPDATE candidates SET photo_path = NULL WHERE id = ?");
            $stmt->bindValue(1, $candidate_id);
            $stmt->execute();
            
            $message = "Photo deleted successfully!";
            
            // Refresh candidate data
            $stmt = $db->prepare("SELECT * FROM candidates WHERE id = ?");
            $stmt->bindValue(1, $candidate_id);
            $result = $stmt->execute();
            $candidate = $result->fetchArray();
        } else {
            $message = "No photo to delete.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>School Learner Government Election System - Edit Candidate</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background-color: #f5f5f5; 
        }
        .container { 
            background-color: white; 
            padding: 20px; 
            border-radius: 8px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        .form-group { 
            margin: 15px 0; 
        }
        label { 
            display: block; 
            margin-bottom: 5px; 
            font-weight: bold; 
        }
        input, select, textarea { 
            width: 100%; 
            padding: 8px; 
            border: 1px solid #ddd; 
            border-radius: 4px; 
            box-sizing: border-box; 
        }
        button { 
            background-color: #007bff; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin-right: 10px; 
            margin-bottom: 10px;
        }
        button:hover { 
            background-color: #0056b3; 
        }
        .back-link { 
            margin-bottom: 15px; 
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
        .candidate-display {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .candidate-photo-display {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #ddd;
        }
        .no-photo-display {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-weight: bold;
            font-size: 1.5em;
            margin-right: 15px;
            border: 2px solid #ccc;
        }
        .candidate-info-display {
            flex: 1;
        }
        .candidate-name-display {
            font-weight: bold;
            font-size: 1.2em;
            color: #2c3e50;
        }
        .candidate-position-display {
            color: #7f8c8d;
            font-size: 1em;
        }
        .candidate-party-display {
            background: #3498db;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.9em;
            display: inline-block;
            margin-top: 5px;
        }
        .photo-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .photo-upload-form {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }
        .photo-upload-form input[type="file"] {
            flex: 1;
        }
        .danger-btn {
            background-color: #dc3545;
        }
        .danger-btn:hover {
            background-color: #c82333;
        }
        .secondary-btn {
            background-color: #6c757d;
        }
        .secondary-btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="back-link">
            <a href="view_candidates.php">← Back to All Candidates</a>
        </div>
        
        <h2>Edit Candidate</h2>
        
        <?php if ($message): ?>
            <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <!-- Display current candidate info -->
        <div class="candidate-display">
            <?php if (!empty($candidate['photo_path']) && file_exists($candidate['photo_path'])): ?>
                <img src="<?= $candidate['photo_path'] ?>" alt="<?= $candidate['name'] ?>" class="candidate-photo-display">
            <?php else: ?>
                <div class="no-photo-display"><?= strtoupper(substr($candidate['name'], 0, 1)) ?></div>
            <?php endif; ?>
            
            <div class="candidate-info-display">
                <div class="candidate-name-display"><?= htmlspecialchars($candidate['name']) ?></div>
                <div class="candidate-position-display"><?= htmlspecialchars($candidate['position']) ?></div>
                <?php if (!empty($candidate['party'])): ?>
                    <div class="candidate-party-display"><?= htmlspecialchars($candidate['party']) ?></div>
                <?php endif; ?>
                <div>Votes: <?= $candidate['vote_count'] ?></div>
            </div>
        </div>
        
        <!-- Basic Info Form -->
        <h3>Edit Basic Information</h3>
        <form method="POST">
            <input type="hidden" name="update_basic" value="1">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?= htmlspecialchars($candidate['name']) ?>" required>
            </div>
            
            <div class="form-group">
                <label for="position">Position:</label>
                <select id="position" name="position" required>
                    <option value="">Select Position</option>
                    <option value="President" <?= $candidate['position'] === 'President' ? 'selected' : '' ?>>President</option>
                    <option value="Vice President" <?= $candidate['position'] === 'Vice President' ? 'selected' : '' ?>>Vice President</option>
                    <option value="Secretary" <?= $candidate['position'] === 'Secretary' ? 'selected' : '' ?>>Secretary</option>
                    <option value="Treasurer" <?= $candidate['position'] === 'Treasurer' ? 'selected' : '' ?>>Treasurer</option>
                    <option value="Auditor" <?= $candidate['position'] === 'Auditor' ? 'selected' : '' ?>>Auditor</option>
                    <option value="Public Information Officer" <?= $candidate['position'] === 'Public Information Officer' ? 'selected' : '' ?>>Public Information Officer</option>
                    <option value="Protocol Officer" <?= $candidate['position'] === 'Protocol Officer' ? 'selected' : '' ?>>Protocol Officer</option>
                    <option value="Grade 10 Representative" <?= $candidate['position'] === 'Grade 10 Representative' ? 'selected' : '' ?>>Grade 10 Representative</option>
                    <option value="Grade 9 Representative" <?= $candidate['position'] === 'Grade 9 Representative' ? 'selected' : '' ?>>Grade 9 Representative</option>
                    <option value="Grade 8 Representative" <?= $candidate['position'] === 'Grade 8 Representative' ? 'selected' : '' ?>>Grade 8 Representative</option>
                    <option value="Grade 10 Representative 1" <?= $candidate['position'] === 'Grade 10 Representative 1' ? 'selected' : '' ?>>Grade 10 Representative 1</option>
                    <option value="Grade 10 Representative 2" <?= $candidate['position'] === 'Grade 10 Representative 2' ? 'selected' : '' ?>>Grade 10 Representative 2</option>
                    <option value="Grade 9 Representative 1" <?= $candidate['position'] === 'Grade 9 Representative 1' ? 'selected' : '' ?>>Grade 9 Representative 1</option>
                    <option value="Grade 9 Representative 2" <?= $candidate['position'] === 'Grade 9 Representative 2' ? 'selected' : '' ?>>Grade 9 Representative 2</option>
                    <option value="Grade 8 Representative 1" <?= $candidate['position'] === 'Grade 8 Representative 1' ? 'selected' : '' ?>>Grade 8 Representative 1</option>
                    <option value="Grade 8 Representative 2" <?= $candidate['position'] === 'Grade 8 Representative 2' ? 'selected' : '' ?>>Grade 8 Representative 2</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="party">Party:</label>
                <input type="text" id="party" name="party" value="<?= htmlspecialchars($candidate['party']) ?>">
            </div>
            
            <button type="submit">Update Basic Information</button>
        </form>
        
        <!-- Photo Management Section -->
        <div class="photo-section">
            <h3>Manage Photo</h3>
            
            <div class="photo-upload-form">
                <form method="POST" enctype="multipart/form-data" style="flex: 1;">
                    <input type="hidden" name="upload_photo" value="1">
                    <label for="photo">Select Photo:</label>
                    <input type="file" name="photo" id="photo" accept="image/*" required>
                    <button type="submit">Upload Photo</button>
                </form>
            </div>
            
            <?php if (!empty($candidate['photo_path']) && file_exists($candidate['photo_path'])): ?>
                <form method="POST" style="margin-top: 10px;">
                    <input type="hidden" name="delete_photo" value="1">
                    <button type="submit" class="danger-btn">Delete Photo</button>
                </form>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 20px;">
            <a href="view_candidates.php" style="text-decoration: none;">
                <button type="button" class="secondary-btn">Cancel</button>
            </a>
        </div>
    </div>
</body>
</html>