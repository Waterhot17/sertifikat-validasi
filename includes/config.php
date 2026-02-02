<?php
// ============================================
// FILE: CONFIG.PHP - KONFIGURASI SISTEM
// ============================================

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'Bina_Prestasi_Gemilang');

// Site Configuration
define('SITE_NAME', 'Bina Prestasi Gemilang');
define('BASE_URL', 'http://localhost/bina_prestasi_gemilang');

// Path Configuration
define('LOGO_PATH', 'assets/images/logo-bpg.png');

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting (for development)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ============================================
// HELPER FUNCTIONS
// ============================================

function base_url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

function redirect($url) {
    header('Location: ' . base_url($url));
    exit();
}

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function format_date($date, $format = 'd-m-Y') {
    if (empty($date) || $date == '0000-00-00') {
        return '-';
    }
    return date($format, strtotime($date));
}

function format_date_indo($date) {
    if (empty($date) || $date == '0000-00-00') {
        return '-';
    }
    
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $tanggal = date('j', strtotime($date));
    $bulan_index = date('n', strtotime($date));
    $tahun = date('Y', strtotime($date));
    
    return $tanggal . ' ' . $bulan[$bulan_index] . ' ' . $tahun;
}

function get_jenis_sertifikat() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $jenis = [];
    
    if ($conn->connect_error) {
        return $jenis;
    }
    
    $sql = "SELECT id, kode_jenis, nama_jenis FROM jenis_sertifikat ORDER BY nama_jenis";
    $result = $conn->query($sql);
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $jenis[$row['id']] = $row;
        }
    }
    
    $conn->close();
    return $jenis;
}

function show_message() {
    if (isset($_SESSION['flash_message'])) {
        $msg = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $alert_type = $msg['type'] === 'error' ? 'danger' : $msg['type'];
        
        echo '<div class="alert alert-' . $alert_type . ' alert-dismissible fade show">
                ' . htmlspecialchars($msg['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
    }
}

function set_message($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}
?>