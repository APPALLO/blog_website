<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Etiket silme işlemi
if (isset($_GET['sil']) && is_numeric($_GET['sil'])) {
    $etiket_id = (int)$_GET['sil'];
    
    // Önce etiket-yazı ilişkilerini sil
    $sql = "DELETE FROM yazi_etiketler WHERE etiket_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $etiket_id);
    $stmt->execute();
    
    // Sonra etiketi sil
    $sql = "DELETE FROM etiketler WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $etiket_id);
    
    if ($stmt->execute()) {
        header("Location: etiketler.php?mesaj=silindi");
    } else {
        header("Location: etiketler.php?mesaj=hata");
    }
    exit();
}

// Etiket düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['etiket_id'])) {
    $etiket_id = (int)$_POST['etiket_id'];
    $etiket_adi = trim($_POST['etiket_adi']);
    $seo_url = createSlug($etiket_adi);
    
    $sql = "UPDATE etiketler SET etiket_adi = ?, seo_url = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $etiket_adi, $seo_url, $etiket_id);
    
    if ($stmt->execute()) {
        header("Location: etiketler.php?mesaj=guncellendi");
    } else {
        header("Location: etiketler.php?mesaj=hata");
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
    $where = "WHERE etiket_adi LIKE ?";
    $params[] = "%$arama%";
    $types .= 's';
}

// Toplam etiket sayısı
$sql = "SELECT COUNT(*) as total FROM etiketler " . $where;
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_rows = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_rows = $conn->query($sql)->fetch_assoc()['total'];
}

$total_pages = ceil($total_rows / $limit);

// Etiketleri getir
$sql = "SELECT e.*, COUNT(ye.yazi_id) as yazi_sayisi 
        FROM etiketler e 
        LEFT JOIN yazi_etiketler ye ON e.id = ye.etiket_id 
        " . $where . "
        GROUP BY e.id 
        ORDER BY e.kullanim_sayisi DESC, e.etiket_adi ASC 
        LIMIT ? OFFSET ?";

$types .= 'ii';
$params[] = $limit;
$params[] = $offset;

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$etiketler = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiketler - Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
    <!-- Mobil Menü Butonu -->
    <button class="toggle-sidebar" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-auto p-0">
                <div class="sidebar" id="sidebar">
                    <div class="sidebar-brand">
                        <i class="fas fa-newspaper me-2"></i>Blog Admin
                    </div>
                    <nav class="nav flex-column">
                        <a class="nav-link" href="panel.php">
                            <i class="fas fa-home me-2"></i>Ana Sayfa
                        </a>
                        <a class="nav-link" href="yazilar.php">
                            <i class="fas fa-file-alt me-2"></i>Yazılar
                        </a>
                        <a class="nav-link" href="kategoriler.php">
                            <i class="fas fa-tags me-2"></i>Kategoriler
                        </a>
                        <a class="nav-link active" href="etiketler.php">
                            <i class="fas fa-hashtag me-2"></i>Etiketler
                        </a>
                        <a class="nav-link" href="yorumlar.php">
                            <i class="fas fa-comments me-2"></i>Yorumlar
                        </a>
                        <a class="nav-link" href="kullanicilar.php">
                            <i class="fas fa-users me-2"></i>Kullanıcılar
                        </a>
                        <a class="nav-link" href="ayarlar.php">
                            <i class="fas fa-cog me-2"></i>Ayarlar
                        </a>
                        <a class="nav-link text-danger" href="cikis.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Ana İçerik -->
            <div class="col p-0">
                <div class="main-content">
                    <!-- Üst Bar -->
                    <div class="top-bar">
                        <h2 class="h4 mb-0">Etiketler</h2>
                        
                        <div class="d-flex align-items-center gap-3">
                            <div class="user-menu dropdown">
                                <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($_SESSION['admin_kullanici_adi'], 0, 1)); ?>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                                    <li><a class="dropdown-item" href="ayarlar.php"><i class="fas fa-cog me-2"></i>Ayarlar</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="cikis.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (isset($_GET['mesaj'])): ?>
                    <div class="alert alert-<?php echo $_GET['mesaj'] === 'silindi' || $_GET['mesaj'] === 'guncellendi' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                        <?php
                        switch ($_GET['mesaj']) {
                            case 'silindi':
                                echo 'Etiket başarıyla silindi.';
                                break;
                            case 'guncellendi':
                                echo 'Etiket başarıyla güncellendi.';
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
                                        <input type="text" class="form-control" name="arama" placeholder="Etiket ara..." value="<?php echo htmlspecialchars($arama); ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">Ara</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Etiket Listesi -->
                    <div class="card">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th width="40">#</th>
                                        <th>Etiket</th>
                                        <th>SEO URL</th>
                                        <th>Yazı Sayısı</th>
                                        <th>Kullanım</th>
                                        <th>Oluşturma</th>
                                        <th width="150">İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($etiket = $etiketler->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $etiket['id']; ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <?php echo htmlspecialchars($etiket['etiket_adi']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($etiket['seo_url']); ?></td>
                                        <td><?php echo $etiket['yazi_sayisi']; ?></td>
                                        <td><?php echo $etiket['kullanim_sayisi']; ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($etiket['olusturma_tarihi'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#duzenleModal" 
                                                        data-id="<?php echo $etiket['id']; ?>"
                                                        data-ad="<?php echo htmlspecialchars($etiket['etiket_adi']); ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="javascript:void(0)" 
                                                   class="btn btn-sm btn-danger" 
                                                   onclick="etiketSil(<?php echo $etiket['id']; ?>, '<?php echo htmlspecialchars($etiket['etiket_adi']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
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
    
    <!-- Düzenleme Modal -->
    <div class="modal fade" id="duzenleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST">
                    <input type="hidden" name="etiket_id" id="etiket_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">Etiket Düzenle</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Etiket Adı</label>
                            <input type="text" class="form-control" name="etiket_adi" id="etiket_adi" required>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
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

    // Etiket Silme İşlevi
    function etiketSil(id, ad) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: `"${ad}" etiketi kalıcı olarak silinecek!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `etiketler.php?sil=${id}`;
            }
        });
    }

    // Düzenleme Modal
    document.getElementById('duzenleModal').addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var ad = button.getAttribute('data-ad');
        
        this.querySelector('#etiket_id').value = id;
        this.querySelector('#etiket_adi').value = ad;
    });
    </script>
</body>
</html> 