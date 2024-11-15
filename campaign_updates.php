<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard_campaign_creator.php");
    exit();
}

$campaign_id = $_GET['id'];
$campaign = getCampaignById($conn, $campaign_id);

if ($campaign['creator_id'] != $_SESSION['user_id']) {
    header("Location: dashboard_campaign_creator.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $update_text = $_POST['update_text'];
    $result = addCampaignUpdate($conn, $campaign_id, $update_text);
    if ($result) {
        $success = "Update posted successfully!";
    } else {
        $error = "Error posting update.";
    }
}

$updates = getCampaignUpdates($conn, $campaign_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campaign Updates - ReliefKenya</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Updates for: <?php echo htmlspecialchars($campaign['title']); ?></h2>
        
        <?php if (isset($success)) echo "<p class='success'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        
        <form method="post">
            <div class="form-group">
                <label for="update_text">New Update:</label>
                <textarea id="update_text" name="update_text" required></textarea>
            </div>
            <button type="submit" class="cta-button">Post Update</button>
        </form>
        
        <h3>Previous Updates</h3>
        <?php if (empty($updates)): ?>
            <p>No updates posted yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($updates as $update): ?>
                    <li>
                        <p><?php echo htmlspecialchars($update['update_text']); ?></p>
                        <small>Posted on: <?php echo $update['created_at']; ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        
        <p><a href="dashboard_campaign_creator.php">Back to Dashboard</a></p>
    </div>
</body>
</html>