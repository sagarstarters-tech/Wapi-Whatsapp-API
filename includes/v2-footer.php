            </div> <!-- End dash-content -->
        </main>
    </div> <!-- End dash-layout -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple dashboard utilities
        document.addEventListener('DOMContentLoaded', function() {
            // Highlighting active nav item based on URL
            const currentPath = window.location.pathname;
            document.querySelectorAll('.dash-nav-item').forEach(item => {
                if(item.getAttribute('href').includes(currentPath)) {
                    document.querySelectorAll('.dash-nav-item').forEach(i => i.classList.remove('active'));
                    item.classList.add('active');
                }
            });
            
            // Mobile sidebar toggle (placeholder)
            window.toggleSidebar = function() {
                const sidebar = document.querySelector('.dash-sidebar');
                sidebar.classList.toggle('collapsed');
            }
        });
    </script>
</body>
</html>
