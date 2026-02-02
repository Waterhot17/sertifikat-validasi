<?php
// ============================================
// FILE: SIDEBAR.PHP - SIDEBAR NAVIGATION
// ============================================

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Get user level
$user_level = isset($user) ? ($user['level'] ?? 'Admin') : 'Admin';
$is_super_admin = $user_level === 'Super Admin';
?>
<!-- Sidebar -->
<div class="col-md-3 col-lg-2 d-md-block sidebar collapse" id="sidebar">
    <!-- User Info -->
    <div class="sidebar-user">
        <div class="user-avatar">
            <?php 
            $username = isset($user) ? $user['username'] : 'AD';
            echo strtoupper(substr($username, 0, 2)); 
            ?>
        </div>
        <div class="user-info">
            <h5><?php echo isset($user) ? htmlspecialchars($user['nama_lengkap'] ?? $user['username']) : 'Admin'; ?></h5>
            <small><?php echo $user_level; ?></small>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="sidebar-nav">
        <!-- Main Navigation -->
        <div class="nav-section">
            <div class="nav-title">Menu Utama</div>
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
        </div>
        
        <!-- Certificate Management -->
        <div class="nav-section">
            <div class="nav-title">Kelola Sertifikat</div>
            <div class="nav-item">
                <a href="jenis_sertifikat.php" class="nav-link <?php echo $current_page == 'jenis_sertifikat.php' ? 'active' : ''; ?>">
                    <i class="fas fa-certificate"></i>
                    <span>Jenis Sertifikat</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="validasi.php" class="nav-link <?php echo $current_page == 'validasi.php' ? 'active' : ''; ?>">
                    <i class="fas fa-search"></i>
                    <span>Validasi Manual</span>
                </a>
            </div>
        </div>
        
        <!-- Administration -->
        <?php if ($is_super_admin): ?>
        <div class="nav-section">
            <div class="nav-title">Administrasi</div>
            <div class="nav-item">
                <a href="manajemen_user.php" class="nav-link <?php echo $current_page == 'manajemen_user.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Manajemen User</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="settings.php" class="nav-link <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i>
                    <span>Pengaturan</span>
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Quick Links -->
        <div class="nav-section">
            <div class="nav-item">
                <a href="../index.php" class="nav-link" target="_blank">
                    <i class="fas fa-home"></i>
                    <span>Beranda Publik</span>
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

<script>
    // Toggle sidebar
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        const sidebar = document.getElementById('sidebar');
        if (sidebar) {
            sidebar.classList.toggle('show');
        }
    });
</script>