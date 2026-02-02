<?php
// ============================================
// FILE: LIST_SERTIFIKAT.PHP - DAFTAR SERTIFIKAT
// ============================================

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$auth->requireLogin();

// Get database connection
$db = Database::getInstance();
$conn = $db->getConnection();

// Get search parameters
$search = $_GET['search'] ?? '';
$jenis_id = $_GET['jenis_id'] ?? '';
$status = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build query
$where = [];
$params = [];
$types = '';

if ($search) {
    $where[] = "(s.nomor_sertifikat LIKE ? OR s.nama_peserta LIKE ? OR s.program LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
    $types .= 'sss';
}

if ($jenis_id) {
    $where[] = "s.jenis_id = ?";
    $params[] = $jenis_id;
    $types .= 'i';
}

if ($status) {
    $today = date('Y-m-d');
    if ($status == 'aktif') {
        $where[] = "(s.tanggal_expired >= ? OR s.tanggal_expired IS NULL)";
        $params[] = $today;
        $types .= 's';
    } elseif ($status == 'expired') {
        $where[] = "s.tanggal_expired < ?";
        $params[] = $today;
        $types .= 's';
    }
}

$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM sertifikat s $where_clause";
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_result = $count_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total = $total_row['total'];
$total_pages = ceil($total / $limit);

// Get certificates
$sql = "SELECT s.*, j.nama_jenis, j.kode_jenis 
        FROM sertifikat s 
        LEFT JOIN jenis_sertifikat j ON s.jenis_id = j.id 
        $where_clause 
        ORDER BY s.created_at DESC 
        LIMIT ? OFFSET ?";
        
$params[] = $limit;
$params[] = $offset;
$types .= 'ii';

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get jenis for filter
$jenis_result = $conn->query("SELECT id, kode_jenis, nama_jenis FROM jenis_sertifikat ORDER BY nama_jenis");
$jenis_list = [];
while ($row = $jenis_result->fetch_assoc()) {
    $jenis_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Sertifikat - <?php echo SITE_NAME; ?></title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    
    <style>
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px 30px;
        }
        
        .btn-action {
            padding: 6px 12px;
            border-radius: 8px;
            margin: 0 2px;
        }
        
        .btn-view { background: #dbeafe; color: #3b82f6; }
        .btn-edit { background: #fef3c7; color: #f59e0b; }
        .btn-delete { background: #fee2e2; color: #ef4444; }
        .btn-qr { background: #dcfce7; color: #10b981; }
        
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-active { background: #d1fae5; color: #065f46; }
        .badge-expired { background: #fee2e2; color: #991b1b; }
        .badge-none { background: #e5e7eb; color: #374151; }
        
        .search-box {
            background: #f8fafc;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .stats-card {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 15px;
        }
        
        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
            margin: 5px 0;
        }
        
        .stats-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'sidebar.php'; ?>
            
            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h1 class="h2 mb-0">
                            <i class="fas fa-list text-primary me-2"></i>
                            Daftar Sertifikat
                        </h1>
                        <p class="text-muted mb-0">Total: <?php echo $total; ?> sertifikat</p>
                    </div>
                    <div>
                        <a href="add_sertifikat.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Tambah Baru
                        </a>
                        <a href="import.php" class="btn btn-outline-secondary ms-2">
                            <i class="fas fa-file-import me-2"></i> Import
                        </a>
                        <a href="export.php" class="btn btn-outline-success ms-2">
                            <i class="fas fa-file-export me-2"></i> Export
                        </a>
                    </div>
                </div>
                
                <!-- Search & Filter -->
                <div class="search-box">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Cari</label>
                            <div class="input-group">
                                <input type="text" 
                                       class="form-control" 
                                       name="search" 
                                       placeholder="Cari nomor/nama/program..."
                                       value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-secondary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Jenis Sertifikat</label>
                            <select class="form-select" name="jenis_id">
                                <option value="">Semua Jenis</option>
                                <?php foreach ($jenis_list as $jenis): ?>
                                    <option value="<?php echo $jenis['id']; ?>" 
                                        <?php echo $jenis_id == $jenis['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($jenis['nama_jenis'] . ' (' . $jenis['kode_jenis'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">Semua Status</option>
                                <option value="aktif" <?php echo $status == 'aktif' ? 'selected' : ''; ?>>Aktif</option>
                                <option value="expired" <?php echo $status == 'expired' ? 'selected' : ''; ?>>Kadaluarsa</option>
                            </select>
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-2"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <?php
                    $today = date('Y-m-d');
                    $stats = [
                        'total' => $conn->query("SELECT COUNT(*) as total FROM sertifikat")->fetch_assoc()['total'],
                        'aktif' => $conn->query("SELECT COUNT(*) as aktif FROM sertifikat WHERE tanggal_expired >= '$today' OR tanggal_expired IS NULL")->fetch_assoc()['aktif'],
                        'expired' => $conn->query("SELECT COUNT(*) as expired FROM sertifikat WHERE tanggal_expired < '$today'")->fetch_assoc()['expired'],
                        'today' => $conn->query("SELECT COUNT(*) as today FROM sertifikat WHERE DATE(created_at) = '$today'")->fetch_assoc()['today']
                    ];
                    ?>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number text-primary"><?php echo $stats['total']; ?></div>
                            <div class="stats-label">Total Sertifikat</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number text-success"><?php echo $stats['aktif']; ?></div>
                            <div class="stats-label">Sertifikat Aktif</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number text-danger"><?php echo $stats['expired']; ?></div>
                            <div class="stats-label">Kadaluarsa</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <div class="stats-number text-warning"><?php echo $stats['today']; ?></div>
                            <div class="stats-label">Ditambahkan Hari Ini</div>
                        </div>
                    </div>
                </div>
                
                <!-- Certificates Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-table me-2"></i> Data Sertifikat</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Nomor Sertifikat</th>
                                            <th>Nama Peserta</th>
                                            <th>Jenis</th>
                                            <th>Program</th>
                                            <th>Tanggal Terbit</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $counter = $offset + 1;
                                        $today = date('Y-m-d');
                                        while ($row = $result->fetch_assoc()): 
                                            // Determine status
                                            if ($row['tanggal_expired'] && $row['tanggal_expired'] < $today) {
                                                $status_class = 'badge-expired';
                                                $status_text = 'Kadaluarsa';
                                            } else {
                                                $status_class = 'badge-active';
                                                $status_text = 'Aktif';
                                            }
                                        ?>
                                            <tr>
                                                <td><?php echo $counter++; ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($row['nomor_sertifikat']); ?></strong>
                                                    <?php if ($row['qr_code']): ?>
                                                        <br><small class="text-muted"><i class="fas fa-qrcode"></i> QR Code tersedia</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['nama_peserta']); ?></td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?php echo htmlspecialchars($row['kode_jenis'] ?? '-'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($row['program']); ?></td>
                                                <td>
                                                    <?php echo date('d/m/Y', strtotime($row['tanggal_terbit'])); ?>
                                                    <?php if ($row['tanggal_expired']): ?>
                                                        <br><small class="text-muted">Exp: <?php echo date('d/m/Y', strtotime($row['tanggal_expired'])); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge-status <?php echo $status_class; ?>">
                                                        <?php echo $status_text; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="view_sertifikat.php?id=<?php echo $row['id']; ?>" 
                                                           class="btn btn-action btn-view" title="Lihat">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit_sertifikat.php?id=<?php echo $row['id']; ?>" 
                                                           class="btn btn-action btn-edit" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($row['qr_code']): ?>
                                                        <a href="generate_qr.php?id=<?php echo $row['id']; ?>" 
                                                           class="btn btn-action btn-qr" title="QR Code">
                                                            <i class="fas fa-qrcode"></i>
                                                        </a>
                                                        <?php endif; ?>
                                                        <a href="delete_sertifikat.php?id=<?php echo $row['id']; ?>" 
                                                           class="btn btn-action btn-delete" 
                                                           title="Hapus"
                                                           onclick="return confirm('Hapus sertifikat ini?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-center mt-4">
                                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5>Tidak ada sertifikat ditemukan</h5>
                                <p class="text-muted">Coba ubah filter pencarian atau tambahkan sertifikat baru</p>
                                <a href="add_sertifikat.php" class="btn btn-primary mt-3">
                                    <i class="fas fa-plus me-2"></i> Tambah Sertifikat
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    
    <script>
        // Initialize DataTables
        $(document).ready(function() {
            $('table').DataTable({
                pageLength: 20,
                language: {
                    search: "Cari:",
                    lengthMenu: "Tampilkan _MENU_ data",
                    info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ sertifikat",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Berikutnya",
                        previous: "Sebelumnya"
                    }
                }
            });
        });
        
        // Quick search on Enter key
        $('input[name="search"]').keypress(function(e) {
            if (e.which == 13) {
                $(this).closest('form').submit();
            }
        });
        
        // Confirm delete
        $('.btn-delete').click(function() {
            return confirm('Apakah Anda yakin ingin menghapus sertifikat ini?');
        });
    </script>
</body>
</html>