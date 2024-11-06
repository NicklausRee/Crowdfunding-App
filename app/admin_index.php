<?php
session_start();
require_once 'db_config.php';

// Check if the user is logged in as an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Function to get pending campaigns
function getPendingCampaigns($conn) {
    $sql = "SELECT * FROM campaigns WHERE status = 'pending'";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to approve or reject a campaign
function updateCampaignStatus($conn, $campaign_id, $status) {
    $sql = "UPDATE campaigns SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $campaign_id);
    return $stmt->execute();
}

// Function to get all users
function getAllUsers($conn) {
    $sql = "SELECT * FROM users";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to update user role
function updateUserRole($conn, $user_id, $new_role) {
    $sql = "UPDATE users SET role = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $new_role, $user_id);
    return $stmt->execute();
}

// Function to ban/unban user
function updateUserStatus($conn, $user_id, $status) {
    $sql = "UPDATE users SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $user_id);
    return $stmt->execute();
}

// Function to get donation statistics
function getDonationStats($conn) {
    $sql = "SELECT 
                SUM(amount) as total_donations, 
                COUNT(*) as donation_count,
                AVG(amount) as average_donation,
                MAX(amount) as largest_donation
            FROM donations";
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

// Function to get monthly donation trends
function getMonthlyDonationTrends($conn) {
    $sql = "SELECT 
                DATE_FORMAT(donation_date, '%Y-%m') as month,
                SUM(amount) as total_amount,
                COUNT(*) as donation_count
            FROM donations
            GROUP BY DATE_FORMAT(donation_date, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Function to get top campaigns
function getTopCampaigns($conn) {
    $sql = "SELECT 
                c.title, 
                SUM(d.amount) as total_raised, 
                COUNT(d.id) as donation_count
            FROM campaigns c
            JOIN donations d ON c.id = d.campaign_id
            GROUP BY c.id
            ORDER BY total_raised DESC
            LIMIT 5";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['approve_campaign'])) {
        updateCampaignStatus($conn, $_POST['campaign_id'], 'approved');
    } elseif (isset($_POST['reject_campaign'])) {
        updateCampaignStatus($conn, $_POST['campaign_id'], 'rejected');
    } elseif (isset($_POST['update_user_role'])) {
        updateUserRole($conn, $_POST['user_id'], $_POST['new_role']);
    } elseif (isset($_POST['ban_user'])) {
        updateUserStatus($conn, $_POST['user_id'], 'banned');
    } elseif (isset($_POST['unban_user'])) {
        updateUserStatus($conn, $_POST['user_id'], 'active');
    }
}

// Get data for display
$pending_campaigns = getPendingCampaigns($conn);
$users = getAllUsers($conn);
$donation_stats = getDonationStats($conn);
$monthly_trends = getMonthlyDonationTrends($conn);
$top_campaigns = getTopCampaigns($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReliefKenya Admin Panel</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container mt-5">
        <h1>ReliefKenya Admin Panel</h1>
        
        <!-- Rest of the HTML content as provided in the previous response -->
        
    </div>

    <script>
        // Monthly Trends Chart
        var ctx = document.getElementById('monthlyTrendsChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column(array_reverse($monthly_trends), 'month')); ?>,
                datasets: [{
                    label: 'Total Donations',
                    data: <?php echo json_encode(array_column(array_reverse($monthly_trends), 'total_amount')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>