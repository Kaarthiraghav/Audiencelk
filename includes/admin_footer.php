        </div>
    </div>
    
    <script>
        // Add any admin-specific JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            // Animation for stats cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>