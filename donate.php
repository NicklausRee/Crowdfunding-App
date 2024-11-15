<?php
// donate.php
session_start();
require_once 'db_config.php';
require_once 'functions.php';

// Check if user is logged in and has donor role
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'donor') {
    header("Location: login.php");
    exit();
}

// Get campaign ID from URL
$campaign_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Validate campaign exists and is active
$campaign = null;
if ($campaign_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM campaigns WHERE id = ? AND status = 'active'");
    $stmt->bind_param("i", $campaign_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $campaign = $result->fetch_assoc();
    $stmt->close();
}

// Redirect if campaign doesn't exist or isn't active
if (!$campaign) {
    $_SESSION['error'] = "Invalid campaign or campaign not found.";
    header("Location: dashboard_donor.php");
    exit();
}

// Get donor details
$user = getUserById($conn, $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-PESA Donation - ReliefKenya</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-mobile-alt"></i> Make an M-PESA Donation</h1>

        <!-- Campaign Details -->
        <div class="dashboard-card">
            <h3>Campaign Details</h3>
            <div class="card-content">
                <h4><?php echo htmlspecialchars($campaign['title']); ?></h4>
                <p><?php echo htmlspecialchars($campaign['description']); ?></p>
                <div class="campaign-stats">
                    <span>Goal: KES <?php echo number_format($campaign['goal']); ?></span>
                    <span>Raised: KES <?php echo number_format($campaign['current_amount']); ?></span>
                </div>
            </div>
        </div>

        <!-- M-PESA Donation Form -->
        <div class="dashboard-card">
            <h3>M-PESA Payment Details</h3>
            <div class="card-content">
                <form id="mpesaDonationForm" action="process_donation.php" method="POST">
                    <input type="hidden" name="campaign_id" value="<?php echo $campaign_id; ?>">
                    <input type="hidden" name="payment_method" value="mpesa">
                    
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" value="<?php echo htmlspecialchars($user['name']); ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="phone">M-PESA Phone Number</label>
                        <input type="tel" id="phone" name="phone" 
                               pattern="^(?:254|\+254|0)([7][0-9]{8})$" 
                               placeholder="254XXXXXXXXX or 07XXXXXXXX" 
                               required>
                        <small class="form-hint">Enter your M-PESA registered number (e.g., 254712345678)</small>
                    </div>

                    <div class="form-group">
                        <label for="amount">Donation Amount (KES)</label>
                        <input type="number" id="amount" name="amount" 
                               min="1" step="1" required>
                        <small class="form-hint">Minimum amount: KES 1</small>
                    </div>

                    <div class="form-group">
                        <label for="message">Message (Optional)</label>
                        <textarea id="message" name="message" rows="3"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="cta-button">
                            <i class="fas fa-mobile-alt"></i> Send M-PESA Payment Request
                        </button>
                        <a href="dashboard_donor.php" class="secondary-button">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.getElementById('mpesaDonationForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const amount = document.getElementById('amount').value;
        const phone = document.getElementById('phone').value;
        
        // Validate amount
        if (amount < 1) {
            alert('Minimum donation amount is KES 1');
            return;
        }
        
        // Validate phone number format
        const phoneRegex = /^(?:254|\+254|0)([7][0-9]{8})$/;
        if (!phoneRegex.test(phone)) {
            alert('Please enter a valid M-PESA phone number');
            return;
        }
        
        // Submit form if validation passes
        this.submit();
    });
    </script>
</body>
</html>