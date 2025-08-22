<?php
// public/includes/footer.php
?>

        </div> <!-- /container-fluid -->
    </div> <!-- /#page-content-wrapper -->
</div> <!-- /#wrapper -->

<!-- Initialize Tooltips -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

</body>
</html>