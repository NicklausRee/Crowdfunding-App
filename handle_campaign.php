<?php
// handle_campaign.php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['action']) || !isset($data['campaign_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$campaign_id = $data['campaign_id'];
$action = $data['action'];
$admin_id = $_SESSION['user_id'];

// Handle the campaign action
$result = handleCampaignAction($conn, $campaign_id, $action, $admin_id);

if ($result) {
    // If campaign was rejected, store the reason
    if ($action === 'reject' && isset($data['reason'])) {
        $sql = "UPDATE campaigns SET rejection_reason = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $data['reason'], $campaign_id);
        $stmt->execute();
    }
    
    // Send notification to campaign creator
    $campaign = getCampaignById($conn, $campaign_id);
    $creator = getUserById($conn, $campaign['creator_id']);
    $status = ($action === 'approve') ? 'approved' : 'rejected';
    
    // You can implement email notifications here
    // sendNotificationEmail($creator['email'], $status, $campaign['title']);
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error updating campaign status']);
}


function handleCampaignAction($conn, $campaign_id, $action, $admin_id) {
    if ($action === 'approve') {
        if (updateCampaignStatus($conn, $campaign_id, 'approved', $admin_id)) {
            error_log("Campaign ID $campaign_id approved by admin ID $admin_id");
            return true;
        }
    } elseif ($action === 'reject') {
        if (rejectCampaign($conn, $campaign_id, $reason, $admin_id)) {
            error_log("Campaign ID $campaign_id rejected by admin ID $admin_id");
            return true;
        }
    }
    error_log("Error updating campaign status for campaign ID $campaign_id");
    return false;
}

function updateCampaignStatus($conn, $campaign_id, $status, $admin_id) {
    $sql = "UPDATE campaigns 
            SET status = ?, 
                reviewed_by = ?, 
                reviewed_at = NOW() 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status, $admin_id, $campaign_id);
    return $stmt->execute();
}