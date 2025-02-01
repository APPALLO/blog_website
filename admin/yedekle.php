<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Yedekleme fonksiyonu
function veritabaniYedekle($host, $user, $pass, $dbname, $tables = '*') {
    $conn = new mysqli($host, $user, $pass, $dbname);
    $conn->set_charset("utf8");
    
    if ($tables == '*') {
        $tables = array();
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }
    } else {
        $tables = is_array($tables) ? $tables : explode(',', $tables);
    }
    
    $return = '';
    
    foreach ($tables as $table) {
        $result = $conn->query("SELECT * FROM $table");
        $numFields = $result->field_count;
        
        $return .= "DROP TABLE IF EXISTS $table;";
        $row2 = $conn->query("SHOW CREATE TABLE $table")->fetch_row();
        $return .= "\n\n" . $row2[1] . ";\n\n";
        
        for ($i = 0; $i < $numFields; $i++) {
            while ($row = $result->fetch_row()) {
                $return .= "INSERT INTO $table VALUES(";
                for ($j = 0; $j < $numFields; $j++) {
                    $row[$j] = addslashes($row[$j]);
                    $row[$j] = preg_replace("/\n/", "\\n", $row[$j]);
                    if (isset($row[$j])) {
                        $return .= '"' . $row[$j] . '"';
                    } else {
                        $return .= '""';
                    }
                    if ($j < ($numFields - 1)) {
                        $return .= ',';
                    }
                }
                $return .= ");\n";
            }
        }
        $return .= "\n\n\n";
    }
    
    $dosya_adi = 'yedek_' . date('Y-m-d_H-i-s') . '.sql';
    $dosya_yolu = 'yedekler/' . $dosya_adi;
    
    if (!file_exists('yedekler')) {
        mkdir('yedekler', 0777, true);
    }
    
    file_put_contents($dosya_yolu, $return);
    return $dosya_adi;
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $dosya_adi = veritabaniYedekle(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $mesaj = array(
            'tip' => 'success',
            'icerik' => 'Veritabanı başarıyla yedeklendi: ' . $dosya_adi
        );
    } catch (Exception $e) {
        $mesaj = array(
            'tip' => 'danger',
            'icerik' => 'Yedekleme sırasında bir hata oluştu: ' . $e->getMessage()
        );
    }
}

// Yedek dosyalarını listele
$yedekler = array();
if (file_exists('yedekler')) {
    $dosyalar = scandir('yedekler');
    foreach ($dosyalar as $dosya) {
        if ($dosya != '.' && $dosya != '..') {
            $yedekler[] = array(
                'ad' => $dosya,
                'tarih' => date('d.m.Y H:i:s', filemtime('yedekler/' . $dosya)),
                'boyut' => round(filesize('yedekler/' . $dosya) / 1024, 2)
            );
        }
    }
    rsort($yedekler);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veritabanı Yedekleme - Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <style>
        .backup-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 2rem;
        }

        .backup-card .card-body {
            padding: 2rem;
        }

        .backup-header {
            color: #4361ee;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .backup-header i {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 8px;
            color: #4361ee;
        }

        .btn-primary {
            background: #4361ee;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #3651d4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.2);
        }

        .backup-list {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
        }

        .backup-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid #e0e0e0;
            transition: all 0.3s ease;
        }

        .backup-item:last-child {
            border-bottom: none;
        }

        .backup-item:hover {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .backup-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .backup-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 8px;
            color: #4361ee;
        }

        .backup-details {
            font-size: 0.9rem;
            color: #666;
        }

        .backup-actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-icon:hover {
            transform: translateY(-2px);
        }

        .btn-download {
            background: rgba(67, 97, 238, 0.1);
            color: #4361ee;
        }

        .btn-download:hover {
            background: #4361ee;
            color: white;
        }

        .btn-delete {
            background: rgba(244, 67, 54, 0.1);
            color: #f44336;
        }

        .btn-delete:hover {
            background: #f44336;
            color: white;
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
                        <h2 class="h4 mb-0">Veritabanı Yedekleme</h2>
                        
                        <div class="d-flex align-items-center gap-3">
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
                        <?php if (isset($mesaj)): ?>
                        <div class="alert alert-<?php echo $mesaj['tip']; ?> alert-dismissible fade show" role="alert">
                            <i class="fas <?php echo $mesaj['tip'] == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> me-2"></i>
                            <?php echo $mesaj['icerik']; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <div class="backup-card">
                            <div class="card-body">
                                <div class="backup-header">
                                    <i class="fas fa-database"></i>
                                    Yeni Yedek Oluştur
                                </div>
                                
                                <form action="" method="POST">
                                    <p class="text-muted mb-4">
                                        Veritabanının tam bir yedeğini oluşturmak için aşağıdaki butona tıklayın.
                                        Yedekleme işlemi birkaç saniye sürebilir.
                                    </p>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-download me-2"></i>Yedek Oluştur
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="backup-card">
                            <div class="card-body">
                                <div class="backup-header">
                                    <i class="fas fa-history"></i>
                                    Yedek Geçmişi
                                </div>
                                
                                <?php if (empty($yedekler)): ?>
                                <p class="text-muted">Henüz yedek oluşturulmamış.</p>
                                <?php else: ?>
                                <div class="backup-list">
                                    <?php foreach ($yedekler as $yedek): ?>
                                    <div class="backup-item">
                                        <div class="backup-info">
                                            <div class="backup-icon">
                                                <i class="fas fa-file-code"></i>
                                            </div>
                                            <div>
                                                <div class="fw-medium"><?php echo $yedek['ad']; ?></div>
                                                <div class="backup-details">
                                                    <?php echo $yedek['tarih']; ?> &bull; <?php echo $yedek['boyut']; ?> KB
                                                </div>
                                            </div>
                                        </div>
                                        <div class="backup-actions">
                                            <a href="yedekler/<?php echo $yedek['ad']; ?>" download class="btn btn-icon btn-download" title="İndir">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <a href="yedek-sil.php?dosya=<?php echo $yedek['ad']; ?>" class="btn btn-icon btn-delete" title="Sil" onclick="return confirm('Bu yedeği silmek istediğinize emin misiniz?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
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
    </script>
</body>
</html> 