<?php
// File: admin/batch_generate_qr.php - TEST QR GENERATION
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test QR Generation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding: 20px; }
        .qr-test { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .qr-image { max-width: 300px; border: 1px solid #ccc; }
    </style>
</head>
<body>
<div class="container">

<h1><i class="fas fa-qrcode"></i> Test QR Code Generation</h1>

<?php
// Include QR code library
$qrcode_file = dirname(__DIR__) . '/includes/qrcode.php';

if (!file_exists($qrcode_file)) {
    echo '<div class="alert alert-danger">QR Code library not found!</div>';
    exit;
}

require_once $qrcode_file;

if (!class_exists('QRCodeGenerator')) {
    echo '<div class="alert alert-danger">QRCodeGenerator class not found!</div>';
    exit;
}

$qr = new QRCodeGenerator();

echo '<div class="alert alert-success">QR Code Generator loaded successfully!</div>';

// Test 1: Generate QR for certificate
echo '<div class="qr-test">';
echo '<h3>Test 1: Generate QR for Certificate</h3>';

$test_id = 1;
$test_number = "BPG-2024-001";

$result = $qr->generateForCertificate($test_id, $test_number);

if ($result['success']) {
    echo '<div class="alert alert-success">';
    echo '<h4>✅ QR Generated Successfully!</h4>';
    echo '<p><strong>Certificate URL:</strong> <a href="' . $result['certificate_url'] . '" target="_blank">' . $result['certificate_url'] . '</a></p>';
    echo '<p><strong>QR File:</strong> ' . $result['qr_path'] . '</p>';
    echo '<p><strong>Has Logo:</strong> ' . ($result['has_logo'] ? 'Yes' : 'No') . '</p>';
    echo '</div>';
    
    // Show QR image
    if (file_exists($result['qr_path'])) {
        echo '<h5>QR Code Image:</h5>';
        echo '<img src="../' . $result['qr_url'] . '" class="qr-image img-thumbnail">';
        echo '<p class="text-muted">Scan this QR code to go to certificate page</p>';
        
        // Test link
        echo '<p><a href="' . $result['certificate_url'] . '" class="btn btn-primary" target="_blank">';
        echo '<i class="fas fa-external-link-alt"></i> Test Certificate Link';
        echo '</a></p>';
    } else {
        echo '<div class="alert alert-warning">QR image file not created!</div>';
    }
    
    // Show QR data
    echo '<h5>QR Data (JSON):</h5>';
    echo '<pre>' . htmlspecialchars($result['qr_data']) . '</pre>';
    
} else {
    echo '<div class="alert alert-danger">';
    echo '<h4>❌ QR Generation Failed!</h4>';
    echo '<p>Error: ' . ($result['error'] ?? 'Unknown error') . '</p>';
    echo '</div>';
}
echo '</div>';

// Test 2: Check statistics
echo '<div class="qr-test">';
echo '<h3>Test 2: System Statistics</h3>';

$stats = $qr->getQRStats();

echo '<table class="table table-bordered">';
echo '<tr><th>Total Certificates</th><td>' . $stats['total_certificates'] . '</td></tr>';
echo '<tr><th>With QR Code</th><td>' . $stats['with_qr'] . '</td></tr>';
echo '<tr><th>Without QR Code</th><td>' . $stats['without_qr'] . '</td></tr>';
echo '<tr><th>Logo Available</th><td>' . ($stats['logo_exists'] ? '✅ Yes' : '❌ No') . '</td></tr>';
echo '<tr><th>QR Files Count</th><td>' . ($stats['qr_files_count'] ?? 0) . '</td></tr>';
echo '</table>';
echo '</div>';

// Test 3: Generate multiple QRs
echo '<div class="qr-test">';
echo '<h3>Test 3: Generate Multiple QR Codes</h3>';

echo '<form method="POST">';
echo '<div class="mb-3">';
echo '<label>Number of test certificates:</label>';
echo '<select name="test_count" class="form-control" style="width: 200px;">';
echo '<option value="1">1 Certificate</option>';
echo '<option value="3" selected>3 Certificates</option>';
echo '<option value="5">5 Certificates</option>';
echo '</select>';
echo '</div>';
echo '<button type="submit" name="generate_test" class="btn btn-primary">';
echo '<i class="fas fa-play"></i> Generate Test QRs';
echo '</button>';
echo '</form>';

if (isset($_POST['generate_test'])) {
    $count = intval($_POST['test_count']);
    
    echo '<hr><h4>Generating ' . $count . ' test QRs:</h4>';
    
    for ($i = 1; $i <= $count; $i++) {
        $cert_id = $i;
        $cert_number = 'TEST-' . date('Y') . '-' . str_pad($i, 3, '0', STR_PAD_LEFT);
        
        echo '<div class="mb-3 p-2 border rounded">';
        echo '<strong>Certificate ' . $i . ':</strong> ' . $cert_number . '<br>';
        
        $result = $qr->generateForCertificate($cert_id, $cert_number);
        
        if ($result['success']) {
            echo '<span class="text-success">✅ Generated</span> ';
            
            if (file_exists($result['qr_path'])) {
                echo '<a href="../' . $result['qr_url'] . '" target="_blank">View QR</a> | ';
                echo '<a href="' . $result['certificate_url'] . '" target="_blank">View Certificate</a>';
            }
        } else {
            echo '<span class="text-danger">❌ Failed: ' . ($result['error'] ?? 'Unknown') . '</span>';
        }
        
        echo '</div>';
    }
}
echo '</div>';

// Information
echo '<div class="alert alert-info mt-4">';
echo '<h4><i class="fas fa-info-circle"></i> How QR Codes Work:</h4>';
echo '<ul>';
echo '<li>Each QR code contains a URL to the certificate page</li>';
echo '<li>Example: http://localhost/bina_prestasi_gemilang/certificate.php?id=1</li>';
echo '<li>When scanned, it will open the certificate page directly</li>';
echo '<li>Company logo is automatically added to the center of QR</li>';
echo '<li>QR images are saved in: uploads/qrcodes/ folder</li>';
echo '</ul>';
echo '</div>';
?>

</div>
</body>
</html>