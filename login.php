<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

if (isset($_SESSION['error_message'])) {
    $error = $_SESSION['error_message'];
    unset($_SESSION['error_message']); // Clear the message after displaying
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Add error logging to debug
    error_log("Login attempt for email: " . $email);
    
    $user = getUserByEmail($conn, $email);
    if ($user && password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];

        // Update the last_login timestamp
        $updateLastLogin = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $updateLastLogin->bind_param("i", $user['id']);
        $updateLastLogin->execute();
        
        // Redirect based on role
        switch ($user['role']) {
            // case 'admin':
            //     header("Location: admin_index.php");
            //     break;
            case 'donor':
                header("Location: dashboard_donor.php");
                break;
            case 'creator':
                header("Location: dashboard_campaign_creator.php");
                break;
            default:
                header("Location: dashboard.php");
        }
        exit();
    } else {
        // Add more detailed error logging
        if (!$user) {
            error_log("User not found with email: " . $email);
            $error = "Invalid email or password";
        } else {
            error_log("Password verification failed for email: " . $email);
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ReliefKenya</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Login to ReliefKenya</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post" action="login.php">
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="cta-button">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>