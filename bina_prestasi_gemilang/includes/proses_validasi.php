<?php
session_start();
require_once 'config.php';
require_once 'database.php';

// Gunakan instance dari Database class
$db = Database::getInstance();
$conn = $db->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'validate') {
        $kode = $conn->real_escape_string($_POST['kode']);
        
        // Query sertifikat - PASTIKAN TABEL INI ADA di database Anda
        $sql = "SELECT s.*, j.nama_jenis,
                       DATE_FORMAT(s.tanggal_terbit, '%d %M %Y') as tgl_terbit,
                       DATE_FORMAT(s.tanggal_expired, '%d %M %Y') as tgl_expired,
                       DATEDIFF(s.tanggal_expired, CURDATE()) as sisa_hari
                FROM sertifikat s
                LEFT JOIN jenis_sertifikat j ON s.jenis_id = j.id
                WHERE s.kode_unik = '$kode' OR s.nomor_sertifikat = '$kode'";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $cert = $result->fetch_assoc();
            
            $isExpired = $cert['sisa_hari'] < 0;
            $isExpiring = $cert['sisa_hari'] <= 30 && $cert['sisa_hari'] >= 0;
            $statusClass = $isExpired ? 'danger' : ($isExpiring ? 'warning' : 'success');
            $statusText = $isExpired ? 'KADALUARSA' : ($isExpiring ? 'SEGERA BERAKHIR' : 'VALID');
            $icon = $isExpired ? 'times' : 'check';
            
            echo '
            <div class="alert alert-'.$statusClass.'">
                <h4><i class="fas fa-'.$icon.'-circle me-2"></i>
                    SERTIFIKAT '.$statusText.'
                </h4>
                '.($isExpired ? 'Sertifikat telah kadaluarsa' : 
                   ($isExpiring ? 'Sertifikat akan kadaluarsa dalam '.$cert['sisa_hari'].' hari' : 
                   'Sertifikat valid dan aktif')).'
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <h5>Detail Sertifikat</h5>
                    <table class="table table-bordered">
                        <tr><th width="40%">Nomor Sertifikat</th><td>'.htmlspecialchars($cert['nomor_sertifikat']).'</td></tr>
                        <tr><th>Nama Peserta</th><td>'.htmlspecialchars($cert['nama_peserta']).'</td></tr>
                        <tr><th>Jenis Sertifikat</th><td>'.htmlspecialchars($cert['nama_jenis'] ?? '-').'</td></tr>
                        <tr><th>Tanggal Terbit</th><td>'.htmlspecialchars($cert['tgl_terbit']).'</td></tr>
                        <tr><th>Tanggal Berakhir</th><td>'.htmlspecialchars($cert['tgl_expired']).'</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Informasi Tambahan</h5>
                    <table class="table table-bordered">
                        <tr><th width="40%">Institusi</th><td>'.htmlspecialchars($cert['institusi']).'</td></tr>
                        <tr><th>Program</th><td>'.htmlspecialchars($cert['program']).'</td></tr>
                        <tr><th>Kode Unik</th><td><code>'.htmlspecialchars($cert['kode_unik']).'</code></td></tr>
                        <tr><th>Status</th><td><span class="badge bg-'.(($cert['status'] ?? '') == 'active' ? 'success' : 'danger').'">'.strtoupper($cert['status'] ?? 'unknown').'</span></td></tr>
                    </table>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <button class="btn btn-primary" onclick="window.viewDetail('.$cert['id'].')">
                    <i class="fas fa-eye me-1"></i> Lihat Detail Lengkap
                </button>
                <button class="btn btn-success" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Cetak Hasil
                </button>
            </div>';
        } else {
            echo '
            <div class="alert alert-danger">
                <h4><i class="fas fa-times-circle me-2"></i>SERTIFIKAT TIDAK DITEMUKAN</h4>
                <p>Kode sertifikat <strong>"'.htmlspecialchars($kode).'"</strong> tidak terdaftar dalam sistem.</p>
                <p class="mb-0">Mungkin sertifikat tidak valid atau sudah dihapus.</p>
            </div>';
            
            // Debug: tampilkan error jika ada
            if (!$result) {
                echo '<div class="alert alert-warning mt-2"><small>Error: '.$conn->error.'</small></div>';
            }
        }
    }
    
    if ($action == 'detail') {
        $id = intval($_POST['id']);
        
        $sql = "SELECT s.*, j.nama_jenis, j.deskripsi as deskripsi_jenis,
                       DATE_FORMAT(s.tanggal_terbit, '%d %M %Y') as tgl_terbit,
                       DATE_FORMAT(s.tanggal_expired, '%d %M %Y') as tgl_expired
                FROM sertifikat s
                LEFT JOIN jenis_sertifikat j ON s.jenis_id = j.id
                WHERE s.id = $id";
        
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $cert = $result->fetch_assoc();
            
            echo '
            <div class="row">
                <div class="col-md-6">
                    <h6>Informasi Peserta</h6>
                    <p><strong>Nama:</strong> '.htmlspecialchars($cert['nama_peserta']).'</p>
                    <p><strong>Email:</strong> '.htmlspecialchars($cert['email']).'</p>
                    <p><strong>No. HP:</strong> '.htmlspecialchars($cert['no_hp']).'</p>
                </div>
                <div class="col-md-6">
                    <h6>Informasi Sertifikat</h6>
                    <p><strong>Jenis:</strong> '.htmlspecialchars($cert['nama_jenis']).'</p>
                    <p><strong>Deskripsi:</strong> '.htmlspecialchars($cert['deskripsi_jenis']).'</p>
                    <p><strong>Kode Unik:</strong> <code>'.htmlspecialchars($cert['kode_unik']).'</code></p>
                </div>
            </div>
            
            <div class="mt-3">
                <h6>QR Code</h6>
                <div class="text-center">
                    <div id="qrcode'.$cert['id'].'"></div>
                    <small class="text-muted">Scan QR code untuk validasi</small>
                </div>
            </div>';
        } else {
            echo '<div class="alert alert-danger">Data tidak ditemukan</div>';
        }
    }
}
?>