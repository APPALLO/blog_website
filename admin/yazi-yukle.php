<?php
session_start();
require_once('../baglan.php');
require_once('includes/auth_check.php');

// Sayfa başlığı ve aktif menü
$sayfa_basligi = "Taslak Yazı Yükle";
$aktif_sayfa = "yazilar";

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hata = '';
    
    // Dosya kontrolü
    if (!isset($_FILES['taslak_dosya']) || $_FILES['taslak_dosya']['error'] !== 0) {
        $hata = 'Lütfen bir dosya seçin.';
    } else {
        $dosya = $_FILES['taslak_dosya'];
        $izin_verilen_uzantilar = ['txt', 'md', 'doc', 'docx'];
        $dosya_uzantisi = strtolower(pathinfo($dosya['name'], PATHINFO_EXTENSION));
        
        if (!in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
            $hata = 'Geçersiz dosya formatı. Sadece TXT, MD, DOC ve DOCX dosyaları yüklenebilir.';
        } else {
            // Dosyayı oku
            $icerik = '';
            if ($dosya_uzantisi === 'txt' || $dosya_uzantisi === 'md') {
                $icerik = file_get_contents($dosya['tmp_name']);
            } else {
                // DOC ve DOCX dosyaları için PHP-Word kütüphanesi kullanılabilir
                $hata = 'DOC ve DOCX dosya desteği yakında eklenecektir.';
            }
            
            if (!empty($icerik)) {
                // Başlığı ilk satırdan al
                $satirlar = explode("\n", $icerik);
                $baslik = trim($satirlar[0]);
                $icerik = implode("\n", array_slice($satirlar, 1));
                
                // Yazıyı veritabanına ekle
                $sql = "INSERT INTO blog_yazilar (baslik, seo_url, icerik, yazar_id, durum, tarih) 
                        VALUES (?, ?, ?, ?, 'taslak', NOW())";
                
                $stmt = $conn->prepare($sql);
                $seo_url = createSlug($baslik);
                $stmt->bind_param("sssi", $baslik, $seo_url, $icerik, $_SESSION['admin']['id']);
                
                if ($stmt->execute()) {
                    $yazi_id = $conn->insert_id;
                    header("Location: yazi-duzenle.php?id=$yazi_id&mesaj=yuklendi");
                    exit();
                } else {
                    $hata = 'Yazı kaydedilirken bir hata oluştu.';
                }
            }
        }
    }
}

// SEO URL oluşturma fonksiyonu
function createSlug($str, $delimiter = '-') {
    $turkce = array('ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'İ', 'Ğ', 'Ü', 'Ş', 'Ö', 'Ç');
    $latin = array('i', 'g', 'u', 's', 'o', 'c', 'i', 'g', 'u', 's', 'o', 'c');
    
    $str = str_replace($turkce, $latin, $str);
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    $str = preg_replace('/-+/', "-", $str);
    return trim($str, '-');
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $sayfa_basligi; ?> - Blog Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="css/admin.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Ana İçerik -->
            <div class="col p-0">
                <div class="main-content p-4">
                    <?php if (isset($hata) && !empty($hata)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $hata; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title mb-4">
                                <i class="fas fa-file-import me-2"></i>Taslak Yazı Yükle
                            </h5>
                            
                            <form action="" method="POST" enctype="multipart/form-data">
                                <div class="mb-4">
                                    <label class="form-label">Taslak Dosyası</label>
                                    <input type="file" name="taslak_dosya" class="form-control" 
                                           accept=".txt,.md,.doc,.docx" required>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Desteklenen formatlar: TXT, MD, DOC, DOCX
                                    </div>
                                </div>
                                
                                <div class="alert alert-info">
                                    <h6 class="alert-heading mb-3">
                                        <i class="fas fa-lightbulb me-2"></i>Dosya Formatı Hakkında
                                    </h6>
                                    <ul class="mb-0">
                                        <li>Dosyanın ilk satırı yazı başlığı olarak kullanılacaktır.</li>
                                        <li>Geri kalan içerik yazı metni olarak kaydedilecektir.</li>
                                        <li>Yazı taslak olarak kaydedilecek ve düzenleme sayfasına yönlendirileceksiniz.</li>
                                        <li>Düzenleme sayfasında kategori, etiket ve diğer ayarları yapabilirsiniz.</li>
                                    </ul>
                                </div>
                                
                                <div class="d-flex gap-2">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-upload me-2"></i>Yükle ve Düzenle
                                    </button>
                                    <a href="yazilar.php" class="btn btn-light">
                                        <i class="fas fa-times me-2"></i>İptal
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 