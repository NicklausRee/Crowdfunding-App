<?php
require_once 'db_config.php'; 




// function sanitize_input($input) {
//     if (is_array($input)) {
//         return array_map('sanitize_input', $input);
//     }
//     return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
// }


function processDonation($name, $phone, $email, $amount, $campaign_id) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        $stmt = $conn->prepare("INSERT INTO donations (campaign_id, donor_name, donor_phone, donor_email, amount, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("isssd", $campaign_id, $name, $phone, $email, $amount);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert donation");
        }
        
        $donation_id = $stmt->insert_id;
        
        // Update campaign goal
        $stmt = $conn->prepare("UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $campaign_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update campaign amount");
        }
        
        $conn->commit();
        return true;
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Donation processing error: " . $e->getMessage());
        return false;
    }
}

function getAllUsers($conn) {
    // Prepare SQL to get all users with their role and last login
    $sql = "SELECT 
            u.id,
            u.name,
            u.email,
            u.role,
            u.last_login,
            u.created_at,
            COUNT(DISTINCT d.id) as total_donations,
            COUNT(DISTINCT c.id) as total_campaigns
        FROM users u
        LEFT JOIN donations d ON u.id = d.user_id AND d.status = 'completed'
        LEFT JOIN campaigns c ON u.id = c.creator_id
        GROUP BY u.id
        ORDER BY u.created_at DESC";
    
    try {
        $result = $conn->query($sql);
        if ($result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        } else {
            error_log("Error fetching users: " . $conn->error);
            return [];
        }
    } catch (Exception $e) {
        error_log("Exception while fetching users: " . $e->getMessage());
        return [];
    }
}

function getDonationStats($conn) {
    $stats = [
        'total_amount' => 0,
        'total_donations' => 0,
        'average_donation' => 0,
        'successful_rate' => 0,
        'this_month' => [
            'amount' => 0,
            'count' => 0
        ],
        'last_month' => [
            'amount' => 0,
            'count' => 0
        ],
        'by_status' => [
            'completed' => 0,
            'pending' => 0,
            'failed' => 0
        ]
    ];
    
    // Get overall donation statistics
    $sql = "SELECT 
            COUNT(*) as total_donations,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount,
            AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END) as average_donation,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) * 100.0 / COUNT(*) as success_rate,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_count
            FROM donations";
            
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['total_donations'] = (int)$row['total_donations'];
        $stats['total_amount'] = (float)$row['total_amount'];
        $stats['average_donation'] = round((float)$row['average_donation'], 2);
        $stats['successful_rate'] = round((float)$row['success_rate'], 1);
        $stats['by_status']['completed'] = (int)$row['completed_count'];
        $stats['by_status']['pending'] = (int)$row['pending_count'];
        $stats['by_status']['failed'] = (int)$row['failed_count'];
    }
    
    // Get this month's statistics
    $sql = "SELECT 
            COUNT(*) as donation_count,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount
            FROM donations 
            WHERE YEAR(created_at) = YEAR(CURRENT_DATE)
            AND MONTH(created_at) = MONTH(CURRENT_DATE)";
            
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['this_month']['count'] = (int)$row['donation_count'];
        $stats['this_month']['amount'] = (float)$row['total_amount'];
    }
    
    // Get last month's statistics
    $sql = "SELECT 
            COUNT(*) as donation_count,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount
            FROM donations 
            WHERE created_at >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 1 MONTH)
            AND created_at < DATE_FORMAT(NOW(), '%Y-%m-01')";
            
    $result = $conn->query($sql);
    if ($result) {
        $row = $result->fetch_assoc();
        $stats['last_month']['count'] = (int)$row['donation_count'];
        $stats['last_month']['amount'] = (float)$row['total_amount'];
    }
    
    return $stats;
}

function getTopCampaigns($conn, $limit = 5) {
    // Get campaigns ordered by amount raised (current_amount)
    $sql = "SELECT 
            c.*,
            u.name as creator_name,
            COUNT(DISTINCT d.user_id) as donor_count,
            (c.current_amount / c.goal * 100) as completion_percentage
        FROM campaigns c
        LEFT JOIN users u ON c.creator_id = u.id
        LEFT JOIN donations d ON c.id = d.campaign_id AND d.status = 'completed'
        WHERE c.status = 'active'
        GROUP BY c.id
        ORDER BY c.current_amount DESC
        LIMIT ?";
        
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing statement: " . $conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $campaigns = [];
    while ($row = $result->fetch_assoc()) {
        // Format some fields for display
        $row['completion_percentage'] = round($row['completion_percentage'], 1);
        $campaigns[] = $row;
    }
    
    $stmt->close();
    return $campaigns;
}


// function secure_file_upload($file, $allowed_types, $max_size, $upload_dir) {
//     // Ensure upload directory exists and is writable
//     if (!file_exists($upload_dir)) {
//         mkdir($upload_dir, 0777, true);
//     }

//     // Validate file type
//     $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
//     if (!in_array($file_extension, $allowed_types)) {
//         return false;
//     }

//     // Validate file size
//     if ($file['size'] > $max_size) {
//         return false;
//     }

//     // Generate unique filename
//     $new_filename = uniqid() . '.' . $file_extension;
//     $upload_path = $upload_dir . $new_filename;

//     // Move uploaded file
//     if (move_uploaded_file($file['tmp_name'], $upload_path)) {
//         return $upload_path;
//     }

//     return false;
// }

function secure_file_upload($file, $allowed_types, $max_size, $upload_dir) {
    // Ensure the upload directory exists and is writable
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return "Error: Failed to create upload directory.";
        }
    }

    // Validate file type
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_types)) {
        return "Error: Invalid file type.";
    }

    // Validate file size
    if ($file['size'] > $max_size) {
        return "Error: File size exceeds the maximum allowed limit.";
    }

    // Generate a unique filename to prevent overwriting
    $new_filename = uniqid('upload_', true) . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;

    // Move uploaded file to the designated directory
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Return a relative or web-accessible path
        return str_replace($_SERVER['DOCUMENT_ROOT'], '', $upload_path);
    }

    return "Error: Failed to move uploaded file.";
}

// function createDonation($conn, $donor_id, $campaign_id, $amount) {
//     try {
//         // Start transaction
//         $conn->begin_transaction();

//         // Fetch the donor's last login timestamp
//         $stmt = $conn->prepare("SELECT last_login FROM donors WHERE id = ?");
//         $stmt->bind_param("i", $donor_id);
//         $stmt->execute();
//         $result = $stmt->get_result();
//         $donor = $result->fetch_assoc();
//         $last_login = $donor['last_login'];

//         // Insert donation record
//         $stmt = $conn->prepare("INSERT INTO donations (donor_id, campaign_id, amount, status, created_at, donor_last_login) VALUES (?, ?, ?, 'pending', NOW(), ?)");
//         $stmt->bind_param("iids", $donor_id, $campaign_id, $amount, $last_login);
//         if (!$stmt->execute()) {
//             throw new Exception("Failed to create donation: " . $stmt->error);
//         }

//         $donation_id = $conn->insert_id;

//         // Update campaign amount
//         $updateStmt = $conn->prepare("UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?");
//         $updateStmt->bind_param("di", $amount, $campaign_id);
//         if (!$updateStmt->execute()) {
//             throw new Exception("Failed to update campaign amount: " . $updateStmt->error);
//         }

//         // Commit transaction
//         $conn->commit();

//         return [
//             'id' => $donation_id,
//             'donor_id' => $donor_id,
//             'campaign_id' => $campaign_id,
//             'amount' => $amount,
//             'donor_last_login' => $last_login
//         ];
//     } catch (Exception $e) {
//         // Rollback on error
//         $conn->rollback();
//         error_log("Donation creation failed: " . $e->getMessage());
//         throw $e;
//     }
// }



function updateDonationMerchantRequestID($donation_id, $merchantRequestID) {
    global $conn;
    $stmt = $conn->prepare("UPDATE donations SET merchant_request_id = ? WHERE id = ?");
    $stmt->bind_param("si", $merchantRequestID, $donation_id);
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        error_log("Error updating donation merchant request ID: " . $stmt->error);
        $stmt->close();
        return false;
    }
}

function updateCampaignProgress($campaign_id) {
    global $conn;
    $stmt = $conn->prepare("
        UPDATE campaigns c
        SET c.current_amount = (
            SELECT COALESCE(SUM(d.amount), 0)
            FROM donations d
            WHERE d.campaign_id = c.id AND d.status = 'completed'
        )
        WHERE c.id = ?
    ");
    $stmt->bind_param("i", $campaign_id);
    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        error_log("Error updating campaign progress: " . $stmt->error);
        $stmt->close();
        return false;
    }
}
 
// function updateDonationMerchantRequestID($donation_id, $merchantRequestID) {
//     global $conn;
//     $stmt = $conn->prepare("UPDATE donations SET merchant_request_id = ? WHERE id = ?");
//     $stmt->bind_param("si", $merchantRequestID, $donation_id);
//     $stmt->execute();
// }

function createUser($conn, $name, $email, $password, $role = 'donor') {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
    if (!$stmt) {
        error_log("Error preparing statement: " . $conn->error);
        return false;
    }
    
    
    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();
        return $user_id;
    } else {
        error_log("Error executing statement: " . $stmt->error);
        $stmt->close();
        return false;
    }
}


function getUserByEmail($conn, $email) {
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing statement: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    return $user;
}

function getUserById($conn, $id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getUserCampaigns($conn, $user_id) {
    $sql = "SELECT c.*, 
            CASE 
                WHEN c.status = 'rejected' THEN cr.reason 
                ELSE NULL 
            END as rejection_reason
            FROM campaigns c
            LEFT JOIN campaign_rejections cr ON c.id = cr.campaign_id 
            WHERE c.creator_id = ? AND c.status IN ('active', 'pending', 'rejected', 'approved')";
    // $stmt = $conn->prepare($sql);
    $stmt = $conn->prepare("SELECT * FROM campaigns WHERE creator_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}


function getUserDonations($conn, $user_id) {
    // First check if the user exists
    $user_check = $conn->prepare("SELECT id FROM users WHERE id = ?");
    if (!$user_check) {
        error_log("Error preparing user check statement: " . $conn->error);
        return [];
    }
    
    $user_check->bind_param("i", $user_id);
    $user_check->execute();
    $user_result = $user_check->get_result();
    
    if ($user_result->num_rows === 0) {
        error_log("User not found: " . $user_id);
        return [];
    }

    // Get donations with campaign information
    $sql = "SELECT d.*, c.title as campaign_title 
            FROM donations d 
            JOIN campaigns c ON d.campaign_id = c.id 
            WHERE d.user_id = ? 
            ORDER BY d.created_at DESC";
            
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Error preparing donations statement: " . $conn->error);
        return [];
    }

    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $donations = [];
    while ($row = $result->fetch_assoc()) {
        $donations[] = $row;
    }
    
    $stmt->close();
    return $donations;
}


function addCampaignUpdate($conn, $campaign_id, $update_text) {
    $sql = "INSERT INTO campaign_updates (campaign_id, update_text) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $campaign_id, $update_text);
    return $stmt->execute();
}

function getCampaignUpdates($conn, $campaign_id) {
    $sql = "SELECT * FROM campaign_updates WHERE campaign_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $campaign_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getDonorByEmail($email) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM donors WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function createDonor($name, $phone, $email) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO donors (name, phone, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $phone, $email);
    $stmt->execute();
    return $stmt->insert_id;
}

function getCampaignById($conn, $campaign_id) {
    $stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $campaign_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// function getCampaignById($conn, $campaign_id) {
//     $stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ?");
//     $stmt->bind_param("i", $campaign_id);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $campaign = $result->fetch_assoc();
//     $stmt->close();
//     return $campaign;
// }

function getDonationsByCampaign($campaign_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM donations WHERE campaign_id = ?");
    $stmt->bind_param("i", $campaign_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// function updateCampaignProgress($campaign_id) {
//     global $conn;
//     $stmt = $conn->prepare("
//         UPDATE campaigns c
//         SET c.current_amount = (
//             SELECT COALESCE(SUM(d.amount), 0)
//             FROM donations d
//             WHERE d.campaign_id = c.id AND d.status = 'completed'
//         )
//         WHERE c.id = ?
//     ");
//     $stmt->bind_param("i", $campaign_id);
//     $stmt->execute();
// }

// function createCampaign($name, $description, $goal, $creator_id) {
//     global $conn;
//     $stmt = $conn->prepare("INSERT INTO campaigns (title, description, goal, creator_id, current_amount, status) VALUES (?, ?, ?, ?, 0, 'pending')");
//     $stmt->bind_param("ssdi", $name, $description, $goal, $creator_id);
//     $stmt->execute();
//     return $stmt->insert_id;
// }

// function createCampaign($name, $description, $goal, $creator_id) {
//     global $conn;
//     $stmt = $conn->prepare("INSERT INTO campaigns (name, description, goal, creator_id, current_amount, status) VALUES (?, ?, ?, ?, 0, 'pending')");
//     $stmt->bind_param("ssdi", $name, $description, $goal, $creator_id);
//     $stmt->execute();
//     return $stmt->insert_id;
// }


// function formatMpesaPhoneNumber($phone) {
//     // Remove any non-numeric characters
//     $phone = preg_replace('/[^0-9]/', '', $phone);
    
//     // If number starts with 0, replace with 254
//     if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
//         $phone = '254' . substr($phone, 1);
//     }
    
//     // If number starts with +254, remove the +
//     if (substr($phone, 0, 4) === '+254') {
//         $phone = substr($phone, 1);
//     }
    
//     // Validate final format
//     if (!preg_match('/^254[7][0-9]{8}$/', $phone)) {
//         throw new Exception('Invalid phone number format. Must be in format 254XXXXXXXXX');
//     }
    
//     return $phone;
// }

// function testMpesaConfiguration() {
//     try {
//         $mpesa = new MpesaAPI();
//         $access_token = $mpesa->getAccessToken();
//         error_log('M-PESA Configuration Test - Access Token: ' . $access_token);
//         return true;
//     } catch (Exception $e) {
//         error_log('M-PESA Configuration Test Failed: ' . $e->getMessage());
//         return false;
//     }
// }

function getActiveCampaigns($conn) {
    $sql = "SELECT * FROM campaigns WHERE status = 'active' ORDER BY created_at DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function makeActiveFromApproved($conn, $campaign_id) {
    $sql = "UPDATE campaigns 
            SET status = 'active'
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $campaign_id);

    if ($stmt->execute()) {
        return true;
    } else {
        error_log("Error making campaign active: " . $stmt->error);
        return false;
    }
}

// function getActiveCampaigns() {
//     global $conn;
//     $stmt = $conn->prepare("SELECT * FROM campaigns WHERE status = 'active' ORDER BY created_at DESC");
//     $stmt->execute();
//     $result = $stmt->get_result();
//     return $result->fetch_all(MYSQLI_ASSOC);
// }

function updateDonationStatus($donation_id, $new_status) {
    global $conn;
    $stmt = $conn->prepare("UPDATE donations SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $donation_id);
    $stmt->execute();
    
    // If the donation is completed, update the campaign progress
    if ($new_status == 'completed') {
        $stmt = $conn->prepare("SELECT campaign_id FROM donations WHERE id = ?");
        $stmt->bind_param("i", $donation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $donation = $result->fetch_assoc();
        updateCampaignProgress($donation['campaign_id']);
    }
}

// Add these functions to functions.php

function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function isStrongPassword($password) {
    // Password must be at least 8 characters long and contain:
    // - At least one uppercase letter
    // - At least one lowercase letter
    // - At least one number
    // - At least one special character
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password);
}

function createAdmin($conn, $name, $email, $password) {
    // Validate inputs
    if (!isValidEmail($email)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    if (!isStrongPassword($password)) {
        return ['success' => false, 'message' => 'Password does not meet security requirements'];
    }
    
    // Check if email already exists
    $existingUser = getUserByEmail($conn, $email);
    if ($existingUser) {
        return ['success' => false, 'message' => 'Email already registered'];
    }
    
    // Create admin user
    $user_id = createUser($conn, $name, $email, $password, 'admin');
    
    if ($user_id) {
        // Add entry to admin_users table for additional admin-specific data
        $sql = "INSERT INTO admin_users (user_id, created_at) VALUES (?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            return ['success' => true, 'user_id' => $user_id];
        } else {
            // Rollback user creation if admin entry fails
            $conn->query("DELETE FROM users WHERE id = " . $user_id);
            return ['success' => false, 'message' => 'Failed to create admin record'];
        }
    }
    
    return ['success' => false, 'message' => 'Failed to create user'];
}

function isAdmin($conn, $user_id) {
    $sql = "SELECT role FROM users WHERE id = ? AND role = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Add this to verify admin access in any admin page
function verifyAdminAccess() {
    if (!isset($_SESSION['user_id']) || !isAdmin($GLOBALS['conn'], $_SESSION['user_id'])) {
        // If the user is not an admin, redirect them to the login page
        header("Location: admin_login.php");
        exit();
    }
    // If the user is an admin, allow them to access the admin panel
}



function getDonationByMerchantRequestID($merchantRequestID) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM donations WHERE merchant_request_id = ?");
    $stmt->bind_param("s", $merchantRequestID);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
} 


function getMonthlyDonationTrends($conn) {
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(amount) as total_amount,
                COUNT(*) as donation_count
            FROM donations
            WHERE status = 'completed'
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month DESC
            LIMIT 12";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getCampaignStats($conn) {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'total_raised' => 0
    ];
    
    $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(current_amount) as total_raised
            FROM campaigns";
            
    $result = $conn->query($sql);
    if ($result) {
        $stats = $result->fetch_assoc();
    }
    return $stats;
}

function getPendingCampaigns($conn) {
    $sql = "SELECT c.*, u.name as creator_name 
            FROM campaigns c 
            JOIN users u ON c.creator_id = u.id 
            WHERE c.status = 'pending' 
            ORDER BY c.created_at DESC";
    $result = $conn->query($sql);
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getDonorStats($conn, $user_id) {
    $stats = [
        'total_donated' => 0,
        'campaigns_supported' => 0,
        'last_donation' => null
    ];
    
    $sql = "SELECT 
            SUM(amount) as total_donated,
            COUNT(DISTINCT campaign_id) as campaigns_supported,
            MAX(created_at) as last_donation
            FROM donations 
            WHERE user_id = ? AND status = 'completed'";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $stats = $result->fetch_assoc();
    }
    return $stats;
}

function updateCampaignStatus($conn, $campaign_id, $status, $admin_id) {
    $sql = "UPDATE campaigns 
            SET status = ?, 
                reviewed_by = ?, 
                reviewed_at = NOW() 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $status, $admin_id, $campaign_id);

    if ($stmt->execute()) {
        if ($status == 'approved') {
            makeActiveFromApproved($conn, $campaign_id);
        }
        return true;
    } else {
        error_log("Error updating campaign status: " . $stmt->error);
        return false;
    }
}
function rejectCampaign($conn, $campaign_id, $reason, $admin_id) {
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update campaign status
        $updateSql = "UPDATE campaigns SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("ii", $admin_id, $campaign_id);
        $updateStmt->execute();
        
        // Insert rejection reason
        $reasonSql = "INSERT INTO campaign_rejections (campaign_id, reason, admin_id) VALUES (?, ?, ?)";
        $reasonStmt = $conn->prepare($reasonSql);
        $reasonStmt->bind_param("isi", $campaign_id, $reason, $admin_id);
        $reasonStmt->execute();
        
        // Commit transaction
        $conn->commit();
        return true;
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        error_log("Error rejecting campaign: " . $e->getMessage());
        return false;
    }
}

function getCreatorStats($conn, $creator_id) {
    $stats = [
        'total_campaigns' => 0,
        'active_campaigns' => 0,
        'total_raised' => 0,
        'total_donors' => 0
    ];
    
    $sql = "SELECT 
            COUNT(*) as total_campaigns,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_campaigns,
            SUM(current_amount) as total_raised,
            (SELECT COUNT(DISTINCT user_id) 
             FROM donations d 
             JOIN campaigns c ON d.campaign_id = c.id 
             WHERE c.creator_id = ?) as total_donors
            FROM campaigns 
            WHERE creator_id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $creator_id, $creator_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result) {
        $stats = $result->fetch_assoc();
    }
    return $stats;
}