<?php
require_once 'db_config.php';

function getAccessToken() {
    $consumerKey = 'E0mdVoNp0abhWvOJvXGuKwvzYzsTadMY3ubh9G1F3gSOGZyl';
    $consumerSecret = 'Lj4n8WGGbrIBh8JSStW9FBfubJnbM1AmHl32PAblAPqhbuV9ZZxPoOtqwNZhrmGQ';
    $credentials = base64_encode($consumerKey . ':' . $consumerSecret);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response);
    return $result->access_token;
}

function initiateSTKPush($transaction_id, $amount, $phoneNumber, $callBackURL) {
    $accessToken = getAccessToken();
    $timestamp = date('YmdHis');
    $password = base64_encode('174379' . 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919' . $timestamp);
    
    $curl_post_data = [
        'BusinessShortCode' => '174379', 
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => $amount,
        'PartyA' => $phoneNumber, 
        'PartyB' => '174379',
        'PhoneNumber' => $phoneNumber, 
        'CallBackURL' => $callBackURL,
        'AccountReference' => 'Donation ' . $transaction_id,
        'TransactionDesc' => 'Donation for Transaction ' . $transaction_id
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken, 'Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($curl_post_data));
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response);
    return $result;
}

function createDonation($donor_id, $campaign_id, $amount) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO donations (donor_id, campaign_id, amount, status) VALUES (?, ?, ?, 'pending')");
    $stmt->bind_param("iid", $donor_id, $campaign_id, $amount);
    $stmt->execute();
    return [
        'id' => $stmt->insert_id,
        'donor_id' => $donor_id,
        'campaign_id' => $campaign_id,
        'amount' => $amount,
        'status' => 'pending',
    ];
}

function createUser($conn, $name, $email, $password) {
    $sql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'user')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $email, $password);
    if ($stmt->execute()) {
        return $stmt->insert_id;
    }
    return false;
}

function getUserByEmail($conn, $email) {
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
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
    $sql = "SELECT * FROM campaigns WHERE creator_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getUserDonations($conn, $user_id) {
    $sql = "SELECT d.*, c.title as campaign_title FROM donations d 
            JOIN campaigns c ON d.campaign_id = c.id 
            WHERE d.donor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
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

function getCampaignById($campaign_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ?");
    $stmt->bind_param("i", $campaign_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getDonationsByCampaign($campaign_id) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM donations WHERE campaign_id = ?");
    $stmt->bind_param("i", $campaign_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
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
    $stmt->execute();
}

function createCampaign($name, $description, $goal, $creator_id) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO campaigns (name, description, goal, creator_id, current_amount, status) VALUES (?, ?, ?, ?, 0, 'pending')");
    $stmt->bind_param("ssdi", $name, $description, $goal, $creator_id);
    $stmt->execute();
    return $stmt->insert_id;
}

function getActiveCampaigns() {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM campaigns WHERE status = 'active' ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

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

// Function to handle M-Pesa callback
function handleMpesaCallback($callbackData) {
    $resultCode = $callbackData['Body']['stkCallback']['ResultCode'];
    $merchantRequestID = $callbackData['Body']['stkCallback']['MerchantRequestID'];
    
    // Find the donation based on the MerchantRequestID
    $donation = getDonationByMerchantRequestID($merchantRequestID);
    
    if ($resultCode == 0) {
        // Payment successful
        updateDonationStatus($donation['id'], 'completed');
        return "Payment successful";
    } else {
        // Payment failed
        updateDonationStatus($donation['id'], 'failed');
        return "Payment failed";
    }
}

function getDonationByMerchantRequestID($merchantRequestID) {
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM donations WHERE merchant_request_id = ?");
    $stmt->bind_param("s", $merchantRequestID);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}