<?php

session_start();



require_once 'db_config.php';
require_once 'functions.php';

if (!isset($_SESSION['user_email']) || !isset($_SESSION['user_role'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}


$upload_dir = 'uploads/campaigns/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_email = $_SESSION['user_email'];
    $user_role = $_SESSION['user_role'];

    // Check if the user has the "creator" role
    if ($user_role !== 'creator') {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to create a campaign']);
        exit;
    }

    // Retrieve user ID from the database
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $user_email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $creator_id = $user['id'];

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
    if (isset($_FILES['campaign_image']) && $_FILES['campaign_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/campaigns/'; // Make sure this directory exists and is writable
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        $image_url = secure_file_upload($_FILES['campaign_image'], $allowed_types, $max_size, $upload_dir);
        if (!$image_url) {
            echo json_encode(['success' => false, 'message' => 'Error uploading image: Invalid file type or size']);
            exit;
        }
    }

    // Create campaign
    $campaign_id = createCampaign($conn, $campaign_title, $campaign_description, $campaign_goal, $creator_id, $image_url);

    if ($campaign_id) {
        echo json_encode(['success' => true, 'message' => 'Campaign created successfully and is pending approval']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error creating campaign']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// session_start();
// require_once 'db_config.php';
// require_once 'functions.php';

// $upload_dir = 'uploads/campaigns/';

// if (!isset($_SESSION['user_id'])) {
//     echo json_encode(['success' => false, 'message' => 'User not logged in']);
//     exit;
// }

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     $creator_id = $_SESSION['user_id'];
//     $campaign_title = sanitize_input($_POST['campaign_title']);
//     $campaign_description = sanitize_input($_POST['campaign_description']);
//     $campaign_goal = floatval($_POST['campaign_goal']);

//     // Validate inputs
//     if (empty($campaign_title) || empty($campaign_description) || !validate_number($campaign_goal) || $campaign_goal <= 0) {
//         echo json_encode(['success' => false, 'message' => 'Invalid input data']);
//         exit;
//     }

//     // Handle file upload
//     $image_url = '';
//     if (isset($_FILES['campaign_image']) && $_FILES['campaign_image']['error'] === UPLOAD_ERR_OK) {
//         $upload_dir = 'uploads/campaigns/'; // Make sure this directory exists and is writable
//         $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
//         $max_size = 5 * 1024 * 1024; // 5MB
        
//         $image_url = secure_file_upload($_FILES['campaign_image'], $allowed_types, $max_size, $upload_dir);
//         if (!$image_url) {
//             echo json_encode(['success' => false, 'message' => 'Error uploading image: Invalid file type or size']);
//             exit;
//         }
//     }


//     // Create campaign
//     $campaign_id = createCampaign($campaign_title, $campaign_description, $campaign_goal, $creator_id, $image_url);

//     if ($campaign_id) {
//         echo json_encode(['success' => true, 'message' => 'Campaign created successfully and is pending approval']);
//     } else {
//         echo json_encode(['success' => false, 'message' => 'Error creating campaign']);
//     }
// } else {
//     echo json_encode(['success' => false, 'message' => 'Invalid request method']);
// }

// function createCampaign($title, $description, $goal, $creator_id, $image_url) {
//     global $conn;
//     $stmt = $conn->prepare("INSERT INTO campaigns (title, description, goal, creator_id, image_url, status) VALUES (?, ?, ?, ?, ?, 'pending')");
//     $stmt->bind_param("ssdis", $title, $description, $goal, $creator_id, $image_url);
//     $stmt->execute();
//     return $stmt->insert_id;
// }

// function createCampaign($conn, $title, $description, $goal, $creator_id, $image_url) {
//     $stmt = $conn->prepare("
//         INSERT INTO campaigns 
//         (title, description, goal, creator_id, image_url, status, created_at) 
//         VALUES (?, ?, ?, ?, ?, 'pending', NOW())
//     ");
//     $stmt->bind_param("ssdis", $title, $description, $goal, $creator_id, $image_url);
//     return $stmt->execute() ? $stmt->insert_id : false;
// }
// 

function createCampaign($conn, $title, $description, $goal, $creator_id, $image_url) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO campaigns 
            (title, description, goal, creator_id, image_url, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->bind_param("ssdis", $title, $description, $goal, $creator_id, $image_url);

        if ($stmt->execute()) {
            return $stmt->insert_id;
        } else {
            error_log("Error creating campaign: " . $stmt->error);
            return false;
        }
    } catch (Exception $e) {
        error_log("Exception creating campaign: " . $e->getMessage());
        return false;
    }
}
?>