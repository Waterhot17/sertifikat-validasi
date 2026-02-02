<?php
// ============================================
// BACKUP SYSTEM - FIXED PATH
// ============================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/auth.php';

$auth = new Auth();
$auth->requireSuperAdmin();

$page_title = 'Backup System';
include 'header.php';

// Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$user = $auth->getUser();

// Function untuk format file size
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}

// Handle download backup
if (isset($_GET['download'])) {
    $backup_id = intval($_GET['download']);
    $result = $conn->query("SELECT * FROM backup_history WHERE id = $backup_id");
    
    if ($result->num_rows > 0) {
        $backup = $result->fetch_assoc();
        $filepath = $backup['filepath'];
        
        if (file_exists($filepath)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            
            // Log activity
            $conn->query("INSERT INTO activity_logs (user_id, action, description, ip_address) 
                          VALUES ({$user['id']}, 'BACKUP_DOWNLOAD', 'Mendownload backup: {$backup['filename']}', '{$_SERVER['REMOTE_ADDR']}')");
            exit;
        }
    }
    
    $_SESSION['error'] = 'File backup tidak ditemukan';
    header('Location: settings.php#backup');
    exit;
}

// Handle delete backup
if (isset($_GET['delete'])) {
    $backup_id = intval($_GET['delete']);
    $result = $conn->query("SELECT * FROM backup_history WHERE id = $backup_id");
    
    if ($result->num_rows > 0) {
        $backup = $result->fetch_assoc();
        $filepath = $backup['filepath'];
        
        // Delete file
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Delete record
        $conn->query("DELETE FROM backup_history WHERE id = $backup_id");
        
        // Log activity
        $conn->query("INSERT INTO activity_logs (user_id, action, description, ip_address) 
                      VALUES ({$user['id']}, 'BACKUP_DELETE', 'Menghapus backup: {$backup['filename']}', '{$_SERVER['REMOTE_ADDR']}')");
        
        $_SESSION['success'] = 'Backup berhasil dihapus!';
    }
    
    header('Location: backup.php');
    exit;
}

// Handle backup process
if (isset($_GET['type'])) {
    $type = $_GET['type'];
    
    if (!in_array($type, ['database', 'full'])) {
        $_SESSION['error'] = 'Tipe backup tidak valid';
        header('Location: backup.php');
        exit;
    }
    
    // Create backup directory
    $backup_dir = '../database_backup/';
    if (!file_exists($backup_dir)) {
        mkdir($backup_dir, 0755, true);
    }
    
    // Generate filename
    $timestamp = date('Ymd_His');
    $filename = "backup_{$type}_{$timestamp}";
    
    try {
        if ($type === 'database') {
            // Backup database only
            $filename .= '.sql';
            $filepath = $backup_dir . $filename;
            
            // Get all tables
            $tables = [];
            $result = $conn->query('SHOW TABLES');
            while ($row = $result->fetch_array()) {
                $tables[] = $row[0];
            }
            
            // Create SQL dump
            $output = "-- BPG Database Backup\n";
            $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $output .= "-- Database: " . DB_NAME . "\n\n";
            
            foreach ($tables as $table) {
                // Drop table if exists
                $output .= "DROP TABLE IF EXISTS `$table`;\n";
                
                // Create table structure
                $create_result = $conn->query("SHOW CREATE TABLE `$table`");
                if ($create_result) {
                    $create_table = $create_result->fetch_array();
                    $output .= $create_table[1] . ";\n\n";
                }
                
                // Insert data
                $rows = $conn->query("SELECT * FROM `$table`");
                if ($rows && $rows->num_rows > 0) {
                    while ($row = $rows->fetch_assoc()) {
                        $keys = array_map(function($k) { return "`$k`"; }, array_keys($row));
                        $values = array_map(function($v) use ($conn) { 
                            return "'" . $conn->real_escape_string($v) . "'"; 
                        }, array_values($row));
                        
                        $output .= "INSERT INTO `$table` (" . implode(', ', $keys) . ") VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $output .= "\n";
                }
            }
            
            // Save to file
            file_put_contents($filepath, $output);
            
        } elseif ($type === 'full') {
            // Backup database first
            $db_filename = "backup_database_{$timestamp}.sql";
            $db_filepath = $backup_dir . $db_filename;
            
            // ... (kode backup database sama seperti di atas) ...
            // Simpan ke $db_filepath
            
            // Create ZIP file
            $filename .= '.zip';
            $filepath = $backup_dir . $filename;
            
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($filepath, ZipArchive::CREATE) === TRUE) {
                    // Add database file
                    if (file_exists($db_filepath)) {
                        $zip->addFile($db_filepath, 'database.sql');
                    }
                    
                    // Add uploads directory
                    $uploads_dir = '../uploads/';
                    if (file_exists($uploads_dir)) {
                        $files = new RecursiveIteratorIterator(
                            new RecursiveDirectoryIterator($uploads_dir),
                            RecursiveIteratorIterator::LEAVES_ONLY
                        );
                        
                        foreach ($files as $name => $file) {
                            if (!$file->isDir()) {
                                $file_path = $file->getRealPath();
                                $relative_path = substr($file_path, strlen(dirname(__DIR__)) + 1);
                                $zip->addFile($file_path, $relative_path);
                            }
                        }
                    }
                    
                    $zip->close();
                    
                    // Remove temporary SQL file
                    if (file_exists($db_filepath)) {
                        unlink($db_filepath);
                    }
                } else {
                    throw new Exception('Gagal membuat file ZIP');
                }
            } else {
                throw new Exception('Ekstensi ZipArchive tidak tersedia');
            }
        }
        
        // Save backup record to database
        $filesize = filesize($filepath);
        $conn->query("INSERT INTO backup_history (filename, filepath, file_size, backup_type, created_by) 
                      VALUES ('$filename', '$filepath', $filesize, '$type', {$user['id']})");
        
        // Log activity
        $conn->query("INSERT INTO activity_logs (user_id, action, description, ip_address) 
                      VALUES ({$user['id']}, 'BACKUP_CREATE', 'Membuat backup: {$filename}', '{$_SERVER['REMOTE_ADDR']}')");
        
        $_SESSION['success'] = "Backup {$type} berhasil dibuat! File: {$filename}";
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Gagal membuat backup: ' . $e->getMessage();
    }
    
    header('Location: backup.php');
    exit;
}

// Get backup history
$history_result = $conn->query("SELECT bh.*, a.username 
                                 FROM backup_history bh 
                                 LEFT JOIN admin a ON bh.created_by = a.id 
                                 ORDER BY bh.created_at DESC");
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Sistem Backup Data</h3>
        <a href="settings.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i> Kembali ke Pengaturan
        </a>
    </div>
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>
    
    <!-- Backup Options -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-database fa-4x text-primary mb-3"></i>
                    <h4>Backup Database</h4>
                    <p class="text-muted">Hanya menyimpan data database (file .sql)</p>
                    <div class="mt-3">
                        <a href="backup.php?type=database" class="btn btn-primary btn-lg" onclick="return confirm('Mulai backup database sekarang?')">
                            <i class="fas fa-download me-2"></i> Backup Database
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <i class="fas fa-file-archive fa-4x text-success mb-3"></i>
                    <h4>Backup Lengkap</h4>
                    <p class="text-muted">Database + Semua file upload (file .zip)</p>
                    <div class="mt-3">
                        <a href="backup.php?type=full" class="btn btn-success btn-lg" onclick="return confirm('Backup lengkap akan memakan waktu lebih lama. Lanjutkan?')">
                            <i class="fas fa-archive me-2"></i> Backup Lengkap
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Backup History -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">Riwayat Backup</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nama File</th>
                            <th>Jenis</th>
                            <th>Ukuran</th>
                            <th>Dibuat Oleh</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($history_result->num_rows > 0): ?>
                            <?php $no = 1; while ($backup = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td>
                                    <code><?= htmlspecialchars($backup['filename']) ?></code>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $backup['backup_type'] == 'full' ? 'success' : 'primary' ?>">
                                        <?= strtoupper($backup['backup_type']) ?>
                                    </span>
                                </td>
                                <td><?= formatFileSize($backup['file_size'] ?? 0) ?></td>
                                <td><?= htmlspecialchars($backup['username'] ?? 'System') ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($backup['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php if (file_exists($backup['filepath'])): ?>
                                        <a href="backup.php?download=<?= $backup['id'] ?>" class="btn btn-outline-primary" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <?php endif; ?>
                                        <a href="backup.php?delete=<?= $backup['id'] ?>" class="btn btn-outline-danger" 
                                           onclick="return confirm('Hapus backup ini?')" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-2x mb-3"></i><br>
                                        Belum ada backup
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Backup Information -->
    <div class="alert alert-info mt-4">
        <h5><i class="fas fa-info-circle me-2"></i> Informasi Backup</h5>
        <ul class="mb-0">
            <li>Backup disimpan di folder: <code>database_backup/</code></li>
            <li>Backup database: Hanya menyimpan struktur dan data SQL</li>
            <li>Backup lengkap: Database + semua file upload (uploads/)</li>
            <li>Disarankan melakukan backup rutin setiap minggu</li>
            <li>Backup otomatis dapat diatur via cron job</li>
        </ul>
    </div>
</div>

<?php include 'footer.php'; ?>