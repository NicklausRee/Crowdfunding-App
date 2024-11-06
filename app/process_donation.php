<?php
// In process_donation.php
require_once 'db_config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $phone = sanitize_input($_POST['phone']);
    $email = sanitize_input($_POST['email']);
    $amount = floatval($_POST['amount']);
    $campaign_id = intval($_POST['campaign_id']);

    // Validate inputs
    if (!validate_email($email) || !validate_number($amount) || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }

    // Get or create donor
    $donor = getDonorByEmail($email);
    if (!$donor) {
        $donor_id = createDonor($name, $phone, $email);
    } else {
        $donor_id = $donor['id'];
    }

    // Create donation record
    $donation = createDonation($donor_id, $campaign_id, $amount);

    // Initiate M-Pesa STK Push
    $callbackUrl = "https://yourwebsite.com/mpesacallback.php";
    $stkPushResult = initiateSTKPush($donation['id'], $amount, $phone, $callbackUrl);

    if ($stkPushResult->ResponseCode == "0") {
        // STK Push initiated successfully
        $merchantRequestID = $stkPushResult->MerchantRequestID;
        updateDonationMerchantRequestID($donation['id'], $merchantRequestID);
        echo json_encode(['success' => true, 'message' => 'Please complete the payment on your phone']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error initiating payment']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

function createDonor($name, $phone, $email) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO donors (name, phone, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $phone, $email);
    $stmt->execute();
    return $stmt->insert_id;
}

function updateDonationMerchantRequestID($donation_id, $merchantRequestID) {
    global $conn;
    $stmt = $conn->prepare("UPDATE donations SET merchant_request_id = ? WHERE id = ?");
    $stmt->bind_param("si", $merchantRequestID, $donation_id);
    $stmt->execute();
}
?>