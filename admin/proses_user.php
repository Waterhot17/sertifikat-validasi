<?php
// ============================================
// FILE: PROSES_USER.PHP - DENGAN DEBUG LOG
// ============================================

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Log awal
$log = [];
$log[] = "=== START PROCESS ===";
$log[] = "Time: " . date('Y-m-d H:i:s');
$log[] = "POST data: " . json_encode($_POST);
$log[] = "SESSION data: " . json_encode($_SESSION);

// Include config
require_once '../includes/config.php';
$log[] = "Config loaded: " . DB_NAME;

// Set JSON header
header('Content-Type: application/json');

// Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    $log[] = "❌ Database connection failed: " . $conn->connect_error;
    echo json_encode([
        'success' => false, 
        'message' => 'Koneksi database gagal',
        'log' => $log
    ]);
    exit;
}
$log[] = "✅ Database connected";

// Set charset
$conn->set_charset("utf8mb4");

// Get action
$action = $_POST['action'] ?? '';
$log[] = "Action: " . $action;

$response = ['success' => false, 'message' => ''];

try {
    switch ($action) {
        case 'add':
            // ========== TAMBAH USER BARU ==========
            $log[] = "Processing ADD action";
            
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $level = $_POST['level'] ?? 'Admin';
            
            $log[] = "Input - Username: $username, Email: $email, Level: $level";
            
            // Validasi input
            if (empty($username) || empty($password) || empty($email)) {
                $response['message'] = 'Username, password, dan email harus diisi';
                $log[] = "❌ Validation failed";
                break;
            }
            
            // Cek apakah username sudah ada
            $check_sql = "SELECT id FROM admin WHERE username = ? OR email = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                $response['message'] = 'Username atau email sudah digunakan';
                $log[] = "❌ Username/email already exists";
                $stmt->close();
                break;
            }
            $stmt->close();
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user baru
            $insert_sql = "INSERT INTO admin (username, password, nama_lengkap, email, level) 
                           VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("sssss", $username, $hashed_password, $nama_lengkap, $email, $level);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'User berhasil ditambahkan';
                $log[] = "✅ User added successfully. ID: " . $stmt->insert_id;
            } else {
                $response['message'] = 'Gagal menambahkan user: ' . $stmt->error;
                $log[] = "❌ Insert failed: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'edit':
            // ========== EDIT USER ==========
            $log[] = "Processing EDIT action";
            
            $id = intval($_POST['id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $new_password = $_POST['password'] ?? '';
            $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $level = $_POST['level'] ?? 'Admin';
            
            $log[] = "Edit ID: $id, Username: $username, Email: $email";
            
            // Validasi
            if ($id < 1) {
                $response['message'] = 'ID user tidak valid';
                $log[] = "❌ Invalid ID";
                break;
            }
            
            // Cek apakah user ada
            $check_sql = "SELECT id FROM admin WHERE id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                $response['message'] = 'User tidak ditemukan';
                $log[] = "❌ User not found";
                $stmt->close();
                break;
            }
            $stmt->close();
            
            // Update user
            if (!empty($new_password)) {
                // Update dengan password baru
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE admin SET 
                               nama_lengkap = ?, 
                               email = ?, 
                               level = ?, 
                               password = ? 
                               WHERE id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("ssssi", $nama_lengkap, $email, $level, $hashed_password, $id);
                $log[] = "Updating with new password";
            } else {
                // Update tanpa password
                $update_sql = "UPDATE admin SET 
                               nama_lengkap = ?, 
                               email = ?, 
                               level = ? 
                               WHERE id = ?";
                $stmt = $conn->prepare($update_sql);
                $stmt->bind_param("sssi", $nama_lengkap, $email, $level, $id);
                $log[] = "Updating without password change";
            }
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'User berhasil diupdate';
                $log[] = "✅ Update successful";
            } else {
                $response['message'] = 'Gagal mengupdate user: ' . $stmt->error;
                $log[] = "❌ Update failed: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'delete':
            // ========== HAPUS USER ==========
            $log[] = "Processing DELETE action";
            
            $id = intval($_POST['id'] ?? 0);
            $log[] = "Delete ID: $id";
            
            // Validasi
            if ($id < 1) {
                $response['message'] = 'ID user tidak valid';
                break;
            }
            
            // Cek apakah user ada
            $check_sql = "SELECT username FROM admin WHERE id = ?";
            $stmt = $conn->prepare($check_sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $check_result = $stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                $response['message'] = 'User tidak ditemukan';
                $stmt->close();
                break;
            }
            $stmt->close();
            
            // Hapus user
            $delete_sql = "DELETE FROM admin WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'User berhasil dihapus';
                $log[] = "✅ Delete successful";
            } else {
                $response['message'] = 'Gagal menghapus user: ' . $stmt->error;
                $log[] = "❌ Delete failed: " . $stmt->error;
            }
            $stmt->close();
            break;
            
        case 'test':
            // ========== TEST ACTION ==========
            $response['success'] = true;
            $response['message'] = 'Test berhasil!';
            $response['data'] = $_POST;
            $log[] = "Test action executed";
            break;
            
        default:
            $response['message'] = 'Action tidak valid: ' . $action;
            $log[] = "Invalid action: " . $action;
            break;
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    $log[] = "Exception: " . $e->getMessage();
}

// Close connection
$conn->close();

// Add log to response
$response['log'] = $log;
$log[] = "=== END PROCESS ===";

// Write log to file
$log_file = 'user_crud_log.txt';
file_put_contents($log_file, implode("\n", $log) . "\n\n", FILE_APPEND);

// Output JSON response
echo json_encode($response);
exit;
?>