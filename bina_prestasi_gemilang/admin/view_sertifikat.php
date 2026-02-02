<?php
// File: VIEW_SERTIFIKAT.PHP - Lihat detail sertifikat
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$auth->requireLogin();

// Get certificate ID
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: list_sertifikat.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get certificate data with jenis
$sql = "SELECT s.*, j.nama_jenis, j.kode_jenis 
        FROM sertifikat s 
        LEFT JOIN jenis_sertifikat j ON s.jenis_id = j.id 
        WHERE s.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$certificate = $result->fetch_assoc();

if (!$certificate) {
    header('Location: list_sertifikat.php');
    exit();
}

// Determine status
$today = date('Y-m-d');
if ($certificate['tanggal_expired'] && $certificate['tanggal_expired'] < $today) {
    $status_class = 'danger';
    $status_text = 'Kadaluarsa';
} else {
    $status_class = 'success';
    $status_text = 'Aktif';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Sertifikat - <?php echo SITE_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .certificate-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            border-radius: 15px 15px 0 0;
        }
        
        .certificate-body {
            background: white;
            padding: 40px;
            border-radius: 0 0 15px 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .info-item {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-value {
            color: #6b7280;
            font-size: 1.1rem;
        }
        
        .qr-container {
            text-align: center;
            padding: 30px;
            background: #f8fafc;
            border-radius: 10px;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 mb-0">
                            <i class="fas fa-file-alt text-primary me-2"></i>
                            Detail Sertifikat
                        </h1>
                        <p class="text-muted mb-0">ID: <?php echo $id; ?></p>
                    </div>
                    <div>
                        <a href="list_sertifikat.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-arrow-left me-2"></i> Kembali
                        </a>
                        <a href="edit_sertifikat.php?id=<?php echo $id; ?>" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i> Edit
                        </a>
                    </div>
                </div>
                
                <!-- Certificate Card -->
                <div class="card">
                    <div class="certificate-header">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h2 class="mb-2">SERTIFIKAT</h2>
                                <h4 class="mb-0"><?php echo htmlspecialchars($certificate['nama_peserta']); ?></h4>
                                <p class="mb-0"><?php echo htmlspecialchars($certificate['program']); ?></p>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?php echo $status_class; ?> fs-6">
                                    <?php echo $status_text; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="certificate-body">
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-lg-8">
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-hashtag"></i>
                                        Nomor Sertifikat
                                    </div>
                                    <div class="info-value fs-5">
                                        <strong><?php echo htmlspecialchars($certificate['nomor_sertifikat']); ?></strong>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-user"></i>
                                        Informasi Peserta
                                    </div>
                                    <div class="info-value">
                                        <strong>Nama:</strong> <?php echo htmlspecialchars($certificate['nama_peserta']); ?><br>
                                        <strong>Program:</strong> <?php echo htmlspecialchars($certificate['program']); ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-calendar-alt"></i>
                                        Informasi Waktu
                                    </div>
                                    <div class="info-value">
                                        <strong>Pelatihan:</strong> <?php echo date('d F Y', strtotime($certificate['tanggal_pelatihan'])); ?><br>
                                        <strong>Terbit:</strong> <?php echo date('d F Y', strtotime($certificate['tanggal_terbit'])); ?><br>
                                        <?php if ($certificate['tanggal_expired']): ?>
                                        <strong>Berlaku sampai:</strong> <?php echo date('d F Y', strtotime($certificate['tanggal_expired'])); ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-certificate"></i>
                                        Jenis Sertifikat
                                    </div>
                                    <div class="info-value">
                                        <?php echo htmlspecialchars($certificate['nama_jenis'] ?? '-'); ?>
                                        <span class="badge bg-light text-dark ms-2">
                                            <?php echo htmlspecialchars($certificate['kode_jenis'] ?? '-'); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-history"></i>
                                        Informasi Sistem
                                    </div>
                                    <div class="info-value">
                                        <strong>Dibuat:</strong> <?php echo date('d/m/Y H:i', strtotime($certificate['created_at'])); ?><br>
                                        <strong>Terakhir update:</strong> <?php echo date('d/m/Y H:i', strtotime($certificate['updated_at'])); ?><br>
                                        <strong>Dibuat oleh:</strong> User ID <?php echo $certificate['created_by']; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div class="col-lg-4">
                                <!-- QR Code -->
                                <div class="qr-container">
                                    <?php if ($certificate['qr_code']): ?>
                                        <div id="qrcode"></div>
                                        <p class="mt-3 mb-1">Scan untuk verifikasi</p>
                                        <small class="text-muted">Nomor: <?php echo $certificate['nomor_sertifikat']; ?></small>
                                    <?php else: ?>
                                        <i class="fas fa-qrcode fa-4x text-muted mb-3"></i>
                                        <p>QR Code belum dibuat</p>
                                        <a href="generate_qr.php?id=<?php echo $id; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-qrcode me-1"></i> Generate QR Code
                                        </a>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Actions -->
                                <div class="mt-4">
                                    <div class="d-grid gap-2">
                                        <a href="../verify.php?nomor=<?php echo urlencode($certificate['nomor_sertifikat']); ?>" 
                                           class="btn btn-outline-primary" target="_blank">
                                            <i class="fas fa-external-link-alt me-2"></i> Lihat di Publik
                                        </a>
                                        <a href="print_sertifikat.php?id=<?php echo $id; ?>" 
                                           class="btn btn-outline-secondary" target="_blank">
                                            <i class="fas fa-print me-2"></i> Cetak Sertifikat
                                        </a>
                                        <a href="delete_sertifikat.php?id=<?php echo $id; ?>" 
                                           class="btn btn-outline-danger"
                                           onclick="return confirm('Hapus sertifikat ini?')">
                                            <i class="fas fa-trash me-2"></i> Hapus Sertifikat
                                        </a>
                                    </div>
                                </div>
                                
                                <!-- Quick Info -->
                                <div class="mt-4 p-3 bg-light rounded">
                                    <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i> Info Cepat</h6>
                                    <div class="small">
                                        <div class="d-flex justify-content-between">
                                            <span>Status:</span>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </div>
                                        <?php if ($certificate['tanggal_expired']): ?>
                                            <?php
                                            $expired_date = new DateTime($certificate['tanggal_expired']);
                                            $today_date = new DateTime();
                                            $interval = $today_date->diff($expired_date);
                                            $days_left = $interval->days;
                                            ?>
                                            <div class="d-flex justify-content-between mt-1">
                                                <span>Hari tersisa:</span>
                                                <span class="<?php echo $days_left < 30 ? 'text-danger' : 'text-success'; ?>">
                                                    <?php echo $days_left; ?> hari
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Log Validasi -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-history me-2"></i> Riwayat Validasi</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $log_sql = "SELECT * FROM log_validasi WHERE sertifikat_id = ? ORDER BY waktu_validasi DESC LIMIT 10";
                        $log_stmt = $conn->prepare($log_sql);
                        $log_stmt->bind_param("i", $id);
                        $log_stmt->execute();
                        $log_result = $log_stmt->get_result();
                        
                        if ($log_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Metode</th>
                                            <th>Hasil</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($log = $log_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($log['waktu_validasi'])); ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo htmlspecialchars($log['metode'] ?? 'Manual'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $result = $log['hasil_validasi'] ?? 'Valid';
                                                    $result_class = $result == 'Valid' ? 'success' : ($result == 'Kadaluarsa' ? 'warning' : 'danger');
                                                    ?>
                                                    <span class="badge bg-<?php echo $result_class; ?>">
                                                        <?php echo $result; ?>
                                                    </span>
                                                </td>
                                                <td><small><?php echo $log['ip_address'] ?? '-'; ?></small></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">
                                <i class="fas fa-search fa-lg me-2"></i>
                                Belum ada riwayat validasi
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Generate QR Code if exists
        <?php if ($certificate['qr_code']): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const qrContainer = document.getElementById('qrcode');
            const qrUrl = '<?php echo $certificate["qr_code"]; ?>';
            
            QRCode.toCanvas(qrContainer, qrUrl, {
                width: 150,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            }, function(error) {
                if (error) {
                    console.error('QR Code error:', error);
                    qrContainer.innerHTML = '<i class="fas fa-exclamation-triangle text-warning"></i>';
                }
            });
        });
        <?php endif; ?>
        
        // Print function
        function printCertificate() {
            window.open('print_sertifikat.php?id=<?php echo $id; ?>', '_blank');
        }
    </script>
</body>
</html>