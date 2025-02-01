<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = trim($_POST['baslik']);
    $kategori_id = (int)$_POST['kategori_id'];
    $icerik = $_POST['icerik'];
    $meta_aciklama = trim($_POST['meta_aciklama']);
    $etiketler = trim($_POST['etiketler']);
    $durum = $_POST['durum'];
    $seo_url = createSlug($baslik);
    $tarih = date('Y-m-d H:i:s');
    $yazar_id = $_SESSION['admin_id'];
    $hata = '';

    // Kapak resmi yükleme
    $kapak_resmi = '';
    if (isset($_FILES['kapak_resmi']) && $_FILES['kapak_resmi']['error'] === 0) {
        $izin_verilen_uzantilar = ['jpg', 'jpeg', 'png', 'webp'];
        $dosya_uzantisi = strtolower(pathinfo($_FILES['kapak_resmi']['name'], PATHINFO_EXTENSION));
        
        if (in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
            $yeni_isim = uniqid() . '.' . $dosya_uzantisi;
            $hedef_klasor = '../uploads/';
            $hedef_dosya = $hedef_klasor . $yeni_isim;
            
            if (move_uploaded_file($_FILES['kapak_resmi']['tmp_name'], $hedef_dosya)) {
                $kapak_resmi = 'uploads/' . $yeni_isim;
            } else {
                $hata = 'Kapak resmi yüklenirken bir hata oluştu.';
            }
        } else {
            $hata = 'Geçersiz dosya formatı. Sadece JPG, JPEG, PNG ve WEBP dosyaları yüklenebilir.';
        }
    }

    if (empty($hata)) {
        // Yazıyı veritabanına ekle
        $sql = "INSERT INTO blog_yazilar (baslik, seo_url, icerik, meta_aciklama, kategori_id, etiketler, kapak_resmi, durum, tarih, yazar_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", $baslik, $seo_url, $icerik, $meta_aciklama, $kategori_id, $etiketler, $kapak_resmi, $durum, $tarih, $yazar_id);
        
        if ($stmt->execute()) {
            header("Location: yazilar.php?mesaj=basarili");
            exit();
        } else {
            $hata = 'Yazı eklenirken bir hata oluştu.';
        }
    }
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

// Kategorileri al
$kategoriler = $conn->query("SELECT * FROM kategoriler ORDER BY kategori_adi");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Yazı Ekle - Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
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
                        <a class="nav-link active" href="yazilar.php">
                            <i class="fas fa-file-alt me-2"></i>Yazılar
                        </a>
                        <a class="nav-link" href="kategoriler.php">
                            <i class="fas fa-tags me-2"></i>Kategoriler
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
                        <h2 class="h4 mb-0">Yeni Yazı Ekle</h2>
                        
                        <div class="d-flex align-items-center gap-3">
                            <button type="submit" form="yaziForm" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Kaydet
                            </button>
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
                    
                    <?php if (isset($hata) && !empty($hata)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $hata; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Yazı Formu -->
                    <form id="yaziForm" action="" method="POST" enctype="multipart/form-data" class="card">
                        <div class="card-body">
                            <div class="row g-4">
                                <!-- Sol Kolon -->
                                <div class="col-md-8">
                                    <div class="mb-4">
                                        <label class="form-label">Başlık</label>
                                        <input type="text" name="baslik" class="form-control form-control-lg" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">İçerik</label>
                                        <textarea name="icerik" id="editor" class="form-control" rows="10"></textarea>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Meta Açıklama</label>
                                        <textarea name="meta_aciklama" class="form-control" rows="3" maxlength="160" placeholder="Yazının kısa açıklaması (SEO için önemli)"></textarea>
                                        <div class="form-text">En fazla 160 karakter</div>
                                    </div>
                                </div>
                                
                                <!-- Sağ Kolon -->
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label class="form-label">Durum</label>
                                        <select name="durum" class="form-select">
                                            <option value="taslak">Taslak</option>
                                            <option value="yayinda">Yayında</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Kategori</label>
                                        <select name="kategori_id" class="form-select" required>
                                            <option value="">Kategori Seçin</option>
                                            <?php while ($kategori = $kategoriler->fetch_assoc()): ?>
                                            <option value="<?php echo $kategori['id']; ?>">
                                                <?php echo htmlspecialchars($kategori['kategori_adi']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Etiketler</label>
                                        <input type="text" name="etiketler" class="form-control" placeholder="Etiketleri virgülle ayırın">
                                        <div class="form-text">Örnek: teknoloji, yazılım, web</div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Kapak Resmi</label>
                                        <div class="kapak-resmi-preview mb-3 d-none">
                                            <img src="" alt="Kapak Resmi Önizleme" class="img-fluid rounded">
                                            <button type="button" class="btn btn-sm btn-danger mt-2" onclick="kapakResmiSil()">
                                                <i class="fas fa-times me-1"></i>Görseli Kaldır
                                            </button>
                                        </div>
                                        <div class="input-group">
                                            <input type="file" class="form-control" id="kapak_resmi" name="kapak_resmi" accept="image/jpeg,image/png,image/webp">
                                            <button class="btn btn-outline-secondary" type="button" onclick="document.getElementById('kapak_resmi').click()">
                                                <i class="fas fa-image me-1"></i>Görsel Seç
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Önerilen boyut: 1200x800px. İzin verilen formatlar: JPG, PNG, WebP. Maksimum dosya boyutu: 5MB
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-tr-TR.min.js"></script>
    <script>
    // Sidebar Toggle İşlevi
    document.getElementById('toggleSidebar').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('active');
    });

    // Summernote Editör
    $(document).ready(function() {
        $('#editor').summernote({
            lang: 'tr-TR',
            height: 300,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onImageUpload: function(files) {
                    // Resim yükleme işlemi burada yapılacak
                    for(let i=0; i < files.length; i++) {
                        uploadImage(files[i]);
                    }
                }
            }
        });
    });

    // Kapak resmi önizleme
    document.getElementById('kapak_resmi').addEventListener('change', function(e) {
        const file = e.target.files[0];
        const preview = document.querySelector('.kapak-resmi-preview');
        const previewImg = preview.querySelector('img');
        
        if (file) {
            // Dosya boyutu kontrolü (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('Dosya boyutu 5MB\'dan büyük olamaz!');
                this.value = '';
                return;
            }
            
            // Dosya türü kontrolü
            const izinVerilenTurler = ['image/jpeg', 'image/png', 'image/webp'];
            if (!izinVerilenTurler.includes(file.type)) {
                alert('Sadece JPG, PNG ve WebP formatları desteklenmektedir!');
                this.value = '';
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImg.src = e.target.result;
                preview.classList.remove('d-none');
            }
            reader.readAsDataURL(file);
        } else {
            preview.classList.add('d-none');
        }
    });

    function kapakResmiSil() {
        const input = document.getElementById('kapak_resmi');
        const preview = document.querySelector('.kapak-resmi-preview');
        
        input.value = '';
        preview.classList.add('d-none');
    }

    // Form gönderilmeden önce kontrol
    document.querySelector('form').addEventListener('submit', function(e) {
        const kapakResmi = document.getElementById('kapak_resmi');
        if (kapakResmi.files.length > 0) {
            const file = kapakResmi.files[0];
            if (file.size > 5 * 1024 * 1024) {
                e.preventDefault();
                alert('Dosya boyutu 5MB\'dan büyük olamaz!');
                return;
            }
        }
    });

    // Resim Yükleme Fonksiyonu
    function uploadImage(file) {
        const formData = new FormData();
        formData.append('image', file);
        
        $.ajax({
            url: 'resim_yukle.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(url) {
                $('#editor').summernote('insertImage', url);
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error(textStatus + " " + errorThrown);
            }
        });
    }
    </script>
</body>
</html> 