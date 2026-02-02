<?php
$page_title = "Import Data";
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
    <h1><i class="fas fa-file-import me-2"></i>Import Data</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Import Data</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Import Sertifikat</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Upload file Excel/CSV untuk import data sertifikat.
                </div>
                
                <form id="importForm" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Pilih File</label>
                        <input type="file" class="form-control" name="import_file" 
                               accept=".xlsx,.xls,.csv" required>
                        <small class="text-muted">Format: .xlsx, .xls, .csv (max 5MB)</small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload me-1"></i> Upload & Import
                    </button>
                </form>
                
                <div class="progress mt-3" style="height: 25px; display: none;" id="progressBar">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         style="width: 0%">0%</div>
                </div>
                
                <div class="mt-3" id="importResult"></div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Panduan Import</h5>
            </div>
            <div class="card-body">
                <h6>Format Data:</h6>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>Kolom</th>
                            <th>Contoh</th>
                            <th>Wajib</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>nomor_sertifikat</td>
                            <td>BPG-2024-001</td>
                            <td><span class="badge bg-danger">Ya</span></td>
                        </tr>
                        <tr>
                            <td>nama_peserta</td>
                            <td>John Doe</td>
                            <td><span class="badge bg-danger">Ya</span></td>
                        </tr>
                        <tr>
                            <td>jenis_sertifikat</td>
                            <td>Pelatihan</td>
                            <td><span class="badge bg-danger">Ya</span></td>
                        </tr>
                        <tr>
                            <td>institusi</td>
                            <td>Universitas Contoh</td>
                            <td><span class="badge bg-warning">Opsional</span></td>
                        </tr>
                        <tr>
                            <td>program</td>
                            <td>Web Development</td>
                            <td><span class="badge bg-warning">Opsional</span></td>
                        </tr>
                        <tr>
                            <td>email</td>
                            <td>john@example.com</td>
                            <td><span class="badge bg-warning">Opsional</span></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="mt-3">
                    <a href="template/template_import.xlsx" class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-download me-1"></i> Download Template
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header">
                <h5 class="card-title mb-0">Import Manual</h5>
            </div>
            <div class="card-body">
                <p>Tambah data satu per satu:</p>
                <a href="add_sertifikat.php" class="btn btn-outline-success w-100">
                    <i class="fas fa-plus-circle me-1"></i> Tambah Sertifikat Manual
                </a>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#importForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $('#progressBar').show();
        $('#importResult').html('');
        
        $.ajax({
            url: 'proses_import.php',
            method: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(e) {
                    if (e.lengthComputable) {
                        const percent = Math.round((e.loaded / e.total) * 100);
                        $('.progress-bar').css('width', percent + '%').text(percent + '%');
                    }
                });
                return xhr;
            },
            success: function(response) {
                $('.progress-bar').removeClass('progress-bar-animated')
                                 .addClass('bg-success')
                                 .text('Selesai!');
                $('#importResult').html(response);
            },
            error: function() {
                $('.progress-bar').addClass('bg-danger').text('Error!');
                $('#importResult').html('<div class="alert alert-danger">Gagal import data</div>');
            }
        });
    });
});
</script>

<?php require_once 'footer.php'; ?>