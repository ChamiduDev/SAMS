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
            if (window.innerWidth < 768) {
                const wrapper = document.getElementById('wrapper');
                const sidebarWrapper = document.getElementById('sidebar-wrapper');
                const toggleButton = document.getElementById('sidebarToggle');
                
                if (!sidebarWrapper.contains(event.target) && 
                    !toggleButton.contains(event.target) && 
                    wrapper.classList.contains('toggled')) {
                    wrapper.classList.remove('toggled');
                }
            }
        });
        
        // Toggle sidebar based on screen size
        function handleResize() {
            const wrapper = document.getElementById('wrapper');
            if (window.innerWidth >= 768) {
                wrapper.classList.add('toggled');
            } else {
                wrapper.classList.remove('toggled');
            }
        }
        
        window.addEventListener('resize', handleResize);
        handleResize(); // Initial check
    </script>
</body>
</html>
