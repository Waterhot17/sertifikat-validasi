<?php
// ============================================
// FILE: DASHBOARD.PHP - HALAMAN DASHBOARD ADMIN
// ============================================

// Load configuration and auth
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Require login
$auth->requireLogin();

// Get current user
$user = $auth->getUser();

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Get statistics
$stats = [];
$today = date('Y-m-d');

// Total certificates
$result = $conn->query("SELECT COUNT(*) as total FROM sertifikat");
$stats['total'] = $result->fetch_assoc()['total'];

// Active certificates
$result = $conn->query("SELECT COUNT(*) as active FROM sertifikat WHERE tanggal_expired >= '$today' OR tanggal_expired IS NULL");
$stats['active'] = $result->fetch_assoc()['active'];

// Expired certificates
$result = $conn->query("SELECT COUNT(*) as expired FROM sertifikat WHERE tanggal_expired < '$today'");
$stats['expired'] = $result->fetch_assoc()['expired'];

// Today's validations
$result = $conn->query("SELECT COUNT(*) as today FROM log_validasi WHERE DATE(waktu_validasi) = '$today'");
$stats['today'] = $result->fetch_assoc()['today'] ?? 0;

// Recent certificates (last 5)
$recent_certs = [];
$result = $conn->query("SELECT s.*, j.nama_jenis 
                       FROM sertifikat s 
                       LEFT JOIN jenis_sertifikat j ON s.jenis_id = j.id 
                       ORDER BY s.created_at DESC LIMIT 5");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_certs[] = $row;
    }
}

// Certificate types distribution
$type_stats = [];
$result = $conn->query("SELECT j.nama_jenis, COUNT(s.id) as jumlah 
                       FROM jenis_sertifikat j 
                       LEFT JOIN sertifikat s ON j.id = s.jenis_id 
                       GROUP BY j.id ORDER BY COUNT(s.id) DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $type_stats[] = $row;
    }
}

// Recent validations (last 10)
$recent_validations = [];
$result = $conn->query("SELECT l.*, s.nomor_sertifikat, s.nama_peserta 
                       FROM log_validasi l 
                       LEFT JOIN sertifikat s ON l.sertifikat_id = s.id 
                       ORDER BY l.waktu_validasi DESC LIMIT 10");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $recent_validations[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary: #667eea;
            --primary-dark: #5a67d8;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --light: #f8fafc;
            --dark: #1f2937;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light);
            color: var(--dark);
            overflow-x: hidden;
        }
        
        /* Top Navigation */
        .navbar-top {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 25px;
            height: 70px;
        }
        
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 15px;
            text-decoration: none;
        }
        
        .brand-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
        }
        
        .brand-text h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin: 0;
            color: var(--dark);
            line-height: 1.2;
        }
        
        .brand-text small {
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        /* Sidebar */
        .sidebar {
            background: white;
            width: 260px;
            position: fixed;
            top: 70px;
            left: 0;
            bottom: 0;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
            transition: all 0.3s;
            z-index: 1020;
        }
        
        .sidebar-user {
            padding: 25px 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .user-info h5 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
        }
        
        .user-info small {
            color: #6b7280;
            font-size: 0.85rem;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-section {
            margin-bottom: 25px;
        }
        
        .nav-title {
            padding: 0 20px 10px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .nav-item {
            margin: 3px 10px;
        }
        
        .nav-link {
            color: #374151;
            padding: 12px 15px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-link:hover {
            background: #f3f4f6;
            color: var(--primary);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .nav-link i {
            width: 20px;
            font-size: 1.1rem;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 260px;
            margin-top: 70px;
            padding: 30px;
            min-height: calc(100vh - 70px);
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .page-subtitle {
            color: #6b7280;
            font-size: 1rem;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
        }
        
        .stat-card.total::before { background: var(--info); }
        .stat-card.active::before { background: var(--success); }
        .stat-card.expired::before { background: var(--danger); }
        .stat-card.today::before { background: var(--warning); }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .stat-card.total .stat-icon { background: #dbeafe; color: var(--info); }
        .stat-card.active .stat-icon { background: #d1fae5; color: var(--success); }
        .stat-card.expired .stat-icon { background: #fee2e2; color: var(--danger); }
        .stat-card.today .stat-icon { background: #fef3c7; color: var(--warning); }
        
        .stat-trend {
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .trend-up { color: var(--success); }
        .trend-down { color: var(--danger); }
        
        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin: 5px 0;
            color: var(--dark);
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.95rem;
            font-weight: 500;
        }
        
        /* Content Cards */
        .content-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            margin-bottom: 30px;
            overflow: hidden;
        }
        
        .card-header {
            padding: 25px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin: 0;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .card-title i {
            color: var(--primary);
        }
        
        .card-body {
            padding: 25px;
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .data-table thead th {
            background: #f9fafb;
            color: #374151;
            font-weight: 600;
            font-size: 0.9rem;
            padding: 15px;
            border-bottom: 2px solid #e5e7eb;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table tbody td {
            padding: 15px;
            border-bottom: 1px solid #f3f4f6;
            vertical-align: middle;
        }
        
        .data-table tbody tr {
            transition: all 0.3s;
        }
        
        .data-table tbody tr:hover {
            background: #f9fafb;
        }
        
        .data-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-expired { background: #fee2e2; color: #991b1b; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        
        /* Buttons */
        .btn-action {
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-view { background: #dbeafe; color: var(--info); }
        .btn-edit { background: #fef3c7; color: var(--warning); }
        .btn-delete { background: #fee2e2; color: var(--danger); }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .action-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            border: 1px solid #e5e7eb;
            transition: all 0.3s;
            text-decoration: none;
            color: inherit;
        }
        
        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }
        
        .action-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 1.5rem;
        }
        
        .action-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--dark);
        }
        
        .action-desc {
            color: #6b7280;
            font-size: 0.9rem;
            margin: 0;
        }
        
        /* Chart Container */
        .chart-container {
            height: 300px;
            position: relative;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            color: #d1d5db;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                margin-left: -260px;
            }
            
            .sidebar.active {
                margin-left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .navbar-brand .brand-text {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .main-content {
                padding: 20px 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar-top">
        <div class="navbar-content">
            <!-- Left: Brand & Menu Toggle -->
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-outline-secondary d-lg-none" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="dashboard.php" class="navbar-brand">
                    <div class="brand-logo">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="brand-text">
                        <h3><?php echo SITE_NAME; ?></h3>
                        <small>Admin Panel</small>
                    </div>
                </a>
            </div>
            
            <!-- Right: User Menu -->
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center gap-2" 
                        type="button" data-bs-toggle="dropdown">
                    <div class="user-avatar-small">
                        <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                    </div>
                    <span><?php echo htmlspecialchars($user['nama_lengkap'] ?: $user['username']); ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header"><?php echo $user['level']; ?></h6></li>
                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i> Profile</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <!-- User Info -->
        <div class="sidebar-user">
            <div class="user-avatar">
                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
            </div>
            <div class="user-info">
                <h5><?php echo htmlspecialchars($user['nama_lengkap'] ?: $user['username']); ?></h5>
                <small><?php echo $user['level']; ?></small>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="sidebar-nav">
            <!-- Main Navigation -->
            <div class="nav-section">
                <div class="nav-title">Menu Utama</div>
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link active">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="add_sertifikat.php" class="nav-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>Tambah Sertifikat</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="list_sertifikat.php" class="nav-link">
                        <i class="fas fa-list"></i>
                        <span>Daftar Sertifikat</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="validasi_manual.php" class="nav-link">
                        <i class="fas fa-search"></i>
                        <span>Validasi Manual</span>
                    </a>
                </div>
            </div>
            
            <!-- Certificate Management -->
            <div class="nav-section">
                <div class="nav-title">Kelola Sertifikat</div>
                <div class="nav-item">
                    <a href="jenis_sertifikat.php" class="nav-link">
                        <i class="fas fa-certificate"></i>
                        <span>Jenis Sertifikat</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="template.php" class="nav-link">
                        <i class="fas fa-file-alt"></i>
                        <span>Template</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="import.php" class="nav-link">
                        <i class="fas fa-file-import"></i>
                        <span>Import Data</span>
                    </a>
                </div>
            </div>
            
            <!-- Administration -->
            <?php if ($auth->isSuperAdmin()): ?>
            <div class="nav-section">
                <div class="nav-title">Administrasi</div>
                <div class="nav-item">
                    <a href="manajemen_user.php" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span>Manajemen User</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="settings.php" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span>Pengaturan</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="activity_log.php" class="nav-link">
                        <i class="fas fa-history"></i>
                        <span>Log Aktivitas</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="backup.php" class="nav-link">
                        <i class="fas fa-database"></i>
                        <span>Backup Data</span>
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Quick Links -->
            <div class="nav-section">
                <div class="nav-item">
                    <a href="../index.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Beranda Publik</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="help.php" class="nav-link">
                        <i class="fas fa-question-circle"></i>
                        <span>Bantuan</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="logout.php" class="nav-link text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Selamat datang kembali, <?php echo htmlspecialchars($user['nama_lengkap'] ?: $user['username']); ?>!</p>
        </div>
        
        <!-- Stats Cards -->
        <div class="stats-grid">
            <!-- Total Certificates -->
            <div class="stat-card total">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        12%
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total']); ?></div>
                <div class="stat-label">Total Sertifikat</div>
            </div>
            
            <!-- Active Certificates -->
            <div class="stat-card active">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        8%
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['active']); ?></div>
                <div class="stat-label">Sertifikat Aktif</div>
            </div>
            
            <!-- Expired Certificates -->
            <div class="stat-card expired">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-trend trend-down">
                        <i class="fas fa-arrow-down"></i>
                        3%
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['expired']); ?></div>
                <div class="stat-label">Kadaluarsa</div>
            </div>
            
            <!-- Today's Validations -->
            <div class="stat-card today">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <div class="stat-trend trend-up">
                        <i class="fas fa-arrow-up"></i>
                        24%
                    </div>
                </div>
                <div class="stat-value"><?php echo number_format($stats['today']); ?></div>
                <div class="stat-label">Validasi Hari Ini</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="add_sertifikat.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="action-title">Tambah Sertifikat</div>
                <p class="action-desc">Tambah sertifikat baru secara manual</p>
            </a>
            
            <a href="list_sertifikat.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-list"></i>
                </div>
                <div class="action-title">Lihat Semua Sertifikat</div>
                <p class="action-desc">Kelola semua sertifikat yang ada</p>
            </a>
            
            <a href="validasi.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-search"></i>
                </div>
                <div class="action-title">Validasi Sertifikat</div>
                <p class="action-desc">Validasi sertifikat secara manual</p>
            </a>
            
            <a href="import_data.php" class="action-card">
                <div class="action-icon">
                    <i class="fas fa-file-import"></i>
                </div>
                <div class="action-title">Import Data</div>
                <p class="action-desc">Import data sertifikat dari Excel/CSV</p>
            </a>
        </div>
        
        <div class="row">
            <!-- Recent Certificates -->
            <div class="col-lg-8">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-history"></i>
                            Sertifikat Terbaru
                        </h5>
                        <a href="list_sertifikat.php" class="btn btn-sm btn-outline-primary">
                            Lihat Semua
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_certs)): ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>No. Sertifikat</th>
                                            <th>Nama Peserta</th>
                                            <th>Jenis</th>
                                            <th>Tanggal</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_certs as $cert): 
                                            $status = 'active';
                                            $badge_class = 'badge-active';
                                            $status_text = 'Aktif';
                                            
                                            if ($cert['tanggal_expired'] && $cert['tanggal_expired'] < $today) {
                                                $status = 'expired';
                                                $badge_class = 'badge-expired';
                                                $status_text = 'Kadaluarsa';
                                            }
                                        ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($cert['nomor_sertifikat']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($cert['nama_peserta']); ?></td>
                                                <td><?php echo htmlspecialchars($cert['nama_jenis'] ?? '-'); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($cert['tanggal_terbit'])); ?></td>
                                                <td>
                                                    <span class="status-badge <?php echo $badge_class; ?>">
                                                        <i class="fas fa-circle" style="font-size: 8px;"></i>
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="view_sertifikat.php?id=<?php echo $cert['id']; ?>" 
                                                           class="btn-action btn-view" title="Lihat">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit_sertifikat.php?id=<?php echo $cert['id']; ?>" 
                                                           class="btn-action btn-edit" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h5>Belum ada sertifikat</h5>
                                <p>Mulai dengan menambahkan sertifikat baru</p>
                                <a href="add_sertifikat.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-plus me-2"></i> Tambah Sertifikat
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Certificate Types Distribution -->
            <div class="col-lg-4">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-pie"></i>
                            Jenis Sertifikat
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="typeChart"></canvas>
                        </div>
                        <div class="mt-4">
                            <?php foreach ($type_stats as $stat): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                    <span><?php echo htmlspecialchars($stat['nama_jenis']); ?></span>
                                    <strong><?php echo $stat['jumlah']; ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Validations -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="content-card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-chart-line"></i>
                            Aktivitas Validasi Terakhir
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_validations)): ?>
                            <div class="table-responsive">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Waktu</th>
                                            <th>No. Sertifikat</th>
                                            <th>Nama Peserta</th>
                                            <th>Metode</th>
                                            <th>Hasil</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_validations as $val): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($val['waktu_validasi'])); ?></td>
                                                <td><?php echo htmlspecialchars($val['nomor_sertifikat'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($val['nama_peserta'] ?? '-'); ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        <?php echo htmlspecialchars($val['metode'] ?? 'Manual'); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $result = $val['hasil_validasi'] ?? 'Valid';
                                                    $result_class = $result == 'Valid' ? 'success' : ($result == 'Kadaluarsa' ? 'warning' : 'danger');
                                                    ?>
                                                    <span class="badge bg-<?php echo $result_class; ?>">
                                                        <?php echo $result; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-search"></i>
                                <h5>Belum ada aktivitas validasi</h5>
                                <p>Belum ada sertifikat yang divalidasi</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebarToggle');
            
            if (window.innerWidth < 992 && 
                !sidebar.contains(event.target) && 
                !toggleBtn.contains(event.target) &&
                sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
        
        // Chart for certificate types
        const typeCtx = document.getElementById('typeChart').getContext('2d');
        
        // Prepare chart data
        const typeLabels = <?php echo json_encode(array_column($type_stats, 'nama_jenis')); ?>;
        const typeData = <?php echo json_encode(array_column($type_stats, 'jumlah')); ?>;
        
        // Chart colors
        const chartColors = [
            '#667eea', '#764ba2', '#10b981', '#f59e0b', '#ef4444',
            '#3b82f6', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'
        ];
        
        // Create chart
        const typeChart = new Chart(typeCtx, {
            type: 'doughnut',
            data: {
                labels: typeLabels,
                datasets: [{
                    data: typeData,
                    backgroundColor: chartColors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
        
        // Update time display every minute
        function updateTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            const dateString = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            // You can display this somewhere if needed
            // document.getElementById('currentTime').textContent = timeString;
            // document.getElementById('currentDate').textContent = dateString;
        }
        
        // Update time immediately and every minute
        updateTime();
        setInterval(updateTime, 60000);
    </script>
</body>
</html>