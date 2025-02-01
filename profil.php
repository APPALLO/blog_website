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
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --accent-color: #818cf8;
            --background-color: #f8fafc;
            --text-color: #0f172a;
            --light-text: #64748b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --hover-color: #6366f1;
            --gradient-start: #4f46e5;
            --gradient-end: #7c3aed;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
        }

        .profile-header {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            padding: 3rem 0;
            margin-bottom: 3rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.1)" x="0" y="0" width="100" height="100"/></svg>') repeat;
            opacity: 0.1;
            animation: backgroundMove 20s linear infinite;
        }

        @keyframes backgroundMove {
            from { background-position: 0 0; }
            to { background-position: 100px 100px; }
        }

        .profile-avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            position: relative;
            margin: 0 auto;
            display: block;
            object-fit: cover;
        }

        .avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: var(--primary-color);
            border: 3px solid white;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .avatar-upload:hover {
            transform: scale(1.1);
            background: var(--hover-color);
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            border-bottom: none;
            padding: 1.5rem;
        }

        .card-title {
            margin: 0;
            font-weight: 600;
        }

        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-label {
            color: var(--light-text);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .activity-item {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }

        .activity-item:hover {
            background: rgba(79, 70, 229, 0.03);
            transform: translateX(10px);
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.1);
        }

        .btn {
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        .profile-nav {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .profile-nav .nav-link {
            color: var(--light-text);
            padding: 1rem;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .profile-nav .nav-link:hover,
        .profile-nav .nav-link.active {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
        }

        .badge {
            padding: 0.5em 1em;
            border-radius: 2rem;
            font-weight: 500;
        }

        .badge.bg-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end)) !important;
        }

        .upload-area {
            border: 2px dashed var(--border-color);
            border-radius: 1rem;
            transition: all 0.3s ease;
        }

        .upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(79, 70, 229, 0.1);
        }

        .image-preview {
            position: relative;
            width: 200px;
            height: 200px;
            margin: 0 auto;
        }

        .preview-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border: 3px solid white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .upload-button {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background: var(--primary-color);
            color: white;
            border-radius: 0.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-button:hover {
            background: var(--hover-color);
            transform: translateY(-2px);
        }

        .progress {
            height: 0.5rem;
            border-radius: 1rem;
            background: rgba(79, 70, 229, 0.1);
        }

        .progress-bar {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 1rem;
            transition: width 0.3s ease;
        }
    </style>
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
                <!-- Profil Başlığı -->
                <div class="profile-header">
                    <div class="container">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center">
                                <div class="position-relative d-inline-block">
                                    <img src="<?php 
                                        echo !empty($kullanici['profil_resmi']) 
                                            ? htmlspecialchars($kullanici['profil_resmi']) 
                                            : 'https://ui-avatars.com/api/?name=' . urlencode($kullanici['ad_soyad']) . '&size=150';
                                    ?>" class="profile-avatar" alt="Profil Resmi">
                                    <button type="button" class="avatar-upload" data-bs-toggle="modal" data-bs-target="#profilResmiModal">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-8 text-center text-md-start mt-4 mt-md-0">
                                <h1 class="display-5 fw-bold mb-2"><?php echo htmlspecialchars($kullanici['ad_soyad']); ?></h1>
                                <p class="lead mb-0">@<?php echo htmlspecialchars($kullanici['kullanici_adi']); ?></p>
                                <div class="d-flex gap-3 justify-content-center justify-content-md-start mt-3">
                                    <span class="badge bg-primary">
                                        <i class="fas fa-user-shield me-1"></i>
                                        <?php echo ucfirst($kullanici['rol']); ?>
                                    </span>
                                    <span class="badge bg-primary">
                                        <i class="fas fa-clock me-1"></i>
                                        <?php echo 'Üyelik: ' . date('d.m.Y', strtotime($kullanici['kayit_tarihi'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ana İçerik -->
                <div class="container">
                    <!-- İstatistik Kartları -->
                    <div class="row g-4 mb-5">
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-file-alt fa-2x mb-3 text-primary"></i>
                                <div class="stat-value"><?php echo number_format($istatistikler['yayinda_yazi']); ?></div>
                                <div class="stat-label">Yayında Olan Yazılar</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-edit fa-2x mb-3 text-warning"></i>
                                <div class="stat-value"><?php echo number_format($istatistikler['taslak_yazi']); ?></div>
                                <div class="stat-label">Taslak Yazılar</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-eye fa-2x mb-3 text-info"></i>
                                <div class="stat-value"><?php echo number_format($istatistikler['toplam_goruntulenme']); ?></div>
                                <div class="stat-label">Toplam Görüntülenme</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card">
                                <i class="fas fa-comments fa-2x mb-3 text-success"></i>
                                <div class="stat-value"><?php echo number_format($istatistikler['toplam_yorum']); ?></div>
                                <div class="stat-label">Toplam Yorum</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Sol Kolon -->
                        <div class="col-lg-4">
                            <!-- Son Aktiviteler -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-history me-2"></i>Son Aktiviteler
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php while ($aktivite = $aktiviteler->fetch_assoc()): ?>
                                        <div class="activity-item">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <h6 class="mb-0">
                                                    <i class="fas fa-<?php echo $aktivite['tip'] === 'yazi' ? 'file-alt' : 'comment'; ?> me-2"></i>
                                                    <?php echo $aktivite['tip'] === 'yazi' ? 'Yeni yazı' : 'Yorum'; ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo date('d.m.Y H:i', strtotime($aktivite['tarih'])); ?>
                                                </small>
                                            </div>
                                            <p class="mb-0">
                                                <a href="yazi.php?id=<?php echo $aktivite['id']; ?>" class="text-decoration-none">
                                                    <?php echo htmlspecialchars($aktivite['baslik']); ?>
                                                </a>
                                            </p>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Sağ Kolon -->
                        <div class="col-lg-8">
                            <!-- Profil Düzenleme -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-user-edit me-2"></i>Profil Bilgilerini Güncelle
                                    </h5>
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

                                    <form action="profil.php" method="POST" class="needs-validation" novalidate>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Ad Soyad</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                    <input type="text" class="form-control" name="ad_soyad" 
                                                           value="<?php echo htmlspecialchars($kullanici['ad_soyad']); ?>" required>
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">E-posta</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                                    <input type="email" class="form-control" name="email" 
                                                           value="<?php echo htmlspecialchars($kullanici['email']); ?>" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mb-4">
                                            <label class="form-label">Kullanıcı Adı</label>
                                            <div class="input-group">
                                                <span class="input-group-text"><i class="fas fa-at"></i></span>
                                                <input type="text" class="form-control" name="kullanici_adi" 
                                                       value="<?php echo htmlspecialchars($kullanici['kullanici_adi']); ?>" readonly>
                                            </div>
                                            <small class="text-muted">Kullanıcı adı değiştirilemez.</small>
                                        </div>

                                        <hr class="my-4">

                                        <h5 class="mb-4">Şifre Değiştir</h5>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Mevcut Şifre</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                                    <input type="password" class="form-control" name="mevcut_sifre">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Yeni Şifre</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                    <input type="password" class="form-control" name="yeni_sifre" minlength="6">
                                                </div>
                                            </div>
                                            
                                            <div class="col-md-4 mb-3">
                                                <label class="form-label">Yeni Şifre Tekrar</label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                    <input type="password" class="form-control" name="yeni_sifre_tekrar" minlength="6">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="d-flex gap-2 mt-4">
                                            <button type="submit" class="btn btn-primary flex-grow-1">
                                                <i class="fas fa-save me-2"></i>Değişiklikleri Kaydet
                                            </button>
                                            <a href="hesap_sil.php" class="btn btn-danger" onclick="return confirm('Hesabınızı silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash-alt me-2"></i>Hesabı Sil
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profil Resmi Modal -->
    <div class="modal fade" id="profilResmiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Profil Resmi Yükle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="profilResmiForm" action="profil.php" method="POST" enctype="multipart/form-data">
                        <div class="upload-area text-center p-4 mb-3">
                            <div class="image-preview mb-3">
                                <img id="previewImage" src="<?php 
                                    echo !empty($kullanici['profil_resmi']) 
                                        ? htmlspecialchars($kullanici['profil_resmi']) 
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($kullanici['ad_soyad']) . '&size=200';
                                ?>" class="rounded-circle preview-img" alt="Profil Resmi">
                            </div>
                            
                            <div class="upload-controls">
                                <label for="profil_resmi" class="upload-button">
                                    <i class="fas fa-camera me-2"></i>Fotoğraf Seç
                                </label>
                                <input type="file" class="d-none" id="profil_resmi" name="profil_resmi" accept="image/*">
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="rotateLeft">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="rotateRight">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="removeImage">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="upload-info mt-3">
                                <small class="text-muted d-block">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Desteklenen formatlar: JPG, JPEG, PNG, GIF
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fas fa-crop me-1"></i>
                                    Önerilen boyut: 400x400 piksel
                                </small>
                                <small class="text-muted d-block">
                                    <i class="fas fa-weight me-1"></i>
                                    Maksimum dosya boyutu: 5MB
                                </small>
                            </div>
                        </div>
                        
                        <div class="progress mb-3 d-none">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-primary" id="uploadButton">
                                <i class="fas fa-upload me-2"></i>Yükle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('profilResmiForm');
        const input = document.getElementById('profil_resmi');
        const preview = document.getElementById('previewImage');
        const uploadArea = document.querySelector('.upload-area');
        const progress = document.querySelector('.progress');
        const progressBar = document.querySelector('.progress-bar');
        const uploadButton = document.getElementById('uploadButton');
        const rotateLeftBtn = document.getElementById('rotateLeft');
        const rotateRightBtn = document.getElementById('rotateRight');
        const removeBtn = document.getElementById('removeImage');
        
        let rotation = 0;
        
        // Dosya seçme
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                validateAndPreviewImage(file);
            }
        });
        
        // Sürükle-bırak
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('dragover');
            
            const file = e.dataTransfer.files[0];
            if (file) {
                input.files = e.dataTransfer.files;
                validateAndPreviewImage(file);
            }
        });
        
        // Resmi döndürme
        rotateLeftBtn.addEventListener('click', function() {
            rotation -= 90;
            preview.style.transform = `rotate(${rotation}deg)`;
        });
        
        rotateRightBtn.addEventListener('click', function() {
            rotation += 90;
            preview.style.transform = `rotate(${rotation}deg)`;
        });
        
        // Resmi kaldırma
        removeBtn.addEventListener('click', function() {
            input.value = '';
            preview.src = 'https://ui-avatars.com/api/?name=<?php echo urlencode($kullanici['ad_soyad']); ?>&size=200';
            rotation = 0;
            preview.style.transform = '';
        });
        
        // Form gönderme
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (!input.files.length) {
                alert('Lütfen bir resim seçin!');
                return;
            }
            
            const formData = new FormData(this);
            formData.append('rotation', rotation);
            
            progress.classList.remove('d-none');
            uploadButton.disabled = true;
            
            fetch('profil_resmi_yukle.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.error || 'Bir hata oluştu!');
                    progress.classList.add('d-none');
                    uploadButton.disabled = false;
                }
            })
            .catch(error => {
                alert('Bir hata oluştu!');
                progress.classList.add('d-none');
                uploadButton.disabled = false;
            });
        });
        
        function validateAndPreviewImage(file) {
            // Dosya boyutu kontrolü (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Dosya boyutu 5MB\'dan büyük olamaz!');
                input.value = '';
                return;
            }
            
            // Dosya türü kontrolü
            if (!file.type.startsWith('image/')) {
                alert('Lütfen geçerli bir resim dosyası seçin!');
                input.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                rotation = 0;
                preview.style.transform = '';
            }
            reader.readAsDataURL(file);
        }
    });
    </script>
</body>
</html> 