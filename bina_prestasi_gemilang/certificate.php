<?php
// ============================================
// HALAMAN DETAIL SERTIFIKAT PESERTA
// ============================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
require_once 'includes/config.php';

// Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Get certificate ID
$cert_id = $_GET['id'] ?? 0;

// Get certificate details
$sql = "SELECT s.*, j.nama_jenis, j.kode_jenis, j.masa_berlaku,
               a.username as created_by_name
        FROM sertifikat s
        JOIN jenis_sertifikat j ON s.jenis_id = j.id
        LEFT JOIN admin a ON s.created_by = a.id
        WHERE s.id = ?";
        
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $cert_id);
$stmt->execute();
$result = $stmt->get_result();
$certificate = $result->fetch_assoc();

if (!$certificate) {
    header('Location: index.php');
    exit();
}

// Get site settings
$settings = [];
$settings_result = $conn->query("SELECT setting_key, setting_value FROM system_settings");
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Log view activity
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$conn->query("INSERT INTO log_validasi (sertifikat_id, hasil, ip_address, user_agent) 
              VALUES ($cert_id, 'Valid', '$ip_address', '$user_agent')");

$page_title = "Sertifikat: " . htmlspecialchars($certificate['nomor_sertifikat']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= SITE_NAME ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: <?= $settings['theme_color'] ?? '#2c3e50' ?>;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }
        
        .certificate-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a2530 100%);
            color: white;
            padding: 60px 0;
            margin-bottom: 40px;
        }
        
        .certificate-card {
            border: 2px solid var(--primary-color);
            border-radius: 10px;
            background: white;
        }
        
        .certificate-info {
            border-left: 3px solid var(--primary-color);
            padding-left: 15px;
        }
        
        .status-badge {
            font-size: 0.9rem;
            padding: 5px 15px;
            border-radius: 20px;
        }
        
        .qr-code-container {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
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
                        <a class="nav-link btn btn-outline-light btn-sm ms-2" href="admin/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login Admin
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Certificate Header -->
    <section class="certificate-header">
        <div class="container text-center">
            <h1 class="display-5 fw-bold mb-3">Detail Sertifikat</h1>
            <p class="lead mb-0">Nomor: <code><?= htmlspecialchars($certificate['nomor_sertifikat']) ?></code></p>
        </div>
    </section>

    <!-- Certificate Details -->
    <section class="container mb-5">
        <div class="row g-4">
            <!-- Left Column - Certificate Info -->
            <div class="col-lg-8">
                <div class="certificate-card p-4 mb-4">
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <h3 class="fw-bold mb-3"><?= htmlspecialchars($certificate['nama_peserta']) ?></h3>
                            <div class="d-flex align-items-center mb-3">
                                <span class="badge bg-primary me-2"><?= htmlspecialchars($certificate['nama_jenis']) ?></span>
                                
                                <!-- Status Badge -->
                                <?php 
                                $expired = strtotime($certificate['tanggal_expired']) < time();
                                $status_class = $expired ? 'bg-danger' : 'bg-success';
                                $status_text = $expired ? 'Kadaluarsa' : 'Masih Berlaku';
                                ?>
                                <span class="badge <?= $status_class ?> status-badge">
                                    <i class="fas fa-circle me-1"></i> <?= $status_text ?>
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <div class="qr-code-container">
                                <?php if (!empty($certificate['qr_image']) && file_exists($certificate['qr_image'])): ?>
                                <img src="<?= htmlspecialchars($certificate['qr_image']) ?>" alt="QR Code" width="120" class="mb-2">
                                <?php else: ?>
                                <i class="fas fa-qrcode fa-3x text-muted mb-2"></i>
                                <?php endif; ?>
                                <p class="small text-muted mb-0">Scan untuk validasi</p>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Certificate Information -->
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <h5 class="fw-bold mb-3 certificate-info">Informasi Program</h5>
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <strong><i class="fas fa-book me-2 text-primary"></i> Program:</strong><br>
                                    <?= htmlspecialchars($certificate['program']) ?>
                                </li>
                                <li class="mb-3">
                                    <strong><i class="fas fa-calendar-day me-2 text-primary"></i> Pelatihan:</strong><br>
                                    <?= date('d F Y', strtotime($certificate['tanggal_pelatihan'])) ?>
                                </li>
                                <li>
                                    <strong><i class="fas fa-calendar-check me-2 text-primary"></i> Terbit:</strong><br>
                                    <?= date('d F Y', strtotime($certificate['tanggal_terbit'])) ?>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <h5 class="fw-bold mb-3 certificate-info">Detail Sertifikat</h5>
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <strong><i class="fas fa-hashtag me-2 text-primary"></i> Nomor:</strong><br>
                                    <code><?= htmlspecialchars($certificate['nomor_sertifikat']) ?></code>
                                </li>
                                <li class="mb-3">
                                    <strong><i class="fas fa-clock me-2 text-primary"></i> Masa Berlaku:</strong><br>
                                    <?= $certificate['masa_berlaku'] ?> bulan
                                </li>
                                <li>
                                    <strong><i class="fas fa-calendar-times me-2 text-primary"></i> Expired:</strong><br>
                                    <?= date('d F Y', strtotime($certificate['tanggal_expired'])) ?>
                                    <?php if ($expired): ?>
                                    <span class="badge bg-danger ms-2">EXPIRED</span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Certificate Actions -->
                <div class="row g-3">
                    <div class="col-md-4">
                        <a href="validasi.php?nomor=<?= urlencode($certificate['nomor_sertifikat']) ?>" 
                           class="btn btn-primary w-100">
                            <i class="fas fa-check-circle me-2"></i> Validasi Ulang
                        </a>
                    </div>
                    <div class="col-md-4">
                        <button onclick="window.print()" class="btn btn-outline-primary w-100">
                            <i class="fas fa-print me-2"></i> Cetak
                        </button>
                    </div>
                    <div class="col-md-4">
                        <a href="index.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-home me-2"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Right Column - Validation & Info -->
            <div class="col-lg-4">
                <!-- Validation Info -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i> Status Validasi</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="fw-bold">TERVALIDASI</h5>
                            <p class="text-muted mb-0">Sertifikat ini valid dan terdaftar di sistem kami</p>
                        </div>
                        
                        <hr>
                        
                        <div class="small text-muted">
                            <p><i class="fas fa-info-circle me-2"></i> Terakhir divalidasi: <?= date('d/m/Y H:i') ?></p>
                            <p><i class="fas fa-globe me-2"></i> IP Address: <?= htmlspecialchars($ip_address) ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Info -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Informasi</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <p class="small mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Sertifikat ini hanya valid jika diverifikasi melalui sistem kami.
                            </p>
                        </div>
                        
                        <ul class="list-unstyled small">
                            <li class="mb-2">
                                <i class="fas fa-user-check me-2 text-success"></i>
                                Dibuat oleh: <?= htmlspecialchars($certificate['created_by_name'] ?? 'System') ?>
                            </li>
                            <li class="mb-2">
                                <i class="fas fa-calendar-plus me-2 text-success"></i>
                                Tanggal buat: <?= date('d/m/Y', strtotime($certificate['created_at'])) ?>
                            </li>
                            <li>
                                <i class="fas fa-database me-2 text-success"></i>
                                ID Database: <?= $certificate['id'] ?>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-4" style="background-color: var(--primary-color); color: white;">
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
    
    <!-- Print CSS -->
    <style media="print">
        @media print {
            nav, footer, .btn, .qr-code-container {
                display: none !important;
            }
            
            body {
                background: white !important;
                color: black !important;
            }
            
            .certificate-header {
                background: white !important;
                color: black !important;
                padding: 20px 0 !important;
            }
            
            .certificate-card {
                border: 2px solid black !important;
                box-shadow: none !important;
            }
        }
    </style>
</body>
</html>
<?php $conn->close(); ?>