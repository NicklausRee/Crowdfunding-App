<?php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// Create the uploads directory if it doesn't exist
$upload_dir = 'uploads/campaigns/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $goal = (float)$_POST['goal'];
    $photo = $_FILES['photo'];

    // Validate input data
    $errors = [];
    if (empty($title) || empty($description) || $goal <= 0 || empty($photo['name'])) {
        $errors[] = "Please fill in all fields and upload a campaign photo.";
    }

    if (strlen($title) > 100) {
        $errors[] = "Title must be 100 characters or less.";
    }

    if (strlen($description) > 1000) {
        $errors[] = "Description must be 1000 characters or less.";
    }

    if (!is_numeric($goal)) {
        $errors[] = "Goal must be a valid number.";
    }

    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    } else {
        // Handle photo upload
        $image_url = secure_file_upload($photo, ['jpg', 'jpeg', 'png', 'gif'], 5 * 1024 * 1024, $upload_dir);
        if (!$image_url) {
            $error = "Error uploading photo.";
        } else {
            // Insert campaign into database
            $stmt = $conn->prepare("INSERT INTO campaigns (title, description, goal, creator_id, image_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiss", $title, $description, $goal, $_SESSION['user_id'], basename($image_url));
            if ($stmt->execute()) {
                $campaign_id = $conn->insert_id;
                header("Location: campaign_updates.php?id=$campaign_id");
                exit();
            } else {
                $error = "Error creating campaign. Please try again.";
            }
        }
    }
}
?>

<!-- Campaign creation form -->
<form method="post" enctype="multipart/form-data">
    <label for="title">Campaign Title:</label>
    <input type="text" id="title" name="title" required><br><br>
    <label for="description">Campaign Description:</label>
    <textarea id="description" name="description" required></textarea><br><br>
    <label for="goal">Campaign Goal:</label>
    <input type="number" id="goal" name="goal" required><br><br>
    <label for="photo">Campaign Photo:</label>
    <input type="file" id="photo" name="photo" required><br><br>
    <button type="submit">Create Campaign</button>
</form>

<?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>