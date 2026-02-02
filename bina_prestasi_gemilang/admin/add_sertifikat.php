<?php
// ============================================
// FILE: ADD_SERTIFIKAT.PHP - TAMBAH SERTIFIKAT BARU
// ============================================

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$auth->requireLogin();

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Get jenis sertifikat untuk dropdown
$jenis_options = '';
$result = $conn->query("SELECT id, kode_jenis, nama_jenis FROM jenis_sertifikat ORDER BY nama_jenis");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $jenis_options .= '<option value="' . $row['id'] . '">' . 
                         htmlspecialchars($row['nama_jenis'] . ' (' . $row['kode_jenis'] . ')') . '</option>';
    }
}

// Get contoh nomor untuk placeholder
$contoh_nomor = '';
$result = $conn->query("SELECT contoh_nomor FROM jenis_sertifikat LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $contoh_nomor = $row['contoh_nomor'];
}

$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $nomor_sertifikat = $_POST['nomor_sertifikat'] ?? '';
        $jenis_id = $_POST['jenis_id'] ?? '';
        $nama_peserta = $_POST['nama_peserta'] ?? '';
        $program = $_POST['program'] ?? '';
        $tanggal_pelatihan = $_POST['tanggal_pelatihan'] ?? '';
        $tanggal_terbit = $_POST['tanggal_terbit'] ?? '';
        $tanggal_expired = $_POST['tanggal_expired'] ?? '';
        
        // Validation
        if (empty($nomor_sertifikat)) {
            throw new Exception('Nomor sertifikat harus diisi');
        }
        if (empty($jenis_id)) {
            throw new Exception('Jenis sertifikat harus dipilih');
        }
        if (empty($nama_peserta)) {
            throw new Exception('Nama peserta harus diisi');
        }
        if (empty($program)) {
            throw new Exception('Program pelatihan harus diisi');
        }
        if (empty($tanggal_pelatihan)) {
            throw new Exception('Tanggal pelatihan harus diisi');
        }
        if (empty($tanggal_terbit)) {
            throw new Exception('Tanggal terbit harus diisi');
        }
        
        // Check if certificate number already exists
        $check_sql = "SELECT id FROM sertifikat WHERE nomor_sertifikat = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $nomor_sertifikat);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            throw new Exception('Nomor sertifikat sudah digunakan');
        }
        
        // Insert certificate
        $sql = "INSERT INTO sertifikat (nomor_sertifikat, jenis_id, nama_peserta, program, 
                tanggal_pelatihan, tanggal_terbit, tanggal_expired, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sisssssi",
            $nomor_sertifikat,
            $jenis_id,
            $nama_peserta,
            $program,
            $tanggal_pelatihan,
            $tanggal_terbit,
            $tanggal_expired,
            $auth->getUser()['id']
        );
        
        if ($stmt->execute()) {
            $sertifikat_id = $stmt->insert_id;
            
            // Generate QR Code (akan kita buat nanti)
            // Untuk sekarang, kita simpan URL verifikasi saja
            $qr_url = BASE_URL . "/verify.php?nomor=" . urlencode($nomor_sertifikat);
            $update_sql = "UPDATE sertifikat SET qr_code = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $qr_url, $sertifikat_id);
            $update_stmt->execute();
            
            $success = 'Sertifikat berhasil ditambahkan!';
            
            // Clear form
            $_POST = [];
        } else {
            throw new Exception('Gagal menyimpan sertifikat: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Sertifikat - <?php echo SITE_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: #374151;
        }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .info-box {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            border-left: 4px solid #667eea;
            margin-bottom: 20px;
        }
        
        .preview-box {
            background: white;
            border: 2px dashed #e5e7eb;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Header (sama seperti dashboard) -->
    <?php include 'header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (sama seperti dashboard) -->
            <?php include 'sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 mb-0">
                            <i class="fas fa-plus-circle text-primary me-2"></i>
                            Tambah Sertifikat
                        </h1>
                        <p class="text-muted mb-0">Tambahkan sertifikat baru ke dalam sistem</p>
                    </div>
                    <div>
                        <a href="list_sertifikat.php" class="btn btn-outline-secondary">
                            <i class="fas fa-list me-2"></i> Lihat Daftar
                        </a>
                    </div>
                </div>
                
                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <!-- Form -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i> Form Sertifikat</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" id="certificateForm">
                                    <div class="row">
                                        <!-- Nomor Sertifikat -->
                                        <div class="col-md-6 mb-3">
                                            <label for="nomor_sertifikat" class="form-label">
                                                <i class="fas fa-hashtag me-1"></i> Nomor Sertifikat
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="nomor_sertifikat" 
                                                   name="nomor_sertifikat" 
                                                   placeholder="Contoh: <?php echo $contoh_nomor; ?>"
                                                   required
                                                   value="<?php echo $_POST['nomor_sertifikat'] ?? ''; ?>">
                                            <small class="text-muted">Format: Sesuai jenis sertifikat</small>
                                        </div>
                                        
                                        <!-- Jenis Sertifikat -->
                                        <div class="col-md-6 mb-3">
                                            <label for="jenis_id" class="form-label">
                                                <i class="fas fa-certificate me-1"></i> Jenis Sertifikat
                                            </label>
                                            <select class="form-select" id="jenis_id" name="jenis_id" required>
                                                <option value="">Pilih Jenis Sertifikat</option>
                                                <?php echo $jenis_options; ?>
                                            </select>
                                        </div>
                                        
                                        <!-- Nama Peserta -->
                                        <div class="col-md-12 mb-3">
                                            <label for="nama_peserta" class="form-label">
                                                <i class="fas fa-user me-1"></i> Nama Peserta
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="nama_peserta" 
                                                   name="nama_peserta" 
                                                   placeholder="Masukkan nama lengkap peserta"
                                                   required
                                                   value="<?php echo $_POST['nama_peserta'] ?? ''; ?>">
                                        </div>
                                        
                                        <!-- Program Pelatihan -->
                                        <div class="col-md-12 mb-3">
                                            <label for="program" class="form-label">
                                                <i class="fas fa-graduation-cap me-1"></i> Program Pelatihan
                                            </label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="program" 
                                                   name="program" 
                                                   placeholder="Masukkan nama program pelatihan"
                                                   required
                                                   value="<?php echo $_POST['program'] ?? ''; ?>">
                                        </div>
                                        
                                        <!-- Tanggal Pelatihan -->
                                        <div class="col-md-4 mb-3">
                                            <label for="tanggal_pelatihan" class="form-label">
                                                <i class="fas fa-calendar-alt me-1"></i> Tanggal Pelatihan
                                            </label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="tanggal_pelatihan" 
                                                   name="tanggal_pelatihan" 
                                                   required
                                                   value="<?php echo $_POST['tanggal_pelatihan'] ?? date('Y-m-d'); ?>">
                                        </div>
                                        
                                        <!-- Tanggal Terbit -->
                                        <div class="col-md-4 mb-3">
                                            <label for="tanggal_terbit" class="form-label">
                                                <i class="fas fa-calendar-check me-1"></i> Tanggal Terbit
                                            </label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="tanggal_terbit" 
                                                   name="tanggal_terbit" 
                                                   required
                                                   value="<?php echo $_POST['tanggal_terbit'] ?? date('Y-m-d'); ?>">
                                        </div>
                                        
                                        <!-- Tanggal Expired -->
                                        <div class="col-md-4 mb-3">
                                            <label for="tanggal_expired" class="form-label">
                                                <i class="fas fa-calendar-times me-1"></i> Tanggal Expired
                                            </label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   id="tanggal_expired" 
                                                   name="tanggal_expired"
                                                   value="<?php echo $_POST['tanggal_expired'] ?? ''; ?>">
                                            <small class="text-muted">Opsional</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Submit Button -->
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-submit">
                                            <i class="fas fa-save me-2"></i> Simpan Sertifikat
                                        </button>
                                        <button type="reset" class="btn btn-outline-secondary ms-2">
                                            <i class="fas fa-redo me-2"></i> Reset Form
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview & Info -->
                    <div class="col-lg-4">
                        <!-- Info Box -->
                        <div class="info-box">
                            <h6><i class="fas fa-info-circle me-2 text-primary"></i> Informasi</h6>
                            <ul class="mb-0 mt-2 ps-3">
                                <li>Nomor sertifikat harus unik</li>
                                <li>Format nomor sesuai jenis sertifikat</li>
                                <li>Tanggal expired bisa dikosongkan</li>
                                <li>QR Code akan otomatis dibuat</li>
                            </ul>
                        </div>
                        
                        <!-- QR Preview -->
                        <div class="preview-box">
                            <i class="fas fa-qrcode fa-4x text-muted mb-3"></i>
                            <h6>QR Code Preview</h6>
                            <p class="text-muted small mb-0">QR Code akan tampil setelah sertifikat dibuat</p>
                        </div>
                        
                        <!-- Quick Stats -->
                        <div class="card mt-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-chart-bar me-2"></i> Statistik
                                </h6>
                                <?php
                                $today = date('Y-m-d');
                                $total = $conn->query("SELECT COUNT(*) as total FROM sertifikat")->fetch_assoc()['total'];
                                $today_count = $conn->query("SELECT COUNT(*) as today FROM sertifikat WHERE DATE(created_at) = '$today'")->fetch_assoc()['today'];
                                ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Sertifikat:</span>
                                    <strong><?php echo $total; ?></strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Hari ini:</span>
                                    <strong><?php echo $today_count; ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
        // Initialize Select2
        $(document).ready(function() {
            $('#jenis_id').select2({
                placeholder: "Pilih Jenis Sertifikat",
                allowClear: true
            });
            
            // Auto-fill example based on certificate type
            $('#jenis_id').change(function() {
                const jenisId = $(this).val();
                if (jenisId) {
                    // In production, you would fetch example number via AJAX
                    // For now, show loading
                    $('#nomor_sertifikat').attr('placeholder', 'Loading contoh nomor...');
                    
                    // Simulate AJAX call
                    setTimeout(() => {
                        $('#nomor_sertifikat').attr('placeholder', 'Contoh: ' + getExampleNumber(jenisId));
                    }, 300);
                }
            });
            
            // Calculate expired date (6 months from issue date)
            $('#tanggal_terbit').change(function() {
                const terbitDate = new Date($(this).val());
                if ($(this).val() && !$('#tanggal_expired').val()) {
                    // Add 6 months
                    terbitDate.setMonth(terbitDate.getMonth() + 6);
                    const expiredDate = terbitDate.toISOString().split('T')[0];
                    $('#tanggal_expired').val(expiredDate);
                }
            });
            
            // Form validation
            $('#certificateForm').submit(function(e) {
                const nomor = $('#nomor_sertifikat').val().trim();
                const nama = $('#nama_peserta').val().trim();
                const program = $('#program').val().trim();
                
                if (!nomor || !nama || !program) {
                    e.preventDefault();
                    alert('Harap isi semua field yang wajib diisi!');
                    return false;
                }
                
                // Show loading
                $('button[type="submit"]').html('<i class="fas fa-spinner fa-spin me-2"></i> Menyimpan...');
                $('button[type="submit"]').prop('disabled', true);
            });
        });
        
        // Helper function for example numbers (simplified)
        function getExampleNumber(jenisId) {
            const examples = {
                '1': '2-A.BPG.24.12.00001',
                '2': '1-A.00001/BPG/2024',
                '3': '3-A/BPG/12-2024/001',
                '4': 'BPG/PROF/2024/001',
                '5': 'BPG/WEB/241220-001'
            };
            return examples[jenisId] || 'Contoh nomor';
        }
    </script>
</body>
</html>