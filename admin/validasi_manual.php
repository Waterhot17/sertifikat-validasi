<?php
$page_title = "Validasi Manual";
require_once '../includes/config.php';
require_once 'header.php';

// Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
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
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Validasi Sertifikat</h5>
            </div>
            <div class="card-body">
                <form id="validationForm" class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Masukkan Kode Sertifikat</label>
                        <input type="text" class="form-control" id="certificateCode" 
                               placeholder="Nomor sertifikat atau kode unik" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-1"></i> Validasi
                        </button>
                    </div>
                </form>
                
                <div class="mt-4" id="validationResult">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-search fa-3x mb-3"></i>
                        <h5>Masukkan kode sertifikat</h5>
                        <p>Hasil validasi akan muncul di sini</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Statistik Database</h5>
            </div>
            <div class="card-body">
                <?php
                // Query dengan error handling
                $total = 0;
                $tables = 0;
                $setupStatus = 'Setup';
                
                // Cek koneksi dan tabel
                $checkTables = $conn->query("SHOW TABLES");
                if ($checkTables) {
                    $tables = $checkTables->num_rows;
                    
                    // Cek tabel sertifikat
                    $checkSertifikat = $conn->query("SHOW TABLES LIKE 'sertifikat'");
                    if ($checkSertifikat && $checkSertifikat->num_rows > 0) {
                        // Query total sertifikat (tanpa status)
                        $totalResult = $conn->query("SELECT COUNT(*) as count FROM sertifikat");
                        if ($totalResult) {
                            $totalRow = $totalResult->fetch_assoc();
                            $total = $totalRow['count'];
                            $setupStatus = 'Aktif';
                        }
                    }
                }
                ?>
                
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Total Tabel</span>
                        <span class="badge bg-info rounded-pill"><?php echo $tables; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Total Sertifikat</span>
                        <span class="badge bg-primary rounded-pill"><?php echo $total; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span>Sistem Status</span>
                        <span class="badge bg-<?php echo ($total > 0) ? 'success' : 'warning'; ?> rounded-pill">
                            <?php echo $setupStatus; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Panduan Validasi</h5>
            </div>
            <div class="card-body">
                <ol class="small">
                    <li>Masukkan kode unik sertifikat</li>
                    <li>Atau scan QR code pada sertifikat</li>
                    <li>Sistem akan mengecek keaslian sertifikat</li>
                    <li>Hasil validasi akan ditampilkan</li>
                    <li>Cetak hasil validasi jika diperlukan</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#validationForm').submit(function(e) {
        e.preventDefault();
        const code = $('#certificateCode').val().trim();
        
        if (code) {
            validateCertificate(code);
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
            url: 'proses_validasi.php',
            method: 'POST',
            data: { kode: code, action: 'validate' },
            success: function(response) {
                $('#validationResult').html(response);
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
});
</script>

<?php require_once 'footer.php'; ?>