<?php
// File: EDIT_SERTIFIKAT.PHP - Edit sertifikat yang sudah ada
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$auth->requireLogin();

// Get certificate ID
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: list_sertifikat.php');
    exit();
}

$db = Database::getInstance();
$conn = $db->getConnection();

// Get certificate data
$stmt = $conn->prepare("SELECT * FROM sertifikat WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$certificate = $result->fetch_assoc();

if (!$certificate) {
    header('Location: list_sertifikat.php');
    exit();
}

// Get jenis sertifikat untuk dropdown
$jenis_options = '';
$jenis_result = $conn->query("SELECT id, kode_jenis, nama_jenis FROM jenis_sertifikat ORDER BY nama_jenis");
while ($row = $jenis_result->fetch_assoc()) {
    $selected = $row['id'] == $certificate['jenis_id'] ? 'selected' : '';
    $jenis_options .= '<option value="' . $row['id'] . '" ' . $selected . '>' . 
                     htmlspecialchars($row['nama_jenis'] . ' (' . $row['kode_jenis'] . ')') . '</option>';
}

$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nomor_sertifikat = $_POST['nomor_sertifikat'] ?? '';
        $jenis_id = $_POST['jenis_id'] ?? '';
        $nama_peserta = $_POST['nama_peserta'] ?? '';
        $program = $_POST['program'] ?? '';
        $tanggal_pelatihan = $_POST['tanggal_pelatihan'] ?? '';
        $tanggal_terbit = $_POST['tanggal_terbit'] ?? '';
        $tanggal_expired = $_POST['tanggal_expired'] ?? '';
        
        // Validation
        if (empty($nomor_sertifikat)) throw new Exception('Nomor sertifikat harus diisi');
        if (empty($jenis_id)) throw new Exception('Jenis sertifikat harus dipilih');
        if (empty($nama_peserta)) throw new Exception('Nama peserta harus diisi');
        if (empty($program)) throw new Exception('Program pelatihan harus diisi');
        if (empty($tanggal_pelatihan)) throw new Exception('Tanggal pelatihan harus diisi');
        if (empty($tanggal_terbit)) throw new Exception('Tanggal terbit harus diisi');
        
        // Check if certificate number already exists (excluding current)
        $check_sql = "SELECT id FROM sertifikat WHERE nomor_sertifikat = ? AND id != ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("si", $nomor_sertifikat, $id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception('Nomor sertifikat sudah digunakan');
        }
        
        // Update certificate
        $sql = "UPDATE sertifikat SET 
                nomor_sertifikat = ?,
                jenis_id = ?,
                nama_peserta = ?,
                program = ?,
                tanggal_pelatihan = ?,
                tanggal_terbit = ?,
                tanggal_expired = ?
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "sisssssi",
            $nomor_sertifikat,
            $jenis_id,
            $nama_peserta,
            $program,
            $tanggal_pelatihan,
            $tanggal_terbit,
            $tanggal_expired,
            $id
        );
        
        if ($stmt->execute()) {
            $success = 'Sertifikat berhasil diupdate!';
            // Refresh certificate data
            $certificate = array_merge($certificate, [
                'nomor_sertifikat' => $nomor_sertifikat,
                'jenis_id' => $jenis_id,
                'nama_peserta' => $nama_peserta,
                'program' => $program,
                'tanggal_pelatihan' => $tanggal_pelatihan,
                'tanggal_terbit' => $tanggal_terbit,
                'tanggal_expired' => $tanggal_expired
            ]);
        } else {
            throw new Exception('Gagal update sertifikat: ' . $stmt->error);
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Sertifikat - <?php echo SITE_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .card-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 mb-0">
                            <i class="fas fa-edit text-warning me-2"></i>
                            Edit Sertifikat
                        </h1>
                        <p class="text-muted mb-0">ID: <?php echo $id; ?> | Dibuat: <?php echo date('d/m/Y', strtotime($certificate['created_at'])); ?></p>
                    </div>
                    <div>
                        <a href="list_sertifikat.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i> Kembali
                        </a>
                    </div>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-edit me-2"></i> Edit Data Sertifikat</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nomor Sertifikat</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="nomor_sertifikat" 
                                                   value="<?php echo htmlspecialchars($certificate['nomor_sertifikat']); ?>"
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Jenis Sertifikat</label>
                                            <select class="form-select" name="jenis_id" required>
                                                <option value="">Pilih Jenis</option>
                                                <?php echo $jenis_options; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Nama Peserta</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="nama_peserta" 
                                                   value="<?php echo htmlspecialchars($certificate['nama_peserta']); ?>"
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-12 mb-3">
                                            <label class="form-label">Program</label>
                                            <input type="text" 
                                                   class="form-control" 
                                                   name="program" 
                                                   value="<?php echo htmlspecialchars($certificate['program']); ?>"
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Tanggal Pelatihan</label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   name="tanggal_pelatihan" 
                                                   value="<?php echo $certificate['tanggal_pelatihan']; ?>"
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Tanggal Terbit</label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   name="tanggal_terbit" 
                                                   value="<?php echo $certificate['tanggal_terbit']; ?>"
                                                   required>
                                        </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label">Tanggal Expired</label>
                                            <input type="date" 
                                                   class="form-control" 
                                                   name="tanggal_expired" 
                                                   value="<?php echo $certificate['tanggal_expired']; ?>">
                                        </div>
                                    </div>
                                    
                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save me-2"></i> Update Sertifikat
                                        </button>
                                        <a href="list_sertifikat.php" class="btn btn-outline-secondary ms-2">
                                            <i class="fas fa-times me-2"></i> Batal
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-info-circle me-2"></i> Informasi
                                </h6>
                                <div class="mb-3">
                                    <small class="text-muted">Dibuat oleh:</small>
                                    <div><?php echo $certificate['created_by'] ? 'User ID: ' . $certificate['created_by'] : '-'; ?></div>
                                </div>
                                <div class="mb-3">
                                    <small class="text-muted">Terakhir diupdate:</small>
                                    <div><?php echo date('d/m/Y H:i', strtotime($certificate['updated_at'])); ?></div>
                                </div>
                                <?php if ($certificate['qr_code']): ?>
                                <div class="mb-3">
                                    <small class="text-muted">QR Code URL:</small>
                                    <div class="text-truncate">
                                        <a href="<?php echo $certificate['qr_code']; ?>" target="_blank">
                                            <?php echo $certificate['qr_code']; ?>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card mt-3">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <i class="fas fa-shield-alt me-2"></i> Verifikasi
                                </h6>
                                <p class="small text-muted">
                                    Sertifikat ini dapat diverifikasi di halaman publik dengan nomor:
                                </p>
                                <code class="d-block p-2 bg-light rounded">
                                    <?php echo $certificate['nomor_sertifikat']; ?>
                                </code>
                                <a href="../verify.php?nomor=<?php echo urlencode($certificate['nomor_sertifikat']); ?>" 
                                   class="btn btn-sm btn-outline-primary mt-2" target="_blank">
                                    <i class="fas fa-external-link-alt me-1"></i> Test Verifikasi
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>