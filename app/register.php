<?php
session_start();
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role']; // donor or campaign creator

    // Validate input data
    if (empty($username) || empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into database
        $query = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
        $stmt->execute();

        // Login user and redirect to dashboard
        $_SESSION['user_id'] = $conn->insert_id;
        header("Location: dashboard.php");
        exit();
    }
}

?>

<!-- Registration form -->
<form method="post">
    <label for="username">Username:</label>
    <input type="text" id="username" name="username" required><br><br>
    <label for="email">Email:</label>
    <input type="email" id="email" name="email" required><br><br>
    <label for="password">Password:</label>
    <input type="password" id="password" name="password" required><br><br>
    <label for="role">Role:</label>
    <select id="role" name="role" required>
        <option value="donor">Donor</option>
        <option value="campaign_creator">Campaign Creator</option>
    </select><br><br>
    <button type="submit">Register</button>
</form>

<?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>