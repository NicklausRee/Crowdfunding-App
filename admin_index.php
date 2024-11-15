<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_config.php';
require_once 'functions.php';
verifyAdminAccess();

// Check if the user is logged in as an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Get admin profile and data for display
$admin = getUserById($conn, $_SESSION['user_id']);
$active_campaigns = getActiveCampaigns($conn);
$pending_campaigns = getPendingCampaigns($conn);
$users = getAllUsers($conn);
$donation_stats = getDonationStats($conn);
$monthly_trends = getMonthlyDonationTrends($conn);
$top_campaigns = getTopCampaigns($conn);
$campaign_stats = getCampaignStats($conn);

// Handle campaign approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve_campaign'])) {
        $campaign_id = $_POST['campaign_id'];
        $admin_id = $_SESSION['user_id'];
        if (updateCampaignStatus($conn, $campaign_id, 'approved', $admin_id)) {
            $_SESSION['success_message'] = "Campaign approved successfully!";
        } else {
            $_SESSION['error_message'] = "Error approving campaign.";
        }
    } elseif (isset($_POST['reject_campaign'])) {
        $campaign_id = $_POST['campaign_id'];
        $admin_id = $_SESSION['user_id'];
        if (isset($_POST['rejection_reason'])) {
            if (rejectCampaign($conn, $campaign_id, $_POST['rejection_reason'], $admin_id)) {
                $_SESSION['success_message'] = "Campaign rejected successfully!";
            } else {
                $_SESSION['error_message'] = "Error rejecting campaign.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReliefKenya Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-none d-md-block bg-light sidebar py-3">
                <div class="sidebar-sticky">
                    <h6 class="sidebar-heading">Admin Menu</h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#dashboard">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#campaigns">
                                <i class="fas fa-clipboard-list"></i> Campaigns
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#users">
                                <i class="fas fa-users"></i> Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#statistics">
                                <i class="fas fa-chart-bar"></i> Statistics
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main role="main" class="col-md-10 ml-sm-auto px-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1>Welcome, <?php echo htmlspecialchars($admin['name']); ?>!</h1>
                </div>

                <!-- Flash Messages -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success" role="alert">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger" role="alert">
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Dashboard Overview -->
                <section id="dashboard" class="mb-4">
                    <div class="row">
                        <!-- Admin Profile Card -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><i class="fas fa-user"></i> Admin Profile</h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">Name: <?php echo htmlspecialchars($admin['name']); ?></p>
                                    <p class="card-text">Email: <?php echo htmlspecialchars($admin['email']); ?></p>
                                    <p class="card-text">Role: <?php echo htmlspecialchars($admin['role']); ?></p>
                                    <p class="card-text">Last Login: <?php echo htmlspecialchars($admin['last_login']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Campaign Statistics Card -->
                        <div class="col-md-8 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0"><i class="fas fa-chart-line"></i> Campaign Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h3><?php echo $campaign_stats['total']; ?></h3>
                                                <p>Total Campaigns</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h3><?php echo $campaign_stats['pending']; ?></h3>
                                                <p>Pending Review</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h3><?php echo $campaign_stats['approved']; ?></h3>
                                                <p>Active Campaigns</p>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-center">
                                                <h3>KES <?php echo number_format($campaign_stats['total_raised']); ?></h3>
                                                <p>Total Raised</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Pending Campaigns Section -->
                <section id="campaigns" class="mb-4">
                    <h2 class="h3 mb-3">Pending Campaigns</h2>
                    <?php if (empty($pending_campaigns)): ?>
                        <div class="alert alert-info">No pending campaigns to review at this time.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Creator</th>
                                        <th>Requested Amount</th>
                                        <th>Date Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending_campaigns as $campaign): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($campaign['title']); ?></td>
                                            <td><?php echo htmlspecialchars($campaign['creator_name']); ?></td>
                                            <td>KES <?php echo number_format($campaign['goal']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($campaign['created_at'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-success" onclick="approveCampaign(<?php echo $campaign['id']; ?>)">
                                                    Approve
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="rejectCampaign(<?php echo $campaign['id']; ?>)">
                                                    Reject
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </section>

                <!-- Monthly Trends Chart -->
                <section id="statistics" class="mb-4">
                    <h2 class="h3 mb-3">Monthly Donation Trends</h2>
                    <div class="card">
                        <div class="card-body">
                            <canvas id="monthlyTrendsChart"></canvas>
                        </div>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <script>
        // Monthly Trends Chart
        var ctx = document.getElementById('monthlyTrendsChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column(array_reverse($monthly_trends), 'month')); ?>,
                datasets: [{
                    label: 'Total Donations (KES)',
                    data: <?php echo json_encode(array_column(array_reverse($monthly_trends), 'total_amount')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'KES ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        function approveCampaign(campaignId) {
            if (confirm('Are you sure you want to approve this campaign?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="campaign_id" value="${campaignId}">
                    <input type="hidden" name="approve_campaign" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function rejectCampaign(campaignId) {
            const reason = prompt('Please provide a reason for rejecting this campaign:');
            if (reason) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="campaign_id" value="${campaignId}">
                    <input type="hidden" name="reject_campaign" value="1">
                    <input type="hidden" name="rejection_reason" value="${reason}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>