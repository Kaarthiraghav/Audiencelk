// Dashboard: Remove booked event on cancel
document.addEventListener('DOMContentLoaded', function() {
    // Apply page transition animation
    document.body.classList.add('fade-in');
    
    // Add glowing effect to main heading
    const mainHeadings = document.querySelectorAll('h1, h2');
    if (mainHeadings.length) {
        mainHeadings.forEach(heading => {
            heading.classList.add('glow-effect');
        });
    }

    // Card hover effects
    const cards = document.querySelectorAll('.card');
    if (cards.length) {
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 15px 35px rgba(0, 0, 0, 0.5)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.4)';
            });
        });
    }
    
    // Button hover effects
    const buttons = document.querySelectorAll('button, .button-exploreevents, .button-backtohome');
    if (buttons.length) {
        buttons.forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-3px)';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    }
    
    // Table row hover effects
    const tableRows = document.querySelectorAll('table tr');
    if (tableRows.length) {
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.backgroundColor = '#252525';
                this.style.transition = 'background-color 0.3s';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.backgroundColor = '';
            });
        });
    }

    // Cancel buttons
    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const element = this.closest('.booked-event');
            element.style.opacity = '0';
            element.style.transform = 'translateX(20px)';
            setTimeout(() => element.remove(), 300);
        });
    });

    // Events: Book Now button and event details expansion
    const checkboxes = document.querySelectorAll('.event-checkbox');
    const bookBtn = document.getElementById('book-now-btn');
    if (checkboxes.length && bookBtn) {
        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const anyChecked = Array.from(checkboxes).some(c => c.checked);
                
                if (anyChecked) {
                    bookBtn.style.display = 'block';
                    bookBtn.style.opacity = '0';
                    bookBtn.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        bookBtn.style.opacity = '1';
                        bookBtn.style.transform = 'translateY(0)';
                    }, 10);
                } else {
                    bookBtn.style.opacity = '0';
                    bookBtn.style.transform = 'translateY(10px)';
                    setTimeout(() => {
                        bookBtn.style.display = 'none';
                    }, 300);
                }

                // expanding event details with smooth animation
                const details = cb.closest('li').querySelector('.event-details');
                if(details) {
                    if(cb.checked) {
                        details.classList.add('open');
                        details.style.opacity = '0';
                        setTimeout(() => details.style.opacity = '1', 10);
                    } else {
                        details.style.opacity = '0';
                        setTimeout(() => details.classList.remove('open'), 300);
                    }
                }
            });
        });
    }
    
    // Add smooth scrolling
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                window.scrollTo({
                    top: target.offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
});