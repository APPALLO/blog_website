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

// Yazıları silme işlemi
if (isset($_POST['sil']) && isset($_POST['yazi_id'])) {
    $yazi_id = (int)$_POST['yazi_id'];
    
    // Yazının kullanıcıya ait olduğunu kontrol et
    $sql = "SELECT id FROM blog_yazilar WHERE id = ? AND yazar_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $yazi_id, $_SESSION['kullanici_id']);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $sql = "DELETE FROM blog_yazilar WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $yazi_id);
        
        if ($stmt->execute()) {
            $_SESSION['basari'] = "Yazı başarıyla silindi.";
        } else {
            $_SESSION['hata'] = "Yazı silinirken bir hata oluştu.";
        }
    }
    
    header("Location: yazilarim.php");
    exit();
}

// Filtreleme parametreleri
$durum = isset($_GET['durum']) ? $_GET['durum'] : '';
$kategori_id = isset($_GET['kategori_id']) ? (int)$_GET['kategori_id'] : 0;
$arama = isset($_GET['arama']) ? trim($_GET['arama']) : '';

// Sayfalama için değişkenler
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 10;
$offset = ($sayfa - 1) * $limit;

// WHERE koşullarını oluştur
$where = ["b.yazar_id = " . $_SESSION['kullanici_id']];
if ($durum) {
    $where[] = "b.durum = '" . $conn->real_escape_string($durum) . "'";
}
if ($kategori_id) {
    $where[] = "b.kategori_id = " . $kategori_id;
}
if ($arama) {
    $where[] = "(b.baslik LIKE '%" . $conn->real_escape_string($arama) . "%' OR b.icerik LIKE '%" . $conn->real_escape_string($arama) . "%')";
}
$where_clause = implode(" AND ", $where);

// Toplam yazı sayısını al
$sql = "SELECT COUNT(*) as total FROM blog_yazilar b WHERE " . $where_clause;
$total = $conn->query($sql)->fetch_assoc()['total'];
$total_pages = ceil($total / $limit);

// Yazıları getir
$sql = "SELECT b.*, k.kategori_adi, 
        (SELECT COUNT(*) FROM yorumlar y WHERE y.yazi_id = b.id AND y.durum = 'onaylanmis') as yorum_sayisi
        FROM blog_yazilar b 
        LEFT JOIN kategoriler k ON b.kategori_id = k.id 
        WHERE " . $where_clause . " 
        ORDER BY b.tarih DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$yazilar = $stmt->get_result();

// Kategorileri getir
$sql = "SELECT * FROM kategoriler ORDER BY kategori_adi ASC";
$kategoriler = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yazılarım - Blog Sitesi</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4F46E5;
            --secondary-color: #9B2C2C;
            --accent-color: #6366F1;
            --success-color: #059669;
            --warning-color: #FBBF24;
            --danger-color: #DC2626;
            --gray-dark: #374151;
            --gray-medium: #9CA3AF;
            --gray-light: #F3F4F6;
            --white: #FFFFFF;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB;
            color: var(--gray-dark);
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            padding: 3rem 0;
            margin-bottom: 3rem;
            color: var(--white);
            border-radius: 0 0 2rem 2rem;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><circle cx="2" cy="2" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.3;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .form-control, .form-select {
            border: 2px solid var(--gray-light);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.1);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.75rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
        }

        .btn-primary:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .table {
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .table th {
            background: var(--gray-light);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
            padding: 1rem;
            border: none;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-light);
        }

        .table tr:hover {
            background: rgba(99, 102, 241, 0.05);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 500;
        }

        .badge.bg-success {
            background: var(--success-color) !important;
        }

        .badge.bg-warning {
            background: var(--warning-color) !important;
            color: var(--gray-dark);
        }

        .btn-group .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
        }

        .btn-outline-danger {
            color: var(--danger-color);
            border-color: var(--danger-color);
        }

        .btn-outline-danger:hover {
            background: var(--danger-color);
            color: var(--white);
            transform: translateY(-2px);
        }

        .alert {
            border: none;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            background: var(--white);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .alert-success {
            background: rgba(5, 150, 105, 0.1);
            color: var(--success-color);
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
        }

        .alert-info {
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
        }

        .pagination {
            gap: 0.5rem;
        }

        .page-link {
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            color: var(--gray-dark);
            transition: all 0.3s ease;
        }

        .page-link:hover {
            background: var(--gray-light);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .page-item.active .page-link {
            background: var(--primary-color);
            color: var(--white);
        }

        .stats-card {
            background: var(--white);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="display-4 fw-bold mb-3">Yazılarım</h1>
                    <p class="lead mb-0">Tüm yazılarınızı buradan yönetebilirsiniz.</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="yazi_ekle.php" class="btn btn-light btn-lg">
                        <i class="fas fa-plus me-2"></i>Yeni Yazı Ekle
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- İstatistikler -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="h5 mb-2">Toplam Yazı</h3>
                    <p class="h2 mb-0"><?php echo $total; ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: rgba(5, 150, 105, 0.1); color: var(--success-color);">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3 class="h5 mb-2">Yayında</h3>
                    <p class="h2 mb-0">
                        <?php
                        $sql = "SELECT COUNT(*) as sayi FROM blog_yazilar WHERE yazar_id = " . $_SESSION['kullanici_id'] . " AND durum = 'yayinda'";
                        echo $conn->query($sql)->fetch_assoc()['sayi'];
                        ?>
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: rgba(251, 191, 36, 0.1); color: var(--warning-color);">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h3 class="h5 mb-2">Taslak</h3>
                    <p class="h2 mb-0">
                        <?php
                        $sql = "SELECT COUNT(*) as sayi FROM blog_yazilar WHERE yazar_id = " . $_SESSION['kullanici_id'] . " AND durum = 'taslak'";
                        echo $conn->query($sql)->fetch_assoc()['sayi'];
                        ?>
                    </p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="stats-icon" style="background: rgba(220, 38, 38, 0.1); color: var(--danger-color);">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3 class="h5 mb-2">Toplam Görüntülenme</h3>
                    <p class="h2 mb-0">
                        <?php
                        $sql = "SELECT SUM(goruntulenme) as toplam FROM blog_yazilar WHERE yazar_id = " . $_SESSION['kullanici_id'];
                        echo number_format($conn->query($sql)->fetch_assoc()['toplam']);
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Filtreleme ve Arama -->
        <div class="card mb-4">
            <div class="card-body">
                <form action="yazilarim.php" method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label for="arama" class="form-label">
                            <i class="fas fa-search me-2"></i>Arama
                        </label>
                        <input type="text" class="form-control" id="arama" name="arama" 
                               value="<?php echo htmlspecialchars($arama); ?>" 
                               placeholder="Başlık veya içerikte ara...">
                    </div>
                    <div class="col-md-3">
                        <label for="kategori_id" class="form-label">
                            <i class="fas fa-folder me-2"></i>Kategori
                        </label>
                        <select class="form-select" id="kategori_id" name="kategori_id">
                            <option value="">Tüm Kategoriler</option>
                            <?php while ($kategori = $kategoriler->fetch_assoc()): ?>
                                <option value="<?php echo $kategori['id']; ?>" 
                                    <?php echo $kategori_id == $kategori['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($kategori['kategori_adi']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="durum" class="form-label">
                            <i class="fas fa-filter me-2"></i>Durum
                        </label>
                        <select class="form-select" id="durum" name="durum">
                            <option value="">Tümü</option>
                            <option value="taslak" <?php echo $durum === 'taslak' ? 'selected' : ''; ?>>Taslak</option>
                            <option value="yayinda" <?php echo $durum === 'yayinda' ? 'selected' : ''; ?>>Yayında</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search me-2"></i>Filtrele
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if(isset($_SESSION['basari'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                    echo $_SESSION['basari'];
                    unset($_SESSION['basari']);
                ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['hata'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                    echo $_SESSION['hata'];
                    unset($_SESSION['hata']);
                ?>
            </div>
        <?php endif; ?>

        <?php if ($yazilar->num_rows > 0): ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Başlık</th>
                                <th>Kategori</th>
                                <th>Durum</th>
                                <th>Görüntülenme</th>
                                <th>Yorumlar</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($yazi = $yazilar->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="yazi.php?id=<?php echo $yazi['id']; ?>" class="text-decoration-none text-dark fw-medium">
                                            <?php echo htmlspecialchars($yazi['baslik']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo htmlspecialchars($yazi['kategori_adi']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $yazi['durum'] === 'yayinda' ? 'success' : 'warning'; ?>">
                                            <?php echo $yazi['durum'] === 'yayinda' ? 'Yayında' : 'Taslak'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-eye text-muted me-2"></i>
                                            <?php echo number_format($yazi['goruntulenme']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-comments text-muted me-2"></i>
                                            <?php echo $yazi['yorum_sayisi']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-calendar text-muted me-2"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($yazi['tarih'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="yazi_duzenle.php?id=<?php echo $yazi['id']; ?>" 
                                               class="btn btn-sm btn-outline-primary" title="Düzenle">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form action="yazilarim.php" method="POST" class="d-inline" 
                                                  onsubmit="return confirm('Bu yazıyı silmek istediğinizden emin misiniz?');">
                                                <input type="hidden" name="yazi_id" value="<?php echo $yazi['id']; ?>">
                                                <button type="submit" name="sil" class="btn btn-sm btn-outline-danger" title="Sil">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($total_pages > 1): ?>
                <nav aria-label="Sayfalama" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($sayfa > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?sayfa=<?php echo $sayfa-1; ?>&durum=<?php echo $durum; ?>&kategori_id=<?php echo $kategori_id; ?>&arama=<?php echo urlencode($arama); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i === $sayfa ? 'active' : ''; ?>">
                                <a class="page-link" href="?sayfa=<?php echo $i; ?>&durum=<?php echo $durum; ?>&kategori_id=<?php echo $kategori_id; ?>&arama=<?php echo urlencode($arama); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($sayfa < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?sayfa=<?php echo $sayfa+1; ?>&durum=<?php echo $durum; ?>&kategori_id=<?php echo $kategori_id; ?>&arama=<?php echo urlencode($arama); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                <?php if ($arama || $durum || $kategori_id): ?>
                    Arama kriterlerinize uygun yazı bulunamadı. 
                    <a href="yazilarim.php" class="alert-link">Tüm yazıları görüntüle</a>
                <?php else: ?>
                    Henüz hiç yazınız bulunmuyor. 
                    <a href="yazi_ekle.php" class="alert-link">Yeni bir yazı eklemek için tıklayın</a>.
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 