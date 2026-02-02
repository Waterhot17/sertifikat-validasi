<?php
// error.php - Custom Error Page
$error_codes = [
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Page Not Found',
    500 => 'Internal Server Error',
    503 => 'Maintenance Mode'
];

$code = isset($_GET['code']) ? (int)$_GET['code'] : 404;
$message = $error_codes[$code] ?? 'Unknown Error';

http_response_code($code);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error <?php echo $code; ?> - <?php echo SITE_NAME ?? 'BPG System'; ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .error-container {
            text-align: center;
            padding: 40px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            width: 90%;
        }
        .error-code {
            font-size: 120px;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 20px;
            color: #fff;
            text-shadow: 3px 3px 0 rgba(0,0,0,0.2);
        }
        .error-message {
            font-size: 24px;
            margin-bottom: 30px;
            color: #f8f9fa;
        }
        .error-desc {
            font-size: 16px;
            margin-bottom: 30px;
            color: #e9ecef;
            line-height: 1.6;
        }
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: #fff;
            color: #667eea;
        }
        .btn-secondary {
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.5);
        }
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .logo {
            max-width: 150px;
            margin-bottom: 30px;
            opacity: 0.9;
        }
        @media (max-width: 600px) {
            .error-code { font-size: 80px; }
            .error-message { font-size: 20px; }
            .actions { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <?php if(file_exists('assets/images/logo-bpg.png')): ?>
        <img src="assets/images/logo-bpg.png" alt="Logo" class="logo">
        <?php endif; ?>
        
        <div class="error-code"><?php echo $code; ?></div>
        
        <div class="error-message"><?php echo htmlspecialchars($message); ?></div>
        
        <div class="error-desc">
            <?php if($code == 404): ?>
                Halaman yang Anda cari tidak ditemukan. Mungkin sudah dihapus atau dipindah.
            <?php elseif($code == 403): ?>
                Anda tidak memiliki izin untuk mengakses halaman ini.
            <?php elseif($code == 500): ?>
                Terjadi kesalahan internal server. Tim kami sedang menanganinya.
            <?php elseif($code == 503): ?>
                Sistem sedang dalam pemeliharaan. Silakan coba lagi nanti.
            <?php else: ?>
                Terjadi kesalahan. Silakan coba lagi atau hubungi administrator.
            <?php endif; ?>
        </div>
        
        <div class="actions">
            <a href="<?php echo SITE_URL ?? '/'; ?>" class="btn btn-primary">
                ← Kembali ke Beranda
            </a>
            
            <?php if($code == 403 || $code == 401): ?>
            <a href="admin/login.php" class="btn btn-secondary">
                🔐 Login Admin
            </a>
            <?php endif; ?>
            
            <a href="javascript:history.back()" class="btn btn-secondary">
                🔙 Kembali Sebelumnya
            </a>
        </div>
        
        <div style="margin-top: 30px; font-size: 14px; color: rgba(255,255,255,0.7);">
            <?php echo SITE_NAME ?? 'Bina Prestasi Gemilang'; ?> • 
            <?php echo date('Y'); ?>
        </div>
    </div>
</body>
</html>