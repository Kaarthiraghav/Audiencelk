<?php 
$pageTitle = 'Home - AudienceLK';
require_once 'config.php';
include 'includes/header.php'; 
include 'includes/db_connect.php'; 

// Get comprehensive stats for the homepage
try {
    $total_events = $connection->query('SELECT COUNT(*) FROM events WHERE status = "approved"')->fetch_row()[0];
    $total_users = $connection->query('SELECT COUNT(*) FROM users')->fetch_row()[0];
    $upcoming_events = $connection->query('SELECT COUNT(*) FROM events WHERE status = "approved" AND event_date > NOW()')->fetch_row()[0];
    $total_bookings = $connection->query('SELECT COUNT(*) FROM bookings')->fetch_row()[0];
    
    // Get featured events
    $featured_events = $connection->query('SELECT e.*, c.name as category_name FROM events e 
                                         LEFT JOIN event_categories c ON e.category_id = c.id 
                                         WHERE e.status = "approved" AND e.event_date > NOW() 
                                         ORDER BY e.event_date ASC LIMIT 3');
    
    // Get event categories
    $categories = $connection->query('SELECT * FROM event_categories ORDER BY name');
} catch (Exception $e) {
    $total_events = $total_users = $upcoming_events = $total_bookings = 0;
    $featured_events = $categories = null;
}
?>

<style>
/* Homepage Specific Styles */
.hero-section {
    position: relative;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, var(--dark-bg) 0%, #1a1a1a 50%, var(--surface-bg) 100%);
    overflow: hidden;
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: 
        radial-gradient(circle at 20% 50%, rgba(255, 215, 0, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 80% 20%, rgba(220, 20, 60, 0.1) 0%, transparent 50%),
        radial-gradient(circle at 40% 80%, rgba(255, 165, 0, 0.1) 0%, transparent 50%);
    animation: backgroundPulse 20s ease-in-out infinite;
}

@keyframes backgroundPulse {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 1; }
}

.hero-content {
    text-align: center;
    z-index: 2;
    max-width: 1000px;
    padding: 0 var(--space-xl);
}

.hero-title {
    font-size: clamp(2.5rem, 8vw, 5rem);
    font-weight: var(--font-weight-black);
    background: var(--gradient-primary);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: var(--space-xl);
    line-height: 1.1;
    animation: slideUpFade 1s ease-out;
}

.hero-subtitle {
    font-size: clamp(1.1rem, 3vw, 1.5rem);
    color: var(--text-secondary);
    margin-bottom: var(--space-4xl);
    line-height: 1.6;
    animation: slideUpFade 1s ease-out 0.2s both;
}

.hero-cta {
    display: flex;
    gap: var(--space-lg);
    justify-content: center;
    flex-wrap: wrap;
    animation: slideUpFade 1s ease-out 0.4s both;
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

.stats-section {
    padding: var(--space-5xl) 0;
    background: var(--gradient-surface);
    position: relative;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: var(--space-3xl);
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--space-xl);
}

.stat-item {
    text-align: center;
    animation: countUp 2s ease-out;
}

.stat-number {
    font-size: clamp(2rem, 5vw, 3.5rem);
    font-weight: var(--font-weight-black);
    color: var(--primary-gold);
    display: block;
    margin-bottom: var(--space-md);
    text-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
}

.stat-label {
    font-size: 1.1rem;
    color: var(--text-secondary);
    font-weight: var(--font-weight-medium);
    text-transform: uppercase;
    letter-spacing: 1px;
}

@keyframes countUp {
    from { opacity: 0; transform: translateY(20px) scale(0.8); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.featured-section {
    padding: var(--space-5xl) 0;
    background: var(--dark-bg);
}

.section-header {
    text-align: center;
    margin-bottom: var(--space-5xl);
}

.section-title {
    font-size: clamp(2rem, 5vw, 3rem);
    font-weight: var(--font-weight-bold);
    color: var(--text-primary);
    margin-bottom: var(--space-lg);
}

.section-subtitle {
    font-size: 1.2rem;
    color: var(--text-muted);
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.6;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: var(--space-3xl);
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--space-xl);
}

.event-card {
    background: var(--gradient-surface);
    border-radius: var(--radius-xl);
    border: 1px solid var(--border-color);
    overflow: hidden;
    transition: all var(--transition-normal);
    position: relative;
    transform: translateY(0);
}

.event-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--shadow-xl);
    border-color: var(--primary-gold);
}

.event-card::before {
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

.event-card:hover::before {
    transform: scaleX(1);
}

.event-image {
    height: 200px;
    background: var(--gradient-secondary);
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: white;
}

.event-category {
    position: absolute;
    top: var(--space-lg);
    right: var(--space-lg);
    background: rgba(0, 0, 0, 0.8);
    padding: var(--space-xs) var(--space-md);
    border-radius: var(--radius-full);
    font-size: 0.8rem;
    color: var(--primary-gold);
    font-weight: var(--font-weight-semibold);
}

.event-info {
    padding: var(--space-2xl);
}

.event-title {
    font-size: 1.4rem;
    font-weight: var(--font-weight-semibold);
    color: var(--text-primary);
    margin-bottom: var(--space-md);
    line-height: 1.3;
}

.event-meta {
    display: flex;
    align-items: center;
    gap: var(--space-lg);
    margin-bottom: var(--space-lg);
    font-size: 0.9rem;
    color: var(--text-muted);
}

.event-description {
    color: var(--text-secondary);
    line-height: 1.6;
    margin-bottom: var(--space-xl);
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.categories-section {
    padding: var(--space-5xl) 0;
    background: var(--gradient-surface);
}

.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: var(--space-2xl);
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 var(--space-xl);
}

.category-card {
    background: var(--card-bg);
    border-radius: var(--radius-lg);
    padding: var(--space-3xl);
    text-align: center;
    border: 1px solid var(--border-color);
    transition: all var(--transition-normal);
    position: relative;
    overflow: hidden;
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: var(--gradient-primary);
    opacity: 0;
    transition: opacity var(--transition-normal);
}

.category-card:hover::before {
    opacity: 0.1;
}

.category-card:hover {
    transform: translateY(-5px);
    border-color: var(--primary-gold);
    box-shadow: var(--shadow-lg);
}

.category-icon {
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
    position: relative;
    z-index: 1;
}

.category-title {
    font-size: 1.3rem;
    font-weight: var(--font-weight-semibold);
    color: var(--text-primary);
    margin-bottom: var(--space-md);
    position: relative;
    z-index: 1;
}

.category-description {
    color: var(--text-secondary);
    line-height: 1.6;
    position: relative;
    z-index: 1;
}

.newsletter-section {
    padding: var(--space-5xl) 0;
    background: var(--dark-bg);
    text-align: center;
}

.newsletter-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 0 var(--space-xl);
}

.newsletter-form {
    display: flex;
    gap: var(--space-md);
    margin-top: var(--space-3xl);
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.newsletter-input {
    flex: 1;
    padding: var(--space-lg);
    border: 2px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--surface-bg);
    color: var(--text-primary);
    font-size: 1rem;
    outline: none;
    transition: border-color var(--transition-normal);
}

.newsletter-input:focus {
    border-color: var(--primary-gold);
}

.newsletter-btn {
    padding: var(--space-lg) var(--space-2xl);
    background: var(--gradient-primary);
    color: #000;
    border: none;
    border-radius: var(--radius-md);
    font-weight: var(--font-weight-semibold);
    cursor: pointer;
    transition: all var(--transition-normal);
}

.newsletter-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-gold);
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-cta {
        flex-direction: column;
        align-items: center;
    }
    
    .newsletter-form {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: var(--space-2xl);
    }
    
    .events-grid,
    .categories-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .event-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-sm);
    }
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
</style>

<!-- Hero Section -->
<section class="hero-section">
    <div class="hero-background"></div>
    <div class="hero-content">
        <h1 class="hero-title">Welcome to AudienceLK</h1>
        <p class="hero-subtitle">
            Your premier platform for discovering and managing amazing events. 
            Connect with your community through unforgettable experiences.
        </p>
        <div class="hero-cta">
            <a href="events/view_events.php" class="btn btn-primary btn-lg">
                <i class="fas fa-calendar-alt"></i>
                Explore Events
            </a>
            <a href="auth/register.php" class="btn btn-outline btn-lg">
                <i class="fas fa-user-plus"></i>
                Join Community
            </a>
        </div>
    </div>
</section>

<!-- Statistics Section -->
<section class="stats-section scroll-reveal">
    <div class="stats-grid">
        <div class="stat-item">
            <span class="stat-number" data-count="<?= $total_events ?>"><?= $total_events ?></span>
            <span class="stat-label">Events Available</span>
        </div>
        <div class="stat-item">
            <span class="stat-number" data-count="<?= $upcoming_events ?>"><?= $upcoming_events ?></span>
            <span class="stat-label">Upcoming Events</span>
        </div>
        <div class="stat-item">
            <span class="stat-number" data-count="<?= $total_users ?>"><?= $total_users ?></span>
            <span class="stat-label">Community Members</span>
        </div>
        <div class="stat-item">
            <span class="stat-number" data-count="<?= $total_bookings ?>"><?= $total_bookings ?></span>
            <span class="stat-label">Total Bookings</span>
        </div>
    </div>
</section>

<!-- Featured Events Section -->
<section class="featured-section scroll-reveal">
    <div class="section-header">
        <h2 class="section-title">Featured Events</h2>
        <p class="section-subtitle">
            Discover upcoming events that will create lasting memories and meaningful connections.
        </p>
    </div>
    
    <div class="events-grid">
        <?php if ($featured_events && $featured_events->num_rows > 0): ?>
            <?php while ($event = $featured_events->fetch_assoc()): ?>
                <div class="event-card">
                    <div class="event-image">
                        <i class="fas fa-calendar-star"></i>
                        <div class="event-category"><?= htmlspecialchars($event['category_name'] ?? 'General') ?></div>
                    </div>
                    <div class="event-info">
                        <h3 class="event-title"><?= htmlspecialchars($event['title']) ?></h3>
                        <div class="event-meta">
                            <span><i class="fas fa-calendar"></i> <?= date('M j, Y', strtotime($event['event_date'])) ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($event['location']) ?></span>
                        </div>
                        <p class="event-description"><?= htmlspecialchars($event['description']) ?></p>
                        <a href="events/view_events.php?id=<?= $event['id'] ?>" class="btn btn-primary">
                            <i class="fas fa-ticket-alt"></i>
                            Book Now
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12 text-center">
                <p class="text-muted">No featured events available at the moment.</p>
                <a href="events/view_events.php" class="btn btn-primary">Browse All Events</a>
            </div>
        <?php endif; ?>
    </div>
    
    <div style="text-align: center; margin-top: var(--space-4xl);">
        <a href="events/view_events.php" class="btn btn-outline btn-lg">
            <i class="fas fa-arrow-right"></i>
            View All Events
        </a>
    </div>
</section>

<!-- Categories Section -->
<section class="categories-section scroll-reveal">
    <div class="section-header">
        <h2 class="section-title">Event Categories</h2>
        <p class="section-subtitle">
            Explore diverse categories of events tailored to your interests and passions.
        </p>
    </div>
    
    <div class="categories-grid">
        <?php if ($categories && $categories->num_rows > 0): ?>
            <?php 
            $category_icons = [
                'Cultural' => 'fas fa-theater-masks',
                'Entertainment' => 'fas fa-music',
                'Sports' => 'fas fa-football-ball',
                'Educational' => 'fas fa-graduation-cap',
                'Business' => 'fas fa-briefcase',
                'Technology' => 'fas fa-laptop-code',
                'Art' => 'fas fa-palette',
                'Food' => 'fas fa-utensils'
            ];
            ?>
            <?php while ($category = $categories->fetch_assoc()): ?>
                <div class="category-card">
                    <div class="category-icon">
                        <i class="<?= $category_icons[$category['name']] ?? 'fas fa-calendar' ?>"></i>
                    </div>
                    <h3 class="category-title"><?= htmlspecialchars($category['name']) ?></h3>
                    <p class="category-description">
                        <?= htmlspecialchars($category['description'] ?? 'Discover amazing ' . strtolower($category['name']) . ' events.') ?>
                    </p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <!-- Default categories if none in database -->
            <div class="category-card">
                <div class="category-icon"><i class="fas fa-theater-masks"></i></div>
                <h3 class="category-title">Cultural Events</h3>
                <p class="category-description">Experience rich traditions, music, and art from diverse cultures.</p>
            </div>
            <div class="category-card">
                <div class="category-icon"><i class="fas fa-music"></i></div>
                <h3 class="category-title">Entertainment</h3>
                <p class="category-description">Enjoy concerts, shows, and performances that entertain and inspire.</p>
            </div>
            <div class="category-card">
                <div class="category-icon"><i class="fas fa-graduation-cap"></i></div>
                <h3 class="category-title">Educational</h3>
                <p class="category-description">Learn new skills and expand your knowledge through workshops and seminars.</p>
            </div>
            <div class="category-card">
                <div class="category-icon"><i class="fas fa-users"></i></div>
                <h3 class="category-title">Community</h3>
                <p class="category-description">Connect with your local community through meaningful gatherings.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-section scroll-reveal">
    <div class="newsletter-container">
        <h2 class="section-title">Stay Updated</h2>
        <p class="section-subtitle">
            Subscribe to our newsletter and never miss out on exciting events and exclusive offers.
        </p>
        <form class="newsletter-form" id="newsletterForm">
            <input type="email" class="newsletter-input" placeholder="Enter your email address" required>
            <button type="submit" class="newsletter-btn">
                <i class="fas fa-paper-plane"></i>
                Subscribe
            </button>
        </form>
        <p style="margin-top: var(--space-lg); font-size: 0.9rem; color: var(--text-muted);">
            We respect your privacy. Unsubscribe at any time.
        </p>
    </div>
</section>

<!-- JavaScript for Animations and Interactions -->
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

    // Newsletter form handling
    document.getElementById('newsletterForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const email = this.querySelector('input[type="email"]').value;
        
        // Simple validation
        if (email) {
            // Here you would typically send the email to your backend
            alert('Thank you for subscribing! We\'ll keep you updated with the latest events.');
            this.reset();
        }
    });

    // Counter animation for stats
    function animateCounters() {
        const counters = document.querySelectorAll('.stat-number');
        
        counters.forEach(counter => {
            const target = parseInt(counter.getAttribute('data-count'));
            const duration = 2000; // 2 seconds
            const steps = 60;
            const stepValue = target / steps;
            let current = 0;
            
            const timer = setInterval(() => {
                current += stepValue;
                if (current >= target) {
                    counter.textContent = target;
                    clearInterval(timer);
                } else {
                    counter.textContent = Math.floor(current);
                }
            }, duration / steps);
        });
    }

    // Trigger counter animation when stats section is visible
    const statsSection = document.querySelector('.stats-section');
    const statsObserver = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounters();
                statsObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    if (statsSection) {
        statsObserver.observe(statsSection);
    }
});
</script>
    
    <div class="card">
        <h2>Tech Events</h2>
        <p style="margin-top: 15px; text-align: center; font-size: 1.1em; line-height: 1.6; color: #ddd;">
            Stay ahead of the curve with the latest trends and innovations in technology by attending our cutting-edge tech events.
            Network with industry professionals.
        </p>
        <div style="text-align: center; margin-top: 20px;">
            <a href="events/view_events.php" class="button-exploreevents" style="padding: 10px 20px;">Explore Tech Events</a>
        </div>
    </div>
    
    <div class="card">
        <h2>Hackathons</h2>
        <p style="margin-top: 15px; text-align: center; font-size: 1.1em; line-height: 1.6; color: #ddd;">
            Collaborate, code, and compete in our hackathons—perfect for creative problem solvers and tech enthusiasts.
            Build innovative solutions with like-minded individuals.
        </p>
        <div style="text-align: center; margin-top: 20px;">
            <a href="events/view_events.php" class="button-exploreevents" style="padding: 10px 20px;">Join Hackathons</a>
        </div>
    </div>
</div>

<div style="text-align: center; margin: 60px 0 40px 0;">
    <div style="margin-bottom: 30px;">
        <h2 style="color: #FFD700; margin-bottom: 20px; font-size: 2em;">Ready to Get Started?</h2>
        <p style="color: #ddd; font-size: 1.1em; margin-bottom: 30px;">
            Discover amazing events happening around you or create your own unforgettable experience.
        </p>
    </div>
    
    <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
        <a href="events/view_events.php">
            <button class="button-exploreevents" style="padding: 15px 30px; font-size: 1.1em;">
                <svg class="svgIcon" viewBox="0 0 512 512" height="1.2em" xmlns="http://www.w3.org/2000/svg" style="margin-right: 10px;">
                    <path d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm50.7-186.9L162.4 380.6c-19.4 7.5-38.5-11.6-31-31l55.5-144.3c3.3-8.5 9.9-15.1 18.4-18.4l144.3-55.5c19.4-7.5 38.5 11.6 31 31L325.1 306.7c-3.2 8.5-9.9 15.1-18.4 18.4zM288 256a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"></path>
                </svg>
                Explore Events
            </button>
        </a>
        
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="auth/register.php">
                <button class="button-backtohome" style="padding: 15px 30px; font-size: 1.1em;">
                    Join Community
                </button>
            </a>
        <?php else: ?>
            <?php if ($_SESSION['role_id'] === 1 || $_SESSION['role_id'] === 2): ?>
                <a href="events/add_event.php">
                    <button class="button-backtohome" style="padding: 15px 30px; font-size: 1.1em;">
                        Create Event
                    </button>
                </a>
            <?php else: ?>
                <a href="bookings/add_booking.php">
                    <button class="button-backtohome" style="padding: 15px 30px; font-size: 1.1em;">
                        Book Event
                    </button>
                </a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

});
</script>

<?php include 'includes/footer.php'; ?>