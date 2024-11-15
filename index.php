<?php session_start(); 
?>

<?php
require_once 'db_config.php';
require_once 'functions.php';

$active_campaigns = getActiveCampaigns($conn);
foreach ($active_campaigns as $campaign) {
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ReliefKenya</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <img src="images/reliefkelogo.png" alt="ReliefKenya logo">
            </div>
            <nav>
                <ul>
                    <li><a href="#homeSection">Home</a></li>
                    <li><a href="#aboutSection">About Us</a></li>
                    <li><a href="#campaignsSection">Campaigns</a></li>
                    <li><a href="#educationSection">Education</a></li>
                    <li><a href="#gallerySection">Gallery</a></li>
                    <li><a href="#joinSection">Join Us</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="register.php">Register</a></li>
                    <?php endif; ?>

                    <!-- <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                        <?php endif; ?> -->
                </ul>
            </nav>
        </div>
    </header>

    <section id="homeSection" class="banner">
        <div class="container">
            <h1>Together We Can Save Lives</h1>
            <p>ReliefKenya stands ready to respond swiftly and effectively to crises across our nation. From devastating droughts to unexpected floods, from wildfires to conflict-driven displacement, we're here to provide immediate aid and long-term support.</p>
            <!-- <a href="#campaignsSection" class="cta-button">Start With A Little</a> -->
        </div>
    </section>

    <section id="aboutSection">
        <div class="container">
            <h2 class="sectionTitle">About Us</h2>
            <p>Born out of the aftermath of the devastating floods that swept across Kenya during the intense rainfall period of 2023, ReliefKenya was created by a tech-savvy Kenyan humanitarian. We believe in the power of collective action and the speed of digital solutions to make a real difference when every minute counts.</p>
            <div class="cards">
                <!-- <div class="card">
                    <i class="fas fa-hand-holding-heart"></i>
                    <h3>Give Donation</h3>
                    <p>Want to make a difference? Donate now and help ReliefKenya provide help across the country.</p>
                    <a href="#" class="cta-button">Donate Now</a>
                </div> -->
                <!-- <div class="card">
                    <i class="fas fa-bullhorn"></i>
                    <h3>Create Campaign</h3>
                    <p>Start a campaign and make a difference. Help us mobilize support for aid across Kenya.</p>
                    <a href="#" class="cta-button">Create Campaign</a>
                </div> -->
            </div>
        </div>
    </section>

    <section id="campaignsSection" class="Campaigns">
    <div id="campaignsSection" class="section Campaigns">
        <div class="container">
            <h2 id="campaigns" class="sectionTitle">Active Campaigns</h2>
            <div class="boxContainer">
                <?php foreach ($active_campaigns as $campaign): ?>
                    <div class="box">
                        <?php if (!empty($campaign['image_url'])): ?>
                            <img src="uploads/campaigns/<?php echo htmlspecialchars($campaign['image_url']); ?>" 
                                alt="<?php echo htmlspecialchars($campaign['title']); ?>" 
                                class="campaign-photo" 
                                onerror="this.src='assets/default-campaign.jpg'">
                        <?php else: ?>
                            <img src="assets/default-campaign.jpg" 
                                alt="Default campaign image" 
                                class="campaign-photo">
                        <?php endif; ?>
                        <!-- <div class="cardImage" style="background-image: url('<?php echo htmlspecialchars($campaign['image_url']); ?>')"></div> -->
                        <h3 class="campaignTitle"><?php echo htmlspecialchars($campaign['title']); ?></h3>
                        <p class="donationGoal">Donation Goal: KES<?php echo number_format($campaign['goal']); ?></p>
                        <!-- <button class="cta-button" onclick="showDonationForm(<?php echo $campaign['id']; ?>)">Donate Now</button> -->
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
        <div class="container">
            <h2 class="sectionTitle">Closed Campaigns</h2>
            <div class="boxContainer">
                <div class="box">
                    <div class="cardImage"></div>
                    <h3 class="campaignTitle">Rise Above the Floods: Help Kenya Recover</h3>
                    <p class="donationGoal">Donation Goal: KES50,000</p>
                    <!-- <a href="#" class="cta-button" data-campaign-id="1">Donate Now</a> -->
                </div>
                <div class="box">
                    <div class="cardImage"></div>
                    <h3 class="campaignTitle">Kenya's Water Crisis: A Call for Action</h3>
                    <p class="donationGoal">Donation Goal: KES60,000</p>
                    <!-- <a href="#" class="cta-button" data-campaign-id="2">Donate Now</a> -->
                </div>
                <div class="box">
                    <div class="cardImage"></div>
                    <h3 class="campaignTitle">Kenya Drought Relief Mission: Lend A Hand</h3>
                    <p class="donationGoal">Donation Goal: KES70,000</p>
                    <!-- <a href="#" class="cta-button" data-campaign-id="3">Donate Now</a> -->
                </div>
                <div class="box">
                    <div class="cardImage"></div>
                    <h3 class="campaignTitle">Kenya's Fire Victims: We Stand Together</h3>
                    <p class="donationGoal">Donation Goal: KES80,000</p>
                    <!-- <a href="#" class="cta-button" data-campaign-id="4">Donate Now</a> -->
                </div>
            </div>
        </div>
    </section>

    <section id="educationSection" class="Education">
        <video autoplay muted loop id="myVideo">
            <source src="video/background.mp4" type="video/mp4">
            Your browser does not support HTML5 video.
        </video>
        <div class="container">
            <h2 class="sectionTitle">Education</h2>
            <div class="educationContainer">
                <h3>Role Of Education in <strong>DISASTER RESILIENCE</strong></h3>
                <p>When you contribute to our disaster relief campaigns, you're not just donating money -- you're changing lives. This is through Immediate Aid, helping Rebuild Lives, promoting Economic Growth by preventing economic collapse in affected areas, promoting Coordinated action, providing Targeted Solutions and building a Global Community.</p>
                <a href="#campaignsSection" class="cta-button">EXPLORE CAMPAIGNS</a>
            </div>
        </div>
    </section>

    <section id="gallerySection" class="Gallery">
        <div class="container">
            <h2 class="sectionTitle">Gallery</h2>
            <div class="galleryContainer">
                <div class="item">
                    <img src="images/gallery/1.jfif" alt="Gallery Image 1">
                    <div class="title"></div>
                </div>
                <div class="item">
                    <img src="images/gallery/2.jfif" alt="Gallery Image 2">
                    <div class="title"></div>
                </div>
                <div class="item">
                    <img src="images/gallery/3.jfif" alt="Gallery Image 3">
                    <div class="title"></div>
                </div>
                <div class="item">
                    <img src="images/gallery/4.jfif" alt="Gallery Image 4">
                    <div class="title"></div>
                </div>
                <div class="item">
                    <img src="images/gallery/5.jfif" alt="Gallery Image 5">
                    <div class="title"></div>
                </div>
                <div class="item">
                    <img src="images/gallery/6.jfif" alt="Gallery Image 6">
                    <div class="title"></div>
                </div>
                <div class="item">
                    <img src="images/gallery/7.jfif" alt="Gallery Image 7">
                    <div class="title"></div>
                </div>
                <div class="item">
                    <img src="images/gallery/8.jfif" alt="Gallery Image 8">
                    <div class="title"></div>
                </div>
            </div>
        </div>
    </section>

    <section id="joinSection" class="join">
        <div class="container">
            <h2 class="joinTitle">BE THE CHANGE & EMPOWER COMMUNITIES IN CRISIS</h2>
            <p>Imagine a world where no one is left behind after a disaster. Where communities come together, stronger than ever, to rebuild their lives. By joining our crowdfunding campaign, you're not just making a donation; you're becoming a catalyst for change.</p>
            <!-- <a href="#" class="cta-button">DONATE NOW</a>
            <a href="#" class="cta-button">CREATE CAMPAIGN</a> -->
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="newsLetterContainer">
                <img src="images/reliefkelogo.png" alt="ReliefKenya Logo">
                <p>Subscribe to our newsletter for updates on current disasters, success stories, and how your donations are making a difference.</p>
                <form>
                    <input type="email" placeholder="Enter your email">
                    <button type="submit">Subscribe</button>
                </form>
            </div>
            <div class="linksContainer">
                <h3 class="title">Useful Links</h3>
                <ul>
                    <li><a href="#homeSection">Home</a></li>
                    <li><a href="#aboutSection">About Us</a></li>
                    <li><a href="#campaignsSection">Campaigns</a></li>
                    <li><a href="#educationSection">Education</a></li>
                    <li><a href="#gallerySection">Gallery</a></li>
                    <li><a href="#joinSection">Join Us</a></li>
                </ul>
            </div>
            <div class="connectContainer">
                <h3 class="title">Connect with us</h3>
                <p>kabuilaura@gmail.com</p>
                <p>(+254) 710733367</p>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
    </footer>

    <div id="donationForm" style="display:none;">
        <h2>Make a Donation</h2>
        <form id="donateForm">
            <input type="text" name="name" placeholder="Your Name" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="number" name="amount" placeholder="Donation Amount" required>
            <input type="hidden" name="campaign_id" id="campaignIdInput">
            <button type="submit">Donate</button>
        </form>
    </div>

    <div id="campaignForm" style="display:none;">
        <h2>Create a Campaign</h2>
        <form id="createCampaignForm">
            <input type="text" name="name" placeholder="Your Name" required>
            <input type="tel" name="phone" placeholder="Phone Number" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="campaign_title" placeholder="Campaign Title" required>
            <textarea name="campaign_description" placeholder="Campaign Description" required></textarea>
            <input type="number" name="campaign_goal" placeholder="Fundraising Goal" required>
            <button type="submit">Create Campaign</button>
        </form>
    </div>

    <script>
function showDonationForm(campaignId) {
    document.getElementById('donationForm').style.display = 'block';
    document.getElementById('campaignIdInput').value = campaignId;
}

function showCampaignForm() {
    document.getElementById('campaignForm').style.display = 'block';
}

document.querySelectorAll('.cta-button').forEach(button => {
    button.addEventListener('click', function(e) {
        if (this.textContent.includes('Create Campaign')) {
            e.preventDefault();
            showCampaignForm();
        }
    });
});

document.getElementById('donateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('process_donation.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Thank you for your donation!');
            document.getElementById('donationForm').style.display = 'none';
        } else {
            alert('There was an error processing your donation. Please try again.');
        }
    });
});

document.getElementById('createCampaignForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('process_campaign.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Your campaign has been created and is pending approval!');
            document.getElementById('campaignForm').style.display = 'none';
        } else {
            alert('There was an error creating your campaign. Please try again.');
        }
    });
});
</script>
</body>
</html>