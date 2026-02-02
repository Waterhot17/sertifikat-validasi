<?php
// ============================================
// PERBAIKI PATH DI AWAL FILE
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

$page_title = 'Log Aktivitas';

// Include header dari admin (bukan includes/header.php)
include 'header.php';

// Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

// Get filters
$filter_user = $_GET['user_id'] ?? '';
$filter_action = $_GET['action'] ?? '';
$filter_date = $_GET['date'] ?? '';

// Handle clear all logs
if (isset($_GET['clear']) && $_GET['clear'] === 'all' && $auth->isSuperAdmin()) {
    $conn->query("TRUNCATE TABLE activity_logs");
    $_SESSION['success'] = 'Semua log aktivitas telah dihapus!';
    header('Location: activity_log.php');
    exit();
}

// Build query
$where = [];
if (!empty($filter_user)) {
    $where[] = "al.user_id = '" . $conn->real_escape_string($filter_user) . "'";
}
if (!empty($filter_action)) {
    $where[] = "al.action LIKE '%" . $conn->real_escape_string($filter_action) . "%'";
}
if (!empty($filter_date)) {
    $where[] = "DATE(al.created_at) = '" . $conn->real_escape_string($filter_date) . "'";
}

$where_clause = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

// Get logs
$sql = "SELECT al.*, a.username, a.nama_lengkap 
        FROM activity_logs al 
        LEFT JOIN admin a ON al.user_id = a.id 
        $where_clause 
        ORDER BY al.created_at DESC 
        LIMIT 100";
$result = $conn->query($sql);

// Get users for filter
$users = $conn->query("SELECT id, username, nama_lengkap FROM admin ORDER BY username");
?>

<div class="container-fluid">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Log Aktivitas Sistem</h3>
        <?php if ($auth->isSuperAdmin()): ?>
        <button class="btn btn-danger" onclick="if(confirm('Hapus semua log? Tindakan ini tidak dapat dibatalkan.')) location.href='activity_log.php?clear=all'">
            <i class="fas fa-trash me-2"></i> Hapus Semua Log
        </button>
        <?php endif; ?>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Pengguna</label>
                    <select name="user_id" class="form-control">
                        <option value="">Semua Pengguna</option>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <option value="<?= $user['id'] ?>" <?= $filter_user == $user['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($user['nama_lengkap'] ?: $user['username']) ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Aksi</label>
                    <input type="text" name="action" class="form-control" placeholder="Cari aksi..." value="<?= htmlspecialchars($filter_action) ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($filter_date) ?>">
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter me-2"></i> Filter
                    </button>
                    <a href="activity_log.php" class="btn btn-outline-secondary">
                        <i class="fas fa-redo"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Log Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="50">#</th>
                            <th>Waktu</th>
                            <th>Pengguna</th>
                            <th>Aksi</th>
                            <th>Deskripsi</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php $no = 1; while ($log = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                <td>
                                    <?php if ($log['username']): ?>
                                        <span class="badge bg-primary"><?= htmlspecialchars($log['nama_lengkap'] ?: $log['username']) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Sistem</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($log['action']) ?></span>
                                </td>
                                <td><?= htmlspecialchars($log['description'] ?? '') ?></td>
                                <td><code><?= htmlspecialchars($log['ip_address'] ?? '') ?></code></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-history fa-2x mb-3"></i><br>
                                        Tidak ada log aktivitas
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
// Include footer dari admin (bukan includes/footer.php)
include 'footer.php'; 
?>