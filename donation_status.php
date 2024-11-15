<?php
// donation_status.php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['request'])) {
    header("Location: dashboard_donor.php");
    exit();
}

$checkoutRequestID = $_GET['request'];

// Get donation attempt details
$stmt = $conn->prepare("SELECT da.*, c.title as campaign_title 
                       FROM donation_attempts da 
                       JOIN campaigns c ON da.campaign_id = c.id 
                       WHERE da.checkout_request_id = ? AND da.user_id = ?");
$stmt->bind_param("si", $checkoutRequestID, $_SESSION['user_id']);
$stmt->execute();
$donation = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$donation) {
    header("Location: dashboard_donor.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donation Status - ReliefKenya</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta http-equiv="refresh" content="10">
</head>
<body>
    <div class="container">
        <div class="dashboard-card">
            <h2><i class="fas fa-info-circle"></i> Donation Status</h2>
            
            <div class="card-content">
                <h3>Campaign: <?php echo htmlspecialchars($donation['campaign_title']); ?></h3>
                <p>Amount: KES <?php echo number_format($donation['amount'], 2); ?></p>
                <p>Phone: <?php echo htmlspecialchars($donation['phone']); ?></p>
                
                <div class="status-container">
                    <?php if ($donation['status'] === 'pending'): ?>
                        <p class="status pending">
                            <i class="fas fa-spinner fa-spin"></i> 
                            Waiting for M-PESA payment confirmation...
                        </p>
                        <p class="hint">Please check your phone and enter your M-PESA PIN to complete the donation.</p>
                    <?php elseif ($donation['status'] === 'completed'): ?>
                        <p class="status success">
                            <i class="fas fa-check-circle"></i>
                            Donation successful! Thank you for your contribution.
                        </p>
                    <?php else: ?>
                        <p class="status failed">
                            <i class="fas fa-times-circle"></i>
                            Donation failed: <?php echo htmlspecialchars($donation['failure_reason']); ?>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <?php if ($donation['status'] === 'failed'): ?>
                        <a href="donate.php?id=<?php echo $donation['campaign_id']; ?>" class="cta-button">
                            <i class="fas fa-redo"></i> Try Again
                        </a>
                    <?php endif; ?>
                    <a href="dashboard_donor.php" class="secondary-button">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>