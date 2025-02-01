<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'baglan.php';

$aranan = isset($_GET['q']) ? trim($_GET['q']) : '';
$kategori = isset($_GET['kategori']) ? (int)$_GET['kategori'] : 0;
$sirala = isset($_GET['sirala']) ? $_GET['sirala'] : 'tarih_yeni';

// Sayfalama için değişkenler
$sayfa = isset($_GET['sayfa']) ? (int)$_GET['sayfa'] : 1;
$limit = 10;
$offset = ($sayfa - 1) * $limit;

// WHERE koşulları
$where = ["b.durum = 'yayinda'"];
if (!empty($aranan)) {
    $aranan_escaped = $conn->real_escape_string($aranan);
    $where[] = "(b.baslik LIKE '%$aranan_escaped%' OR b.icerik LIKE '%$aranan_escaped%' OR k.kategori_adi LIKE '%$aranan_escaped%')";
}
if ($kategori > 0) {
    $where[] = "b.kategori_id = $kategori";
}

// ORDER BY koşulu
switch ($sirala) {
    case 'tarih_eski':
        $order_by = "b.tarih ASC";
        break;
    case 'populer':
        $order_by = "b.goruntulenme DESC";
        break;
    case 'yorumlar':
        $order_by = "yorum_sayisi DESC";
        break;
    default:
        $order_by = "b.tarih DESC";
}

$where_clause = implode(" AND ", $where);

// Toplam sonuç sayısı
$sql = "SELECT COUNT(*) as total FROM blog_yazilar b 
        LEFT JOIN kategoriler k ON b.kategori_id = k.id 
        WHERE $where_clause";
$result = $conn->query($sql);
$total_rows = $result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Sonuçları getir
$sql = "SELECT b.*, k.kategori_adi, u.ad_soyad as yazar_adi,
        (SELECT COUNT(*) FROM yorumlar y WHERE y.yazi_id = b.id AND y.durum = 'onaylanmis') as yorum_sayisi
        FROM blog_yazilar b
        LEFT JOIN kategoriler k ON b.kategori_id = k.id
        LEFT JOIN kullanicilar u ON b.yazar_id = u.id
        WHERE $where_clause
        ORDER BY $order_by
        LIMIT $offset, $limit";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Arama Sonuçları - Blog Sitesi</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .search-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            padding: 3rem 0;
            margin-bottom: 3rem;
            color: var(--white);
        }

        .search-result-card {
            background: var(--white);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .search-result-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .search-result-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .search-result-content {
            padding: 1.5rem;
        }

        .search-result-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--gray-dark);
            text-decoration: none;
        }

        .search-result-title:hover {
            color: var(--primary-color);
        }

        .search-result-excerpt {
            color: var(--gray-medium);
            margin-bottom: 1.5rem;
        }

        .search-result-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            color: var(--gray-medium);
            font-size: 0.875rem;
        }

        .search-result-meta i {
            color: var(--primary-color);
        }

        .search-filters {
            background: var(--white);
            padding: 1.5rem;
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        mark {
            background: rgba(99, 102, 241, 0.2);
            color: var(--primary-color);
            padding: 0.2em 0;
            border-radius: 0.2em;
        }

        .empty-results {
            text-align: center;
            padding: 4rem 2rem;
            background: var(--white);
            border-radius: 1rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .empty-results i {
            font-size: 4rem;
            color: var(--gray-medium);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="search-header">
        <div class="container">
            <h1 class="display-4 mb-4">
                <?php if (!empty($aranan)): ?>
                    "<?php echo htmlspecialchars($aranan); ?>" için arama sonuçları
                <?php else: ?>
                    Tüm Yazılar
                <?php endif; ?>
            </h1>
            <p class="lead">
                <?php echo $total_rows; ?> sonuç bulundu
            </p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <article class="search-result-card">
                            <?php if($row['kapak_resmi']): ?>
                                <img src="uploads/<?php echo htmlspecialchars($row['kapak_resmi']); ?>" 
                                     class="search-result-image" 
                                     alt="<?php echo htmlspecialchars($row['baslik']); ?>">
                            <?php endif; ?>
                            
                            <div class="search-result-content">
                                <h2>
                                    <a href="yazi.php?id=<?php echo $row['id']; ?>" class="search-result-title">
                                        <?php 
                                        $baslik = htmlspecialchars($row['baslik']);
                                        if (!empty($aranan)) {
                                            $baslik = preg_replace('/(' . preg_quote($aranan, '/') . ')/i', '<mark>$1</mark>', $baslik);
                                        }
                                        echo $baslik;
                                        ?>
                                    </a>
                                </h2>
                                
                                <div class="search-result-excerpt">
                                    <?php 
                                    $ozet = strip_tags($row['icerik']);
                                    $ozet = mb_substr($ozet, 0, 200) . '...';
                                    if (!empty($aranan)) {
                                        $ozet = preg_replace('/(' . preg_quote($aranan, '/') . ')/i', '<mark>$1</mark>', htmlspecialchars($ozet));
                                    }
                                    echo $ozet;
                                    ?>
                                </div>
                                
                                <div class="search-result-meta">
                                    <span>
                                        <i class="fas fa-user me-1"></i>
                                        <?php echo htmlspecialchars($row['yazar_adi']); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-folder me-1"></i>
                                        <?php echo htmlspecialchars($row['kategori_adi']); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-calendar me-1"></i>
                                        <?php echo date('d.m.Y', strtotime($row['tarih'])); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-eye me-1"></i>
                                        <?php echo number_format($row['goruntulenme']); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-comments me-1"></i>
                                        <?php echo $row['yorum_sayisi']; ?>
                                    </span>
                                </div>
                            </div>
                        </article>
                    <?php endwhile; ?>

                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Sayfalama" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $sayfa == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?q=<?php echo urlencode($aranan); ?>&kategori=<?php echo $kategori; ?>&sirala=<?php echo $sirala; ?>&sayfa=<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="empty-results">
                        <i class="fas fa-search"></i>
                        <h3>Sonuç Bulunamadı</h3>
                        <p class="text-muted">Farklı anahtar kelimeler kullanarak tekrar deneyin.</p>
                        <a href="index.php" class="btn btn-primary mt-3">
                            <i class="fas fa-home me-2"></i>Ana Sayfaya Dön
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="search-filters sticky-top" style="top: 2rem;">
                    <h4 class="mb-4">Filtreleme Seçenekleri</h4>
                    <form action="arama.php" method="GET">
                        <input type="hidden" name="q" value="<?php echo htmlspecialchars($aranan); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select name="kategori" class="form-select" onchange="this.form.submit()">
                                <option value="">Tüm Kategoriler</option>
                                <?php
                                $sql = "SELECT * FROM kategoriler ORDER BY kategori_adi ASC";
                                $kategoriler = $conn->query($sql);
                                while ($kat = $kategoriler->fetch_assoc()) {
                                    $selected = $kategori == $kat['id'] ? 'selected' : '';
                                    echo '<option value="' . $kat['id'] . '" ' . $selected . '>' . 
                                         htmlspecialchars($kat['kategori_adi']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Sıralama</label>
                            <select name="sirala" class="form-select" onchange="this.form.submit()">
                                <option value="tarih_yeni" <?php echo $sirala == 'tarih_yeni' ? 'selected' : ''; ?>>En Yeni</option>
                                <option value="tarih_eski" <?php echo $sirala == 'tarih_eski' ? 'selected' : ''; ?>>En Eski</option>
                                <option value="populer" <?php echo $sirala == 'populer' ? 'selected' : ''; ?>>En Popüler</option>
                                <option value="yorumlar" <?php echo $sirala == 'yorumlar' ? 'selected' : ''; ?>>En Çok Yorumlanan</option>
                            </select>
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
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        let searchTimeout;

        if (searchInput && searchResults) {
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                const query = this.value.trim();
                
                if (query.length < 2) {
                    searchResults.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    const kategori = document.querySelector('select[name="kategori"]').value;
                    
                    fetch(`arama_ajax.php?q=${encodeURIComponent(query)}&kategori=${kategori}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.data.length > 0) {
                                let html = '';
                                data.data.forEach(item => {
                                    html += `
                                        <a href="yazi.php?id=${item.id}" class="search-result-item">
                                            <img src="uploads/${item.resim}" class="search-result-image" alt="${item.baslik}">
                                            <div class="search-result-content">
                                                <div class="search-result-title">${item.baslik}</div>
                                                <div class="search-result-info">
                                                    <span><i class="fas fa-user me-1"></i>${item.yazar}</span>
                                                    <span><i class="fas fa-calendar me-1"></i>${item.tarih}</span>
                                                    <span><i class="fas fa-eye me-1"></i>${item.goruntulenme}</span>
                                                </div>
                                            </div>
                                        </a>
                                    `;
                                });
                                searchResults.innerHTML = html;
                                searchResults.style.display = 'block';
                            } else {
                                searchResults.innerHTML = '<div class="p-3 text-center text-muted">Sonuç bulunamadı</div>';
                                searchResults.style.display = 'block';
                            }
                        })
                        .catch(error => {
                            console.error('Arama hatası:', error);
                            searchResults.style.display = 'none';
                        });
                }, 300);
            });

            // Sayfa dışına tıklandığında sonuçları gizle
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                    searchResults.style.display = 'none';
                }
            });
        }
    });
    </script>
</body>
</html> 