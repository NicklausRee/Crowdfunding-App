<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// Verify admin access
verifyAdminAccess();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaign_id = $_POST['campaign_id'];
    $action = $_POST['action'];
    $reason = $_POST['reason'] ?? '';

    if ($action === 'approve') {
        $status = 'active';
    } else if ($action === 'reject') {
        $status = 'rejected';
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }

    $result = updateCampaignStatus($conn, $campaign_id, $status, $_SESSION['user_id']);
    if ($result && $action === 'reject') {
        $result = rejectCampaign($conn, $campaign_id, $reason, $_SESSION['user_id']);
    }

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Campaign status updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating campaign status']);
    }
    exit;
}

$pending_campaigns = getPendingCampaigns($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - ReliefKenya</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Admin Panel</h1>
        <h2>Pending Campaigns</h2>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Goal</th>
                    <th>Creator</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pending_campaigns as $campaign): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($campaign['title']); ?></td>
                        <td><?php echo htmlspecialchars(substr($campaign['description'], 0, 100)) . '...'; ?></td>
                        <td>KES <?php echo number_format($campaign['goal'], 2); ?></td>
                        <td><?php echo htmlspecialchars($campaign['creator_name']); ?></td>
                        <td>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-success">Approve</button>
                            </form>
                            <form method="post" class="inline-form">
                                <input type="hidden" name="campaign_id" value="<?php echo $campaign['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <input type="text" name="reason" placeholder="Rejection Reason" required>
                                <button type="submit" class="btn btn-danger">Reject</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>