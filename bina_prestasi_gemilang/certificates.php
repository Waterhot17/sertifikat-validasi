<?php
// File: certificate.php - TEST PAGE
?>
<!DOCTYPE html>
<html>
<head>
    <title>Certificate Page</title>
</head>
<body>
    <h1>Certificate Detail Page</h1>
    
    <?php
    $id = $_GET['id'] ?? '1';
    echo "<h2>Certificate ID: $id</h2>";
    echo "<p>This is a test certificate page.</p>";
    echo "<p>QR Code should direct to this page.</p>";
    ?>
    
    <hr>
    <a href="../admin/batch_generate_qr.php">Back to QR Generator</a>
</body>
</html>