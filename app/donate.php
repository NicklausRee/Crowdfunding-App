<?php
session_start();
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $campaign_id = $_POST['campaign_id'];
    $amount = $_POST['amount'];

    // Validate input data
    if (empty($campaign_id) || empty($amount)) {
        $error = "Please fill in all fields";
    } else {
        // Insert donation into database
        $query = "INSERT INTO donations (campaign_id, donor_id, amount) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iii", $campaign_id, $_SESSION['user_id'], $amount);
        $stmt->execute();

        // Update campaign goal
        $query = "UPDATE campaigns SET goal = goal - ? WHERE id = ?";
       