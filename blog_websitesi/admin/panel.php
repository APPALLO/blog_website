<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Admin bilgilerini al
$admin_id = $_SESSION['admin_id'];
$sql = "SELECT * FROM kullanicilar WHERE id = ? AND rol = 'admin' LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if (!$admin) {
    session_destroy();
    header("Location: index.php?hata=yetkisiz");
    exit();
}

// Admin session bilgilerini güncelle
$_SESSION['admin'] = array(
    'id' => $admin['id'],
    'kullanici_adi' => $admin['kullanici_adi'],
    'ad_soyad' => $admin['ad_soyad'],
    'email' => $admin['email'],
    'rol' => $admin['rol'],
    'son_giris' => $admin['son_giris']
);

// İstatistikleri al
$istatistikler = array();

// Toplam yazı sayısı ve görüntülenme
$sql = "SELECT COUNT(*) as toplam, SUM(goruntulenme) as toplam_goruntulenme FROM blog_yazilar WHERE durum = 'aktif'";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$istatistikler['yazi_sayisi'] = $row['toplam'];
$istatistikler['toplam_goruntulenme'] = $row['toplam_goruntulenme'] ?? 0;

// Toplam kullanıcı sayısı ve bugün kayıt olan kullanıcılar
$sql = "SELECT 
        (SELECT COUNT(*) FROM kullanicilar WHERE rol != 'admin') as toplam_kullanici,
        (SELECT COUNT(*) FROM kullanicilar WHERE DATE(kayit_tarihi) = CURDATE()) as bugun_kayit";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$istatistikler['kullanici_sayisi'] = $row['toplam_kullanici'];
$istatistikler['bugun_kayit'] = $row['bugun_kayit'];

// Bekleyen yorumlar ve toplam yorum sayısı
$sql = "SELECT 
        (SELECT COUNT(*) FROM yorumlar WHERE durum = 'onay_bekliyor') as bekleyen_yorum,
        (SELECT COUNT(*) FROM yorumlar WHERE durum = 'onaylandi') as toplam_yorum";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$istatistikler['bekleyen_yorum'] = $row['bekleyen_yorum'];
$istatistikler['toplam_yorum'] = $row['toplam_yorum'];

// Okunmamış mesaj sayısı ve toplam mesaj
$sql = "SELECT 
        (SELECT COUNT(*) FROM iletisim_mesajlari WHERE durum = 'okunmamis') as okunmamis_mesaj,
        (SELECT COUNT(*) FROM iletisim_mesajlari) as toplam_mesaj";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$istatistikler['okunmamis_mesaj'] = $row['okunmamis_mesaj'];
$istatistikler['toplam_mesaj'] = $row['toplam_mesaj'];

// Son aktiviteleri al
$aktiviteler = array();

// Son yazılar
$sql = "SELECT id, baslik, tarih, goruntulenme, kategori_id FROM blog_yazilar WHERE durum = 'aktif' ORDER BY tarih DESC LIMIT 5";
$yazilar = $conn->query($sql);

// Son yorumlar
$sql = "SELECT y.*, k.kullanici_adi, b.baslik as yazi_baslik, b.id as yazi_id 
        FROM yorumlar y 
        INNER JOIN kullanicilar k ON y.kullanici_id = k.id 
        INNER JOIN blog_yazilar b ON y.yazi_id = b.id 
        WHERE y.durum != 'silindi' 
        ORDER BY y.tarih DESC LIMIT 5";
$yorumlar = $conn->query($sql);

// Popüler yazılar
$sql = "SELECT id, baslik, goruntulenme 
        FROM blog_yazilar 
        WHERE durum = 'aktif' 
        ORDER BY goruntulenme DESC 
        LIMIT 5";
$populer_yazilar = $conn->query($sql);

// Son 7 günün görüntülenme istatistikleri
$sql = "SELECT 
        DATE(tarih) as gun,
        SUM(goruntulenme) as toplam_goruntulenme,
        COUNT(*) as yazi_sayisi
        FROM blog_yazilar 
        WHERE tarih >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(tarih)
        ORDER BY gun ASC";
$goruntulenme_istatistikleri = $conn->query($sql);

$gunler = array();
$goruntulenme_verileri = array();
$yazi_sayilari = array();

// Son 7 günü diziye ekle
for($i = 6; $i >= 0; $i--) {
    $tarih = date('Y-m-d', strtotime("-$i days"));
    $gunler[] = date('d.m', strtotime($tarih));
    $goruntulenme_verileri[$tarih] = 0;
    $yazi_sayilari[$tarih] = 0;
}

// Veritabanından gelen verileri diziye ekle
while($row = $goruntulenme_istatistikleri->fetch_assoc()) {
    $goruntulenme_verileri[$row['gun']] = (int)$row['toplam_goruntulenme'];
    $yazi_sayilari[$row['gun']] = (int)$row['yazi_sayisi'];
}

// JSON formatına çevir
$chart_data = json_encode(array_values($goruntulenme_verileri));
$chart_labels = json_encode($gunler);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts/dist/apexcharts.css">
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
        
        .top-bar {
            background: white;
            padding: 1rem 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-menu {
            cursor: pointer;
        }
        
        .user-menu .dropdown-menu {
            min-width: 200px;
            padding: 0.5rem 0;
            margin-top: 0.5rem;
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
            border: none;
            border-radius: 0.5rem;
        }
        
        .user-menu .dropdown-item {
            padding: 0.75rem 1.25rem;
            color: #333;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .user-menu .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .user-menu .dropdown-item i {
            width: 20px;
            text-align: center;
        }
        
        .user-menu .dropdown-divider {
            margin: 0.5rem 0;
            border-color: #f1f1f1;
        }
        
        .user-menu .text-danger:hover {
            background-color: #fee2e2;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 1rem;
        }
        
        .stats-icon.blue { background: var(--primary-color); }
        .stats-icon.burgundy { background: var(--secondary-color); }
        
        .list-group-item {
            border: none;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border-radius: 8px !important;
            transition: all 0.3s ease;
        }
        
        .list-group-item:hover {
            background: #f8f9fa;
        }
        
        .toggle-sidebar {
            display: none;
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 5px;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .toggle-sidebar {
                display: block;
            }
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .card-header {
            background: none;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.25rem;
        }
        
        .card-title {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .notification-dot {
            width: 8px;
            height: 8px;
            background: var(--danger-color);
            border-radius: 50%;
            display: inline-block;
            margin-left: 5px;
        }
        
        .quick-action {
            padding: 1rem;
            border-radius: 10px;
            background: white;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .quick-action i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }
        
        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 2px;
            background: #e9ecef;
        }
        
        .activity-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .activity-item::before {
            content: '';
            position: absolute;
            left: -34px;
            top: 0;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 2px solid white;
        }
        
        .chart-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .progress-card {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .progress-card .progress {
            height: 8px;
            margin-top: 0.5rem;
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>
<body>
    <!-- Mobil Menü Butonu -->
    <button class="toggle-sidebar" id="toggleSidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Ana İçerik -->
            <div class="col p-0">
                <div class="main-content">
                    <!-- Üst Bar -->
                    <div class="top-bar">
                        <h2 class="h4 mb-0">Yönetim Paneli</h2>
                        
                        <div class="user-menu dropdown">
                            <div class="d-flex align-items-center" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-info text-end me-3">
                                    <div class="fw-medium"><?php echo htmlspecialchars($_SESSION['admin']['ad_soyad']); ?></div>
                                    <small class="text-muted"><?php echo ucfirst($_SESSION['admin']['rol']); ?></small>
                                </div>
                                <div class="user-avatar">
                                    <?php echo strtoupper(substr($_SESSION['admin']['ad_soyad'], 0, 1)); ?>
                                </div>
                            </div>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="profil.php">
                                        <i class="fas fa-user me-2"></i>Profil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="ayarlar.php">
                                        <i class="fas fa-cog me-2"></i>Ayarlar
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="cikis.php">
                                        <i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Hızlı İşlemler -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="quick-action" onclick="window.location.href='yazi-ekle.php'">
                                <i class="fas fa-plus-circle"></i>
                                <h5>Yeni Yazı</h5>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="quick-action" onclick="window.location.href='kategori-ekle.php'">
                                <i class="fas fa-folder-plus"></i>
                                <h5>Yeni Kategori</h5>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="quick-action" onclick="window.location.href='kullanici-ekle.php'">
                                <i class="fas fa-user-plus"></i>
                                <h5>Yeni Kullanıcı</h5>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="quick-action" onclick="window.location.href='yedekle.php'">
                                <i class="fas fa-database"></i>
                                <h5>Yedekleme</h5>
                            </div>
                        </div>
                    </div>
                    
                    <!-- İstatistikler -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6 col-lg-3">
                            <div class="stats-card">
                                <div class="stats-icon blue">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <h4>Toplam Yazı</h4>
                                <div class="h2"><?php echo $istatistikler['yazi_sayisi']; ?></div>
                                <small class="text-muted"><?php echo number_format($istatistikler['toplam_goruntulenme']); ?> görüntülenme</small>
                                <a href="yazilar.php" class="stretched-link"></a>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="stats-card">
                                <div class="stats-icon burgundy">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h4>Toplam Kullanıcı</h4>
                                <div class="h2"><?php echo $istatistikler['kullanici_sayisi']; ?></div>
                                <small class="text-muted">Bugün: +<?php echo $istatistikler['bugun_kayit']; ?> yeni</small>
                                <a href="kullanicilar.php" class="stretched-link"></a>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="stats-card">
                                <div class="stats-icon blue">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h4>Bekleyen Yorum</h4>
                                <div class="h2"><?php echo $istatistikler['bekleyen_yorum']; ?></div>
                                <small class="text-muted">Toplam: <?php echo $istatistikler['toplam_yorum']; ?> yorum</small>
                                <a href="yorumlar.php" class="stretched-link"></a>
                            </div>
                        </div>
                        
                        <div class="col-md-6 col-lg-3">
                            <div class="stats-card">
                                <div class="stats-icon burgundy">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <h4>Okunmamış Mesaj</h4>
                                <div class="h2"><?php echo $istatistikler['okunmamis_mesaj']; ?></div>
                                <small class="text-muted">Toplam: <?php echo $istatistikler['toplam_mesaj']; ?> mesaj</small>
                                <a href="mesajlar.php" class="stretched-link"></a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Grafikler ve Aktiviteler -->
                    <div class="row">
                        <!-- Grafikler -->
                        <div class="col-md-8">
                            <div class="chart-card">
                                <h5 class="card-title">Görüntülenme İstatistikleri</h5>
                                <div id="viewsChart"></div>
                            </div>
                        </div>
                        
                        <!-- Son Aktiviteler -->
                        <div class="col-md-4">
                            <div class="card mb-4">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Son Aktiviteler</h5>
                                    <a href="aktiviteler.php" class="btn btn-sm btn-outline-primary">Tümü</a>
                                </div>
                                <div class="card-body">
                                    <div class="activity-timeline">
                                        <?php while ($yorum = $yorumlar->fetch_assoc()): ?>
                                        <div class="activity-item">
                                            <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($yorum['tarih'])); ?></small>
                                            <p class="mb-0"><strong><?php echo htmlspecialchars($yorum['kullanici_adi']); ?></strong> yorum yaptı</p>
                                            <small class="text-muted">"<?php echo htmlspecialchars($yorum['yazi_baslik']); ?>"</small>
                                        </div>
                                        <?php endwhile; ?>
                                        
                                        <?php while ($yazi = $yazilar->fetch_assoc()): ?>
                                        <div class="activity-item">
                                            <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($yazi['tarih'])); ?></small>
                                            <p class="mb-0">Yeni yazı eklendi</p>
                                            <small class="text-muted">"<?php echo htmlspecialchars($yazi['baslik']); ?>"</small>
                                        </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Popüler Yazılar -->
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">Popüler Yazılar</h5>
                                    <a href="yazilar.php?sirala=goruntulenme" class="btn btn-sm btn-outline-primary">Tümü</a>
                                </div>
                                <div class="card-body">
                                    <?php while ($yazi = $populer_yazilar->fetch_assoc()): ?>
                                    <div class="progress-card">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">
                                                <a href="../yazi.php?id=<?php echo $yazi['id']; ?>" class="text-decoration-none text-dark">
                                                    <?php echo htmlspecialchars($yazi['baslik']); ?>
                                                </a>
                                            </h6>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-eye me-1"></i>
                                                <?php echo number_format($yazi['goruntulenme']); ?>
                                            </span>
                                        </div>
                                        <div class="progress mt-2">
                                            <?php 
                                            $max_goruntulenme = 1000; // Örnek maksimum değer
                                            $yuzde = min(($yazi['goruntulenme'] / $max_goruntulenme) * 100, 100);
                                            ?>
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?php echo $yuzde; ?>%" 
                                                 aria-valuenow="<?php echo $yuzde; ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                            </div>
                                        </div>
                                    </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script>
        // Sidebar Toggle İşlevi
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Ekran Boyutu Değiştiğinde
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        });

        // Tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Görüntülenme Grafiği
        var options = {
            series: [{
                name: 'Görüntülenme',
                data: <?php echo $chart_data; ?>
            }],
            chart: {
                type: 'area',
                height: 350,
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 3
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: <?php echo $chart_labels; ?>,
                labels: {
                    style: {
                        colors: '#666',
                        fontSize: '12px',
                        fontFamily: 'Inter, sans-serif'
                    }
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#666',
                        fontSize: '12px',
                        fontFamily: 'Inter, sans-serif'
                    },
                    formatter: function (value) {
                        return Math.round(value).toLocaleString('tr-TR');
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(value) {
                        return value.toLocaleString('tr-TR') + ' görüntülenme';
                    }
                }
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 4
            },
            markers: {
                size: 5,
                colors: ['#4361ee'],
                strokeColors: '#fff',
                strokeWidth: 2,
                hover: {
                    size: 7
                }
            },
            theme: {
                mode: 'light'
            }
        };

        var chart = new ApexCharts(document.querySelector("#viewsChart"), options);
        chart.render();
    </script>
</body>
</html> 