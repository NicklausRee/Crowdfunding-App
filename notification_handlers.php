<!-- <?php
// notification_handlers.php

require_once 'db_config.php';

function sendDonationConfirmation($donation_id) {
    global $conn;
    
    try {
        // Get donation details with user and campaign information
        $stmt = $conn->prepare("
            SELECT 
                d.*,
                u.name as donor_name,
                c.title as campaign_title
            FROM donations d
            JOIN users u ON d.user_id = u.id
            JOIN campaigns c ON d.campaign_id = c.id
            WHERE d.id = ?
        ");
        
        $stmt->bind_param("i", $donation_id);
        $stmt->execute();
        $donation = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$donation) {
            throw new Exception("Donation not found");
        }
        
        // Generate receipt number
        $receipt_number = 'RCP' . date('Ymd') . str_pad($donation_id, 6, '0', STR_PAD_LEFT);
        
        // Store receipt number in database
        $stmt = $conn->prepare("UPDATE donations SET receipt_number = ? WHERE id = ?");
        $stmt->bind_param("si", $receipt_number, $donation_id);
        $stmt->execute();
        $stmt->close();
        
        // Log successful notification
        logNotification($donation_id, 'success', 'Donation recorded successfully');
        
        return true;
        
    } catch (Exception $e) {
        // Log error
        logNotification($donation_id, 'error', $e->getMessage());
        return false;
    }
}

function logNotification($donation_id, $status, $message) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO notification_logs 
            (donation_id, status, message, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("iss", $donation_id, $status, $message);
        $stmt->execute();
        $stmt->close();
        
    } catch (Exception $e) {
        error_log("Error logging notification: " . $e->getMessage());
    }
}
?> -->