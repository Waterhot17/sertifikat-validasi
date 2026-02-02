<?php
// ============================================
// FILE: HEADER.PHP - HEADER UNTUK SEMUA HALAMAN
// ============================================

// Session dan konfigurasi
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config jika diperlukan
$config_loaded = false;
if (file_exists('../includes/config.php')) {
    require_once '../includes/config.php';
    $config_loaded = true;
} elseif (file_exists('includes/config.php')) {
    require_once 'includes/config.php';
    $config_loaded = true;
}

// Define constants if not defined
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Bina Prestasi Gemilang');
}

// User data - default jika tidak ada session
$user = [
    'username' => 'Admin',
    'nama_lengkap' => 'Administrator',
    'level' => 'Admin'
];

// Ambil dari session jika ada
if (isset($_SESSION['user_id'])) {
    $user = [
        'username' => $_SESSION['username'] ?? 'Admin',
        'nama_lengkap' => $_SESSION['nama_lengkap'] ?? 'Administrator',
        'level' => $_SESSION['user_level'] ?? 'Admin'
    ];
}

// Determine current page for active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <style>
        /* FIXED HEADER */
        .navbar-top {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 70px;
            z-index: 1030;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .navbar-container {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 25px;
            max-width: 100%;
            margin: 0;
        }
        
        /* Brand Logo */
        .brand-area {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .brand-logo {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            color: #1f2937;
        }
        
        .brand-text small {
            font-size: 0.8rem;
            color: #6b7280;
            display: block;
        }
        
        /* Toggle Button */
        #sidebarToggle {
            background: transparent;
            border: 1px solid #d1d5db;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #4b5563;
        }
        
        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar-small {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
        }
        
        /* FIXED SIDEBAR */
        .sidebar {
            background: white;
            width: 260px;
            position: fixed;
            top: 70px; /* Mulai di bawah header */
            left: 0;
            bottom: 0;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
            z-index: 1020;
            transition: transform 0.3s ease;
        }
        
        .sidebar-user {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .user-avatar-large {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
        }
        
        .user-info h6 {
            margin: 0;
            font-weight: 600;
        }
        
        .user-info small {
            color: #6b7280;
            font-size: 0.85rem;
        }
        
        /* Navigation */
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-section {
            margin-bottom: 25px;
        }
        
        .nav-title {
            padding: 0 20px 10px;
            font-size: 0.75rem;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .nav-item {
            margin: 2px 10px;
        }
        
        .nav-link {
            color: #374151;
            padding: 10px 15px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 500;
            font-size: 0.95rem;
        }
        
        .nav-link:hover {
            background: #f3f4f6;
            color: #667eea;
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        /* Main Content Area - FIXED POSITION */
        .main-content {
            margin-top: 70px; /* Space for fixed header */
            margin-left: 260px; /* Space for sidebar */
            padding: 30px;
            min-height: calc(100vh - 70px);
            background: #f9fafb;
            transition: margin-left 0.3s ease;
        }
        
        /* When sidebar is hidden on mobile */
        .sidebar-hidden .sidebar {
            transform: translateX(-100%);
        }
        
        .sidebar-hidden .main-content {
            margin-left: 0;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 70px;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 1019;
                display: none;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
        
        /* Page Content */
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .page-header .breadcrumb {
            background: transparent;
            padding: 0;
            margin: 0;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Fixed Header -->
    <nav class="navbar-top">
        <div class="navbar-container">
            <!-- Left: Brand & Toggle -->
            <div class="brand-area">
                <button class="btn d-lg-none me-2" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <a href="dashboard.php" class="d-flex align-items-center text-decoration-none">
                    <div class="brand-logo">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="brand-text">
                        <h3><?php echo SITE_NAME; ?></h3>
                        <small>Sistem Validasi Sertifikat</small>
                    </div>
                </a>
            </div>
            
            <!-- Right: User Info -->
            <div class="user-menu">
                <div class="dropdown">
                    <div class="user-avatar-small dropdown-toggle" data-bs-toggle="dropdown">
                        <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
                    </div>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header"><?php echo $user['nama_lengkap']; ?></h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Sidebar Overlay (Mobile only) -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <!-- User Info -->
        <div class="sidebar-user">
            <div class="user-avatar-large">
                <?php echo strtoupper(substr($user['username'], 0, 2)); ?>
            </div>
            <div class="user-info">
                <h6><?php echo htmlspecialchars($user['nama_lengkap']); ?></h6>
                <small><?php echo $user['level']; ?></small>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="sidebar-nav">
            <!-- MENU UTAMA -->
            <div class="nav-section">
                <div class="nav-title">MENU UTAMA</div>
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="add_sertifikat.php" class="nav-link <?php echo $current_page == 'add_sertifikat.php' ? 'active' : ''; ?>">
                        <i class="fas fa-plus-circle"></i>
                        <span>Tambah Sertifikat</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="list_sertifikat.php" class="nav-link <?php echo $current_page == 'list_sertifikat.php' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i>
                        <span>Daftar Sertifikat</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="validasi_manual.php" class="nav-link <?php echo $current_page == 'validasi_manual.php' ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i>
                        <span>Validasi Manual</span>
                    </a>
                </div>
            </div>
            
            <!-- KELOLA SERTIFIKAT -->
            <div class="nav-section">
                <div class="nav-title">KELOLA SERTIFIKAT</div>
                <div class="nav-item">
                    <a href="jenis_sertifikat.php" class="nav-link <?php echo $current_page == 'jenis_sertifikat.php' ? 'active' : ''; ?>">
                        <i class="fas fa-certificate"></i>
                        <span>Jenis Sertifikat</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="template.php" class="nav-link <?php echo $current_page == 'template.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-alt"></i>
                        <span>Template</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="import_data.php" class="nav-link <?php echo $current_page == 'import_data.php' ? 'active' : ''; ?>">
                        <i class="fas fa-file-import"></i>
                        <span>Import Data</span>
                    </a>
                </div>
            </div>
            
            <!-- ADMINISTRASI -->
            <div class="nav-section">
                <div class="nav-title">ADMINISTRASI</div>
                <div class="nav-item">
                    <a href="manajemen_user.php" class="nav-link <?php echo $current_page == 'manajemen_user.php' ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Manajemen User</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="pengaturan.php" class="nav-link <?php echo $current_page == 'pengaturan.php' ? 'active' : ''; ?>">
                        <i class="fas fa-cog"></i>
                        <span>Pengaturan</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="log_aktivitas.php" class="nav-link <?php echo $current_page == 'log_aktivitas.php' ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i>
                        <span>Log Aktivitas</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="backup_data.php" class="nav-link <?php echo $current_page == 'backup_data.php' ? 'active' : ''; ?>">
                        <i class="fas fa-database"></i>
                        <span>Backup Data</span>
                    </a>
                </div>
            </div>
            
            <!-- LAINNYA -->
            <div class="nav-section">
                <div class="nav-title">LAINNYA</div>
                <div class="nav-item">
                    <a href="../index.php" target="_blank" class="nav-link">
                        <i class="fas fa-globe"></i>
                        <span>Beranda Publik</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a href="bantuan.php" class="nav-link <?php echo $current_page == 'bantuan.php' ? 'active' : ''; ?>">
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
    
    <!-- Main Content Area -->
    <div class="main-content" id="mainContent">
        <!-- JavaScript for Sidebar Toggle -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const sidebarToggle = document.getElementById('sidebarToggle');
                const sidebar = document.getElementById('sidebar');
                const sidebarOverlay = document.getElementById('sidebarOverlay');
                const mainContent = document.getElementById('mainContent');
                
                if (sidebarToggle) {
                    sidebarToggle.addEventListener('click', function() {
                        sidebar.classList.toggle('show');
                        sidebarOverlay.classList.toggle('show');
                    });
                }
                
                if (sidebarOverlay) {
                    sidebarOverlay.addEventListener('click', function() {
                        sidebar.classList.remove('show');
                        sidebarOverlay.classList.remove('show');
                    });
                }
                
                // Close sidebar when clicking outside on mobile
                document.addEventListener('click', function(event) {
                    const isMobile = window.innerWidth < 992;
                    if (isMobile && !sidebar.contains(event.target) && 
                        !sidebarToggle.contains(event.target) && 
                        sidebar.classList.contains('show')) {
                        sidebar.classList.remove('show');
                        sidebarOverlay.classList.remove('show');
                    }
                });
            });
        </script>