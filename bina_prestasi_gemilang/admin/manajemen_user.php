\<?php
$page_title = "Manajemen User";
require_once '../includes/config.php';
require_once 'header.php';

// Koneksi database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>

<div class="page-header">
    <h1><i class="fas fa-users me-2"></i>Manajemen User</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
            <li class="breadcrumb-item active">Manajemen User</li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Pengguna Admin</h5>
                <button class="btn btn-primary" onclick="showAddModal()">
                    <i class="fas fa-user-plus me-1"></i> Tambah User
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="userTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Level</th>
                                <th>Terakhir Login</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT id, username, nama_lengkap, email, level, last_login, created_at 
                                    FROM admin ORDER BY created_at DESC";
                            $result = $conn->query($sql);
                            
                            if ($result === false) {
                                echo '<tr><td colspan="8" class="text-center text-danger">Error: ' . $conn->error . '</td></tr>';
                            } elseif ($result->num_rows == 0) {
                                echo '<tr><td colspan="8" class="text-center text-muted">Belum ada user admin</td></tr>';
                            } else {
                                $no = 1;
                                while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                    <?php if(isset($_SESSION['username']) && $row['username'] == $_SESSION['username']): ?>
                                    <span class="badge bg-info">Anda</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['nama_lengkap'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $row['level'] == 'Super Admin' ? 'danger' : 
                                             ($row['level'] == 'Admin' ? 'primary' : 'secondary'); 
                                    ?>">
                                        <?php echo $row['level']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $row['last_login'] ? date('d/m/Y H:i', strtotime($row['last_login'])) : 'Belum login'; ?>
                                </td>
                                <td>
                                    <?php echo date('d/m/Y', strtotime($row['created_at'])); ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" onclick="editUser(
                                        <?php echo $row['id']; ?>,
                                        '<?php echo addslashes($row['username']); ?>',
                                        '<?php echo addslashes($row['nama_lengkap']); ?>',
                                        '<?php echo addslashes($row['email']); ?>',
                                        '<?php echo addslashes($row['level']); ?>'
                                    )">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if(!isset($_SESSION['username']) || $row['username'] != $_SESSION['username']): ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteUser(
                                        <?php echo $row['id']; ?>,
                                        '<?php echo addslashes($row['username']); ?>'
                                    )">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah User -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formAddUser" onsubmit="addUser(event)">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah User Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username *</label>
                        <input type="text" class="form-control" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Level *</label>
                        <select class="form-select" name="level" required>
                            <option value="Super Admin">Super Admin</option>
                            <option value="Admin">Admin</option>
                            <option value="Operator">Operator</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit User -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="formEditUser" onsubmit="updateUser(event)">
                <input type="hidden" name="id" id="editUserId">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User Admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" class="form-control" name="username" id="editUsername" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password (kosongkan jika tidak diubah)</label>
                        <input type="password" class="form-control" name="password">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" class="form-control" name="nama_lengkap" id="editNamaLengkap">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" id="editEmail" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Level *</label>
                        <select class="form-select" name="level" id="editLevel" required>
                            <option value="Super Admin">Super Admin</option>
                            <option value="Admin">Admin</option>
                            <option value="Operator">Operator</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Fungsi untuk menampilkan modal tambah
function showAddModal() {
    const modal = new bootstrap.Modal(document.getElementById('addUserModal'));
    modal.show();
}

// Fungsi untuk edit user
function editUser(id, username, nama, email, level) {
    document.getElementById('editUserId').value = id;
    document.getElementById('editUsername').value = username;
    document.getElementById('editNamaLengkap').value = nama;
    document.getElementById('editEmail').value = email;
    document.getElementById('editLevel').value = level;
    
    const modal = new bootstrap.Modal(document.getElementById('editUserModal'));
    modal.show();
}

// Fungsi untuk hapus user
function deleteUser(id, username) {
    if (confirm(`Hapus user "${username}"?`)) {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        
        // Tampilkan loading
        const originalText = event.target.innerHTML;
        event.target.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        event.target.disabled = true;
        
        fetch('proses_user.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert('Error: ' + data.message);
                event.target.innerHTML = originalText;
                event.target.disabled = false;
            }
        })
        .catch(error => {
            alert('Terjadi kesalahan: ' + error);
            event.target.innerHTML = originalText;
            event.target.disabled = false;
        });
    }
}

// Fungsi untuk tambah user
function addUser(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'add');
    
    // Disable tombol
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    submitBtn.disabled = true;
    
    fetch('proses_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Tutup modal dan reload
            const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
            modal.hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Fungsi untuk update user
function updateUser(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'edit');
    
    // Disable tombol
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    submitBtn.disabled = true;
    
    fetch('proses_user.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            // Tutup modal dan reload
            const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
            modal.hide();
            location.reload();
        } else {
            alert('Error: ' + data.message);
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error);
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
}

// Inisialisasi Bootstrap modal jika diperlukan
document.addEventListener('DOMContentLoaded', function() {
    console.log('Manajemen User script loaded');
});
</script>

<?php 
$conn->close();
require_once 'footer.php'; 
?>