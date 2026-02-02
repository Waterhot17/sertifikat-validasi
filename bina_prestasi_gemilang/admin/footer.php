<?php
// ============================================
// FILE: FOOTER.PHP - FOOTER UNTUK SEMUA HALAMAN
// ============================================
?>
        </div> <!-- End of main-content -->
    </div> <!-- End of row -->
</div> <!-- End of container-fluid -->

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Custom Scripts -->
<script>
// Sidebar toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.getElementById('mainContent');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            if (window.innerWidth < 992) {
                if (sidebar.classList.contains('show')) {
                    mainContent.style.marginLeft = '260px';
                } else {
                    mainContent.style.marginLeft = '0';
                }
            }
        });
    }
    
    // Auto-hide sidebar on mobile when clicking outside
    document.addEventListener('click', function(event) {
        if (window.innerWidth < 992 && sidebar && sidebar.classList.contains('show')) {
            if (!sidebar.contains(event.target) && 
                sidebarToggle && 
                !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('show');
                mainContent.style.marginLeft = '0';
            }
        }
    });
    
    // Tooltip initialization
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Popover initialization
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
});
</script>

<!-- Success/Error message handler -->
<script>
$(document).ready(function() {
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert:not(.alert-permanent)').fadeOut('slow');
    }, 5000);
    
    // Confirm before deleting
    $('.confirm-delete').on('click', function() {
        return confirm('Apakah Anda yakin ingin menghapus data ini?');
    });
    
    // Form validation
    $('form.needs-validation').on('submit', function(event) {
        if (this.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
});
</script>

<!-- Print functionality -->
<script>
function printElement(elementId) {
    var printContent = document.getElementById(elementId).innerHTML;
    var originalContent = document.body.innerHTML;
    
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}
</script>

<!-- Datepicker initialization (if needed) -->
<script>
$(function() {
    $('.datepicker').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true
    });
});
</script>

<!-- DataTables initialization (if needed) -->
<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Indonesian.json"
            },
            "responsive": true,
            "pageLength": 25
        });
    }
});
</script>

</body>
</html>