        </div> <!-- Close container-fluid -->
    </div> <!-- Close main-wrapper -->
</div> <!-- Close app-container -->

<footer class="footer mt-auto py-4 bg-white border-top border-light">
    <div class="container text-center">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <span class="text-muted small">&copy; <?php echo date("Y"); ?> <strong>Student Apt</strong>. All rights reserved.</span>
            <div class="d-flex gap-4">
                <a href="#" class="text-muted small text-decoration-none hover-primary transition">Terms</a>
                <a href="#" class="text-muted small text-decoration-none hover-primary transition">Privacy</a>
                <a href="#" class="text-muted small text-decoration-none hover-primary transition">Support</a>
            </div>
            <span class="text-muted small opacity-50">v2.1.0-premium</span>
        </div>
    </div>
</footer>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainWrapper = document.querySelector('.main-wrapper');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle && sidebar && mainWrapper) {
        sidebarToggle.addEventListener('click', function() {
            if (window.innerWidth >= 992) {
                // Desktop: Toggle collapsed state for sidebar and full-width for main
                sidebar.classList.toggle('collapsed');
                mainWrapper.classList.toggle('full-width');
            } else {
                // Mobile: Toggle show state and overlay
                sidebar.classList.toggle('show');
                if (overlay) overlay.classList.toggle('show');
            }
        });

        if (overlay) {
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('show');
                overlay.classList.remove('show');
            });
        }
    }
    
    // Auto-active link highlight reinforcement
    const currentPath = window.location.pathname;
    document.querySelectorAll('.nav-link').forEach(link => {
        if (link.getAttribute('href') && currentPath.includes(link.getAttribute('href'))) {
            // Only add active if not already there (PHP usually handles this, but JS is a fallback)
            if (!link.classList.contains('active')) {
                // link.classList.add('active'); // PHP is reliable enough, but this helps for dynamic content
            }
        }
    });

    // Add glass effect on scroll to navbar
    window.addEventListener('scroll', function() {
        const nav = document.querySelector('.navbar');
        if (window.scrollY > 10) {
            nav.classList.add('shadow-sm');
            nav.style.background = 'rgba(255, 255, 255, 0.9)';
        } else {
            nav.style.background = 'rgba(255, 255, 255, 0.85)';
        }
    });
});
</script>
</body>
</html>
