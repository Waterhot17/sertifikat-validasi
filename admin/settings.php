<?php
// ============================================
// PERBAIKI PATH DI BARIS AWAL
// ============================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Definisikan BASE_PATH
define('BASE_PATH', dirname(__DIR__));

// Load config dan auth dari folder includes
require_once BASE_PATH . '/includes/config.php';
require_once BASE_PATH . '/includes/auth.php';

// Inisialisasi auth
$auth = new Auth();
$auth->requireAdmin();

$page_title = 'Pengaturan Sistem';

// Include header dari admin (bukan includes/header.php)
include 'header.php';

// Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'setting_') === 0) {
            $setting_key = str_replace('setting_', '', $key);
            $value = $conn->real_escape_string(trim($value));
            
            // Check if setting exists
            $check = $conn->query("SELECT id FROM system_settings WHERE setting_key = '$setting_key'");
            
            if ($check->num_rows > 0) {
                $conn->query("UPDATE system_settings SET setting_value = '$value', updated_at = NOW() WHERE setting_key = '$setting_key'");
            } else {
                $conn->query("INSERT INTO system_settings (setting_key, setting_value) VALUES ('$setting_key', '$value')");
            }
        }
    }
    
    // Log activity
    $user = $auth->getUser();
    $conn->query("INSERT INTO activity_logs (user_id, action, description, ip_address) 
                  VALUES ({$user['id']}, 'SETTINGS_UPDATE', 'Memperbarui pengaturan sistem', '{$_SERVER['REMOTE_ADDR']}')");
    
    $_SESSION['success'] = 'Pengaturan berhasil diperbarui!';
    header('Location: settings.php');
    exit();
}

// Get all settings
$settings = [];
$result = $conn->query("SELECT * FROM system_settings ORDER BY category, setting_key");
while ($row = $result->fetch_assoc()) {
    $settings[$row['category']][$row['setting_key']] = $row;
}
?>

<!-- ============================================
SISANYA KODE HTML TETAP SAMA
============================================ -->

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Menu Pengaturan</h5>
                </div>
                <div class="list-group list-group-flush" id="settingsMenu">
                    <a href="#general" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="fas fa-cog me-2"></i> Umum
                    </a>
                    <a href="#appearance" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-palette me-2"></i> Tampilan
                    </a>
                    <a href="#contact" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-address-book me-2"></i> Kontak
                    </a>
                    <a href="#email" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-envelope me-2"></i> Email
                    </a>
                    <?php if ($auth->isSuperAdmin()): ?>
                    <a href="#backup" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-database me-2"></i> Backup
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-md-9">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Pengaturan Sistem</h4>
                </div>
                
                <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <?= $_SESSION['success']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    
                    <form method="POST" id="settingsForm">
                        <div class="tab-content">
                            <!-- Tab: General -->
                            <div class="tab-pane fade show active" id="general">
                                <h5 class="mb-3">Pengaturan Umum</h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Nama Situs</label>
                                        <input type="text" class="form-control" name="setting_site_title" 
                                               value="<?= htmlspecialchars($settings['general']['site_title']['setting_value'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Mode Maintenance</label>
                                        <select class="form-control" name="setting_maintenance_mode">
                                            <option value="0" <?= ($settings['general']['maintenance_mode']['setting_value'] ?? 0) == 0 ? 'selected' : '' ?>>Tidak Aktif</option>
                                            <option value="1" <?= ($settings['general']['maintenance_mode']['setting_value'] ?? 0) == 1 ? 'selected' : '' ?>>Aktif</option>
                                        </select>
                                    </div>
                                    
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Deskripsi Situs</label>
                                        <textarea class="form-control" name="setting_site_description" rows="3"><?= htmlspecialchars($settings['general']['site_description']['setting_value'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Keywords (SEO)</label>
                                        <input type="text" class="form-control" name="setting_site_keywords" 
                                               value="<?= htmlspecialchars($settings['general']['site_keywords']['setting_value'] ?? '') ?>">
                                        <small class="text-muted">Pisahkan dengan koma</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tab: Appearance -->
                            <div class="tab-pane fade" id="appearance">
                                <h5 class="mb-3">Pengaturan Tampilan</h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Path Logo</label>
                                        <input type="text" class="form-control" name="setting_logo_path"
                                               value="<?= htmlspecialchars($settings['appearance']['logo_path']['setting_value'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Warna Tema</label>
                                        <input type="color" class="form-control" name="setting_theme_color"
                                               value="<?= htmlspecialchars($settings['appearance']['theme_color']['setting_value'] ?? '#2c3e50') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tab: Contact -->
                            <div class="tab-pane fade" id="contact">
                                <h5 class="mb-3">Informasi Kontak</h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email Kontak</label>
                                        <input type="email" class="form-control" name="setting_contact_email"
                                               value="<?= htmlspecialchars($settings['contact']['contact_email']['setting_value'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Telepon</label>
                                        <input type="text" class="form-control" name="setting_contact_phone"
                                               value="<?= htmlspecialchars($settings['contact']['contact_phone']['setting_value'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Alamat</label>
                                        <textarea class="form-control" name="setting_contact_address" rows="3"><?= htmlspecialchars($settings['contact']['contact_address']['setting_value'] ?? '') ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tab: Email -->
                            <div class="tab-pane fade" id="email">
                                <h5 class="mb-3">Pengaturan Email</h5>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Host</label>
                                        <input type="text" class="form-control" name="setting_smtp_host"
                                               value="<?= htmlspecialchars($settings['email']['smtp_host']['setting_value'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Port</label>
                                        <input type="number" class="form-control" name="setting_smtp_port"
                                               value="<?= htmlspecialchars($settings['email']['smtp_port']['setting_value'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Username</label>
                                        <input type="text" class="form-control" name="setting_smtp_username"
                                               value="<?= htmlspecialchars($settings['email']['smtp_username']['setting_value'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">SMTP Password</label>
                                        <input type="password" class="form-control" name="setting_smtp_password"
                                               value="<?= htmlspecialchars($settings['email']['smtp_password']['setting_value'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Tab: Backup -->
                            <?php if ($auth->isSuperAdmin()): ?>
                            <div class="tab-pane fade" id="backup">
                                <h5 class="mb-3">Backup Data</h5>
                                
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Backup akan menyimpan seluruh data database dan file penting sistem.
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <i class="fas fa-database fa-3x text-primary mb-3"></i>
                                                <h5>Backup Database</h5>
                                                <p class="text-muted">Simpan semua data ke file SQL</p>
                                                <a href="backup.php?type=database" class="btn btn-primary">
                                                    <i class="fas fa-download me-2"></i> Backup Sekarang
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-body text-center">
                                                <i class="fas fa-file-archive fa-3x text-success mb-3"></i>
                                                <h5>Backup Full</h5>
                                                <p class="text-muted">Database + File sistem</p>
                                                <a href="backup.php?type=full" class="btn btn-success">
                                                    <i class="fas fa-archive me-2"></i> Backup Full
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Backup History -->
                                <div class="mt-4">
                                    <h6>Riwayat Backup</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Nama File</th>
                                                    <th>Jenis</th>
                                                    <th>Ukuran</th>
                                                    <th>Tanggal</th>
                                                    <th>Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Function untuk format file size
                                                function format_file_size($bytes) {
                                                    if ($bytes === 0) return '0 Bytes';
                                                    $k = 1024;
                                                    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
                                                    $i = floor(log($bytes) / log($k));
                                                    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
                                                }
                                                
                                                $history = $conn->query("SELECT * FROM backup_history ORDER BY created_at DESC LIMIT 10");
                                                while ($backup = $history->fetch_assoc()): 
                                                    $file_size = format_file_size($backup['file_size'] ?? 0);
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($backup['filename'] ?? '') ?></td>
                                                    <td><?= htmlspecialchars($backup['backup_type'] ?? '') ?></td>
                                                    <td><?= $file_size ?></td>
                                                    <td><?= !empty($backup['created_at']) ? date('d/m/Y H:i', strtotime($backup['created_at'])) : '-' ?></td>
                                                    <td>
                                                        <?php if (!empty($backup['filepath'])): ?>
                                                        <a href="backup.php?download=<?= $backup['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Simpan Pengaturan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// Switch tabs on menu click
document.querySelectorAll('#settingsMenu a').forEach(function(el) {
    el.addEventListener('click', function(e) {
        e.preventDefault();
        var tabId = this.getAttribute('href');
        
        // Update active class
        document.querySelectorAll('#settingsMenu a').forEach(function(item) {
            item.classList.remove('active');
        });
        this.classList.add('active');
        
        // Show tab
        document.querySelectorAll('.tab-pane').forEach(function(tab) {
            tab.classList.remove('show', 'active');
        });
        document.querySelector(tabId).classList.add('show', 'active');
    });
});
</script>