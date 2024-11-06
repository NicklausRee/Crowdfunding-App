<?php
// In process_campaign.php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $creator_id = $_SESSION['user_id'];
    $campaign_title = sanitize_input($_POST['campaign_title']);
    $campaign_description = sanitize_input($_POST['campaign_description']);
    $campaign_goal = floatval($_POST['campaign_goal']);

    // Validate inputs
    if (empty($campaign_title) || empty($campaign_description) || !validate_number($campaign_goal) || $campaign_goal <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }

    // Handle file upload
    $image_url = '';
    if (isset($_FILES['campaign_image'])) {
        $upload_dir = 'uploads/';
        $image_url = secure_file_upload($_FILES['campaign_image'], ['jpg', 'jpeg', 'png', 'gif'], 5000000, $upload_dir);
        if (!$image_url) {
            echo json_encode(['success' => false, 'message' => 'Error uploading image']);
            exit;
        }
    }

    // Create campaign
    $campaign_id = createCampaign($campaign_title, $campaign_description, $campaign_goal, $creator_id, $image_url);

    if ($campaign_id) {
        echo json_encode(['success' => true, 'message' => 'Campaign created successfully and is pending approval']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating campaign']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

function createCampaign($title, $description, $goal, $creator_id, $image_url) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO campaigns (title, description, goal, creator_id, image_url, status) VALUES (?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("ssdis", $title, $description, $goal, $creator_id, $image_url);
    $stmt->execute();
    return $stmt->insert_id;
}
?>