<?php
// create_user.php - Buat user baru untuk Roby
require_once '../includes/config.php';
require_once '../includes/database.php';

// Password yang diinginkan
$username = 'roby';
$password = 'Mr.Water17'; // Ganti dengan password yang Anda inginkan
$email = 'roby@binaprestasigemilang.com';
$nama_lengkap = 'Roby';
$level = 'Super Admin';

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Koneksi database
$conn = db();

// Cek apakah user sudah ada
$sql_check = "SELECT id FROM admin WHERE username = '$username' OR email = '$email'";
$result = $conn->query($sql_check);

if ($result->num_rows > 0) {
    echo "User sudah ada!<br>";
    
    // Update password yang ada
    $sql_update = "UPDATE admin SET 
                  password = '$hashed_password',
                  nama_lengkap = '$nama_lengkap',
                  level = '$level'
                  WHERE username = '$username'";
    
    if ($conn->query($sql_update)) {
        echo "Password user '$username' berhasil diupdate!<br>";
    } else {
        echo "Error update: " . $conn->error . "<br>";
    }
} else {
    // Insert user baru
    $sql_insert = "INSERT INTO admin (username, password, email, nama_lengkap, level) 
                   VALUES ('$username', '$hashed_password', '$email', '$nama_lengkap', '$level')";
    
    if ($conn->query($sql_insert)) {
        echo "User baru berhasil dibuat!<br>";
    } else {
        echo "Error insert: " . $conn->error . "<br>";
    }
}

// Tampilkan info login
echo "<hr>";
echo "<h3>Login Details:</h3>";
echo "<strong>URL Login:</strong> <a href='login.php'>http://localhost/bina_prestasi_gemilang/admin/login.php</a><br>";
echo "<strong>Username:</strong> $username<br>";
echo "<strong>Password:</strong> $password<br>";
echo "<strong>Email:</strong> $email<br>";
echo "<strong>Nama:</strong> $nama_lengkap<br>";
echo "<strong>Level:</strong> $level<br>";

echo "<hr>";
echo "<a href='login.php' class='btn btn-primary'>Login Sekarang</a>";

$conn->close();
?>