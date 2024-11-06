<?php
require_once 'db_config.php';
require_once 'functions.php';

$callbackData = json_decode(file_get_contents('php://input'), true);

$result = handleMpesaCallback($callbackData);

// Log the result or send a response if needed
error_log($result);

echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback received successfully']);
?>