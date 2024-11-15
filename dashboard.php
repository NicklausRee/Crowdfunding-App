<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    // Set an error message in session
    $_SESSION['error_message'] = "Please login to access the dashboard";
    // Redirect to login page
    header("Location: login.php");
    exit();
}

// if (!isset($_SESSION['user_id'])) {
//     header("Location: login.php");
//     exit();
// }

$user = getUserById($conn, $_SESSION['user_id']);
$user_campaigns = getUserCampaigns($conn, $_SESSION['user_id']);
$user_donations = getUserDonations($conn, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ReliefKenya</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Welcome to your logout dashboard <?php echo htmlspecialchars($user['name']); ?>!</h2>
        
        <!-- <h3>Your Campaigns</h3>
        <?php if (empty($user_campaigns)): ?>
            <p>You haven't created any campaigns yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($user_campaigns as $campaign): ?>
                    <li>
                        <?php echo htmlspecialchars($campaign['title']); ?> - 
                        <?php echo $campaign['status']; ?> - 
                        <a href="edit_campaign.php?id=<?php echo $campaign['id']; ?>">Edit</a> - 
                        <a href="campaign_updates.php?id=<?php echo $campaign['id']; ?>">Post Update</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <a href="create_campaign.php" class="cta-button">Create New Campaign</a>
        
        <h3>Your Donations</h3>
        <?php if (empty($user_donations)): ?>
            <p>You haven't made any donations yet.</p>
        <?php else: ?>
            <ul>
                <?php foreach ($user_donations as $donation): ?>
                    <li>
                        <?php echo htmlspecialchars($donation['campaign_title']); ?> - 
                        KES <?php echo number_format($donation['amount'], 2); ?> - 
                        <?php echo $donation['status']; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <a href="index.php#campaignsSection" class="cta-button">Explore Campaigns</a>
         -->
        <p><a href="logout.php">Logout</a></p>
    </div>
</body>
</html>