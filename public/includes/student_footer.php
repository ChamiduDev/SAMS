    </div> <!-- End of wrapper -->
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Sidebar Toggle Script -->
    <script>
        document.getElementById("sidebarToggle").addEventListener("click", function(e) {
            e.preventDefault();
            document.getElementById("wrapper").classList.toggle("toggled");
        });
        
        // Close sidebar on mobile when clicking outside
        document.addEventListener('click', function(event) {
            const wrapper = document.getElementById('wrapper');
            const sidebarWrapper = document.getElementById('sidebar-wrapper');
            const toggleButton = document.getElementById('sidebarToggle');
            
            if (window.innerWidth < 768) {
                if (!sidebarWrapper.contains(event.target) && 
                    !toggleButton.contains(event.target) && 
                    !wrapper.classList.contains('toggled')) {
                    wrapper.classList.remove('toggled');
                }
            }
        });
    </script>
</body>
</html>
