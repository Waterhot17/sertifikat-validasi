<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$message = '';
$error = '';
$show_form = false;

// Ambil token dari URL
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

// Proses reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = trim($_POST['token']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validasi
    if (empty($token)) {
        $error = 'Token tidak valid';
    } elseif (empty($password)) {
        $error = 'Password baru harus diisi';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } elseif ($password !== $confirm_password) {
        $error = 'Konfirmasi password tidak sama';
    } else {
        // Reset password
        $result = $auth->resetPassword($token, $password);
        
        if ($result['success']) {
            $message = $result['message'];
            $show_form = false;
            
            // Hapus token dari session
            unset($_SESSION['reset_token']);
            unset($_SESSION['reset_user_id']);
        } else {
            $error = $result['message'];
            $show_form = true;
        }
    }
} elseif (!empty($token)) {
    // Cek validitas token
    require_once '../includes/auth.php';
    $conn = db();
    
    $token_hash = hash('sha256', $token);
    $now = date('Y-m-d H:i:s');
    
    $sql = "SELECT id FROM admin WHERE reset_token = ? AND token_expiry > ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $token_hash, $now);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $show_form = true;
    } else {
        $error = 'Token tidak valid atau sudah kadaluarsa';
        $show_form = false;
    }
} else {
    $error = 'Token tidak ditemukan';
    $show_form = false;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .reset-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .reset-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }
        
        .reset-header h1 {
            font-size: 1.3rem;
            margin: 0;
            font-weight: 600;
        }
        
        .reset-body {
            padding: 30px;
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            width: 100%;
        }
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        
        .password-requirements ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        
        .password-requirements li {
            margin-bottom: 5px;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 5px;
            background: #e9ecef;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s;
        }
        
        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #28a745; }
    </style>
</head>
<body>
    <div class="reset-card">
        <div class="reset-header">
            <h1><i class="fas fa-key me-2"></i> Reset Password</h1>
            <p>Buat password baru Anda</p>
        </div>
        
        <div class="reset-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                    <div class="mt-3">
                        <a href="login.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-1"></i> Login Sekarang
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($show_form): ?>
                <div class="password-requirements">
                    <strong>Password harus mengandung:</strong>
                    <ul>
                        <li>Minimal 6 karakter</li>
                        <li>Kombinasi huruf dan angka</li>
                        <li>Tidak boleh sama dengan username</li>
                    </ul>
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock me-1"></i> Password Baru
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Minimal 6 karakter"
                                   required
                                   onkeyup="checkPasswordStrength(this.value)">
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <small class="text-muted" id="passwordStrengthText"></small>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">
                            <i class="fas fa-lock me-1"></i> Konfirmasi Password
                        </label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" 
                                   class="form-control" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   placeholder="Ketik ulang password"
                                   required>
                            <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <small class="text-muted" id="passwordMatchText"></small>
                    </div>
                    
                    <button type="submit" class="btn btn-reset mb-3" id="submitBtn" disabled>
                        <i class="fas fa-save me-2"></i> Reset Password
                    </button>
                </form>
                
                <div class="text-center mt-3">
                    <a href="login.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> Kembali ke Login
                    </a>
                </div>
            <?php elseif (empty($message) && empty($error)): ?>
                <div class="text-center">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                    </div>
                    <p>Token tidak valid atau sudah kadaluarsa.</p>
                    <a href="forgot_password.php" class="btn btn-primary">
                        <i class="fas fa-key me-1"></i> Minta Link Reset Baru
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Check password strength
        function checkPasswordStrength(password) {
            const bar = document.getElementById('passwordStrengthBar');
            const text = document.getElementById('passwordStrengthText');
            const submitBtn = document.getElementById('submitBtn');
            const confirmInput = document.getElementById('confirm_password');
            
            let strength = 0;
            let message = '';
            let barClass = '';
            
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update strength bar
            let width = (strength / 5) * 100;
            bar.style.width = width + '%';
            
            // Update message and color
            if (password.length === 0) {
                message = '';
                barClass = '';
                bar.style.display = 'none';
            } else if (password.length < 6) {
                message = 'Password terlalu pendek (minimal 6 karakter)';
                barClass = 'strength-weak';
                bar.style.display = 'block';
            } else if (strength < 3) {
                message = 'Password lemah';
                barClass = 'strength-weak';
                bar.style.display = 'block';
            } else if (strength < 4) {
                message = 'Password cukup';
                barClass = 'strength-medium';
                bar.style.display = 'block';
            } else {
                message = 'Password kuat';
                barClass = 'strength-strong';
                bar.style.display = 'block';
            }
            
            bar.className = 'password-strength-bar ' + barClass;
            text.textContent = message;
            
            // Check password match
            checkPasswordMatch(password, confirmInput.value);
        }
        
        // Check password match
        function checkPasswordMatch(password, confirmPassword) {
            const matchText = document.getElementById('passwordMatchText');
            const submitBtn = document.getElementById('submitBtn');
            
            if (confirmPassword.length === 0) {
                matchText.textContent = '';
                matchText.className = 'text-muted';
                submitBtn.disabled = true;
            } else if (password === confirmPassword) {
                matchText.textContent = 'Password cocok ✓';
                matchText.className = 'text-success';
                submitBtn.disabled = false;
            } else {
                matchText.textContent = 'Password tidak cocok ✗';
                matchText.className = 'text-danger';
                submitBtn.disabled = true;
            }
        }
        
        // Check on confirm password change
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            checkPasswordMatch(password, this.value);
        });
        
        // Auto focus on password field
        <?php if ($show_form): ?>
        document.getElementById('password').focus();
        <?php endif; ?>
        
        // Handle form submission
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Memproses...';
        });
    </script>
</body>
</html>