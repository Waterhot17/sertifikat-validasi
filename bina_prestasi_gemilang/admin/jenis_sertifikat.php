<?php
$page_title = "Validasi Manual";
require_once 'header.php';

// Hapus require_once database.php
// require_once 'includes/database.php';

// Buat koneksi database langsung
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "Bina_Prestasi_Gemilang";

// Buat koneksi
$conn = new mysqli($servername, $username, $password, $dbname);

// Cek koneksi
if ($conn->connect_error) {
    // Jika error, tampilkan pesan tanpa menghentikan script
    echo '<div class="alert alert-danger">Koneksi database gagal: ' . $conn->connect_error . '</div>';
    $conn = null; // Set null untuk menghindari error
}
?>

<div class="page-header">
    <h1><i class="fas fa-check-circle me-2"></i>Validasi Manual</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Validasi Manual</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Cari Sertifikat</h5>
            </div>
            <div class="card-body">
                <form id="searchForm" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nomor Sertifikat / Kode Unik</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchCode" 
                                   placeholder="Masukkan nomor sertifikat">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search me-1"></i> Cari
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Atau Scan QR Code</label>
                        <div class="input-group">
                            <input type="file" class="form-control" id="qrFile" accept="image/*" capture="environment">
                            <button class="btn btn-secondary" type="button" id="scanQR">
                                <i class="fas fa-qrcode me-1"></i> Scan
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Hasil Validasi</h5>
            </div>
            <div class="card-body" id="validationResult">
                <div class="text-center text-muted py-5">
                    <i class="fas fa-search fa-3x mb-3"></i>
                    <h5>Masukkan kode sertifikat untuk memulai validasi</h5>
                    <p>Gunakan nomor sertifikat atau scan QR code</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Sertifikat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Content akan diisi via AJAX -->
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Form submit
    $('#searchForm').submit(function(e) {
        e.preventDefault();
        const code = $('#searchCode').val().trim();
        
        if (code) {
            validateCertificate(code);
        }
    });
    
    // Scan QR Code
    $('#scanQR').click(function() {
        $('#qrFile').click();
    });
    
    $('#qrFile').change(function() {
        const file = this.files[0];
        if (file) {
            // Simulasi scan QR (dalam implementasi asli gunakan library)
            alert('Fitur scan QR akan menggunakan library seperti html5-qrcode');
            // Implementasi nyata: decode QR dan panggil validateCertificate()
        }
    });
    
    function validateCertificate(code) {
        $('#validationResult').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Memvalidasi sertifikat...</p>
            </div>
        `);
        
        $.ajax({
            url: 'proses_validasi.php', // Ubah ke file di folder yang sama
            method: 'POST',
            data: { kode: code, action: 'validate' },
            success: function(response) {
                $('#validationResult').html(response);
                
                // Log aktivitas
                <?php if(isset($_SESSION['user_id'])): ?>
                $.post('log_aktivitas.php', {
                    action: 'validasi_manual',
                    deskripsi: `Validasi sertifikat: ${code}`,
                    user_id: <?= $_SESSION['user_id'] ?>
                });
                <?php endif; ?>
            },
            error: function() {
                $('#validationResult').html(`
                    <div class="alert alert-danger">
                        <i class="fas fa-times-circle me-2"></i>
                        Gagal memvalidasi sertifikat. Silakan coba lagi.
                    </div>
                `);
            }
        });
    }
    
    // Global function untuk view detail
    window.viewDetail = function(id) {
        $.ajax({
            url: 'proses_validasi.php',
            method: 'POST',
            data: { id: id, action: 'detail' },
            success: function(response) {
                $('#modalBody').html(response);
                $('#detailModal').modal('show');
            }
        });
    }
});
</script>

<?php require_once 'footer.php'; ?>