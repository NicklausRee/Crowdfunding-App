<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';


// Check if user is logged in and has the 'donor' role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: login.php");
    exit();
}

// Get user details and their donations
$user = getUserById($conn, $_SESSION['user_id']);
$donations = getUserDonations($conn, $_SESSION['user_id']);

// Get active campaigns
$active_campaigns = getActiveCampaigns($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - ReliefKenya</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-home"></i> Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>

        <!-- Donor Profile -->
        <div class="dashboard-card">
            <h3><i class="fas fa-user"></i> Your Profile</h3>
            <div class="card-content">
                <div class="profile-info">
                    <div>
                        <p class="label">Name :
                        <?php echo htmlspecialchars($user['name']); ?></p>
                    </div>
                    <div>
                        <p class="label">Email :
                        <?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <div>
                        <p class="label">Member since :
                        <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Donation History -->
        <div class="dashboard-card">
            <h3><i class="fas fa-history"></i> Your Donations</h3>
            <div class="card-content">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($donations as $donation): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($donation['campaign_title']); ?></td>
                                <td>KES <?php echo number_format($donation['amount']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($donation['status']); ?>">
                                        <?php echo ucfirst($donation['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Active Campaigns -->
        <div class="dashboard-card">
            <h3><i class="fas fa-heart"></i> Support Active Campaigns</h3>
            <div class="card-content">
                <?php if (empty($active_campaigns)): ?>
                    <p>No active campaigns available at this time.</p>
                <?php else: ?>
                    <div class="campaign-list">
                        <?php foreach ($active_campaigns as $campaign): ?>
                            <div class="campaign-item" style="margin-bottom: 1.5rem; padding: 1rem; border: 1px solid #e5e5e5; border-radius: 0.5rem;">
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
                                <h4><?php echo htmlspecialchars($campaign['title']); ?></h4>
                                <p><?php echo htmlspecialchars($campaign['description']); ?></p>
                                <div class="progress-bar">
                                    <?php
                                    $percentage = ($campaign['current_amount'] / $campaign['goal']) * 100;
                                    $percentage = min($percentage, 100);
                                    ?>
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                                <div class="campaign-stats">
                                    <span>Goal: KES <?php echo number_format($campaign['goal']); ?></span>
                                    <span>Raised: KES <?php echo number_format($campaign['current_amount']); ?></span>
                                </div>
                                <div>
                                    <a href="donate.php?id=<?php echo $campaign['id']; ?>" class="cta-button">
                                        <i class="fas fa-donate"></i> Donate Now
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- <div style="text-align: center; margin-top: 2rem;">
            <a href="logout.php" class="cta-button">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div> -->
        <div class="cta-section">
            <h3>Ready to Make Another Impact?</h3>
            <p>Explore our current campaigns and help make a difference in someone's life today.</p>
            <a href="index.php#campaignsSection" class="cta-button">View Active Campaigns</a>
        </div>

        <p><a href="logout.php">Logout</a></p>
    </div>
    <script>
        // Add smooth scrolling for the campaigns link
        document.querySelector('a[href*="#campaignsSection"]').addEventListener('click', function(e) {
            e.preventDefault();
            
            // First navigate to index.php
            window.location.href = 'index.php#campaignsSection';
        });
    </script>

</body>
</html>