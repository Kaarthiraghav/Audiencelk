<?php
include 'includes/header.php';
// Start session if you want to use flash messages
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Variables for error/success messages
$successMessage = "";
$errorMessage = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Collect form data
  $organizer = trim($_POST['organizer'] ?? '');
  $company   = trim($_POST['company'] ?? '');
  $region    = trim($_POST['region'] ?? '');
  $genre     = trim($_POST['genre'] ?? '');
  $email     = trim($_POST['email'] ?? '');
  $mobile    = trim($_POST['mobile'] ?? '');
  $remark    = trim($_POST['remark'] ?? '');

    // Validate required fields
    if ($organizer && $company && $email && $mobile) {
        $successMessage = "✅ Application submitted successfully!";
    } else {
        $errorMessage = "⚠️ Please fill in all required fields.";
    }
}
?>
  <div class="form-container">
    <h2>Contact Us</h2>
    <form>
      <div class="form-group">
        <label for="name">Name</label>
        <input type="text" id="name" name="name" placeholder="Your Name">
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" placeholder="Your Email">
      </div>
      <div class="form-group">
        <label for="message">Message</label>
        <textarea id="message" name="message" placeholder="Your Message"></textarea>
      </div>
      <div class="form-group">
        <input type="submit" value="Send">
      </div>
    </form>
  </div>
<?php include 'includes/footer.php'; ?>