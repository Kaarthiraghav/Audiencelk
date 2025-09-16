<?php
$pageTitle = 'Contact Us - AudienceLK';
include 'includes/header.php';
include 'includes/db_connect.php';

// Start session for flash messages
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Variables for error/success messages
$successMessage = "";
$errorMessage = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  // Collect form data
  $name    = trim($_POST['name'] ?? '');
  $email   = trim($_POST['email'] ?? '');
  $subject = trim($_POST['subject'] ?? '');
  $message = trim($_POST['message'] ?? '');

  // Validate required fields
  if (empty($name)) {
    $errorMessage = "Please enter your name.";
  } elseif (empty($email)) {
    $errorMessage = "Please enter your email address.";
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errorMessage = "Please enter a valid email address.";
  } elseif (empty($subject)) {
    $errorMessage = "Please enter a subject.";
  } elseif (empty($message)) {
    $errorMessage = "Please enter your message.";
  } else {
    // In a real application, you would save this to a database or send an email
    // For now, we'll just show a success message
    $successMessage = "Thank you for your message! We'll get back to you soon.";
    
    // Clear form data on success
    $name = $email = $subject = $message = '';
  }
}
?>

<style>
/* Contact Page Specific Styles */
.contact-hero {
    padding: var(--space-5xl) 0;
    background: var(--gradient-dark);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.contact-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 25% 25%, rgba(255, 215, 0, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 75% 75%, rgba(220, 20, 60, 0.1) 0%, transparent 50%);
    animation: backgroundPulse 12s ease-in-out infinite;
}

.contact-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 var(--space-xl);
    position: relative;
    z-index: 2;
}

.contact-title {
    font-size: clamp(2.5rem, 6vw, 4rem);
    font-weight: var(--font-weight-black);
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: var(--space-2xl);
    animation: slideUpFade 1s ease-out;
}

.contact-subtitle {
    font-size: 1.3rem;
    color: var(--text-secondary);
    margin-bottom: var(--space-4xl);
    line-height: 1.6;
    animation: slideUpFade 1s ease-out 0.2s both;
}

.contact-content {
    padding: var(--space-5xl) 0;
    background: var(--dark-bg);
}

.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--space-5xl);
    align-items: start;
}

.contact-form-section {
    animation: slideUpFade 1s ease-out 0.4s both;
}

.contact-info-section {
    animation: slideUpFade 1s ease-out 0.6s both;
}

.section-title {
    font-size: 2rem;
    font-weight: var(--font-weight-bold);
    color: var(--text-primary);
    margin-bottom: var(--space-2xl);
    position: relative;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 0;
    width: 60px;
    height: 3px;
    background: var(--gradient-primary);
    border-radius: var(--radius-full);
}

.contact-form {
    background: var(--gradient-surface);
    border-radius: var(--radius-xl);
    padding: var(--space-3xl);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-lg);
}

.form-group {
    margin-bottom: var(--space-2xl);
}

.form-label {
    display: block;
    margin-bottom: var(--space-sm);
    font-weight: var(--font-weight-semibold);
    color: var(--text-primary);
    font-size: 1rem;
}

.form-control {
    width: 100%;
    padding: var(--space-lg);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--card-bg);
    color: var(--text-primary);
    font-family: var(--font-family);
    font-size: 1rem;
    transition: all var(--transition-normal);
    outline: none;
}

.form-control:focus {
    border-color: var(--primary-gold);
    box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.2);
    background: var(--surface-bg);
}

.form-control::placeholder {
    color: var(--text-muted);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
    line-height: 1.6;
}

.submit-btn {
    width: 100%;
    padding: var(--space-lg) var(--space-2xl);
    background: var(--gradient-primary);
    color: #000;
    border: none;
    border-radius: var(--radius-md);
    font-family: var(--font-family);
    font-weight: var(--font-weight-semibold);
    font-size: 1.1rem;
    cursor: pointer;
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
}

.submit-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.submit-btn:hover::before {
    left: 100%;
}

.submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-gold);
    filter: brightness(1.1);
}

.submit-btn:active {
    transform: translateY(0);
}

.contact-info {
    background: var(--gradient-surface);
    border-radius: var(--radius-xl);
    padding: var(--space-3xl);
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-lg);
    height: fit-content;
}

.info-item {
    display: flex;
    align-items: center;
    margin-bottom: var(--space-2xl);
    padding: var(--space-lg);
    background: var(--card-bg);
    border-radius: var(--radius-md);
    border: 1px solid var(--border-color);
    transition: all var(--transition-normal);
}

.info-item:hover {
    transform: translateY(-2px);
    border-color: var(--primary-gold);
    box-shadow: var(--shadow-md);
}

.info-icon {
    width: 50px;
    height: 50px;
    border-radius: var(--radius-full);
    background: var(--gradient-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: #000;
    margin-right: var(--space-lg);
    flex-shrink: 0;
}

.info-content {
    flex: 1;
}

.info-title {
    font-size: 1.1rem;
    font-weight: var(--font-weight-semibold);
    color: var(--text-primary);
    margin-bottom: var(--space-xs);
}

.info-value {
    color: var(--text-secondary);
    font-size: 1rem;
}

.faq-section {
    margin-top: var(--space-4xl);
    padding: var(--space-3xl);
    background: var(--card-bg);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-color);
}

.faq-title {
    font-size: 1.5rem;
    font-weight: var(--font-weight-semibold);
    color: var(--text-primary);
    margin-bottom: var(--space-xl);
    text-align: center;
}

.faq-item {
    margin-bottom: var(--space-lg);
    padding: var(--space-lg);
    background: var(--surface-bg);
    border-radius: var(--radius-md);
    border-left: 3px solid var(--primary-gold);
}

.faq-question {
    font-weight: var(--font-weight-semibold);
    color: var(--text-primary);
    margin-bottom: var(--space-sm);
}

.faq-answer {
    color: var(--text-secondary);
    line-height: 1.6;
}

.back-link {
    text-align: center;
    margin-top: var(--space-4xl);
}

@keyframes slideUpFade {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes backgroundPulse {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 1; }
}

/* Responsive Design */
@media (max-width: 768px) {
    .contact-grid {
        grid-template-columns: 1fr;
        gap: var(--space-3xl);
    }
    
    .contact-form,
    .contact-info {
        padding: var(--space-2xl);
    }
}

@media (max-width: 480px) {
    .contact-form,
    .contact-info {
        padding: var(--space-xl);
    }
    
    .info-item {
        flex-direction: column;
        text-align: center;
    }
    
    .info-icon {
        margin-right: 0;
        margin-bottom: var(--space-md);
    }
}
</style>

<!-- Hero Section -->
<section class="contact-hero">
    <div class="contact-container">
        <h1 class="contact-title">Contact Us</h1>
        <p class="contact-subtitle">
            Have questions or suggestions? We'd love to hear from you! 
            Send us a message and we'll respond as soon as possible.
        </p>
    </div>
</section>

<!-- Main Content -->
<section class="contact-content">
    <div class="contact-container">
        
        <!-- Success/Error Messages -->
        <?php if (!empty($successMessage)): ?>
            <div class="alert alert-success" style="margin-bottom: var(--space-3xl);">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($successMessage) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-error" style="margin-bottom: var(--space-3xl);">
                <i class="fas fa-exclamation-circle"></i>
                <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>
        
        <div class="contact-grid">
            <!-- Contact Form -->
            <div class="contact-form-section">
                <h2 class="section-title">Send us a Message</h2>
                
                <form method="post" class="contact-form" id="contactForm">
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name *</label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            class="form-control"
                            placeholder="Enter your full name" 
                            value="<?= htmlspecialchars($name ?? '') ?>" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address *</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="form-control"
                            placeholder="Enter your email address" 
                            value="<?= htmlspecialchars($email ?? '') ?>" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="subject" class="form-label">Subject *</label>
                        <input 
                            type="text" 
                            id="subject" 
                            name="subject" 
                            class="form-control"
                            placeholder="What is this about?" 
                            value="<?= htmlspecialchars($subject ?? '') ?>" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="message" class="form-label">Message *</label>
                        <textarea 
                            id="message" 
                            name="message" 
                            class="form-control"
                            placeholder="Tell us more about your inquiry..." 
                            required
                        ><?= htmlspecialchars($message ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-paper-plane"></i>
                        Send Message
                    </button>
                </form>
            </div>
            
            <!-- Contact Information -->
            <div class="contact-info-section">
                <h2 class="section-title">Get in Touch</h2>
                
                <div class="contact-info">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-title">Email Address</div>
                            <div class="info-value">info@audiencelk.com</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-title">Phone Number</div>
                            <div class="info-value">+94 11 234 5678</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-title">Support Hours</div>
                            <div class="info-value">Monday - Friday: 9:00 AM - 6:00 PM</div>
                        </div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-title">Location</div>
                            <div class="info-value">Colombo, Sri Lanka</div>
                        </div>
                    </div>
                </div>
                
                <!-- FAQ Section -->
                <div class="faq-section">
                    <h3 class="faq-title">Frequently Asked Questions</h3>
                    
                    <div class="faq-item">
                        <div class="faq-question">How quickly will I receive a response?</div>
                        <div class="faq-answer">We typically respond to all inquiries within 24 hours during business days.</div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">Can I get help with event planning?</div>
                        <div class="faq-answer">Absolutely! Our team can assist you with event planning, promotion, and management.</div>
                    </div>
                    
                    <div class="faq-item">
                        <div class="faq-question">Is there technical support available?</div>
                        <div class="faq-answer">Yes, we provide technical support for all platform features and booking issues.</div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Back to Home Link -->
        <div class="back-link">
            <a href="index.php" class="btn btn-outline btn-lg">
                <i class="fas fa-home"></i>
                Back to Home
            </a>
        </div>
    </div>
</section>

<!-- JavaScript for Form Enhancement -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contactForm');
    const inputs = form.querySelectorAll('input, textarea');
    
    // Add real-time validation
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            // Remove error styling on input
            this.style.borderColor = '';
        });
    });
    
    function validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        
        if (field.hasAttribute('required') && !value) {
            isValid = false;
        } else if (field.type === 'email' && value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            isValid = emailRegex.test(value);
        }
        
        if (!isValid) {
            field.style.borderColor = 'var(--error-color)';
            field.style.boxShadow = '0 0 0 3px rgba(220, 53, 69, 0.2)';
        } else {
            field.style.borderColor = 'var(--success-color)';
            field.style.boxShadow = '0 0 0 3px rgba(40, 167, 69, 0.2)';
        }
    }
    
    // Form submission with loading state
    form.addEventListener('submit', function(e) {
        const submitBtn = this.querySelector('.submit-btn');
        const originalText = submitBtn.innerHTML;
        
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        submitBtn.disabled = true;
        
        // Re-enable after a short delay (form will reload anyway)
        setTimeout(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }, 2000);
    });
});
</script>

<?php include 'includes/footer.php'; ?>