<?php
session_start();
require_once('../baglan.php');
require_once('includes/auth_check.php');

// Sayfa başlığı ve aktif menü
$sayfa_basligi = "Yazılar";
$aktif_sayfa = "yazilar";

// Yardımcı fonksiyonlar
function guvenli_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// CSRF token oluştur
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Silme işlemi
if (isset($_POST['sil']) && isset($_POST['yazi_id']) && isset($_POST['csrf_token'])) {
    error_log("Silme işlemi başlatıldı - POST verisi: " . print_r($_POST, true));

    // CSRF Token kontrolü
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        error_log("CSRF token hatası: " . json_encode($_POST));
        echo json_encode(['status' => 'error', 'message' => 'CSRF token hatası.']);
        exit();
    }

    // Kullanıcı doğrulama
    if (!isset($_SESSION['admin']['id'])) {
        error_log("Yetkisiz erişim: Kullanıcı giriş yapmamış.");
        echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
        exit();
    }

    $yazi_id = filter_input(INPUT_POST, 'yazi_id', FILTER_VALIDATE_INT);
    error_log("Silinecek yazı ID: " . $yazi_id);

    try {
        // Yazının mevcut durumunu kontrol et
        $durum_sql = "SELECT COALESCE(durum, 'aktif') as durum FROM blog_yazilar WHERE id = ?";
        $durum_stmt = $conn->prepare($durum_sql);
        $durum_stmt->bind_param("i", $yazi_id);
        $durum_stmt->execute();
        $durum_result = $durum_stmt->get_result();
        $mevcut_durum = $durum_result->fetch_assoc();
        
        error_log("Yazının mevcut durumu: " . print_r($mevcut_durum, true));

        // Yazının var olduğunu ve kullanıcının yetkisini kontrol et
        $kontrol_sql = "SELECT y.*, COALESCE(y.durum, 'aktif') as durum, u.kullanici_adi 
                       FROM blog_yazilar y 
                       LEFT JOIN kullanicilar u ON y.yazar_id = u.id 
                       WHERE y.id = ?";
        $kontrol_stmt = $conn->prepare($kontrol_sql);
        
        if (!$kontrol_stmt) {
            error_log("Kontrol sorgusu hazırlama hatası: " . $conn->error);
            throw new Exception("Veritabanı sorgusu hazırlanamadı");
        }
        
        $kontrol_stmt->bind_param("i", $yazi_id);
        if (!$kontrol_stmt->execute()) {
            error_log("Kontrol sorgusu çalıştırma hatası: " . $kontrol_stmt->error);
            throw new Exception("Kontrol sorgusu çalıştırılamadı");
        }
        
        $yazi = $kontrol_stmt->get_result()->fetch_assoc();
        error_log("Yazı kontrol sonucu: " . print_r($yazi, true));
        
        if (!$yazi) {
            error_log("Yazı bulunamadı. ID: " . $yazi_id);
            echo json_encode(['status' => 'error', 'message' => 'Yazı bulunamadı.']);
            exit();
        }

        if ($yazi['durum'] === 'silindi') {
            error_log("Yazı zaten silinmiş. ID: " . $yazi_id);
            echo json_encode(['status' => 'error', 'message' => 'Yazı zaten silinmiş.']);
            exit();
        }
        
        $conn->begin_transaction();
        error_log("Transaction başlatıldı");
        
        // Yazıyı sil - durum kontrolünü kaldırdık
        $sql = "UPDATE blog_yazilar SET durum = 'silindi', guncellenme_tarihi = NOW() WHERE id = ?";
        error_log("Silme SQL: " . $sql . " [ID: " . $yazi_id . "]");
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("Silme sorgusu hazırlama hatası: " . $conn->error);
            throw new Exception("Veritabanı sorgusu hazırlanamadı");
        }
        
        $stmt->bind_param("i", $yazi_id);
        if (!$stmt->execute()) {
            error_log("Silme sorgusu çalıştırma hatası: " . $stmt->error);
            throw new Exception("Güncelleme sorgusu çalıştırılamadı");
        }
        
        error_log("Etkilenen satır sayısı: " . $stmt->affected_rows);
        
        if ($stmt->affected_rows === 0) {
            error_log("Yazı güncellenemedi: Etkilenen satır yok. ID: " . $yazi_id);
            throw new Exception("Yazı güncellenemedi: Etkilenen satır yok");
        }
        
        // Güncelleme sonrası durumu kontrol et
        $son_durum_sql = "SELECT durum FROM blog_yazilar WHERE id = ?";
        $son_durum_stmt = $conn->prepare($son_durum_sql);
        $son_durum_stmt->bind_param("i", $yazi_id);
        $son_durum_stmt->execute();
        $son_durum_result = $son_durum_stmt->get_result();
        $son_durum = $son_durum_result->fetch_assoc();
        
        error_log("Yazının son durumu: " . print_r($son_durum, true));
        
        if ($son_durum['durum'] !== 'silindi') {
            throw new Exception("Yazı durumu güncellenemedi");
        }
        
        $conn->commit();
        error_log("Transaction commit edildi");
        echo json_encode(['status' => 'success', 'message' => 'Yazı başarıyla silindi.']);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Yazı silme hatası: " . $e->getMessage());
        echo json_encode(['status' => 'error', 'message' => 'Silme işlemi başarısız: ' . $e->getMessage()]);
    }
    exit();
}

// Filtreleme ve Arama Parametreleri
$filtreler = [
    'kategori' => isset($_GET['kategori']) ? (int)$_GET['kategori'] : null,
    'arama' => isset($_GET['arama']) ? guvenli_input($_GET['arama']) : null,
    'sirala' => isset($_GET['sirala']) ? guvenli_input($_GET['sirala']) : 'tarih',
    'sayfa' => isset($_GET['sayfa']) ? max(1, (int)$_GET['sayfa']) : 1
];

// SQL sorgusu oluşturma
$where = ["y.durum != 'silindi'"];
$params = [];
$types = "";

if ($filtreler['kategori']) {
    $where[] = "y.kategori_id = ?";
    $params[] = $filtreler['kategori'];
    $types .= "i";
}

if ($filtreler['arama']) {
    $where[] = "(y.baslik LIKE ? OR y.icerik LIKE ?)";
    $arama_terimi = "%" . $filtreler['arama'] . "%";
    $params[] = $arama_terimi;
    $params[] = $arama_terimi;
    $types .= "ss";
}

$where_clause = implode(" AND ", $where);

// Sıralama
$siralama_secenekleri = [
    'tarih' => 'y.tarih DESC',
    'baslik' => 'y.baslik ASC',
    'goruntulenme' => 'y.goruntulenme DESC',
    'yorum' => 'yorum_sayisi DESC'
];

$order = $siralama_secenekleri[$filtreler['sirala']] ?? 'y.tarih DESC';

// Sayfalama
$limit = 10;
$offset = ($filtreler['sayfa'] - 1) * $limit;

// Toplam yazı sayısı
$count_sql = "SELECT COUNT(*) as toplam FROM blog_yazilar y WHERE $where_clause";
$stmt = $conn->prepare($count_sql);
if (!empty($types)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$toplam = $stmt->get_result()->fetch_assoc()['toplam'];
$toplam_sayfa = ceil($toplam / $limit);

// Yazıları getir
$sql = "SELECT 
            y.*, 
            k.kategori_adi,
            u.kullanici_adi as yazar_adi,
            (SELECT COUNT(*) FROM yorumlar WHERE yazi_id = y.id AND durum = 'onaylandi') as yorum_sayisi
        FROM blog_yazilar y
        LEFT JOIN kategoriler k ON y.kategori_id = k.id
        LEFT JOIN kullanicilar u ON y.yazar_id = u.id
        WHERE $where_clause
        ORDER BY $order
        LIMIT ? OFFSET ?";

// Debug için log ekleyelim
error_log("SQL Sorgusu: " . $sql);
error_log("Where Clause: " . $where_clause);
error_log("Params: " . print_r($params, true));

$stmt = $conn->prepare($sql);
$types .= "ii";
$params[] = $limit;
$params[] = $offset;
$stmt->bind_param($types, ...$params);
$stmt->execute();
$yazilar = $stmt->get_result();

// Kategorileri getir
$kategoriler = $conn->query("SELECT id, kategori_adi, (SELECT COUNT(*) FROM blog_yazilar WHERE kategori_id = kategoriler.id AND durum != 'silindi') as yazi_sayisi FROM kategoriler ORDER BY kategori_adi ASC");

// Cache-Control header'ı ekle
header('Cache-Control: private, must-revalidate');
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $sayfa_basligi; ?> - Blog Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4caf50;
            --info-color: #2196f3;
            --warning-color: #ff9800;
            --danger-color: #f44336;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            min-height: 100vh;
            width: 260px;
            transition: all 0.3s ease;
            position: fixed;
            z-index: 1000;
        }
        
        .sidebar-brand {
            padding: 1.5rem;
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8) !important;
            padding: 0.8rem 1.5rem;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .nav-link:hover {
            color: white !important;
            background: rgba(255,255,255,0.1);
        }
        
        .nav-link.active {
            color: white !important;
            background: rgba(255,255,255,0.2);
        }
        
        .nav-badge {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255,255,255,0.2);
            padding: 0.2rem 0.6rem;
            border-radius: 30px;
            font-size: 0.75rem;
        }
        
        .main-content {
            margin-left: 260px;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .yazi-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .yazi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .yazi-baslik {
            color: #2c3e50;
            text-decoration: none;
            font-weight: 600;
            line-height: 1.4;
        }
        
        .yazi-meta {
            font-size: 0.85rem;
            color: #6c757d;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .yazi-meta i {
            width: 16px;
            text-align: center;
        }
        
        .yazi-ozet {
            font-size: 0.95rem;
            color: #4a5568;
            line-height: 1.6;
            margin: 1rem 0;
        }
        
        .etiket {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            background: #f8f9fa;
            color: #495057;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .filtre-panel {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .btn-primary {
            background: #4361ee;
            border-color: #4361ee;
        }
        
        .btn-primary:hover {
            background: #3a54d6;
            border-color: #3a54d6;
        }
        
        .pagination .page-link {
            color: #4361ee;
        }
        
        .pagination .active .page-link {
            background: #4361ee;
            border-color: #4361ee;
        }
        
        .alert {
            border-radius: 12px;
        }
        
        .input-group .form-control:focus,
        .form-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
            border-color: #4361ee;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Ana İçerik -->
            <div class="col p-0">
                <div class="main-content p-4">
                    <!-- Üst Bar -->
                    <div class="d-flex justify-content-between align-items-center bg-white p-3 mb-4 rounded-3 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <button type="button" class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#yeniYaziModal">
                                <i class="fas fa-pen-to-square me-2"></i>Yeni Yazı Ekle
                            </button>
                        </div>
                        
                        <div class="dropdown">
                            <div class="d-flex align-items-center cursor-pointer" data-bs-toggle="dropdown" style="cursor: pointer;">
                                <div class="text-end me-2">
                                    <div class="fw-semibold"><?php echo $_SESSION['admin']['ad_soyad']; ?></div>
                                    <div class="text-muted small"><?php echo $_SESSION['admin']['unvan']; ?></div>
                                </div>
                                <?php if ($_SESSION['admin']['profil_resmi']): ?>
                                    <img src="<?php echo $_SESSION['admin']['profil_resmi']; ?>" 
                                         alt="<?php echo $_SESSION['admin']['ad_soyad']; ?>" 
                                         class="rounded-circle"
                                         style="width: 40px; height: 40px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px; font-weight: 500;">
                                        <?php echo strtoupper(mb_substr($_SESSION['admin']['ad'], 0, 1)); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <ul class="dropdown-menu dropdown-menu-end mt-2 border-0 shadow-sm">
                                <li>
                                    <a class="dropdown-item py-2 px-4" href="profil.php">
                                        <i class="fas fa-user me-2 text-muted"></i>Profil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 px-4" href="ayarlar.php">
                                        <i class="fas fa-cog me-2 text-muted"></i>Ayarlar
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item py-2 px-4 text-danger" href="cikis.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Filtreler -->
                    <div class="filtre-panel">
                        <form method="get" class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" name="arama" 
                                           placeholder="Yazılarda ara..." value="<?php echo $filtreler['arama']; ?>">
                                </div>
                            </div>
                            
                            <div class="col-md-3">
                                <select class="form-select" name="kategori">
                                    <option value="">Tüm Kategoriler</option>
                                    <?php while ($kategori = $kategoriler->fetch_assoc()): ?>
                                    <option value="<?php echo $kategori['id']; ?>" 
                                            <?php echo ($filtreler['kategori'] == $kategori['id']) ? 'selected' : ''; ?>>
                                        <?php echo guvenli_input($kategori['kategori_adi']); ?> 
                                        (<?php echo $kategori['yazi_sayisi']; ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <select class="form-select" name="sirala">
                                    <option value="tarih" <?php echo ($filtreler['sirala'] == 'tarih') ? 'selected' : ''; ?>>
                                        En Yeni
                                    </option>
                                    <option value="baslik" <?php echo ($filtreler['sirala'] == 'baslik') ? 'selected' : ''; ?>>
                                        Başlığa Göre
                                    </option>
                                    <option value="goruntulenme" <?php echo ($filtreler['sirala'] == 'goruntulenme') ? 'selected' : ''; ?>>
                                        En Çok Görüntülenen
                                    </option>
                                    <option value="yorum" <?php echo ($filtreler['sirala'] == 'yorum') ? 'selected' : ''; ?>>
                                        En Çok Yorumlanan
                                    </option>
                                </select>
                            </div>
                            
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-filter me-2"></i>Filtrele
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Yazılar -->
                    <div class="row g-4">
                        <?php if ($yazilar->num_rows == 0): ?>
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>Gösterilecek yazı bulunamadı.
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php while ($yazi = $yazilar->fetch_assoc()): ?>
                        <div class="col-12 mb-4" data-yazi-id="<?php echo $yazi['id']; ?>">
                            <div class="card yazi-card">
                                <div class="card-body">
                                    <h5 class="mb-3">
                                        <a href="../yazi.php?id=<?php echo $yazi['id']; ?>" class="yazi-baslik" target="_blank">
                                            <?php echo guvenli_input($yazi['baslik']); ?>
                                        </a>
                                    </h5>
                                    
                                    <div class="yazi-meta mb-3">
                                        <span><i class="fas fa-calendar"></i><?php echo date('d.m.Y', strtotime($yazi['tarih'])); ?></span>
                                        <span><i class="fas fa-user"></i><?php echo guvenli_input($yazi['yazar_adi']); ?></span>
                                        <span><i class="fas fa-eye"></i><?php echo number_format($yazi['goruntulenme']); ?> görüntülenme</span>
                                        <span><i class="fas fa-comments"></i><?php echo $yazi['yorum_sayisi']; ?> yorum</span>
                                    </div>
                                    
                                    <p class="yazi-ozet">
                                        <?php echo guvenli_input(mb_substr(strip_tags($yazi['icerik']), 0, 150)) . '...'; ?>
                                    </p>
                                    
                                    <div class="yazi-etiketler mb-3">
                                        <span class="etiket">
                                            <i class="fas fa-folder"></i>
                                            <?php echo guvenli_input($yazi['kategori_adi'] ?? 'Kategorisiz'); ?>
                                        </span>
                                        <?php if (!empty($yazi['etiketler'])): 
                                            $etiketler = explode(',', $yazi['etiketler']);
                                            foreach ($etiketler as $etiket): 
                                                if (trim($etiket) !== ''): ?>
                                                <span class="etiket">
                                                    <i class="fas fa-tag"></i>
                                                    <?php echo guvenli_input(trim($etiket)); ?>
                                                </span>
                                                <?php endif;
                                            endforeach;
                                        endif; ?>
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <a href="yazi-duzenle.php?id=<?php echo $yazi['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit me-1"></i>Düzenle
                                        </a>
                                        
                                        <button type="button" 
                                                class="btn btn-sm btn-danger" 
                                                onclick="yaziSil(<?php echo $yazi['id']; ?>, '<?php echo addslashes($yazi['baslik']); ?>')">
                                            <i class="fas fa-trash-alt me-1"></i>Sil
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    
                    <!-- Sayfalama -->
                    <?php if ($toplam_sayfa > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                            <li class="page-item <?php echo $filtreler['sayfa'] == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?sayfa=<?php echo $i; 
                                    echo $filtreler['kategori'] ? '&kategori=' . $filtreler['kategori'] : '';
                                    echo $filtreler['arama'] ? '&arama=' . urlencode($filtreler['arama']) : '';
                                    echo $filtreler['sirala'] ? '&sirala=' . $filtreler['sirala'] : '';
                                ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mesaj gösterimi
        <?php if (isset($_GET['mesaj']) || isset($_GET['hata'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const mesajTipi = <?php echo isset($_GET['mesaj']) ? "'success'" : "'danger'"; ?>;
            const mesajMetni = <?php 
                if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'silindi') {
                    echo "'Yazı başarıyla silindi'";
                } elseif (isset($_GET['hata']) && $_GET['hata'] == 'silinemedi') {
                    echo "'Yazı silinemedi'";
                } else {
                    echo "''";
                }
            ?>;
            
            if (mesajMetni) {
                const alert = document.createElement('div');
                alert.className = `alert alert-${mesajTipi} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
                alert.innerHTML = `
                    <i class="fas fa-${mesajTipi == 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${mesajMetni}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                `;
                document.body.appendChild(alert);
                
                setTimeout(() => alert.remove(), 3000);
            }
        });
        <?php endif; ?>
    </script>

    <!-- Silme Modal -->
    <div class="modal" id="silModal" tabindex="-1" role="dialog" aria-labelledby="silModalLabel" inert>
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="silModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Yazıyı Sil
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Bu yazıyı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!</p>
                    <div class="alert alert-warning mt-3 mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Silinen yazı geri dönüşüm kutusuna taşınacak ve daha sonra geri yüklenebilir.
                    </div>
                </div>
                <div class="modal-footer">
                    <form id="silForm" method="post" class="w-100 d-flex justify-content-end gap-2">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="yazi_id" id="silinecekYaziId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>İptal
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash-alt me-2"></i>Evet, Sil
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Yeni Yazı Modal -->
    <div class="modal" id="yeniYaziModal" tabindex="-1" role="dialog" aria-labelledby="yeniYaziModalLabel" inert>
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="yeniYaziModalLabel">
                        <i class="fas fa-pen-to-square me-2"></i>
                        Yeni Yazı Ekle
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        <i class="fas fa-edit me-2"></i>
                                        Editör ile Yaz
                                    </h6>
                                    <p class="text-muted small mb-3">
                                        Zengin metin editörü ile yazınızı oluşturun, resim ve medya ekleyin.
                                    </p>
                                    <a href="yazi-ekle.php" class="btn btn-primary w-100">
                                        <i class="fas fa-pen me-2"></i>
                                        Editör ile Başla
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        <i class="fas fa-file-import me-2"></i>
                                        Taslak Yükle
                                    </h6>
                                    <p class="text-muted small mb-3">
                                        Hazır bir taslak dosyasından yazınızı içe aktarın.
                                    </p>
                                    <a href="yazi-yukle.php" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-upload me-2"></i>
                                        Taslak Yükle
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Kapat
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function yaziSil(yaziId, yaziBaslik) {
        const silModal = document.getElementById('silModal');
        const silButton = document.querySelector(`[data-yazi-id="${yaziId}"] .btn-danger`);
        
        silModal.removeAttribute('inert');
        document.getElementById('silinecekYaziId').value = yaziId;
        
        const modal = new bootstrap.Modal(silModal);
        modal.show();
        
        silModal.addEventListener('hidden.bs.modal', function () {
            silModal.setAttribute('inert', '');
            if (silButton) {
                silButton.focus();
            }
        }, { once: true });
    }

    document.getElementById('yeniYaziModal').addEventListener('show.bs.modal', function () {
        this.removeAttribute('inert');
    });

    document.getElementById('yeniYaziModal').addEventListener('hidden.bs.modal', function () {
        this.setAttribute('inert', '');
        document.querySelector('[data-bs-target="#yeniYaziModal"]').focus();
    });

    document.getElementById('silForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('sil', '1');
        const yaziId = formData.get('yazi_id');
        
        // Form verilerini kontrol et
        if (!yaziId) {
            console.error('Yazı ID bulunamadı');
            showAlert('danger', 'Yazı ID bulunamadı');
            return;
        }
        
        if (!formData.get('csrf_token')) {
            console.error('CSRF token bulunamadı');
            showAlert('danger', 'Güvenlik doğrulaması başarısız');
            return;
        }
        
        fetch('yazi-sil.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const silModal = bootstrap.Modal.getInstance(document.getElementById('silModal'));
            silModal.hide();
            
            if (data.status === 'success') {
                const yaziCard = document.querySelector(`[data-yazi-id="${yaziId}"]`);
                if (yaziCard) {
                    yaziCard.remove();
                    showAlert('success', data.message);
                    updateRecycleBinCount(1);
                }
            } else {
                console.error('Silme hatası:', data.message);
                showAlert('danger', data.message);
            }
        })
        .catch(error => {
            console.error('Silme işlemi hatası:', error);
            showAlert('danger', 'Silme işlemi sırasında bir hata oluştu');
        });
    });

    function showAlert(type, message) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        const mainContent = document.querySelector('.main-content');
        const firstChild = mainContent.firstChild;
        mainContent.insertBefore(alert, firstChild);
        
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.remove();
            }
        }, 3000);
    }

    function updateRecycleBinCount(increment) {
        const copKutusuLink = document.querySelector('a[href="cop_kutusu.php"]');
        if (copKutusuLink) {
            let copKutusuBadge = copKutusuLink.querySelector('.nav-badge');
            if (copKutusuBadge) {
                const yeniSayi = parseInt(copKutusuBadge.textContent) + increment;
                copKutusuBadge.textContent = yeniSayi;
            } else {
                copKutusuBadge = document.createElement('span');
                copKutusuBadge.className = 'nav-badge';
                copKutusuBadge.textContent = '1';
                copKutusuLink.appendChild(copKutusuBadge);
            }
        }
    }
    </script>
</body>
</html> 