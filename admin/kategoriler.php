<?php
session_start();
require_once('../baglan.php');
require_once('includes/auth_check.php');

// Sayfa başlığı ve aktif menü
$sayfa_basligi = "Kategoriler";
$aktif_sayfa = "kategoriler";

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Kategori silme işlemi
if (isset($_GET['sil']) && is_numeric($_GET['sil'])) {
    $kategori_id = (int)$_GET['sil'];
    
    // Önce kategori adını al
    $stmt = $db->prepare("SELECT kategori_adi FROM kategoriler WHERE id = ?");
    $stmt->execute([$kategori_id]);
    $kategori = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$kategori) {
        header("Location: kategoriler.php?mesaj=hata");
        exit();
    }
    
    // Önce bu kategorideki yazıları varsayılan kategoriye taşı
    $sql = "UPDATE blog_yazilar SET kategori_id = 1 WHERE kategori_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$kategori_id]);
    
    // Sonra kategoriyi sil
    $sql = "DELETE FROM kategoriler WHERE id = ?";
    $stmt = $db->prepare($sql);
    
    if ($stmt->execute([$kategori_id])) {
        // Aktivite kaydı
        aktivite_kaydet(
            $_SESSION['admin_id'], 
            AKTIVITE_KATEGORI_SILME,
            "\"" . $kategori['kategori_adi'] . "\" kategorisi silindi",
            'kategoriler'
        );
        header("Location: kategoriler.php?mesaj=silindi");
    } else {
        header("Location: kategoriler.php?mesaj=hata");
    }
    exit();
}

// Kategori ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['yeni_kategori'])) {
    $kategori_adi = trim($_POST['kategori_adi']);
    $seo_url = createSlug($kategori_adi);
    $aciklama = trim($_POST['aciklama']);
    
    $sql = "INSERT INTO kategoriler (kategori_adi, seo_url, aciklama) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$kategori_adi, $seo_url, $aciklama])) {
        // Aktivite kaydı
        aktivite_kaydet(
            $_SESSION['admin_id'],
            AKTIVITE_KATEGORI_EKLEME,
            "\"" . $kategori_adi . "\" kategorisi eklendi",
            'kategoriler'
        );
        header("Location: kategoriler.php?mesaj=eklendi");
    } else {
        header("Location: kategoriler.php?mesaj=hata");
    }
    exit();
}

// Sıralama güncelleme işlemi
if (isset($_POST['siralama'])) {
    $siralar = $_POST['siralama'];
    foreach ($siralar as $id => $sira) {
        $sql = "UPDATE kategoriler SET sira = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $sira, $id);
        $stmt->execute();
    }
    header("Location: kategoriler.php?mesaj=siralandi");
    exit();
}

// Kategori düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kategori_id'])) {
    $kategori_id = (int)$_POST['kategori_id'];
    $kategori_adi = trim($_POST['kategori_adi']);
    $seo_url = createSlug($kategori_adi);
    $aciklama = trim($_POST['aciklama']);
    
    $sql = "UPDATE kategoriler SET kategori_adi = ?, seo_url = ?, aciklama = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt->execute([$kategori_adi, $seo_url, $aciklama, $kategori_id])) {
        // Aktivite kaydı
        aktivite_kaydet(
            $_SESSION['admin_id'],
            AKTIVITE_KATEGORI_DUZENLEME,
            "\"" . $kategori_adi . "\" kategorisi güncellendi",
            'kategoriler'
        );
        header("Location: kategoriler.php?mesaj=guncellendi");
    } else {
        header("Location: kategoriler.php?mesaj=hata");
    }
    exit();
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

// Sayfalama
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 20;
$offset = ($sayfa - 1) * $limit;

// Arama
$arama = isset($_GET['arama']) ? trim($_GET['arama']) : '';
$where = '';
$params = [];
$types = '';

if (!empty($arama)) {
    $where = "WHERE kategori_adi LIKE ? OR aciklama LIKE ?";
    $params[] = "%$arama%";
    $params[] = "%$arama%";
    $types .= 'ss';
}

// Toplam kategori sayısı
$sql = "SELECT COUNT(*) as total FROM kategoriler " . $where;
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_rows = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_rows = $conn->query($sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_rows / $limit);

// Kategorileri getir (sıralama değişti)
$sql = "SELECT k.*, COUNT(y.id) as yazi_sayisi 
        FROM kategoriler k 
        LEFT JOIN blog_yazilar y ON k.id = y.kategori_id 
        " . $where . "
        GROUP BY k.id 
        ORDER BY k.sira ASC 
        LIMIT ? OFFSET ?";

$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$kategoriler = $stmt->get_result();
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
        
        .main-content {
            margin-left: 260px;
            padding: 2rem;
            transition: all 0.3s ease;
        }
        
        .card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .btn-primary {
            background: #4361ee;
            border-color: #4361ee;
        }
        
        .btn-primary:hover {
            background: #3a54d6;
            border-color: #3a54d6;
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
                <div class="main-content">
                    <!-- Üst Bar -->
                    <div class="d-flex justify-content-between align-items-center bg-white p-3 mb-4 rounded-3 shadow-sm">
                        <div class="d-flex align-items-center gap-3">
                            <button type="button" class="btn btn-primary rounded-3" data-bs-toggle="modal" data-bs-target="#yeniKategoriModal">
                                <i class="fas fa-folder-plus me-2"></i>Yeni Kategori
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
                    
                    <?php if (isset($_GET['mesaj'])): ?>
                    <div class="alert alert-<?php echo $_GET['mesaj'] === 'silindi' || $_GET['mesaj'] === 'eklendi' || $_GET['mesaj'] === 'guncellendi' || $_GET['mesaj'] === 'siralandi' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php
                        switch ($_GET['mesaj']) {
                            case 'silindi':
                                echo 'Kategori başarıyla silindi.';
                                break;
                            case 'eklendi':
                                echo 'Kategori başarıyla eklendi.';
                                break;
                            case 'guncellendi':
                                echo 'Kategori başarıyla güncellendi.';
                                break;
                            case 'siralandi':
                                echo 'Kategori sıralaması başarıyla güncellendi.';
                                break;
                            case 'hata':
                                echo 'Bir hata oluştu. Lütfen tekrar deneyin.';
                                break;
                        }
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Arama Formu -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form action="" method="GET" class="row g-3">
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" name="arama" placeholder="Kategori ara..." value="<?php echo htmlspecialchars($arama); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">Ara</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Kategori Listesi -->
                    <form action="" method="POST" id="siralamaForm">
                        <div class="card">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th width="80">Sıra</th>
                                            <th>Kategori</th>
                                            <th>SEO URL</th>
                                            <th>Açıklama</th>
                                            <th>Yazı Sayısı</th>
                                            <th width="150">İşlemler</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($kategori = $kategoriler->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <input type="number" class="form-control form-control-sm" 
                                                       name="siralama[<?php echo $kategori['id']; ?>]" 
                                                       value="<?php echo $kategori['sira']; ?>" 
                                                       min="1" 
                                                       style="width: 70px;">
                                            </td>
                                            <td><?php echo htmlspecialchars($kategori['kategori_adi']); ?></td>
                                            <td><?php echo htmlspecialchars($kategori['seo_url']); ?></td>
                                            <td><?php echo htmlspecialchars($kategori['aciklama']); ?></td>
                                            <td><?php echo $kategori['yazi_sayisi']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#duzenleModal" 
                                                            data-id="<?php echo $kategori['id']; ?>"
                                                            data-ad="<?php echo htmlspecialchars($kategori['kategori_adi']); ?>"
                                                            data-aciklama="<?php echo htmlspecialchars($kategori['aciklama']); ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($kategori['id'] != 1): ?>
                                                    <a href="javascript:void(0)" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="kategoriSil(<?php echo $kategori['id']; ?>, '<?php echo htmlspecialchars($kategori['kategori_adi']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i>Sıralamayı Kaydet
                            </button>
                        </div>
                    </form>
                    
                    <!-- Sayfalama -->
                    <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <li class="page-item <?php echo $sayfa <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?sayfa=<?php echo $sayfa - 1; ?><?php echo !empty($arama) ? '&arama=' . urlencode($arama) : ''; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $sayfa == $i ? 'active' : ''; ?>">
                                <a class="page-link" href="?sayfa=<?php echo $i; ?><?php echo !empty($arama) ? '&arama=' . urlencode($arama) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <li class="page-item <?php echo $sayfa >= $total_pages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="?sayfa=<?php echo $sayfa + 1; ?><?php echo !empty($arama) ? '&arama=' . urlencode($arama) : ''; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Yeni Kategori Modal -->
    <div class="modal fade" id="yeniKategoriModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Kategori Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="yeni_kategori_adi" class="form-label">Kategori Adı</label>
                            <input type="text" class="form-control" id="yeni_kategori_adi" name="kategori_adi" required>
                        </div>
                        <div class="mb-3">
                            <label for="yeni_aciklama" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="yeni_aciklama" name="aciklama" rows="3"></textarea>
                        </div>
                        <input type="hidden" name="yeni_kategori" value="1">
                        <button type="submit" class="btn btn-primary">Kategori Ekle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Düzenleme Modal -->
    <div class="modal fade" id="duzenleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Kategori Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="kategori_adi" class="form-label">Kategori Adı</label>
                            <input type="text" class="form-control" id="kategori_adi" name="kategori_adi" required>
                        </div>
                        <div class="mb-3">
                            <label for="kategori_aciklama" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="kategori_aciklama" name="aciklama" rows="3"></textarea>
                        </div>
                        <input type="hidden" id="kategori_id" name="kategori_id">
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    // Sidebar Toggle İşlevi
    document.getElementById('toggleSidebar').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });

    // Kategori Silme İşlevi
    function kategoriSil(id, ad) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: `"${ad}" kategorisi silinecek ve içindeki yazılar varsayılan kategoriye taşınacak!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `kategoriler.php?sil=${id}`;
            }
        });
    }

    // Düzenleme Modal
    document.getElementById('duzenleModal').addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var ad = button.getAttribute('data-ad');
        var aciklama = button.getAttribute('data-aciklama');
        
        this.querySelector('#kategori_id').value = id;
        this.querySelector('#kategori_adi').value = ad;
        this.querySelector('#kategori_aciklama').value = aciklama;
    });

    // Sıralama değişikliğinde otomatik kaydetme
    document.querySelectorAll('input[name^="siralama"]').forEach(input => {
        input.addEventListener('change', function() {
            document.getElementById('siralamaForm').submit();
        });
    });
    </script>
</body>
</html> 