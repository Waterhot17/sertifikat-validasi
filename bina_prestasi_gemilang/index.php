<?php
// ============================================
// HALAMAN UTAMA PUBLIK
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

// Get total certificates
$total_certs = $conn->query("SELECT COUNT(*) as total FROM sertifikat WHERE tanggal_expired > NOW()")->fetch_assoc()['total'];

// Get recent certificates
$recent_certs = $conn->query("SELECT s.*, j.nama_jenis 
                               FROM sertifikat s 
                               JOIN jenis_sertifikat j ON s.jenis_id = j.id 
                               WHERE s.tanggal_expired > NOW() 
                               ORDER BY s.created_at DESC 
                               LIMIT 6");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settings['site_title'] ?? SITE_NAME) ?> - Sertifikasi Profesional</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: <?= $settings['theme_color'] ?? '#2c3e50' ?>;
            --secondary-color: #3498db;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
        }
        
        .navbar-brand img {
            height: 40px;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1a2530 100%);
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
        }
        
        .stat-card {
            border: none;
            border-radius: 10px;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .certificate-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .certificate-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        footer {
            background-color: var(--primary-color);
            color: white;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: var(--primary-color);">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <?php if (!empty($settings['logo_path']) && file_exists($settings['logo_path'])): ?>
                <img src="<?= htmlspecialchars($settings['logo_path']) ?>" alt="<?= SITE_NAME ?>">
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
                        <a class="nav-link active" href="index.php">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="validasi.php">Validasi Sertifikat</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">Tentang Kami</a>
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

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3">Sertifikasi Profesional Terpercaya</h1>
            <p class="lead mb-4"><?= htmlspecialchars($settings['site_description'] ?? 'Lembaga sertifikasi profesional yang mengeluarkan sertifikat kompetensi resmi') ?></p>
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <form action="validasi.php" method="GET" class="d-flex">
                        <input type="text" name="nomor" class="form-control form-control-lg me-2" 
                               placeholder="Masukkan nomor sertifikat untuk validasi...">
                        <button type="submit" class="btn btn-light btn-lg">
                            <i class="fas fa-search me-2"></i> Validasi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics -->
    <section class="container mb-5">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card stat-card shadow-sm border-0 bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-certificate fa-3x mb-3"></i>
                        <h2 class="fw-bold"><?= number_format($total_certs) ?>+</h2>
                        <p class="mb-0">Sertifikat Terbit</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card stat-card shadow-sm border-0 bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-3x mb-3"></i>
                        <h2 class="fw-bold">5+</h2>
                        <p class="mb-0">Jenis Sertifikasi</p>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card stat-card shadow-sm border-0 bg-warning text-dark">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <h2 class="fw-bold">100%</h2>
                        <p class="mb-0">Terakreditasi</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Recent Certificates -->
    <section class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold">Sertifikat Terbaru</h2>
            <a href="validasi.php" class="btn btn-outline-primary">Lihat Semua</a>
        </div>
        
        <div class="row g-4">
            <?php if ($recent_certs->num_rows > 0): ?>
                <?php while ($cert = $recent_certs->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="certificate-card p-4 h-100">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-primary"><?= htmlspecialchars($cert['nama_jenis']) ?></span>
                                <h5 class="fw-bold mt-2"><?= htmlspecialchars($cert['nama_peserta']) ?></h5>
                            </div>
                            <i class="fas fa-award fa-2x text-warning"></i>
                        </div>
                        
                        <p class="text-muted mb-2">
                            <i class="fas fa-book me-2"></i>
                            <?= htmlspecialchars($cert['program']) ?>
                        </p>
                        
                        <p class="text-muted mb-3">
                            <i class="fas fa-calendar me-2"></i>
                            Terbit: <?= date('d/m/Y', strtotime($cert['tanggal_terbit'])) ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">
                                No: <code><?= htmlspecialchars($cert['nomor_sertifikat']) ?></code>
                            </small>
                            <a href="certificate.php?id=<?= $cert['id'] ?>" class="btn btn-sm btn-outline-primary">
                                Detail
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-certificate fa-3x mb-3"></i>
                        <p>Belum ada sertifikat terbit</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3"><?= SITE_NAME ?></h5>
                    <p><?= htmlspecialchars($settings['site_description'] ?? 'Lembaga sertifikasi profesional') ?></p>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3">Kontak Kami</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2">
                            <i class="fas fa-envelope me-2"></i>
                            <?= htmlspecialchars($settings['contact_email'] ?? 'info@binaprestasigemilang.com') ?>
                        </li>
                        <li class="mb-2">
                            <i class="fas fa-phone me-2"></i>
                            <?= htmlspecialchars($settings['contact_phone'] ?? '+62 812 3456 7890') ?>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?= htmlspecialchars($settings['contact_address'] ?? 'Jl. Contoh No. 123, Jakarta') ?>
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-4 mb-4">
                    <h5 class="fw-bold mb-3">Link Cepat</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="validasi.php" class="text-light text-decoration-none">Validasi Sertifikat</a></li>
                        <li class="mb-2"><a href="about.php" class="text-light text-decoration-none">Tentang Kami</a></li>
                        <li class="mb-2"><a href="contact.php" class="text-light text-decoration-none">Kontak</a></li>
                        <li><a href="admin/login.php" class="text-light text-decoration-none">Login Admin</a></li>
                    </ul>
                </div>
            </div>
            
            <hr class="bg-light">
            
            <div class="text-center pt-3">
                <p class="mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script>
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        var alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            var bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    </script>
</body>
</html>
<?php $conn->close(); ?>