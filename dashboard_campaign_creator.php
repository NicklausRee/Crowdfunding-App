<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';


// Check if user is logged in and has the 'creator' role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'creator') {
    header("Location: login.php");
    exit();
}



// Get user details and their campaigns
$user = getUserById($conn, $_SESSION['user_id']);
$campaigns = getUserCampaigns($conn, $_SESSION['user_id']);

$active_campaigns = [];
$rejected_campaigns = [];
$approved_campaigns = [];
$pending_campaigns = []; // Add this

foreach ($campaigns as $campaign) {
    switch ($campaign['status']) {
        case 'active':
            $active_campaigns[] = $campaign;
            break;
        case 'rejected':
            $rejected_campaigns[] = $campaign;
            break;
        case 'approved':
            $approved_campaigns[] = $campaign;
            break;
        case 'pending':
            $pending_campaigns[] = $campaign;
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creator Dashboard - ReliefKenya</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>

        <!-- Creator Profile -->
        <div class="dashboard-card">
            <h3><i class="fas fa-user"></i> Your Profile</h3>
            <p>Name: <?php echo htmlspecialchars($user['name']); ?></p>
            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
            <p>Member since: <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
        </div>

        <!-- Campaign Statistics -->
        <div class="dashboard-card">
            <h3><i class="fas fa-chart-line"></i> Campaign Statistics</h3>
            <!-- Add campaign statistics here -->
        </div>

        <!-- Create New Campaign -->
        <div class="dashboard-card">
            <h3><i class="fas fa-plus-circle"></i> Quick Actions</h3>
            <p><a href="create_campaign.php" class="cta-button">Create New Campaign</a></p>
        </div>

        <!-- Campaign List -->
        <div class="campaign-list">
            <h2><i class="fas fa-list"></i> Your Campaigns</h2>

            <!-- Active Campaigns -->
            <div style="margin-bottom: 2rem;">
            <h3>Active Campaigns</h3>
            <?php foreach ($active_campaigns as $campaign): ?>
                <?php if ($campaign['status'] === 'active'): ?>
                    <div class="campaign-item-wrapper">
                    <div class="campaign-item">
                        <?php if (!empty($campaign['image_url'])): ?>
                            <img src="uploads/campaigns/<?php echo htmlspecialchars($campaign['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($campaign['title']); ?>" 
                                 class="campaign-photo"
                                 onerror="this.src='assets/default-campaign.jpg'">
                        <?php else: ?>
                            <img src="assets/default-campaign.jpg" 
                                 alt="Default campaign image" 
                                 class="campaign-photo">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($campaign['title']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($campaign['description'], 0, 150)) . '...'; ?></p>
                        <div class="progress-bar">
                            <?php
                            $percentage = ($campaign['current_amount'] / $campaign['goal']) * 100;
                            $percentage = min($percentage, 100);
                            ?>
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="stats">
                            <span>Goal: KES <?php echo number_format($campaign['goal'], 2); ?></span>
                            <span>Raised: KES <?php echo number_format($campaign['current_amount'], 2); ?></span>
                            <span><?php echo number_format($percentage, 1); ?>% Complete</span>
                        </div>
                        <div style="margin-top: 15px;">
                            <a href="campaign_updates.php?id=<?php echo $campaign['id']; ?>" class="cta-button">
                                <i class="fas fa-edit"></i> Post Update
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Pending Campaigns -->
            <div style="margin-bottom: 2rem;">
            <h3>Pending Campaigns</h3>
            <?php foreach ($pending_campaigns as $campaign): ?>
                <div class="campaign-item-wrapper">
                <div class="campaign-item">
                    <?php if (!empty($campaign['image_url'])): ?>
                        <img src="uploads/campaigns/<?php echo htmlspecialchars($campaign['image_url']); ?>" 
                            alt="<?php echo htmlspecialchars($campaign['title']); ?>" 
                            class="campaign-photo"
                            onerror="this.src='assets/default-campaign.jpg'">
                    <?php else: ?>
                        <img src="assets/default-campaign.jpg" 
                            alt="Default campaign image" 
                            class="campaign-photo">
                    <?php endif; ?>
                    <h3><?php echo htmlspecialchars($campaign['title']); ?></h3>
                    <p><?php echo htmlspecialchars(substr($campaign['description'], 0, 150)) . '...'; ?></p>
                    <div class="stats">
                        <span>Goal: KES <?php echo number_format($campaign['goal'], 2); ?></span>
                        <span>Status: Pending Approval</span>
                    </div>
                </div>
            <?php endforeach; ?>

             <!-- Rejected Campaigns -->
            <div style="margin-bottom: 2rem;">
            <h3>Rejected Campaigns</h3>
            <?php foreach ($rejected_campaigns as $campaign): ?>
                <?php if ($campaign['status'] === 'rejected'): ?>
                    <div class="campaign-item-wrapper">
                    <div class="campaign-item">
                        <?php if (!empty($campaign['image_url'])): ?>
                            <img src="uploads/campaigns/<?php echo htmlspecialchars($campaign['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($campaign['title']); ?>" 
                                 class="campaign-photo"
                                 onerror="this.src='assets/default-campaign.jpg'">
                        <?php else: ?>
                            <img src="assets/default-campaign.jpg" 
                                 alt="Default campaign image" 
                                 class="campaign-photo">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($campaign['title']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($campaign['description'], 0, 150)) . '...'; ?></p>
                        <?php if (isset($campaign['rejection_reason'])): ?>
                            <p class="text-red-500">Reason for rejection: <?php echo htmlspecialchars($campaign['rejection_reason']); ?></p>
                        <?php endif; ?>
                        <!-- <p class="text-red-500">Reason for rejection: <?php echo htmlspecialchars($campaign['rejection_reason']); ?></p> -->
                        <div class="stats">
                            <span>Goal: KES <?php echo number_format($campaign['goal'], 2); ?></span>
                            <span>Raised: KES <?php echo number_format($campaign['current_amount'], 2); ?></span>
                            <span>Status: Rejected</span>
                        </div>
                        <div style="margin-top: 15px;">
                            <button class="cta-button" disabled>
                                <i class="fas fa-edit"></i> Post Update
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>

            <!-- Approved Campaigns -->
            <div style="margin-bottom: 2rem;">
            <h3>Approved Campaigns</h3>
            <?php foreach ($approved_campaigns as $campaign): ?>
                <?php if ($campaign['status'] === 'approved'): ?>
                    <div class="campaign-item-wrapper">
                    <div class="campaign-item">
                        <?php if (!empty($campaign['image_url'])): ?>
                            <img src="uploads/campaigns/<?php echo htmlspecialchars($campaign['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($campaign['title']); ?>" 
                                 class="campaign-photo"
                                 onerror="this.src='assets/default-campaign.jpg'">
                        <?php else: ?>
                            <img src="assets/default-campaign.jpg" 
                                 alt="Default campaign image" 
                                 class="campaign-photo">
                        <?php endif; ?>
                        <h3><?php echo htmlspecialchars($campaign['title']); ?></h3>
                        <p><?php echo htmlspecialchars(substr($campaign['description'], 0, 150)) . '...'; ?></p>
                        <div class="progress-bar">
                            <?php
                            $percentage = ($campaign['current_amount'] / $campaign['goal']) * 100;
                            $percentage = min($percentage, 100);
                            ?>
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="stats">
                            <span>Goal: KES <?php echo number_format($campaign['goal'], 2); ?></span>
                            <span>Raised: KES <?php echo number_format($campaign['current_amount'], 2); ?></span>
                            <span><?php echo number_format($percentage, 1); ?>% Complete</span>
                            <span>Status: Approved</span>
                        </div>
                        <div style="margin-top: 15px;">
                            <a href="campaign_updates.php?id=<?php echo $campaign['id']; ?>" class="cta-button">
                                <i class="fas fa-edit"></i> Post Update
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <p><a href="logout.php">Logout</a></p>
    </div>

    <script>
        // Add any necessary JavaScript here
    </script>
</body>
</html>