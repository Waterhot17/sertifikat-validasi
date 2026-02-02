<?php
// ============================================
// QR CODE GENERATOR WITH LOGO
// ============================================

require_once 'config.php';

class QRCodeGenerator {
    private $qrSize = 300;
    private $qrMargin = 10;
    private $uploadPath = '../uploads/qrcodes/';
    private $logoPath = '../assets/images/logo-bpg.png'; // Logo perusahaan
    
    public function __construct() {
        // Create upload directory if not exists
        if (!file_exists($this->uploadPath)) {
            mkdir($this->uploadPath, 0755, true);
        }
        
        // Use default logo if exists, otherwise no logo
        $this->logoPath = file_exists($this->logoPath) ? $this->logoPath : null;
    }
    
    /**
     * Set custom logo path
     */
    public function setLogoPath($path) {
        if (file_exists($path)) {
            $this->logoPath = $path;
            return true;
        }
        return false;
    }
    
    /**
     * Generate QR Code with company logo
     */
    public function generateForCertificate($certificate_id, $certificate_number) {
        // Data to encode in QR
        $data = [
            'id' => $certificate_id,
            'nomor' => $certificate_number,
            'url' => BASE_URL . '/certificate.php?id=' . $certificate_id,
            'timestamp' => time(),
            'company' => SITE_NAME
        ];
        
        $qrData = json_encode($data);
        $filename = 'cert_' . $certificate_id . '_' . md5($certificate_number) . '.png';
        $filepath = $this->uploadPath . $filename;
        
        // Generate QR with Google Charts API
        $qrImageUrl = $this->generateWithGoogleAPI($qrData);
        
        // Download QR image
        $qrImageData = file_get_contents($qrImageUrl);
        if (!$qrImageData) {
            return ['success' => false, 'error' => 'Failed to generate QR image'];
        }
        
        // Save QR image
        file_put_contents($filepath, $qrImageData);
        
        // Add logo to QR if logo exists
        if ($this->logoPath) {
            $this->addLogoToQR($filepath);
        }
        
        return [
            'success' => true,
            'qr_data' => $qrData,
            'qr_path' => $filepath,
            'qr_url' => str_replace('../', '', $filepath),
            'has_logo' => $this->logoPath !== null
        ];
    }
    
    /**
     * Add company logo to center of QR code
     */
    private function addLogoToQR($qrImagePath) {
        // Load QR image
        $qr = imagecreatefrompng($qrImagePath);
        
        // Load logo
        $logo_ext = strtolower(pathinfo($this->logoPath, PATHINFO_EXTENSION));
        
        switch ($logo_ext) {
            case 'png':
                $logo = imagecreatefrompng($this->logoPath);
                break;
            case 'jpg':
            case 'jpeg':
                $logo = imagecreatefromjpeg($this->logoPath);
                break;
            case 'gif':
                $logo = imagecreatefromgif($this->logoPath);
                break;
            default:
                return false; // Unsupported format
        }
        
        if (!$logo) return false;
        
        // Get dimensions
        $qr_width = imagesx($qr);
        $qr_height = imagesy($qr);
        $logo_width = imagesx($logo);
        $logo_height = imagesy($logo);
        
        // Calculate logo size (20% of QR size)
        $logo_size = min($qr_width, $qr_height) * 0.2;
        
        // Resize logo if needed
        if ($logo_width > $logo_size || $logo_height > $logo_size) {
            $aspect_ratio = $logo_width / $logo_height;
            
            if ($logo_width > $logo_height) {
                $new_width = $logo_size;
                $new_height = $logo_size / $aspect_ratio;
            } else {
                $new_height = $logo_size;
                $new_width = $logo_size * $aspect_ratio;
            }
            
            $resized_logo = imagescale($logo, $new_width, $new_height);
            imagedestroy($logo);
            $logo = $resized_logo;
            $logo_width = imagesx($logo);
            $logo_height = imagesy($logo);
        }
        
        // Calculate position (center of QR)
        $x = ($qr_width - $logo_width) / 2;
        $y = ($qr_height - $logo_height) / 2;
        
        // Create a white background for logo
        $logo_bg = imagecreatetruecolor($logo_width + 4, $logo_height + 4);
        $white = imagecolorallocate($logo_bg, 255, 255, 255);
        imagefill($logo_bg, 0, 0, $white);
        
        // Put logo on white background
        imagecopy($logo_bg, $logo, 2, 2, 0, 0, $logo_width, $logo_height);
        
        // Merge logo with QR code
        imagecopymerge($qr, $logo_bg, $x - 2, $y - 2, 0, 0, $logo_width + 4, $logo_height + 4, 100);
        
        // Save final image
        imagepng($qr, $qrImagePath);
        
        // Clean up
        imagedestroy($qr);
        imagedestroy($logo);
        imagedestroy($logo_bg);
        
        return true;
    }
    
    /**
     * Generate QR using Google Charts API
     */
    private function generateWithGoogleAPI($data) {
        $params = http_build_query([
            'cht' => 'qr',
            'chs' => $this->qrSize . 'x' . $this->qrSize,
            'chl' => urlencode($data),
            'choe' => 'UTF-8',
            'chld' => 'H|' . $this->qrMargin,
            'chof' => 'png'
        ]);
        
        return 'https://chart.googleapis.com/chart?' . $params;
    }
    
    /**
     * Generate QR Code with custom color and logo
     */
    public function generateCustomQR($data, $options = []) {
        $defaults = [
            'size' => 300,
            'color' => '000000',
            'bgcolor' => 'FFFFFF',
            'margin' => 4,
            'logo' => true
        ];
        
        $options = array_merge($defaults, $options);
        
        // Generate QR with custom color
        $params = http_build_query([
            'cht' => 'qr',
            'chs' => $options['size'] . 'x' . $options['size'],
            'chl' => urlencode($data),
            'choe' => 'UTF-8',
            'chld' => 'H|' . $options['margin'],
            'chco' => $options['color'],
            'chof' => 'png'
        ]);
        
        $qrUrl = 'https://chart.googleapis.com/chart?' . $params;
        $tempFile = tempnam(sys_get_temp_dir(), 'qr_');
        
        // Download QR
        $qrData = file_get_contents($qrUrl);
        file_put_contents($tempFile, $qrData);
        
        // Add logo if requested and exists
        if ($options['logo'] && $this->logoPath) {
            $this->addLogoToQR($tempFile);
        }
        
        return $tempFile;
    }
    
    /**
     * Display QR with logo for web
     */
    public function displayQRWithLogo($data, $size = 200, $include_logo = true) {
        // Generate temporary QR
        $tempFile = $this->generateCustomQR($data, [
            'size' => $size,
            'logo' => $include_logo
        ]);
        
        // Read and encode to base64
        $imageData = file_get_contents($tempFile);
        $base64 = base64_encode($imageData);
        
        // Clean up
        unlink($tempFile);
        
        return 'data:image/png;base64,' . $base64;
    }
    
    // ... (fungsi lainnya tetap sama: validateQR, scanFromImage, etc) ...
    
    /**
     * Get QR Code statistics
     */
    public function getQRStats() {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        $stats = [
            'total_certificates' => 0,
            'with_qr' => 0,
            'without_qr' => 0,
            'with_logo' => 0,
            'qr_folder_size' => 0
        ];
        
        // Count certificates
        $result = $conn->query("SELECT COUNT(*) as total FROM sertifikat");
        $stats['total_certificates'] = $result->fetch_assoc()['total'];
        
        // Count with QR
        $result = $conn->query("SELECT COUNT(*) as count FROM sertifikat WHERE qr_code_data IS NOT NULL");
        $stats['with_qr'] = $result->fetch_assoc()['count'];
        
        $stats['without_qr'] = $stats['total_certificates'] - $stats['with_qr'];
        
        // Count QR files with logo (check if logo exists in QR folder)
        if (file_exists($this->uploadPath)) {
            $files = glob($this->uploadPath . '*.png');
            $stats['qr_folder_size'] = array_sum(array_map('filesize', $files));
            $stats['qr_file_count'] = count($files);
        }
        
        return $stats;
    }
}

// ============================================
// FUNGSI HELPER UPDATE
// ============================================

/**
 * Generate QR for all certificates that don't have QR (with logo)
 */
function generateMissingQRCodes() {
    require_once 'config.php';
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $qrGenerator = new QRCodeGenerator();
    
    // Get certificates without QR
    $sql = "SELECT id, nomor_sertifikat FROM sertifikat WHERE qr_code_data IS NULL OR qr_code_path IS NULL";
    $result = $conn->query($sql);
    
    $generated = 0;
    $errors = 0;
    $details = [];
    
    while ($cert = $result->fetch_assoc()) {
        $result_qr = $qrGenerator->generateForCertificate($cert['id'], $cert['nomor_sertifikat']);
        
        if ($result_qr['success']) {
            // Update database
            $update = $conn->prepare("UPDATE sertifikat SET qr_code_data = ?, qr_code_path = ? WHERE id = ?");
            $update->bind_param("ssi", $result_qr['qr_data'], $result_qr['qr_path'], $cert['id']);
            
            if ($update->execute()) {
                $generated++;
                $details[] = [
                    'id' => $cert['id'],
                    'nomor' => $cert['nomor_sertifikat'],
                    'has_logo' => $result_qr['has_logo']
                ];
            } else {
                $errors++;
            }
        } else {
            $errors++;
        }
    }
    
    return [
        'generated' => $generated, 
        'errors' => $errors,
        'details' => $details,
        'has_logo' => $qrGenerator->logoPath !== null
    ];
}

/**
 * Get QR Code for certificate (with logo)
 */
function getCertificateQR($certificate_id) {
    require_once 'config.php';
    
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $qrGenerator = new QRCodeGenerator();
    
    // Get certificate
    $sql = "SELECT id, nomor_sertifikat, qr_code_data, qr_code_path FROM sertifikat WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $certificate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $cert = $result->fetch_assoc();
        
        // If QR doesn't exist, generate it
        if (empty($cert['qr_code_data']) || empty($cert['qr_code_path']) || !file_exists($cert['qr_code_path'])) {
            $qrResult = $qrGenerator->generateForCertificate($cert['id'], $cert['nomor_sertifikat']);
            
            if ($qrResult['success']) {
                // Update database
                $update = $conn->prepare("UPDATE sertifikat SET qr_code_data = ?, qr_code_path = ? WHERE id = ?");
                $update->bind_param("ssi", $qrResult['qr_data'], $qrResult['qr_path'], $cert['id']);
                $update->execute();
                
                return [
                    'success' => true,
                    'qr_data' => $qrResult['qr_data'],
                    'qr_path' => $qrResult['qr_path'],
                    'qr_url' => $qrResult['qr_url'],
                    'display_url' => $qrGenerator->displayQRWithLogo($qrResult['qr_data']),
                    'has_logo' => $qrResult['has_logo']
                ];
            }
        } else {
            // QR already exists
            return [
                'success' => true,
                'qr_data' => $cert['qr_code_data'],
                'qr_path' => $cert['qr_code_path'],
                'qr_url' => str_replace('../', '', $cert['qr_code_path']),
                'display_url' => $qrGenerator->displayQRWithLogo($cert['qr_code_data']),
                'has_logo' => $qrGenerator->logoPath !== null
            ];
        }
    }
    
    return ['success' => false, 'error' => 'Certificate not found'];
}
?>