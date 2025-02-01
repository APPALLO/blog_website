<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Mesajları listele
$sql = "SELECT * FROM iletisim_mesajlari ORDER BY tarih DESC";
$mesajlar = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesajlar - Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
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
                        <h2 class="h4 mb-0">Mesajlar</h2>
                        
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
                        <div class="content-card">
                            <div class="card-body">
                                <div class="content-header">
                                    <i class="fas fa-envelope"></i>
                                    Gelen Mesajlar
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Gönderen</th>
                                                <th>E-posta</th>
                                                <th>Konu</th>
                                                <th>Mesaj</th>
                                                <th>Durum</th>
                                                <th>Tarih</th>
                                                <th style="width: 100px">İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($mesaj = $mesajlar->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($mesaj['ad_soyad']); ?></td>
                                                <td><?php echo htmlspecialchars($mesaj['email']); ?></td>
                                                <td><?php echo htmlspecialchars($mesaj['konu']); ?></td>
                                                <td>
                                                    <?php 
                                                    $mesaj_metni = htmlspecialchars($mesaj['mesaj']);
                                                    echo strlen($mesaj_metni) > 100 ? substr($mesaj_metni, 0, 100) . '...' : $mesaj_metni;
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $mesaj['okundu'] ? 'active' : 'passive'; ?>">
                                                        <?php echo $mesaj['okundu'] ? 'Okundu' : 'Okunmadı'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($mesaj['tarih'])); ?></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="mesaj-detay.php?id=<?php echo $mesaj['id']; ?>" class="btn btn-icon btn-view" title="Görüntüle">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="mesaj-sil.php?id=<?php echo $mesaj['id']; ?>" class="btn btn-icon btn-delete" title="Sil" onclick="return confirm('Bu mesajı silmek istediğinize emin misiniz?')">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
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