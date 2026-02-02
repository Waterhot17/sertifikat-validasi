<?php
// debug_qr.php - Debug QR Code System

// Enable all errors
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h2>Debug QR Code System</h2>";

// 1. Check GD Library
echo "<h3>1. Checking GD Library:</h3>";
if (extension_loaded('gd')) {
    echo "✅ GD Library is enabled<br>";
    
    // Check GD functions
    $gd_functions = ['imagecreatetruecolor', 'imagecolorallocate', 'imagepng'];
    foreach ($gd_functions as $func) {
        if (function_exists($func)) {
            echo "✅ $func() is available<br>";
        } else {
            echo "❌ $func() is NOT available<br>";
        }
    }
} else {
    echo "❌ GD Library is NOT enabled. Install with: <code>sudo apt-get install php-gd</code><br>";
}

// 2. Check file structure
echo "<h3>2. File Structure:</h3>";
$base_path = __DIR__;
echo "Base Path: $base_path<br>";

$files_to_check = [
    '/includes/qrcode.php',
    '/includes/config.php',
    '/includes/auth.php',
    '/admin/batch_generate_qr.php',
    '/uploads/qrcodes/'
];

foreach ($files_to_check as $file) {
    $full_path = $base_path . $file;
    if (is_dir($full_path)) {
        echo "📁 " . $file . " (Directory) - " . (is_writable($full_path) ? "Writable" : "Not Writable") . "<br>";
    } else {
        echo (file_exists($full_path) ? "✅ " : "❌ ") . $file . "<br>";
    }
}

// 3. Try to load QR Code class
echo "<h3>3. Testing QR Code Class:</h3>";
$qrcode_file = $base_path . '/includes/qrcode.php';

if (file_exists($qrcode_file)) {
    // First, check file content
    $content = file_get_contents($qrcode_file);
    echo "File size: " . strlen($content) . " bytes<br>";
    
    // Check for class definition
    if (strpos($content, 'class QRCodeGenerator') !== false) {
        echo "✅ Class QRCodeGenerator found in file<br>";
        
        // Try to include
        try {
            require_once $qrcode_file;
            echo "✅ File included successfully<br>";
            
            // Check if class exists
            if (class_exists('QRCodeGenerator')) {
                echo "✅ Class QRCodeGenerator is defined<br>";
                
                // Try to create instance
                try {
                    $qr = new QRCodeGenerator();
                    echo "✅ Instance created successfully<br>";
                    
                    // Try to call method
                    $stats = $qr->getQRStats();
                    echo "✅ getQRStats() returned: <pre>" . print_r($stats, true) . "</pre>";
                    
                } catch (Exception $e) {
                    echo "❌ Error creating instance: " . $e->getMessage() . "<br>";
                }
            } else {
                echo "❌ Class QRCodeGenerator NOT defined after inclusion<br>";
            }
        } catch (Exception $e) {
            echo "❌ Error including file: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "❌ Class definition not found in file<br>";
    }
} else {
    echo "❌ File not found: $qrcode_file<br>";
}

// 4. Check database connection
echo "<h3>4. Database Connection:</h3>";
try {
    $conn = new mysqli("localhost", "root", "", "bina_prestasi_gemilang");
    if ($conn->connect_error) {
        echo "❌ Database connection failed: " . $conn->connect_error . "<br>";
    } else {
        echo "✅ Database connected successfully<br>";
        
        // Check sertifikat table
        $result = $conn->query("SHOW TABLES LIKE 'sertifikat'");
        if ($result->num_rows > 0) {
            echo "✅ Table 'sertifikat' exists<br>";
            
            // Check QR columns
            $result = $conn->query("SHOW COLUMNS FROM sertifikat");
            $columns = [];
            while ($row = $result->fetch_assoc()) {
                $columns[] = $row['Field'];
            }
            
            echo "Columns in sertifikat: " . implode(', ', $columns) . "<br>";
            
            // Check for QR columns
            $has_qr_data = in_array('qr_code_data', $columns);
            $has_qr_path = in_array('qr_code_path', $columns);
            
            echo "Has qr_code_data: " . ($has_qr_data ? '✅' : '❌') . "<br>";
            echo "Has qr_code_path: " . ($has_qr_path ? '✅' : '❌') . "<br>";
            
            if (!$has_qr_data || !$has_qr_path) {
                echo "<h4>Adding missing columns...</h4>";
                if (!$has_qr_data) {
                    $conn->query("ALTER TABLE sertifikat ADD COLUMN qr_code_data TEXT DEFAULT NULL");
                    echo "Added qr_code_data column<br>";
                }
                if (!$has_qr_path) {
                    $conn->query("ALTER TABLE sertifikat ADD COLUMN qr_code_path VARCHAR(255) DEFAULT NULL");
                    echo "Added qr_code_path column<br>";
                }
            }
            
            // Count certificates
            $result = $conn->query("SELECT COUNT(*) as total FROM sertifikat");
            $total = $result->fetch_assoc()['total'];
            echo "Total certificates: $total<br>";
            
        } else {
            echo "❌ Table 'sertifikat' does not exist<br>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

// 5. Check permissions
echo "<h3>5. Folder Permissions:</h3>";
$folders = [
    $base_path . '/uploads',
    $base_path . '/uploads/qrcodes',
    $base_path . '/includes'
];

foreach ($folders as $folder) {
    if (file_exists($folder)) {
        $perms = substr(sprintf('%o', fileperms($folder)), -4);
        $writable = is_writable($folder);
        echo "📁 $folder - Perms: $perms - " . ($writable ? "✅ Writable" : "❌ Not Writable") . "<br>";
    } else {
        echo "📁 $folder - ❌ Does not exist<br>";
    }
}

echo "<hr><h3>Next Steps:</h3>";
echo "1. Akses: <a href='admin/batch_generate_qr.php' target='_blank'>batch_generate_qr.php</a><br>";
echo "2. Jika masih white screen, cek error log Laragon:<br>";
echo "   - C:\\laragon\\logs\\php_error.log<br>";
echo "   - C:\\laragon\\logs\\apache_error.log<br>";
?>