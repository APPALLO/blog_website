<?php
session_start();
require_once('../baglan.php');
require_once('includes/auth_check.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Sayfa başlığı ve aktif menü
$sayfa_basligi = "Aktiviteler";
$aktif_sayfa = "aktiviteler";

function get_aktivite_renk($tip) {
    $renkler = [
        'giris' => 'bg-success',
        'cikis' => 'bg-danger',
        'yazi_ekleme' => 'bg-primary',
        'yazi_duzenleme' => 'bg-info',
        'yazi_silme' => 'bg-danger',
        'yorum_ekleme' => 'bg-primary',
        'yorum_onaylama' => 'bg-success',
        'yorum_silme' => 'bg-danger',
        'kategori_ekleme' => 'bg-primary',
        'kategori_duzenleme' => 'bg-info',
        'kategori_silme' => 'bg-danger',
        'kullanici_ekleme' => 'bg-primary',
        'kullanici_duzenleme' => 'bg-info',
        'kullanici_silme' => 'bg-danger',
        'ayar_guncelleme' => 'bg-warning'
    ];
    
    return $renkler[$tip] ?? 'bg-secondary';
}

// İstatistikleri getir
$stats_query = "SELECT 
    COUNT(*) as toplam_aktivite,
    COUNT(DISTINCT kullanici_id) as tekil_kullanici,
    COUNT(CASE WHEN tarih >= NOW() - INTERVAL 24 HOUR THEN 1 END) as son_24_saat,
    COUNT(CASE WHEN tarih >= NOW() - INTERVAL 7 DAY THEN 1 END) as son_7_gun
FROM aktiviteler";
$stats = $db->query($stats_query)->fetch(PDO::FETCH_ASSOC);

// Aktiviteleri getir
$query = "SELECT a.*, k.kullanici_adi, k.ad_soyad 
          FROM aktiviteler a 
          LEFT JOIN kullanicilar k ON a.kullanici_id = k.id 
          ORDER BY a.tarih DESC 
          LIMIT 50";
$stmt = $db->prepare($query);
$stmt->execute();
$aktiviteler = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        .stats-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        
        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #4361ee, #3f37c9);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .activity-table {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .activity-table th {
            background: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            padding: 1rem;
        }
        
        .activity-table td {
            padding: 1rem;
            vertical-align: middle;
        }
        
        .activity-table tbody tr {
            transition: all 0.3s ease;
        }
        
        .activity-table tbody tr:hover {
            background: rgba(67, 97, 238, 0.05);
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            background: #4361ee;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .activity-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 30px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .activity-time {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .ip-address {
            font-family: monospace;
            background: #f8f9fa;
            padding: 0.3rem 0.6rem;
            border-radius: 5px;
            font-size: 0.85rem;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .activity-filter {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .filter-input {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 0.6rem 1rem;
            transition: all 0.3s ease;
        }
        
        .filter-input:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
        }
    </style>
</head>
<body>
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
                        <h2 class="h4 mb-0">Aktiviteler</h2>
                        
                        <div class="d-flex align-items-center">
                            <div class="user-menu dropdown">
                                <div class="d-flex align-items-center" role="button" data-bs-toggle="dropdown">
                                    <div class="user-info text-end me-3">
                                        <div class="fw-medium"><?php echo htmlspecialchars($_SESSION['admin']['ad_soyad']); ?></div>
                                        <small class="text-muted"><?php echo ucfirst($_SESSION['admin']['rol']); ?></small>
                                    </div>
                                    <div class="user-avatar">
                                        <?php echo strtoupper(substr($_SESSION['admin']['ad_soyad'], 0, 1)); ?>
                                    </div>
                                </div>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user me-2"></i>Profil</a></li>
                                    <li><a class="dropdown-item" href="ayarlar.php"><i class="fas fa-cog me-2"></i>Ayarlar</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item text-danger" href="cikis.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Ana İçerik -->
                    <div class="p-4">
                        <!-- İstatistik Kartları -->
                        <div class="row g-4 mb-4">
                            <div class="col-md-3">
                                <div class="stats-card animate-fade-in" style="animation-delay: 0.1s">
                                    <div class="stats-icon" style="background: rgba(67, 97, 238, 0.1); color: #4361ee;">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="stats-number"><?php echo number_format($stats['toplam_aktivite']); ?></div>
                                    <div class="stats-label">Toplam Aktivite</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card animate-fade-in" style="animation-delay: 0.2s">
                                    <div class="stats-icon" style="background: rgba(46, 204, 113, 0.1); color: #2ecc71;">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stats-number"><?php echo number_format($stats['tekil_kullanici']); ?></div>
                                    <div class="stats-label">Tekil Kullanıcı</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card animate-fade-in" style="animation-delay: 0.3s">
                                    <div class="stats-icon" style="background: rgba(52, 152, 219, 0.1); color: #3498db;">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stats-number"><?php echo number_format($stats['son_24_saat']); ?></div>
                                    <div class="stats-label">Son 24 Saat</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stats-card animate-fade-in" style="animation-delay: 0.4s">
                                    <div class="stats-icon" style="background: rgba(155, 89, 182, 0.1); color: #9b59b6;">
                                        <i class="fas fa-calendar-week"></i>
                                    </div>
                                    <div class="stats-number"><?php echo number_format($stats['son_7_gun']); ?></div>
                                    <div class="stats-label">Son 7 Gün</div>
                                </div>
                            </div>
                        </div>

                        <!-- Filtreler -->
                        <div class="activity-filter animate-fade-in" style="animation-delay: 0.5s">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control filter-input" id="userFilter" placeholder="Kullanıcı ara...">
                                </div>
                                <div class="col-md-4">
                                    <input type="text" class="form-control filter-input" id="activityFilter" placeholder="Aktivite ara...">
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select filter-input" id="dateFilter">
                                        <option value="">Tüm Zamanlar</option>
                                        <option value="today">Bugün</option>
                                        <option value="week">Bu Hafta</option>
                                        <option value="month">Bu Ay</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Aktiviteler Tablosu -->
                        <div class="activity-table animate-fade-in" style="animation-delay: 0.6s">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Kullanıcı</th>
                                            <th>Aktivite Tipi</th>
                                            <th>Detay</th>
                                            <th>Modül</th>
                                            <th>Tarih</th>
                                            <th>IP Adresi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($aktiviteler as $aktivite): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="user-avatar me-2">
                                                        <?php echo strtoupper(substr($aktivite['ad_soyad'] ?? $aktivite['kullanici_adi'] ?? 'M', 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="fw-medium"><?php echo htmlspecialchars($aktivite['ad_soyad'] ?? ''); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars($aktivite['kullanici_adi'] ?? 'Misafir'); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="activity-badge <?php echo get_aktivite_renk($aktivite['aktivite_tipi']); ?> text-white">
                                                    <?php echo ucwords(str_replace('_', ' ', $aktivite['aktivite_tipi'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="activity-detail">
                                                    <?php echo htmlspecialchars($aktivite['detay']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark">
                                                    <?php echo ucfirst($aktivite['modul'] ?? '-'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="activity-time">
                                                    <i class="far fa-clock me-1"></i>
                                                    <?php echo date('d.m.Y H:i:s', strtotime($aktivite['tarih'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="ip-address">
                                                    <?php echo htmlspecialchars($aktivite['ip_adresi']); ?>
                                                </span>
                                                <small class="d-block text-muted" style="font-size: 0.75rem;">
                                                    <?php echo substr(htmlspecialchars($aktivite['tarayici']), 0, 50) . '...'; ?>
                                                </small>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle
        document.getElementById('toggleSidebar').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });

        // Responsive kontrol
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                document.getElementById('sidebar').classList.remove('active');
            }
        });

        // Filtreleme işlevleri
        document.getElementById('userFilter').addEventListener('input', filterActivities);
        document.getElementById('activityFilter').addEventListener('input', filterActivities);
        document.getElementById('dateFilter').addEventListener('change', filterActivities);

        function filterActivities() {
            const userFilter = document.getElementById('userFilter').value.toLowerCase();
            const activityFilter = document.getElementById('activityFilter').value.toLowerCase();
            const dateFilter = document.getElementById('dateFilter').value;
            
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const user = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
                const activity = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const date = row.querySelector('td:nth-child(3)').textContent;
                
                let showRow = true;
                
                if (userFilter && !user.includes(userFilter)) showRow = false;
                if (activityFilter && !activity.includes(activityFilter)) showRow = false;
                
                if (dateFilter) {
                    const rowDate = new Date(date.split(' ')[0].split('.').reverse().join('-'));
                    const today = new Date();
                    const weekAgo = new Date(today.getTime() - 7 * 24 * 60 * 60 * 1000);
                    const monthAgo = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
                    
                    switch(dateFilter) {
                        case 'today':
                            if (rowDate.toDateString() !== today.toDateString()) showRow = false;
                            break;
                        case 'week':
                            if (rowDate < weekAgo) showRow = false;
                            break;
                        case 'month':
                            if (rowDate < monthAgo) showRow = false;
                            break;
                    }
                }
                
                row.style.display = showRow ? '' : 'none';
            });
        }
    </script>
</body>
</html> 