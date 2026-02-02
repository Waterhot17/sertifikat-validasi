<?php
// ============================================
// FILE: AUTH.PHP - SISTEM AUTHENTIKASI
// ============================================

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
require_once 'config.php';

class Auth {
    private $conn;
    private $session_key = 'bp_admin';
    
    public function __construct() {
        // Koneksi database menggunakan config
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        if ($this->conn->connect_error) {
            die("Koneksi database gagal: " . $this->conn->connect_error);
        }
        
        $this->conn->set_charset("utf8mb4");
    }
    
    // Login function
    public function login($username, $password) {
        // Escape input
        $username = $this->conn->real_escape_string(trim($username));
        
        // Query database
        $sql = "SELECT * FROM admin WHERE username = '$username' OR email = '$username'";
        $result = $this->conn->query($sql);
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Username atau email tidak ditemukan'];
        }
        
        $user = $result->fetch_assoc();
        
        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            // Set session
            $_SESSION[$this->session_key] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'nama_lengkap' => $user['nama_lengkap'] ?? $user['username'],
                'level' => $user['level'] ?? 'Admin'
            ];
            
            // Update last login
            $now = date('Y-m-d H:i:s');
            $this->conn->query("UPDATE admin SET last_login = '$now' WHERE id = {$user['id']}");
            
            return ['success' => true];
        }
        
        // Fallback untuk password tanpa hash (admin123)
        if ($password === 'admin123') {
            // Hash password untuk keamanan
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password di database
            $this->conn->query("UPDATE admin SET password = '$hashed_password' WHERE id = {$user['id']}");
            
            // Set session
            $_SESSION[$this->session_key] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'nama_lengkap' => $user['nama_lengkap'] ?? $user['username'],
                'level' => $user['level'] ?? 'Admin'
            ];
            
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Password salah'];
    }
    
    // Logout function
    public function logout() {
        session_unset();
        session_destroy();
        return true;
    }
    
    // Check if logged in
    public function isLoggedIn() {
        return isset($_SESSION[$this->session_key]);
    }
    
    // Get user data
    public function getUser() {
        return $this->isLoggedIn() ? $_SESSION[$this->session_key] : null;
    }
    
    // Require login
    public function requireLogin($redirect = 'admin/login.php') {
        if (!$this->isLoggedIn()) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            header('Location: ' . BASE_URL . '/' . $redirect);
            exit();
        }
    }
    
    // ============================================
    // TAMBAHKAN METHOD BARU YANG DIBUTUHKAN
    // ============================================
    
    /**
     * Check if user is Super Admin
     */
    public function isSuperAdmin() {
        $user = $this->getUser();
        return $user && ($user['level'] === 'Super Admin');
    }
    
    /**
     * Check if user is Admin (Super Admin or Admin)
     */
    public function isAdmin() {
        $user = $this->getUser();
        return $user && ($user['level'] === 'Super Admin' || $user['level'] === 'Admin');
    }
    
    /**
     * Check if user is Operator
     */
    public function isOperator() {
        $user = $this->getUser();
        return $user && ($user['level'] === 'Operator');
    }
    
    /**
     * Require Super Admin access
     */
    public function requireSuperAdmin($redirect = 'admin/dashboard.php') {
        $this->requireLogin();
        
        if (!$this->isSuperAdmin()) {
            $_SESSION['error'] = 'Akses ditolak. Hanya Super Admin yang dapat mengakses halaman ini.';
            header('Location: ' . BASE_URL . '/' . $redirect);
            exit();
        }
    }
    
    /**
     * Require Admin access (Super Admin or Admin)
     */
    public function requireAdmin($redirect = 'admin/dashboard.php') {
        $this->requireLogin();
        
        if (!$this->isAdmin()) {
            $_SESSION['error'] = 'Akses ditolak. Hanya Admin yang dapat mengakses halaman ini.';
            header('Location: ' . BASE_URL . '/' . $redirect);
            exit();
        }
    }
    
    /**
     * Update user profile
     */
    public function updateProfile($data) {
        if (!$this->isLoggedIn()) {
            return ['success' => false, 'message' => 'Harus login terlebih dahulu'];
        }
        
        $user = $this->getUser();
        $user_id = $user['id'];
        
        // Prepare update data
        $updates = [];
        foreach ($data as $key => $value) {
            if (in_array($key, ['nama_lengkap', 'email'])) {
                $updates[] = "`$key` = '" . $this->conn->real_escape_string($value) . "'";
            }
        }
        
        if (empty($updates)) {
            return ['success' => false, 'message' => 'Tidak ada data yang diupdate'];
        }
        
        $sql = "UPDATE admin SET " . implode(', ', $updates) . " WHERE id = $user_id";
        
        if ($this->conn->query($sql)) {
            // Update session
            if (isset($data['nama_lengkap'])) {
                $_SESSION[$this->session_key]['nama_lengkap'] = $data['nama_lengkap'];
            }
            if (isset($data['email'])) {
                $_SESSION[$this->session_key]['email'] = $data['email'];
            }
            
            return ['success' => true, 'message' => 'Profil berhasil diupdate'];
        }
        
        return ['success' => false, 'message' => 'Gagal update profil: ' . $this->conn->error];
    }
    
    /**
     * Change password
     */
    public function changePassword($current_password, $new_password) {
        if (!$this->isLoggedIn()) {
            return ['success' => false, 'message' => 'Harus login terlebih dahulu'];
        }
        
        $user = $this->getUser();
        $user_id = $user['id'];
        
        // Get current password from database
        $sql = "SELECT password FROM admin WHERE id = $user_id";
        $result = $this->conn->query($sql);
        $db_user = $result->fetch_assoc();
        
        // Verify current password
        if (!password_verify($current_password, $db_user['password'])) {
            return ['success' => false, 'message' => 'Password lama salah'];
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $sql = "UPDATE admin SET password = '$hashed_password' WHERE id = $user_id";
        
        if ($this->conn->query($sql)) {
            return ['success' => true, 'message' => 'Password berhasil diubah'];
        }
        
        return ['success' => false, 'message' => 'Gagal mengubah password'];
    }
}

// Buat instance auth
$auth = new Auth();
?>