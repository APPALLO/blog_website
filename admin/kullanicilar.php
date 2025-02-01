<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Kullanıcıları listele
$sql = "SELECT * FROM kullanicilar ORDER BY kayit_tarihi DESC";
$kullanicilar = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcılar - Admin Paneli</title>
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
                        <h2 class="h4 mb-0">Kullanıcılar</h2>
                        
                        <div class="d-flex align-items-center gap-3">
                            <a href="kullanici-ekle.php" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Yeni Kullanıcı Ekle
                            </a>
                            
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
                        <?php if (isset($_GET['mesaj']) && $_GET['mesaj'] == 'eklendi'): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            Kullanıcı başarıyla eklendi.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php endif; ?>

                        <div class="content-card">
                            <div class="card-body">
                                <div class="content-header">
                                    <i class="fas fa-users"></i>
                                    Tüm Kullanıcılar
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th style="width: 50px"></th>
                                                <th>Kullanıcı Adı</th>
                                                <th>Ad Soyad</th>
                                                <th>E-posta</th>
                                                <th>Rol</th>
                                                <th>Durum</th>
                                                <th>Kayıt Tarihi</th>
                                                <th style="width: 100px">İşlemler</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($kullanici = $kullanicilar->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div class="user-avatar">
                                                        <?php echo strtoupper(substr($kullanici['ad_soyad'], 0, 1)); ?>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($kullanici['kullanici_adi']); ?></td>
                                                <td><?php echo htmlspecialchars($kullanici['ad_soyad']); ?></td>
                                                <td><?php echo htmlspecialchars($kullanici['email']); ?></td>
                                                <td>
                                                    <span class="role-badge <?php echo $kullanici['rol']; ?>">
                                                        <?php echo ucfirst($kullanici['rol']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?php echo $kullanici['durum']; ?>">
                                                        <?php echo ucfirst($kullanici['durum']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d.m.Y H:i', strtotime($kullanici['kayit_tarihi'])); ?></td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="kullanici-duzenle.php?id=<?php echo $kullanici['id']; ?>" class="btn btn-icon btn-edit" title="Düzenle">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($kullanici['id'] != $_SESSION['admin_id']): ?>
                                                        <a href="kullanici-sil.php?id=<?php echo $kullanici['id']; ?>" class="btn btn-icon btn-delete" title="Sil" onclick="return confirm('Bu kullanıcıyı silmek istediğinize emin misiniz?')">
                                                            <i class="fas fa-trash-alt"></i>
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