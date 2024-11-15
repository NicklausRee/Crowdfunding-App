<?php
// process_donation.php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// Check if user is logged in and has donor role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: login.php");
    exit();
}

// Validate POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: dashboard_donor.php");
    exit();
}

// Get and validate form data
$campaign_id = isset($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : 0;
$phone = isset($_POST['phone']) ? clean_phone_number($_POST['phone']) : '';
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validate campaign exists and is active
$stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ? AND status = 'active'");
$stmt->bind_param("i", $campaign_id);
$stmt->execute();
$campaign = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$campaign) {
    $_SESSION['error'] = "Invalid campaign or campaign not found.";
    header("Location: dashboard_donor.php");
    exit();
}

// Validate amount and phone
if ($amount < 1) {
    $_SESSION['error'] = "Minimum donation amount is KES 1";
    header("Location: donate.php?id=" . $campaign_id);
    exit();
}

if (!preg_match('/^254[7][0-9]{8}$/', $phone)) {
    $_SESSION['error'] = "Invalid phone number format";
    header("Location: donate.php?id=" . $campaign_id);
    exit();
}

// Initialize STK Push
try {
    // Include the access token file
    include 'accessToken.php';
    
    date_default_timezone_set('Africa/Nairobi');
    
    $processrequestUrl = 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest';
    $callbackurl = ''; // Update with your callback URL
    $passkey = "bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919";
    $BusinessShortCode = '174379';
    $Timestamp = date('YmdHis');
    
    // Generate password
    $Password = base64_encode($BusinessShortCode . $passkey . $Timestamp);
    
    // Prepare STK Push request
    $stkpushheader = [
        'Content-Type:application/json',
        'Authorization:Bearer ' . $access_token
    ];

    // Initialize cURL
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $processrequestUrl);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $stkpushheader);
    
    $curl_post_data = array(
        'BusinessShortCode' => $BusinessShortCode,
        'Password' => $Password,
        'Timestamp' => $Timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phone,
        'PartyB' => $BusinessShortCode,
        'PhoneNumber' => $phone,
        'CallBackURL' => $callbackurl,
        'AccountReference' => 'Campaign#' . $campaign_id,
        'TransactionDesc' => 'Donation to ' . substr($campaign['title'], 0, 20)
    );

    $data_string = json_encode($curl_post_data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
    
    $curl_response = curl_exec($curl);
    $data = json_decode($curl_response);
    
    // Check if request was successful
    if (isset($data->ResponseCode) && $data->ResponseCode == "0") {
        // Store donation attempt in database
        $stmt = $conn->prepare("INSERT INTO donation_attempts (user_id, campaign_id, amount, phone, message, checkout_request_id, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("iidsss", 
            $_SESSION['user_id'],
            $campaign_id,
            $amount,
            $phone,
            $message,
            $data->CheckoutRequestID
        );
        $stmt->execute();
        $stmt->close();
        
        $_SESSION['success'] = "Please check your phone to complete the M-PESA payment.";
        header("Location: donation_status.php?request=" . $data->CheckoutRequestID);
        exit();
    } else {
        throw new Exception("Failed to initiate M-PESA payment.");
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to process donation: " . $e->getMessage();
    header("Location: donate.php?id=" . $campaign_id);
    exit();
}

// Helper function to clean phone number
function clean_phone_number($phone) {
    // Remove any non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Convert to required format (254XXXXXXXXX)
    if (strlen($phone) === 9) {
        // If 9 digits (without prefix)
        return "254" . $phone;
    } elseif (strlen($phone) === 10 && substr($phone, 0, 1) === "0") {
        // If starts with 0
        return "254" . substr($phone, 1);
    } elseif (strlen($phone) === 12 && substr($phone, 0, 3) === "254") {
        // If already in correct format
        return $phone;
    }
    
    return $phone;
}