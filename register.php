<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// In register.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];  // Get plain password from form
    $role = $_POST['role'];

    // Validate input data
    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if email already exists
        $existing_user = getUserByEmail($conn, $email);
        if ($existing_user) {
            $error = "Email address already registered";
        } else {
            // REMOVE the password hashing here - let createUser handle it
            // DO NOT do: $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Pass the plain password directly to createUser
            $user_id = createUser($conn, $name, $email, $password, $role);
            
            if ($user_id) {
                // Set up session
                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_role'] = $role;
                
                // Redirect based on role
                switch ($role) {
                    case 'donor':
                        header("Location: dashboard_donor.php");
                        break;
                    case 'creator':
                        header("Location: dashboard_campaign_creator.php");
                        break;
                    default:
                        header("Location: dashboard_donor.php");
                }
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ReliefKenya</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h1>Create an Account</h1>
        
        <?php if (isset($error)): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="registration-form">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required 
                    value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required
                    minlength="8">
            </div>

            <div class="form-group">
                <label for="role">I want to:</label>
                <select id="role" name="role" required>
                    <option value="donor" <?php echo (isset($_POST['role']) && $_POST['role'] === 'donor') ? 'selected' : ''; ?>>
                        Make donations
                    </option>
                    <option value="creator" <?php echo (isset($_POST['role']) && $_POST['role'] === 'creator') ? 'selected' : ''; ?>>
                        Create fundraising campaigns
                    </option>
                </select>
            </div>

            <button type="submit" class="submit-button">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</body>
</html>