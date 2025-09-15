// Dashboard: Remove booked event on cancel
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.cancel-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.closest('.booked-event').remove();
        });
    });

    // Events: Book Now button and event details expansion
    const checkboxes = document.querySelectorAll('.event-checkbox');
    const bookBtn = document.getElementById('book-now-btn');
    if (checkboxes.length && bookBtn) {
        checkboxes.forEach(cb => {
            cb.addEventListener('change', () => {
                const anyChecked = Array.from(checkboxes).some(c => c.checked);
                bookBtn.style.display = anyChecked ? 'block' : 'none';

                // expanding event details
                const details = cb.closest('li').querySelector('.event-details');
                if(details) {
                    if(cb.checked) {
                        details.classList.add('open');
                    } else {
                        details.classList.remove('open');
                    }
                }
            });
        });
    }
});