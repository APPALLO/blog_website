<?php
session_start();
require_once('../baglan.php');
require_once('includes/auth_check.php');

// Sayfa başlığı ve aktif menü
$sayfa_basligi = "Geri Dönüşüm Kutusu";
$aktif_sayfa = "cop_kutusu";

// Yardımcı fonksiyonlar
function guvenli_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Geri yükleme işlemi
if (isset($_POST['geri_yukle']) && isset($_POST['yazi_id']) && isset($_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo "error";
        exit();
    }
    
    $yazi_id = (int)$_POST['yazi_id'];
    
    // Yazının var olduğunu ve silinmiş olduğunu kontrol et
    $kontrol_sql = "SELECT yazar_id FROM blog_yazilar WHERE id = ? AND durum = 'silindi'";
    $kontrol_stmt = $conn->prepare($kontrol_sql);
    $kontrol_stmt->bind_param("i", $yazi_id);
    $kontrol_stmt->execute();
    $yazi = $kontrol_stmt->get_result()->fetch_assoc();
    
    if ($yazi) {
        // Admin veya yazının sahibi mi kontrol et
        if ($_SESSION['admin']['rol'] === 'admin' || $yazi['yazar_id'] == $_SESSION['admin']['id']) {
            $sql = "UPDATE blog_yazilar SET durum = 'taslak', guncellenme_tarihi = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $yazi_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                http_response_code(200);
                echo "success";
            } else {
                http_response_code(500);
                echo "error";
            }
        } else {
            http_response_code(403);
            echo "unauthorized";
        }
    } else {
        http_response_code(404);
        echo "not_found";
    }
    exit();
}

// Kalıcı silme işlemi
if (isset($_POST['kalici_sil']) && isset($_POST['yazi_id']) && isset($_POST['csrf_token'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        echo "error";
        exit();
    }
    
    $yazi_id = (int)$_POST['yazi_id'];
    
    // Yazının var olduğunu ve silinmiş olduğunu kontrol et
    $kontrol_sql = "SELECT yazar_id FROM blog_yazilar WHERE id = ? AND durum = 'silindi'";
    $kontrol_stmt = $conn->prepare($kontrol_sql);
    $kontrol_stmt->bind_param("i", $yazi_id);
    $kontrol_stmt->execute();
    $yazi = $kontrol_stmt->get_result()->fetch_assoc();
    
    if ($yazi) {
        // Admin veya yazının sahibi mi kontrol et
        if ($_SESSION['admin']['rol'] === 'admin' || $yazi['yazar_id'] == $_SESSION['admin']['id']) {
            $conn->begin_transaction();
            
            try {
                // Yazıyı sil
                $sql = "DELETE FROM blog_yazilar WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $yazi_id);
                
                if ($stmt->execute()) {
                    // Etiketleri temizle
                    $sql = "DELETE FROM yazi_etiketler WHERE yazi_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $yazi_id);
                    $stmt->execute();
                    
                    // Kullanılmayan etiketleri temizle
                    $sql = "DELETE e FROM etiketler e 
                            LEFT JOIN yazi_etiketler ye ON e.id = ye.etiket_id 
                            WHERE ye.etiket_id IS NULL";
                    $conn->query($sql);
                    
                    $conn->commit();
                    http_response_code(200);
                    echo "success";
                } else {
                    throw new Exception("Yazı silinemedi");
                }
            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(500);
                echo "error";
            }
        } else {
            http_response_code(403);
            echo "unauthorized";
        }
    } else {
        http_response_code(404);
        echo "not_found";
    }
    exit();
}

// CSRF token oluştur
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Silinmiş yazıları getir
$sql = "SELECT 
            y.*, 
            k.kategori_adi,
            u.kullanici_adi as yazar_adi,
            (SELECT COUNT(*) FROM yorumlar WHERE yazi_id = y.id AND durum = 'onaylandi') as yorum_sayisi
        FROM blog_yazilar y
        LEFT JOIN kategoriler k ON y.kategori_id = k.id
        LEFT JOIN kullanicilar u ON y.yazar_id = u.id
        WHERE y.durum = 'silindi'
        ORDER BY y.guncellenme_tarihi DESC";

$yazilar = $conn->query($sql);

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
    <link href="css/admin.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <?php include 'includes/sidebar.php'; ?>
            
            <!-- Ana İçerik -->
            <div class="col p-0">
                <div class="main-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h1 class="h3 mb-0">
                            <i class="fas fa-trash-alt me-2"></i>Geri Dönüşüm Kutusu
                        </h1>
                    </div>
                    
                    <?php if ($yazilar->num_rows == 0): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Geri dönüşüm kutusu boş.
                    </div>
                    <?php else: ?>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Dikkat:</strong> Kalıcı olarak silinen yazılar geri getirilemez.
                    </div>
                    
                    <div class="row g-4">
                        <?php while ($yazi = $yazilar->fetch_assoc()): ?>
                        <div class="col-12" data-yazi-id="<?php echo $yazi['id']; ?>">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title mb-3"><?php echo guvenli_input($yazi['baslik']); ?></h5>
                                    
                                    <div class="text-muted small mb-3">
                                        <i class="fas fa-user me-1"></i><?php echo guvenli_input($yazi['yazar_adi']); ?> |
                                        <i class="fas fa-calendar me-1"></i><?php echo date('d.m.Y', strtotime($yazi['tarih'])); ?> |
                                        <i class="fas fa-folder me-1"></i><?php echo guvenli_input($yazi['kategori_adi']); ?> |
                                        <i class="fas fa-eye me-1"></i><?php echo number_format($yazi['goruntulenme']); ?> görüntülenme |
                                        <i class="fas fa-comments me-1"></i><?php echo $yazi['yorum_sayisi']; ?> yorum
                                    </div>
                                    
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-success btn-sm" 
                                                onclick="yaziGeriYukle(<?php echo $yazi['id']; ?>)">
                                            <i class="fas fa-undo me-1"></i>Geri Yükle
                                        </button>
                                        
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="yaziKaliciSil(<?php echo $yazi['id']; ?>, '<?php echo addslashes($yazi['baslik']); ?>')">
                                            <i class="fas fa-trash me-1"></i>Kalıcı Olarak Sil
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Kalıcı Silme Modal -->
    <div class="modal fade" id="kaliciSilModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Kalıcı Olarak Sil
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0">Bu yazıyı kalıcı olarak silmek istediğinizden emin misiniz?</p>
                    <div class="alert alert-danger mt-3 mb-0">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>DİKKAT:</strong> Bu işlem geri alınamaz ve yazı tamamen silinecektir!
                    </div>
                </div>
                <div class="modal-footer">
                    <form id="kaliciSilForm" method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="yazi_id" id="silinecekYaziId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>İptal
                        </button>
                        <button type="submit" name="kalici_sil" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Kalıcı Olarak Sil
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function yaziGeriYukle(yaziId) {
        if (confirm('Bu yazıyı geri yüklemek istediğinizden emin misiniz?')) {
            const formData = new FormData();
            formData.append('geri_yukle', '1');
            formData.append('yazi_id', yaziId);
            formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (response.status === 200) {
                    return response.text().then(text => {
                        if (text === 'success') {
                            const yaziCard = document.querySelector(`[data-yazi-id="${yaziId}"]`);
                            if (yaziCard) {
                                yaziCard.remove();
                                
                                // Başarı mesajı göster
                                const alert = document.createElement('div');
                                alert.className = 'alert alert-success alert-dismissible fade show';
                                alert.innerHTML = `
                                    <i class="fas fa-check-circle me-2"></i>Yazı başarıyla geri yüklendi
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                `;
                                document.querySelector('.main-content').insertBefore(alert, document.querySelector('.card'));
                                
                                // 3 saniye sonra mesajı kaldır
                                setTimeout(() => alert.remove(), 3000);
                            }
                        } else {
                            throw new Error('Geri yükleme işlemi başarısız oldu');
                        }
                    });
                } else if (response.status === 403) {
                    throw new Error('Bu işlem için yetkiniz yok');
                } else if (response.status === 404) {
                    throw new Error('Yazı bulunamadı');
                } else {
                    throw new Error('Geri yükleme işlemi başarısız oldu');
                }
            })
            .catch(error => {
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.innerHTML = `
                    <i class="fas fa-exclamation-circle me-2"></i>${error.message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.querySelector('.main-content').insertBefore(alert, document.querySelector('.card'));
            });
        }
    }

    function yaziKaliciSil(yaziId, yaziBaslik) {
        const kaliciSilModal = new bootstrap.Modal(document.getElementById('kaliciSilModal'));
        document.getElementById('silinecekYaziId').value = yaziId;
        kaliciSilModal.show();
    }

    document.getElementById('kaliciSilForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const yaziId = formData.get('yazi_id');
        
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (response.status === 200) {
                return response.text().then(text => {
                    if (text === 'success') {
                        const yaziCard = document.querySelector(`[data-yazi-id="${yaziId}"]`);
                        if (yaziCard) {
                            yaziCard.remove();
                            
                            // Başarı mesajı göster
                            const alert = document.createElement('div');
                            alert.className = 'alert alert-success alert-dismissible fade show';
                            alert.innerHTML = `
                                <i class="fas fa-check-circle me-2"></i>Yazı kalıcı olarak silindi
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            `;
                            document.querySelector('.main-content').insertBefore(alert, document.querySelector('.card'));
                            
                            // 3 saniye sonra mesajı kaldır
                            setTimeout(() => alert.remove(), 3000);
                        }
                        
                        // Modalı kapat
                        const kaliciSilModal = bootstrap.Modal.getInstance(document.getElementById('kaliciSilModal'));
                        kaliciSilModal.hide();
                    } else {
                        throw new Error('Silme işlemi başarısız oldu');
                    }
                });
            } else if (response.status === 403) {
                throw new Error('Bu işlem için yetkiniz yok');
            } else if (response.status === 404) {
                throw new Error('Yazı bulunamadı');
            } else {
                throw new Error('Silme işlemi başarısız oldu');
            }
        })
        .catch(error => {
            const alert = document.createElement('div');
            alert.className = 'alert alert-danger alert-dismissible fade show';
            alert.innerHTML = `
                <i class="fas fa-exclamation-circle me-2"></i>${error.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.querySelector('.main-content').insertBefore(alert, document.querySelector('.card'));
            
            // Modalı kapat
            const kaliciSilModal = bootstrap.Modal.getInstance(document.getElementById('kaliciSilModal'));
            kaliciSilModal.hide();
        });
    });
    </script>
</body>
</html> 