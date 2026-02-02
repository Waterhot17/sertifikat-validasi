<?php
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
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus me-1"></i> Tambah User
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Username</th>
                                <th>Nama Lengkap</th>
                                <th>Email</th>
                                <th>Level</th>
                                <th>Terakhir Login</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Query tabel admin (bukan users)
                            $sql = "SELECT id, username, nama_lengkap, email, level, last_login 
                                    FROM admin 
                                    ORDER BY created_at DESC";
                            $result = $conn->query($sql);
                            
                            if (!$result) {
                                echo '<tr><td colspan="7" class="text-center">Error query: ' . $conn->error . '</td></tr>';
                            } elseif ($result->num_rows == 0) {
                                echo '<tr><td colspan="7" class="text-center">Belum ada user admin</td></tr>';
                            } else {
                                $no = 1;
                                while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                    <?php if(isset($_SESSION['user_id']) && $row['id'] == $_SESSION['user_id']): ?>
                                    <span class="badge bg-info">Anda</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($row['nama_lengkap']); ?></td>
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
                                    <button class="btn btn-sm btn-warning edit-user" 
                                            data-id="<?php echo $row['id']; ?>"
                                            data-username="<?php echo htmlspecialchars($row['username']); ?>"
                                            data-nama="<?php echo htmlspecialchars($row['nama_lengkap']); ?>"
                                            data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                            data-level="<?php echo $row['level']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if(!isset($_SESSION['user_id']) || $row['id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-sm btn-danger delete-user" 
                                            data-id="<?php echo $row['id']; ?>"
                                            data-username="<?php echo htmlspecialchars($row['username']); ?>">
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
            <form id="formAddUser">
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
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" name="nama_lengkap" required>
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
            <form id="formEditUser">
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
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" name="nama_lengkap" id="editNamaLengkap" required>
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
$(document).ready(function() {
    // Tambah user
    $('#formAddUser').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'proses_user.php',
            method: 'POST',
            data: $(this).serialize() + '&action=add',
            success: function(response) {
                alert(response.message || 'User berhasil ditambahkan');
                location.reload();
            },
            error: function(xhr) {
                alert('Gagal menambahkan user: ' + (xhr.responseText || 'Unknown error'));
            }
        });
    });
    
    // Edit user
    $('.edit-user').click(function() {
        const id = $(this).data('id');
        const username = $(this).data('username');
        const nama = $(this).data('nama');
        const email = $(this).data('email');
        const level = $(this).data('level');
        
        $('#editUserId').val(id);
        $('#editUsername').val(username);
        $('#editNamaLengkap').val(nama);
        $('#editEmail').val(email);
        $('#editLevel').val(level);
        
        $('#editUserModal').modal('show');
    });
    
    // Update user
    $('#formEditUser').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: 'proses_user.php',
            method: 'POST',
            data: $(this).serialize() + '&action=edit',
            success: function(response) {
                alert(response.message || 'User berhasil diupdate');
                location.reload();
            },
            error: function(xhr) {
                alert('Gagal mengupdate user: ' + (xhr.responseText || 'Unknown error'));
            }
        });
    });
    
    // Delete user
    $('.delete-user').click(function() {
        const id = $(this).data('id');
        const username = $(this).data('username');
        
        if (confirm(`Hapus user "${username}"?`)) {
            $.ajax({
                url: 'proses_user.php',
                method: 'POST',
                data: { id: id, action: 'delete' },
                success: function(response) {
                    alert(response.message || 'User berhasil dihapus');
                    location.reload();
                },
                error: function(xhr) {
                    alert('Gagal menghapus user: ' + (xhr.responseText || 'Unknown error'));
                }
            });
        }
    });
});
</script>

<?php 
$conn->close();
require_once 'footer.php'; 
?>