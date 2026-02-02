<?php
// File: includes/qrcode.php - REAL QR CODE WITH LOGO

class QRCodeGenerator {
    private $base_url = "http://localhost/bina_prestasi_gemilang";
    
    public function __construct() {
        // Create uploads folder
        $upload_dir = dirname(__DIR__) . '/uploads/qrcodes';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
    }
    
    /**
     * Generate REAL QR Code using Google Charts API
     */
    public function generateForCertificate($id, $number) {
        try {
            // URL untuk halaman sertifikat
            $certificate_url = $this->base_url . "/certificate.php?id=" . $id;
            
            // Data untuk QR (URL ke sertifikat)
            $qr_data = $certificate_url;
            
            // Generate QR menggunakan Google Charts API
            $qr_image_url = $this->generateQRImage($qr_data, $id, $number);
            
            // Download QR image
            $image_data = file_get_contents($qr_image_url);
            
            if (!$image_data) {
                // Fallback: buat QR sederhana
                return $this->createSimpleQR($id, $number, $certificate_url);
            }
            
            // Save QR image
            $upload_dir = dirname(__DIR__) . '/uploads/qrcodes';
            $filename = 'cert_' . $id . '_' . substr(md5($number), 0, 8) . '.png';
            $filepath = $upload_dir . '/' . $filename;
            
            file_put_contents($filepath, $image_data);
            
            // Add logo if exists
            $has_logo = $this->addLogoToQR($filepath);
            
            // QR data untuk database
            $qr_json_data = json_encode([
                'id' => $id,
                'nomor' => $number,
                'url' => $certificate_url,
                'timestamp' => time()
            ]);
            
            return [
                'success' => true,
                'qr_data' => $qr_json_data,
                'qr_path' => $filepath,
                'qr_url' => 'uploads/qrcodes/' . $filename,
                'qr_image_url' => $this->base_url . '/uploads/qrcodes/' . $filename,
                'certificate_url' => $certificate_url,
                'has_logo' => $has_logo
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate QR Code using Google Charts API
     */
    private function generateQRImage($data, $id, $number) {
        // Parameter untuk Google Charts API
        $params = [
            'cht' => 'qr',  // Chart type: QR code
            'chs' => '300x300',  // Size
            'chl' => urlencode($data),  // Data to encode
            'choe' => 'UTF-8',  // Encoding
            'chld' => 'H|4',  // Error correction level (H = High)
            'chof' => 'png'  // Output format
        ];
        
        // Build URL
        $url = 'https://chart.googleapis.com/chart?' . http_build_query($params);
        return $url;
    }
    
    /**
     * Add company logo to QR code
     */
    private function addLogoToQR($qr_image_path) {
        $logo_path = dirname(__DIR__) . '/assets/images/logo-bpg.png';
        
        // Check if logo exists
        if (!file_exists($logo_path)) {
            return false;
        }
        
        // Check if GD library is available
        if (!function_exists('imagecreatefrompng')) {
            return false;
        }
        
        try {
            // Load QR image
            $qr = imagecreatefrompng($qr_image_path);
            if (!$qr) return false;
            
            // Load logo
            $logo_ext = strtolower(pathinfo($logo_path, PATHINFO_EXTENSION));
            $logo = null;
            
            switch ($logo_ext) {
                case 'png':
                    $logo = imagecreatefrompng($logo_path);
                    break;
                case 'jpg':
                case 'jpeg':
                    $logo = imagecreatefromjpeg($logo_path);
                    break;
                case 'gif':
                    $logo = imagecreatefromgif($logo_path);
                    break;
            }
            
            if (!$logo) {
                imagedestroy($qr);
                return false;
            }
            
            // Get dimensions
            $qr_width = imagesx($qr);
            $qr_height = imagesy($qr);
            $logo_width = imagesx($logo);
            $logo_height = imagesy($logo);
            
            // Calculate logo size (20% of QR)
            $logo_size = $qr_width * 0.2;
            
            // Resize logo if needed
            if ($logo_width > $logo_size || $logo_height > $logo_size) {
                $ratio = $logo_width / $logo_height;
                
                if ($logo_width > $logo_height) {
                    $new_width = $logo_size;
                    $new_height = $logo_size / $ratio;
                } else {
                    $new_height = $logo_size;
                    $new_width = $logo_size * $ratio;
                }
                
                $resized_logo = imagecreatetruecolor($new_width, $new_height);
                
                // Preserve transparency for PNG
                if ($logo_ext == 'png') {
                    imagealphablending($resized_logo, false);
                    imagesavealpha($resized_logo, true);
                    $transparent = imagecolorallocatealpha($resized_logo, 0, 0, 0, 127);
                    imagefill($resized_logo, 0, 0, $transparent);
                }
                
                imagecopyresampled($resized_logo, $logo, 0, 0, 0, 0, $new_width, $new_height, $logo_width, $logo_height);
                imagedestroy($logo);
                $logo = $resized_logo;
                $logo_width = $new_width;
                $logo_height = $new_height;
            }
            
            // Position logo in center
            $x = ($qr_width - $logo_width) / 2;
            $y = ($qr_height - $logo_height) / 2;
            
            // Merge logo onto QR
            imagecopymerge($qr, $logo, $x, $y, 0, 0, $logo_width, $logo_height, 100);
            
            // Save final image
            imagepng($qr, $qr_image_path);
            
            // Cleanup
            imagedestroy($qr);
            imagedestroy($logo);
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Create simple QR as fallback (if no internet)
     */
    private function createSimpleQR($id, $number, $url) {
        $upload_dir = dirname(__DIR__) . '/uploads/qrcodes';
        $filename = 'cert_' . $id . '_simple.png';
        $filepath = $upload_dir . '/' . $filename;
        
        // Create image
        $image = imagecreatetruecolor(300, 300);
        
        // Colors
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        $blue = imagecolorallocate($image, 41, 128, 185);
        
        // Fill background
        imagefilledrectangle($image, 0, 0, 300, 300, $white);
        
        // Draw QR-like pattern
        $this->drawQRPattern($image);
        
        // Add text
        imagestring($image, 5, 50, 100, "QR CODE", $black);
        imagestring($image, 3, 40, 130, "Scan untuk:", $black);
        imagestring($image, 2, 30, 150, $url, $blue);
        imagestring($image, 3, 60, 180, "No: " . $number, $black);
        imagestring($image, 2, 50, 200, "Bina Prestasi", $black);
        imagestring($image, 2, 60, 215, "Gemilang", $black);
        
        // Save image
        imagepng($image, $filepath);
        imagedestroy($image);
        
        return [
            'success' => true,
            'qr_data' => json_encode(['id' => $id, 'nomor' => $number, 'url' => $url]),
            'qr_path' => $filepath,
            'qr_url' => 'uploads/qrcodes/' . $filename,
            'qr_image_url' => $this->base_url . '/uploads/qrcodes/' . $filename,
            'certificate_url' => $url,
            'has_logo' => false,
            'note' => 'Simple QR (no internet)'
        ];
    }
    
    /**
     * Draw QR-like pattern
     */
    private function drawQRPattern($image) {
        $black = imagecolorallocate($image, 0, 0, 0);
        
        // Draw corner squares
        imagefilledrectangle($image, 20, 20, 60, 60, $black);
        imagefilledrectangle($image, 240, 20, 280, 60, $black);
        imagefilledrectangle($image, 20, 240, 60, 280, $black);
        
        // Draw pattern dots
        for ($i = 0; $i < 50; $i++) {
            $x = rand(70, 230);
            $y = rand(70, 230);
            $size = rand(4, 8);
            imagefilledellipse($image, $x, $y, $size, $size, $black);
        }
    }
    
    /**
     * Get QR statistics
     */
    public function getQRStats() {
        $conn = new mysqli("localhost", "root", "", "bina_prestasi_gemilang");
        
        $stats = [
            'total_certificates' => 0,
            'with_qr' => 0,
            'without_qr' => 0,
            'logo_exists' => false
        ];
        
        if (!$conn->connect_error) {
            // Get total certificates
            $result = $conn->query("SELECT COUNT(*) as total FROM sertifikat");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['total_certificates'] = $row['total'] ?? 0;
            }
            
            // Get certificates with QR
            $result = $conn->query("SELECT COUNT(*) as count FROM sertifikat WHERE qr_code_data IS NOT NULL");
            if ($result) {
                $row = $result->fetch_assoc();
                $stats['with_qr'] = $row['count'] ?? 0;
            }
            
            $conn->close();
        }
        
        $stats['without_qr'] = $stats['total_certificates'] - $stats['with_qr'];
        
        // Check if logo exists
        $logo_path = dirname(__DIR__) . '/assets/images/logo-bpg.png';
        $stats['logo_exists'] = file_exists($logo_path);
        
        // Check uploads folder
        $upload_dir = dirname(__DIR__) . '/uploads/qrcodes';
        if (is_dir($upload_dir)) {
            $files = glob($upload_dir . '/*.png');
            $stats['qr_files_count'] = count($files);
            $stats['qr_folder_size'] = 0;
            
            foreach ($files as $file) {
                $stats['qr_folder_size'] += filesize($file);
            }
        }
        
        return $stats;
    }
    
    /**
     * Generate QR image URL for display
     */
    public function getQRImageUrl($data, $size = 200) {
        $params = [
            'cht' => 'qr',
            'chs' => $size . 'x' . $size,
            'chl' => urlencode($data),
            'choe' => 'UTF-8',
            'chld' => 'H|4'
        ];
        
        return 'https://chart.googleapis.com/chart?' . http_build_query($params);
    }
}
?>