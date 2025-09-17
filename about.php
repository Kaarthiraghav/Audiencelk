<?php 
$pageTitle = 'About Us - AudienceLK';
include 'includes/header.php'; 
include 'includes/db_connect.php'; 
?>

<style>
/* About Page Specific Styles */
/* Removing old styles since we're using inline styles for the hero section */

.about-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 var(--space-xl);
    position: relative;
    z-index: 2;
}

/* Removed old about-title styles as we're using inline styles */

.about-subtitle {
    font-size: 1.3rem;
    color: var(--text-secondary);
    margin-bottom: var(--space-4xl);
    line-height: 1.6;
    animation: slideUpFade 1s ease-out 0.2s both;
}

.about-sections {
    padding: var(--space-5xl) 0;
    background: var(--dark-bg);
}

.about-section {
    margin-bottom: var(--space-5xl);
    animation: slideUpFade 1s ease-out;
}

.section-title {
    font-size: 2.5rem;
    font-weight: var(--font-weight-bold);
    color: var(--text-primary);
    margin-bottom: var(--space-2xl);
    text-align: center;
    position: relative;
}

.section-title::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 50%;
    transform: translateX(-50%);
    width: 100px;
    height: 3px;
    background: var(--gradient-primary);
    border-radius: var(--radius-full);
}

.section-content {
    font-size: 1.2rem;
    color: var(--text-secondary);
    line-height: 1.8;
    text-align: center;
    max-width: 800px;
    margin: 0 auto;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--space-3xl);
    margin-top: var(--space-4xl);
}

.feature-card {
    background: var(--gradient-surface);
    border-radius: var(--radius-xl);
    padding: var(--space-3xl);
    text-align: center;
    border: 1px solid var(--border-color);
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--gradient-primary);
    transform: scaleX(0);
    transition: transform var(--transition-normal);
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
    border-color: var(--primary-gold);
}

.feature-card:hover::before {
    transform: scaleX(1);
}

.feature-icon {
    width: 80px;
    height: 80px;
    border-radius: var(--radius-full);
    background: var(--gradient-primary);
    margin: 0 auto var(--space-xl);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: #000;
}

.feature-title {
    font-size: 1.5rem;
    font-weight: var(--font-weight-semibold);
    color: var(--text-primary);
    margin-bottom: var(--space-lg);
}

.feature-description {
    color: var(--text-secondary);
    line-height: 1.6;
    font-size: 1.1rem;
}

.values-section {
    padding: var(--space-5xl) 0;
    background: var(--gradient-surface);
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--space-2xl);
    margin-top: var(--space-4xl);
}

.value-card {
    background: var(--card-bg);
    border-radius: var(--radius-lg);
    padding: var(--space-2xl);
    text-align: center;
    border: 1px solid var(--border-color);
    transition: all var(--transition-normal);
}

.value-card:hover {
    transform: translateY(-5px);
    border-color: var(--primary-gold);
    box-shadow: var(--shadow-lg);
}

.value-icon {
    width: 60px;
    height: 60px;
    border-radius: var(--radius-full);
    background: var(--gradient-secondary);
    margin: 0 auto var(--space-lg);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
}

.value-title {
    font-size: 1.2rem;
    font-weight: var(--font-weight-semibold);
    color: var(--text-primary);
    margin-bottom: var(--space-md);
}

.value-description {
    color: var(--text-secondary);
    line-height: 1.6;
    font-size: 0.95rem;
}

.cta-section {
    padding: var(--space-5xl) 0;
    background: var(--dark-bg);
    text-align: center;
}

.cta-buttons {
    display: flex;
    gap: var(--space-lg);
    justify-content: center;
    flex-wrap: wrap;
    margin-top: var(--space-3xl);
}

.team-credit {
    margin-top: var(--space-4xl);
    padding: var(--space-2xl);
    background: var(--gradient-surface);
    border-radius: var(--radius-lg);
    border: 1px solid var(--border-color);
    text-align: center;
    position: relative;
    overflow: hidden;
}

.team-credit::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--gradient-primary);
    opacity: 0.05;
}

.team-credit-content {
    position: relative;
    z-index: 1;
}

.team-title {
    font-size: 1.5rem;
    font-weight: var(--font-weight-bold);
    color: var(--primary-gold);
    margin-bottom: var(--space-md);
}

.team-description {
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: var(--space-lg);
}

.team-signature {
    font-size: 1.2rem;
    font-weight: var(--font-weight-semibold);
    color: var(--primary-gold);
    cursor: pointer;
    transition: all var(--transition-normal);
    display: inline-block;
}

.team-signature:hover {
    transform: scale(1.05);
    text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
}

/* Scroll animations */
.scroll-reveal {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.8s ease-out;
}

.scroll-reveal.revealed {
    opacity: 1;
    transform: translateY(0);
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

.page-gradient-bg {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to bottom, rgba(0,0,0,0.4) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0.6) 100%),
                radial-gradient(ellipse at center, rgba(50, 50, 50, 0.4) 0%, transparent 70%);
    z-index: -1;
}

@keyframes backgroundPulse {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 1; }
}

/* Responsive Design */
@media (max-width: 768px) {
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .values-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<!-- Hero Section -->
<section class="hero-section" style="position: relative; height: 70vh; min-height: 500px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #121212 0%, #1a1a1a 100%); border-bottom: 1px solid rgba(255, 215, 0, 0.2);">
    <div class="hero-background" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: radial-gradient(circle at 20% 50%, rgba(255, 215, 0, 0.1) 0%, transparent 50%), radial-gradient(circle at 80% 20%, rgba(255, 215, 0, 0.1) 0%, transparent 50%);"></div>
    
    <div class="hero-content" style="text-align: center; z-index: 2; max-width: 1000px; padding: 0 var(--space-xl); animation: fadeInUp 1.2s ease-out;">
        <h1 style="font-size: clamp(3.5rem, 8vw, 5rem); font-weight: 800; color: #FFD700; margin-bottom: var(--space-xl); line-height: 1.1;">About AudienceLK</h1>
        <p style="font-size: clamp(1.1rem, 3vw, 1.5rem); color: #ddd; margin: 0 auto; max-width: 800px; line-height: 1.6;">
            Your premier platform for discovering and managing amazing events. Connect with your community through unforgettable experiences.
        </p>
    </div>
</section>

<!-- Decorative divider -->
<div style="width: 100%; height: 6px; background: linear-gradient(90deg, transparent, rgba(255, 215, 0, 0.3), transparent); margin: 0 auto;"></div>

<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<!-- Main Content Sections -->
<section class="about-sections" style="margin-top: 60px;">
    <div class="about-container">
        <!-- Vision Section -->
        <div class="about-section scroll-reveal">
            <h2 class="section-title" style="position: relative;">Our Vision</h2>
            <div class="section-content">
                <p style="font-size: 1.25rem; line-height: 1.8;">
                    At AudienceLK, our vision is to create a vibrant and sustainable platform where communities can discover, share, and participate in meaningful events. 
                    We believe in connecting people through experiences that inspire, educate, and empower, while promoting inclusive gatherings that bring people together across cultures and interests.
                </p>
            </div>
        </div>

        <!-- Mission Section -->
        <div class="about-section scroll-reveal">
            <h2 class="section-title">Our Mission</h2>
            <div class="section-content">
                <p>
                    We are dedicated to providing a comprehensive event management platform that makes it easy for organizers to create and manage events, 
                    while giving attendees a seamless booking experience. Our platform supports cultural events, tech conferences, hackathons, workshops, and more, 
                    fostering a thriving ecosystem of knowledge sharing and community building.
                </p>
            </div>
        </div>

        <!-- Features Section -->
        <div class="about-section scroll-reveal">
            <h2 class="section-title">What We Offer</h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <h3 class="feature-title">Easy Booking</h3>
                    <p class="feature-description">
                        Simple and secure event booking process with multiple payment options and instant confirmations.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3 class="feature-title">Event Management</h3>
                    <p class="feature-description">
                        Comprehensive tools for organizers to create, manage, and promote their events with real-time analytics.
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="feature-title">Community Focus</h3>
                    <p class="feature-description">
                        Building connections through shared experiences, networking opportunities, and collaborative events.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="values-section scroll-reveal">
    <div class="about-container">
        <h2 class="section-title">Our Values</h2>
        
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3 class="value-title">Inclusivity</h3>
                <p class="value-description">
                    We welcome everyone regardless of background, culture, or experience level.
                </p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3 class="value-title">Trust</h3>
                <p class="value-description">
                    Security and transparency in all our operations and user interactions.
                </p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3 class="value-title">Innovation</h3>
                <p class="value-description">
                    Continuously improving and adapting to meet evolving community needs.
                </p>
            </div>
            
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-handshake"></i>
                </div>
                <h3 class="value-title">Collaboration</h3>
                <p class="value-description">
                    Working together to create meaningful connections and experiences.
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="cta-section scroll-reveal" style="background: linear-gradient(135deg, #121212 0%, #1a1a1a 100%); border-top: 1px solid rgba(255, 215, 0, 0.2); margin-top: 60px; padding: 80px 0;">
    <div class="about-container">
        <h2 class="section-title" style="color: #FFD700; font-size: 2.8rem;">Ready to Get Started?</h2>
        <p class="section-content" style="margin-bottom: 30px; font-size: 1.2rem; color: #ddd;">
            Join our community today and discover amazing events or start organizing your own.
        </p>
        
        <div class="cta-buttons" style="display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">
            <a href="events/view_events.php" class="button-exploreevents" style="padding: 15px 30px; font-size: 1.1em; background: linear-gradient(45deg, #FFD700, #FFA500); border-radius: 25px;">
                <i class="fas fa-calendar-alt" style="margin-right: 8px;"></i>
                EXPLORE EVENTS
            </a>
            <a href="auth/register.php" style="text-decoration: none;">
                <button class="button-backtohome" style="padding: 15px 30px; font-size: 1.1em; border: 2px solid #FFD700; background: transparent; color: #FFD700 !important; transition: all 0.3s ease; border-radius: 25px;">
                    JOIN COMMUNITY
                </button>
            </a>
        </div>
        
        <!-- Team Credit -->
        <div class="team-credit">
            <div class="team-credit-content">
                <h3 class="team-title">Created with ❤️</h3>
                <p class="team-description">
                    AudienceLK is proudly developed by a passionate team dedicated to bringing communities together through technology.
                </p>
                <div class="team-signature" onclick="showTeamModal()">
                    Web Group AF
                </div>
            </div>
        </div>
    </div>
</section>

<!-- JavaScript for Animations -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Scroll reveal animation
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
            }
        });
    }, observerOptions);

    document.querySelectorAll('.scroll-reveal').forEach(el => {
        observer.observe(el);
    });
});

function showTeamModal() {
    alert('🎉 Thank you for visiting AudienceLK!\n\nDeveloped by Web Group AF with passion for connecting communities through meaningful events.\n\n✨ Features include modern design, secure authentication, event management, and community building tools.');
}
</script>

<?php include 'includes/footer.php'; ?>