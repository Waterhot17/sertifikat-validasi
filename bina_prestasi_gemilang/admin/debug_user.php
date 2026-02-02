<?php
// File: admin/debug_user.php
require_once '../includes/config.php';

echo "<h2>Debug User CRUD</h2>";
echo "<h4>Database: " . DB_NAME . "</h4>";
echo "<hr>";

// Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("❌ Koneksi gagal: " . $conn->connect_error);
}

echo "✅ Database connected<br>";

// 1. Cek tabel admin
$result = $conn->query("SHOW TABLES LIKE 'admin'");
if ($result->num_rows > 0) {
    echo "✅ Tabel admin ditemukan<br>";
} else {
    echo "❌ Tabel admin TIDAK ditemukan<br>";
    exit;
}

// 2. Tampilkan data admin
echo "<h3>Data Admin:</h3>";
$data = $conn->query("SELECT * FROM admin");
if ($data->num_rows > 0) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Level</th><th>Created</th></tr>";
    while($row = $data->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . $row['level'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Tidak ada data admin<br>";
}

// 3. Test INSERT
echo "<h3>Test INSERT:</h3>";
$test_user = "debug_" . time();
$test_email = $test_user . "@test.com";
$password_hash = password_hash('debug123', PASSWORD_DEFAULT);

$sql = "INSERT INTO admin (username, password, email, nama_lengkap, level) 
        VALUES ('$test_user', '$password_hash', '$test_email', 'Debug User', 'Operator')";

if ($conn->query($sql)) {
    echo "✅ Insert berhasil. ID: " . $conn->insert_id . "<br>";
    $last_id = $conn->insert_id;
    
    // 4. Test UPDATE
    echo "<h3>Test UPDATE:</h3>";
    $update_sql = "UPDATE admin SET nama_lengkap = 'Updated Name' WHERE id = $last_id";
    if ($conn->query($update_sql)) {
        echo "✅ Update berhasil<br>";
    } else {
        echo "❌ Update gagal: " . $conn->error . "<br>";
    }
    
    // 5. Test DELETE
    echo "<h3>Test DELETE:</h3>";
    $delete_sql = "DELETE FROM admin WHERE id = $last_id";
    if ($conn->query($delete_sql)) {
        echo "✅ Delete berhasil<br>";
    } else {
        echo "❌ Delete gagal: " . $conn->error . "<br>";
    }
    
} else {
    echo "❌ Insert gagal: " . $conn->error . "<br>";
}

// 6. Cek apakah ada session
echo "<h3>Session:</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
echo "Session status: " . session_status() . "<br>";
if (isset($_SESSION)) {
    echo "Session data: <pre>";
    print_r($_SESSION);
    echo "</pre>";
} else {
    echo "Session kosong<br>";
}

$conn->close();

// 7. Form test AJAX
echo "<h3>Test AJAX Form:</h3>";
?>
<form id="testForm">
    <input type="hidden" name="action" value="test">
    <button type="button" onclick="testAjax()">Test AJAX ke proses_user.php</button>
</form>

<script>
function testAjax() {
    fetch('proses_user.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=test&data=hello'
    })
    .then(response => response.text())
    .then(data => {
        alert('Response: ' + data);
        console.log('Response:', data);
    })
    .catch(error => {
        alert('Error: ' + error);
        console.error('Error:', error);
    });
}
</script>