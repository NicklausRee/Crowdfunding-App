<?php
// donation_callback.php
require_once 'db_config.php';

// Get the callback response
$callbackJSONData = file_get_contents('php://input');
$callbackData = json_decode($callbackJSONData);

// Verify the transaction
if ($callbackData) {
    $resultCode = $callbackData->Body->stkCallback->ResultCode;
    $resultDesc = $callbackData->Body->stkCallback->ResultDesc;
    $merchantRequestID = $callbackData->Body->stkCallback->MerchantRequestID;
    $checkoutRequestID = $callbackData->Body->stkCallback->CheckoutRequestID;
    
    // Get the donation attempt
    $stmt = $conn->prepare("SELECT * FROM donation_attempts WHERE checkout_request_id = ? AND status = 'pending'");
    $stmt->bind_param("s", $checkoutRequestID);
    $stmt->execute();
    $donation = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if ($donation) {
        if ($resultCode == 0) {
            // Transaction successful
            $stmt = $conn->prepare("UPDATE donation_attempts SET status = 'completed', transaction_date = NOW() WHERE id = ?");
            $stmt->bind_param("i", $donation['id']);
            $stmt->execute();
            $stmt->close();
            
            // Update campaign amount
            $stmt = $conn->prepare("UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?");
            $stmt->bind_param("di", $donation['amount'], $donation['campaign_id']);
            $stmt->execute();
            $stmt->close();
        } else {
            // Transaction failed
            $stmt = $conn->prepare("UPDATE donation_attempts SET status = 'failed', failure_reason = ? WHERE id = ?");
            $stmt->bind_param("si", $resultDesc, $donation['id']);
            $stmt->execute();
            $stmt->close();
        }
    }
}

// Return response to M-PESA
$response = [
    "ResultCode" => 0,
    "ResultDesc" => "Confirmation received successfully"
];
header('Content-Type: application/json');
echo json_encode($response);