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

// Yazı ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: yazilarim.php");
    exit();
}

$yazi_id = (int)$_GET['id'];

// Yazının var olduğunu ve kullanıcıya ait olduğunu kontrol et
$sql = "SELECT b.*, GROUP_CONCAT(e.etiket_adi) as etiketler 
        FROM blog_yazilar b 
        LEFT JOIN yazi_etiketler ye ON b.id = ye.yazi_id 
        LEFT JOIN etiketler e ON ye.etiket_id = e.id 
        WHERE b.id = ? AND b.yazar_id = ? 
        GROUP BY b.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $yazi_id, $_SESSION['kullanici_id']);
$stmt->execute();
$yazi = $stmt->get_result()->fetch_assoc();

if (!$yazi) {
    $_SESSION['hata'] = "Düzenlemek istediğiniz yazı bulunamadı.";
    header("Location: yazilarim.php");
    exit();
}

// Kategorileri getir
$sql = "SELECT * FROM kategoriler ORDER BY kategori_adi ASC";
$kategoriler = $conn->query($sql);

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = trim(htmlspecialchars($_POST['baslik']));
    $icerik = trim($_POST['icerik']);
    $kategori_id = (int)$_POST['kategori_id'];
    $durum = $_POST['durum'];
    $etiketler = trim(htmlspecialchars($_POST['etiketler']));
    
    // Validasyon
    $hata = false;
    if (empty($baslik) || empty($icerik) || empty($kategori_id)) {
        $_SESSION['hata'] = "Lütfen tüm zorunlu alanları doldurun.";
        $hata = true;
    }
    
    if (!$hata) {
        // Yazıyı güncelle
        $sql = "UPDATE blog_yazilar SET baslik = ?, icerik = ?, kategori_id = ?, durum = ? WHERE id = ? AND yazar_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissi", $baslik, $icerik, $kategori_id, $durum, $yazi_id, $_SESSION['kullanici_id']);
        
        if ($stmt->execute()) {
            // Mevcut etiketleri temizle
            $sql = "DELETE FROM yazi_etiketler WHERE yazi_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $yazi_id);
            $stmt->execute();
            
            // Yeni etiketleri ekle
            if (!empty($etiketler)) {
                $etiket_array = array_unique(array_map('trim', explode(',', $etiketler)));
                foreach ($etiket_array as $etiket) {
                    // Önce etiketi kontrol et veya ekle
                    $sql = "INSERT INTO etiketler (etiket_adi) VALUES (?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $etiket);
                    $stmt->execute();
                    $etiket_id = $stmt->insert_id;
                    
                    // Yazı-etiket ilişkisini ekle
                    $sql = "INSERT IGNORE INTO yazi_etiketler (yazi_id, etiket_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $yazi_id, $etiket_id);
                    $stmt->execute();
                }
            }
            
            $_SESSION['basari'] = "Yazı başarıyla güncellendi.";
            header("Location: yazilarim.php");
            exit();
        } else {
            $_SESSION['hata'] = "Yazı güncellenirken bir hata oluştu.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yazı Düzenle - Blog Sitesi</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CKEditor CDN -->
    <script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title">Yazı Düzenle</h1>
                        
                        <?php if(isset($_SESSION['hata'])): ?>
                            <div class="alert alert-danger">
                                <?php 
                                    echo $_SESSION['hata'];
                                    unset($_SESSION['hata']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <form action="yazi_duzenle.php?id=<?php echo $yazi_id; ?>" method="POST">
                            <div class="mb-3">
                                <label for="baslik" class="form-label">Başlık *</label>
                                <input type="text" class="form-control" id="baslik" name="baslik" 
                                       value="<?php echo htmlspecialchars($yazi['baslik']); ?>" 
                                       required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="kategori_id" class="form-label">Kategori *</label>
                                <select class="form-select" id="kategori_id" name="kategori_id" required>
                                    <option value="">Kategori Seçin</option>
                                    <?php while ($kategori = $kategoriler->fetch_assoc()): ?>
                                        <option value="<?php echo $kategori['id']; ?>" 
                                            <?php echo $yazi['kategori_id'] == $kategori['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($kategori['kategori_adi']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="icerik" class="form-label">İçerik *</label>
                                <textarea class="form-control" id="editor" name="icerik" rows="10" required>
                                    <?php echo htmlspecialchars($yazi['icerik']); ?>
                                </textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="etiketler" class="form-label">Etiketler</label>
                                <input type="text" class="form-control" id="etiketler" name="etiketler" 
                                       value="<?php echo htmlspecialchars($yazi['etiketler']); ?>" 
                                       placeholder="Etiketleri virgülle ayırarak yazın">
                                <div class="form-text">Örnek: teknoloji, yazılım, php</div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Durum</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="durum" id="durum_taslak" 
                                           value="taslak" <?php echo $yazi['durum'] === 'taslak' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="durum_taslak">
                                        Taslak olarak kaydet
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="durum" id="durum_yayinda" 
                                           value="yayinda" <?php echo $yazi['durum'] === 'yayinda' ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="durum_yayinda">
                                        Hemen yayınla
                                    </label>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Değişiklikleri Kaydet
                                </button>
                                <a href="yazilarim.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> İptal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        ClassicEditor
            .create(document.querySelector('#editor'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable'],
                language: 'tr'
            })
            .catch(error => {
                console.error(error);
            });
    </script>
</body>
</html> 