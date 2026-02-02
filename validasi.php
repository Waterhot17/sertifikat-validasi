<?php
// ============================================
// HALAMAN VALIDASI SERTIFIKAT
// ============================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
require_once 'includes/config.php';

// Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Get site settings
$settings = [];
$result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle validation
$validation_result = null;
$nomor_sertifikat = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['nomor'])) {
    $nomor_sertifikat = $_POST['nomor'] ?? $_GET['nomor'] ?? '';
    $nomor_sertifikat = trim($nomor_sertifikat);
    
    if (!empty($nomor_sertifikat)) {
        // Log validation attempt
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Get certificate
        $sql = "SELECT s.*, j.nama_jenis, j.kode_jenis, j.masa_berlaku
                FROM sertifikat s
                JOIN jenis_sertifikat j ON s.jenis_id = j.id
                WHERE s.nomor_sertifikat = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $nomor_sertifikat);
        $stmt->execute();
        $result = $stmt->get_result();
        $certificate = $result->fetch_assoc();
        
        if ($certificate) {
            // Check if expired
            $expired = strtotime($certificate['tanggal_expired']) < time();
            
            $validation_result = [
                'status' => $expired ? 'expired' : 'valid',
                'certificate' => $certificate,
                'message' => $expired ? 'Sertifikat sudah kadaluarsa' : 'Sertifikat valid'
            ];
            
            // Log validation
            $log_result = $expired ? 'Kadaluarsa' : 'Valid';
            $conn->query("INSERT INTO log_validasi (sertifikat_id, hasil, ip_address, user_agent) 
                          VALUES ({$certificate['id']}, '$log_result', '$ip_address', '$user_agent')");
            
        } else {
            $validation_result = [
                'status' => 'invalid',
                'message' => 'Sertifikat tidak ditemukan'
            ];
            
            // Log invalid attempt
            $conn->query("INSERT INTO log_validasi (sertifikat_id, hasil, ip_address, user_agent) 
                          VALUES (NULL, 'Tidak Valid', '$ip_address', '$user_agent')");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Sertifikat - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: <?= $settings['theme_color'] ?? '#2c3e50' ?>;
        }
        
        .validation-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a2530 100%);
            color: white;
            padding: 80px 0;
        }
        
        .validation-card {
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .result-icon {
            font-size: 4rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--primary-color);">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <?php if (!empty($settings['logo_path']) && file_exists($settings['logo_path'])): ?>
                <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="<?= SITE_NAME ?>" height="40">
                <?php else: ?>
                <i class="fas fa-graduation-cap me-2"></i>
                <?php endif; ?>
                <strong><?= SITE_NAME ?></strong>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="validasi.php">Validasi Sertifikat</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Kontak</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light btn-sm ms-2" href="admin/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Validation Section -->
    <section class="validation-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-5">
                        <h1 class="display-5 fw-bold mb-3">Validasi Sertifikat</h1>
                        <p class="lead">Verifikasi keaslian sertifikat dengan memasukkan nomor sertifikat</p>
                    </div>
                    
                    <!-- Validation Form -->
                    <div class="card validation-card">
                        <div class="card-body p-4">
                            <form method="POST" class="row g-3">
                                <div class="col-md-10">
                                    <input type="text" name="nomor" class="form-control form-control-lg" 
                                           placeholder="Masukkan nomor sertifikat (contoh: 2-A.BPG.24.12.00001)" 
                                           value="<?= htmlspecialchars($nomor_sertifikat) ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary btn-lg w-100">
                                        <i class="fas fa-search me-2"></i> Validasi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Validation Result -->
    <?php if ($validation_result): ?>
    <section class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card validation-card">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <?php if ($validation_result['status'] === 'valid'): ?>
                                <i class="fas fa-check-circle result-icon text-success"></i>
                                <h3 class="fw-bold text-success mt-3">SERTIFIKAT VALID</h3>
                            <?php elseif ($validation_result['status'] === 'expired'): ?>
                                <i class="fas fa-exclamation-triangle result-icon text-warning"></i>
                                <h3 class="fw-bold text-warning mt-3">SERTIFIKAT KADALUARSA</h3>
                            <?php else: ?>
                                <i class="fas fa-times-circle result-icon text-danger"></i>
                                <h3 class="fw-bold text-danger mt-3">SERTIFIKAT TIDAK VALID</h3>
                            <?php endif; ?>
                            
                            <p class="lead mb-0"><?= $validation_result['message'] ?></p>
                        </div>
                        
                        <?php if ($validation_result['status'] === 'valid' || $validation_result['status'] === 'expired'): 
                            $cert = $validation_result['certificate'];
                        ?>
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="fw-bold mb-3">Detail Sertifikat</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <strong>Nomor:</strong><br>
                                        <code><?= htmlspecialchars($cert['nomor_sertifikat']) ?></code>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Nama Peserta:</strong><br>
                                        <?= htmlspecialchars($cert['nama_peserta']) ?>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Program:</strong><br>
                                        <?= htmlspecialchars($cert['program']) ?>
                                    </li>
                                    <li>
                                        <strong>Jenis:</strong><br>
                                        <?= htmlspecialchars($cert['nama_jenis']) ?>
                                    </li>
                                </ul>
                            </div>
                            
                            <div class="col-md-6">
                                <h5 class="fw-bold mb-3">Informasi Masa Berlaku</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <strong>Tanggal Terbit:</strong><br>
                                        <?= date('d/m/Y', strtotime($cert['tanggal_terbit'])) ?>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Tanggal Expired:</strong><br>
                                        <?= date('d/m/Y', strtotime($cert['tanggal_expired'])) ?>
                                        <?php if ($validation_result['status'] === 'expired'): ?>
                                        <span class="badge bg-danger ms-2">EXPIRED</span>
                                        <?php endif; ?>
                                    </li>
                                    <li class="mb-2">
                                        <strong>Masa Berlaku:</strong><br>
                                        <?= $cert['masa_berlaku'] ?> bulan
                                    </li>
                                    <li>
                                        <strong>Status:</strong><br>
                                        <span class="badge bg-<?= $validation_result['status'] === 'valid' ? 'success' : 'warning' ?>">
                                            <?= $validation_result['status'] === 'valid' ? 'MASIH BERLAKU' : 'KADALUARSA' ?>
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="certificate.php?id=<?= $cert['id'] ?>" class="btn btn-primary">
                                <i class="fas fa-external-link-alt me-2"></i> Lihat Detail Lengkap
                            </a>
                            <a href="validasi.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-redo me-2"></i> Validasi Lagi
                            </a>
                        </div>
                        <?php else: ?>
                        <div class="text-center mt-4">
                            <a href="validasi.php" class="btn btn-primary">
                                <i class="fas fa-redo me-2"></i> Coba Lagi
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary ms-2">
                                <i class="fas fa-home me-2"></i> Kembali ke Beranda
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="py-4 mt-5" style="background-color: var(--primary-color); color: white;">
        <div class="container text-center">
            <p class="mb-0">
                &copy; <?= date('Y') ?> <?= SITE_NAME ?>. 
                <span class="ms-2">
                    <a href="index.php" class="text-light text-decoration-none">Beranda</a> | 
                    <a href="validasi.php" class="text-light text-decoration-none">Validasi</a> | 
                    <a href="contact.php" class="text-light text-decoration-none">Kontak</a>
                </span>
            </p>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>