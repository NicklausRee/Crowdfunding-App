<?php
session_start();
require_once 'db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $goal = $_POST['goal'];

    // Validate input data
    if (empty($title) || empty($description) || empty($goal)) {
        $error = "Please fill in all fields";
    } else {
        // Insert campaign into database
        $query = "INSERT INTO campaigns (title, description, goal, creator_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssi", $title, $description, $goal, $_SESSION['user_id']);
        $stmt->execute();

        // Redirect to campaign updates page
        $campaign_id = $conn->insert_id;
        header("Location: campaign_updates.php?id=$campaign_id");
        exit();
    }
}

?>

<!-- Campaign creation form -->
<form method="post">
    <label for="title">Campaign Title:</label>
    <input type="text" id="title" name="title" required><br><br>
    <label for="description">Campaign Description:</label>
    <textarea id="description" name="description" required></textarea><br><br>
    <label for="goal">Campaign Goal:</label>
    <input type="number" id="goal" name="goal" required><br><br>
    <button type="submit">Create Campaign</button>
</form>

<?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>