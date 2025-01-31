<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Oturum kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit();
}

include 'baglan.php';

// Kullanıcı bilgilerini al
$kullanici_id = $_SESSION['kullanici_id'];
$sql = "SELECT * FROM kullanicilar WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $kullanici_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $_SESSION['hata'] = "Kullanıcı bilgileri alınamadı!";
    header("Location: giris.php");
    exit();
}

$kullanici = $result->fetch_assoc();

// Kullanıcı istatistiklerini al
$sql = "SELECT 
        COALESCE((SELECT COUNT(*) FROM blog_yazilar WHERE yazar_id = ? AND durum = 'yayinda'), 0) as yayinda_yazi,
        COALESCE((SELECT COUNT(*) FROM blog_yazilar WHERE yazar_id = ? AND durum = 'taslak'), 0) as taslak_yazi,
        COALESCE((SELECT SUM(goruntulenme) FROM blog_yazilar WHERE yazar_id = ?), 0) as toplam_goruntulenme,
        COALESCE((SELECT COUNT(*) FROM yorumlar y 
         INNER JOIN blog_yazilar b ON y.yazi_id = b.id 
         WHERE b.yazar_id = ? AND y.durum = 'onaylanmis'), 0) as toplam_yorum";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiii", $kullanici_id, $kullanici_id, $kullanici_id, $kullanici_id);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    $istatistikler = [
        'yayinda_yazi' => 0,
        'taslak_yazi' => 0,
        'toplam_goruntulenme' => 0,
        'toplam_yorum' => 0
    ];
} else {
    $istatistikler = $result->fetch_assoc();
    if (!$istatistikler) {
        $istatistikler = [
            'yayinda_yazi' => 0,
            'taslak_yazi' => 0,
            'toplam_goruntulenme' => 0,
            'toplam_yorum' => 0
        ];
    }
}

// Son aktiviteleri al
$sql = "SELECT b.id, b.baslik, b.tarih, b.durum, 'yazi' as tip
        FROM blog_yazilar b 
        WHERE b.yazar_id = ?
        UNION ALL
        SELECT y.yazi_id, SUBSTRING(y.yorum_metni, 1, 100) as baslik, y.tarih, y.durum, 'yorum' as tip
        FROM yorumlar y
        INNER JOIN blog_yazilar b ON y.yazi_id = b.id
        WHERE y.kullanici_id = ?
        ORDER BY tarih DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $kullanici_id, $kullanici_id);
$stmt->execute();
$aktiviteler = $stmt->get_result();

// Profil güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['profil_resmi']) && $_FILES['profil_resmi']['error'] === 0) {
        $izin_verilen_uzantilar = ['jpg', 'jpeg', 'png', 'gif'];
        $dosya_uzantisi = strtolower(pathinfo($_FILES['profil_resmi']['name'], PATHINFO_EXTENSION));
        
        if (in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
            $yeni_isim = 'profil_' . $kullanici_id . '_' . time() . '.' . $dosya_uzantisi;
            $hedef_dizin = 'uploads/profil/';
            
            if (!file_exists($hedef_dizin)) {
                mkdir($hedef_dizin, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['profil_resmi']['tmp_name'], $hedef_dizin . $yeni_isim)) {
                $sql = "UPDATE kullanicilar SET profil_resmi = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $profil_resmi_yolu = $hedef_dizin . $yeni_isim;
                $stmt->bind_param("si", $profil_resmi_yolu, $kullanici_id);
                $stmt->execute();
            }
        }
    }
    
    $ad_soyad = trim(htmlspecialchars($_POST['ad_soyad']));
    $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $mevcut_sifre = $_POST['mevcut_sifre'];
    $yeni_sifre = $_POST['yeni_sifre'];
    $yeni_sifre_tekrar = $_POST['yeni_sifre_tekrar'];
    
    $hata = false;
    
    // Email kontrolü
    if ($email !== $kullanici['email']) {
        $sql = "SELECT id FROM kullanicilar WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $email, $kullanici_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $_SESSION['hata'] = "Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.";
            $hata = true;
        }
    }
    
    // Şifre değişikliği kontrolü
    if (!empty($mevcut_sifre)) {
        if (!password_verify($mevcut_sifre, $kullanici['sifre'])) {
            $_SESSION['hata'] = "Mevcut şifre hatalı.";
            $hata = true;
        } elseif ($yeni_sifre !== $yeni_sifre_tekrar) {
            $_SESSION['hata'] = "Yeni şifreler eşleşmiyor.";
            $hata = true;
        } elseif (strlen($yeni_sifre) < 6) {
            $_SESSION['hata'] = "Yeni şifre en az 6 karakter olmalıdır.";
            $hata = true;
        }
    }
    
    if (!$hata) {
        // Profil güncelleme sorgusu
        if (!empty($yeni_sifre)) {
            $yeni_sifre_hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            $sql = "UPDATE kullanicilar SET ad_soyad = ?, email = ?, sifre = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssi", $ad_soyad, $email, $yeni_sifre_hash, $kullanici_id);
        } else {
            $sql = "UPDATE kullanicilar SET ad_soyad = ?, email = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $ad_soyad, $email, $kullanici_id);
        }
        
        if ($stmt->execute()) {
            $_SESSION['basari'] = "Profil bilgileriniz başarıyla güncellendi.";
            $_SESSION['ad_soyad'] = $ad_soyad;
            header("Location: profil.php");
            exit();
        } else {
            $_SESSION['hata'] = "Profil güncellenirken bir hata oluştu.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - Blog Sitesi</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-4">
                <!-- Profil Kartı -->
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <div class="position-relative d-inline-block mb-3">
                            <img src="<?php 
                                echo !empty($kullanici['profil_resmi']) 
                                    ? htmlspecialchars($kullanici['profil_resmi']) 
                                    : 'https://ui-avatars.com/api/?name=' . urlencode($kullanici['ad_soyad'] ?? 'Kullanıcı') . '&size=128';
                            ?>" class="rounded-circle" width="128" height="128" alt="Profil Resmi">
                            <button type="button" class="btn btn-sm btn-primary position-absolute bottom-0 end-0" 
                                    data-bs-toggle="modal" data-bs-target="#profilResmiModal">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        <h4><?php echo htmlspecialchars($kullanici['ad_soyad'] ?? 'Kullanıcı'); ?></h4>
                        <p class="text-muted mb-1">@<?php echo htmlspecialchars($kullanici['kullanici_adi'] ?? ''); ?></p>
                        <p class="text-muted mb-3">
                            <i class="fas fa-user-shield"></i> <?php echo ucfirst($kullanici['rol'] ?? 'kullanıcı'); ?>
                        </p>
                        <div class="d-flex justify-content-center mb-2">
                            <a href="yazilarim.php" class="btn btn-primary me-2">
                                <i class="fas fa-pencil-alt"></i> Yazılarım
                            </a>
                            <a href="yazi_ekle.php" class="btn btn-outline-primary">
                                <i class="fas fa-plus"></i> Yeni Yazı
                            </a>
                        </div>
                    </div>
                </div>

                <!-- İstatistikler -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">İstatistikler</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-file-alt text-primary me-2"></i>
                                Yayında Olan Yazılar
                            </div>
                            <span class="badge bg-primary rounded-pill">
                                <?php echo number_format($istatistikler['yayinda_yazi'] ?? 0); ?>
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-edit text-warning me-2"></i>
                                Taslak Yazılar
                            </div>
                            <span class="badge bg-warning rounded-pill">
                                <?php echo number_format($istatistikler['taslak_yazi'] ?? 0); ?>
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-eye text-info me-2"></i>
                                Toplam Görüntülenme
                            </div>
                            <span class="badge bg-info rounded-pill">
                                <?php echo number_format($istatistikler['toplam_goruntulenme'] ?? 0); ?>
                            </span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-comments text-success me-2"></i>
                                Toplam Yorum
                            </div>
                            <span class="badge bg-success rounded-pill">
                                <?php echo number_format($istatistikler['toplam_yorum'] ?? 0); ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Son Aktiviteler -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Son Aktiviteler</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <?php while ($aktivite = $aktiviteler->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        <i class="fas fa-<?php echo $aktivite['tip'] === 'yazi' ? 'file-alt' : 'comment'; ?> me-2"></i>
                                        <?php 
                                            if ($aktivite['tip'] === 'yazi') {
                                                echo 'Yeni yazı: ';
                                            } else {
                                                echo 'Yorum yaptı: ';
                                            }
                                        ?>
                                    </h6>
                                    <small class="text-muted">
                                        <?php echo date('d.m.Y H:i', strtotime($aktivite['tarih'])); ?>
                                    </small>
                                </div>
                                <p class="mb-1">
                                    <a href="yazi.php?id=<?php echo $aktivite['id']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($aktivite['baslik']); ?>
                                    </a>
                                </p>
                                <small class="text-muted">
                                    <?php 
                                        if ($aktivite['tip'] === 'yazi') {
                                            echo $aktivite['durum'] === 'yayinda' ? 'Yayında' : 'Taslak';
                                        } else {
                                            echo $aktivite['durum'] === 'onaylanmis' ? 'Onaylandı' : 'Onay Bekliyor';
                                        }
                                    ?>
                                </small>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Profil Düzenleme -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Profil Bilgilerini Güncelle</h5>
                    </div>
                    <div class="card-body">
                        <?php if(isset($_SESSION['hata'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <?php 
                                    echo $_SESSION['hata'];
                                    unset($_SESSION['hata']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if(isset($_SESSION['basari'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <?php 
                                    echo $_SESSION['basari'];
                                    unset($_SESSION['basari']);
                                ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form action="profil.php" method="POST">
                            <div class="mb-3">
                                <label for="ad_soyad" class="form-label">Ad Soyad</label>
                                <input type="text" class="form-control" id="ad_soyad" name="ad_soyad" 
                                       value="<?php echo isset($kullanici['ad_soyad']) ? htmlspecialchars($kullanici['ad_soyad']) : ''; ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo isset($kullanici['email']) ? htmlspecialchars($kullanici['email']) : ''; ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="kullanici_adi" class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="kullanici_adi" name="kullanici_adi" 
                                       value="<?php echo isset($kullanici['kullanici_adi']) ? htmlspecialchars($kullanici['kullanici_adi']) : ''; ?>" 
                                       readonly>
                                <div class="form-text text-muted">Kullanıcı adı değiştirilemez.</div>
                            </div>
                            
                            <hr>
                            
                            <h6 class="mb-3">Şifre Değiştir</h6>
                            <div class="mb-3">
                                <label for="mevcut_sifre" class="form-label">Mevcut Şifre</label>
                                <input type="password" class="form-control" id="mevcut_sifre" name="mevcut_sifre" 
                                       autocomplete="current-password">
                                <div class="form-text">Şifrenizi değiştirmek için önce mevcut şifrenizi girin.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="yeni_sifre" class="form-label">Yeni Şifre</label>
                                <input type="password" class="form-control" id="yeni_sifre" name="yeni_sifre" 
                                       minlength="6" autocomplete="new-password">
                                <div class="form-text">En az 6 karakter olmalıdır.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="yeni_sifre_tekrar" class="form-label">Yeni Şifre Tekrar</label>
                                <input type="password" class="form-control" id="yeni_sifre_tekrar" name="yeni_sifre_tekrar" 
                                       minlength="6" autocomplete="new-password">
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                                </button>
                                <a href="hesap_sil.php" class="btn btn-danger">
                                    <i class="fas fa-trash-alt me-2"></i>Hesabı Sil
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profil Resmi Yükleme Modal -->
    <div class="modal fade" id="profilResmiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Profil Resmi Yükle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="profil.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="profil_resmi" class="form-label">Resim Seçin</label>
                            <input type="file" class="form-control" id="profil_resmi" name="profil_resmi" accept="image/*" required>
                            <div class="form-text">
                                Desteklenen formatlar: JPG, JPEG, PNG, GIF<br>
                                Önerilen boyut: 128x128 piksel
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Yükle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 