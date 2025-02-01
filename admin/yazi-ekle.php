<?php
session_start();
require_once('../baglan.php');
require_once('includes/auth_check.php');

// Sayfa başlığı ve aktif menü
$sayfa_basligi = "Yeni Yazı Ekle";
$aktif_sayfa = "yazilar";

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = trim($_POST['baslik']);
    $kategori_id = (int)$_POST['kategori_id'];
    $icerik = $_POST['icerik'];
    $meta_aciklama = trim($_POST['meta_aciklama']);
    $etiketler = isset($_POST['etiketler']) ? array_unique(array_filter(array_map('trim', $_POST['etiketler']))) : [];
    $durum = $_POST['durum'];
    $seo_url = createSlug($baslik);
    $hata = '';

    // Kapak resmi yükleme
    $kapak_resmi = '';
    if (isset($_FILES['kapak_resmi']) && $_FILES['kapak_resmi']['error'] === 0) {
        $izin_verilen_uzantilar = ['jpg', 'jpeg', 'png', 'webp'];
        $dosya_uzantisi = strtolower(pathinfo($_FILES['kapak_resmi']['name'], PATHINFO_EXTENSION));
        
        if (in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
            $yeni_dosya_adi = uniqid() . '.' . $dosya_uzantisi;
            $hedef_klasor = '../uploads/kapak_resimleri/';
            $hedef_dosya = $hedef_klasor . $yeni_dosya_adi;
            
            if (!file_exists($hedef_klasor)) {
                mkdir($hedef_klasor, 0777, true);
            }
            
            if (move_uploaded_file($_FILES['kapak_resmi']['tmp_name'], $hedef_dosya)) {
                $kapak_resmi = 'uploads/kapak_resimleri/' . $yeni_dosya_adi;
            } else {
                $hata = 'Kapak resmi yüklenirken bir hata oluştu.';
            }
        } else {
            $hata = 'Geçersiz dosya formatı. Sadece JPG, JPEG, PNG ve WEBP dosyaları yüklenebilir.';
        }
    }

    if (empty($hata)) {
        // Yazıyı ekle
        $sql = "INSERT INTO blog_yazilar (baslik, seo_url, icerik, meta_aciklama, kategori_id, yazar_id, kapak_resmi, durum, tarih) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssisss", $baslik, $seo_url, $icerik, $meta_aciklama, $kategori_id, $_SESSION['admin']['id'], $kapak_resmi, $durum);
        
        if ($stmt->execute()) {
            $yazi_id = $conn->insert_id;
            
            // Etiketleri ekle
            if (!empty($etiketler)) {
                foreach ($etiketler as $etiket) {
                    // Önce etiketin var olup olmadığını kontrol et
                    $sql = "SELECT id FROM etiketler WHERE etiket_adi = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $etiket);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows > 0) {
                        $etiket_id = $result->fetch_assoc()['id'];
                    } else {
                        // Yeni etiket ekle
                        $sql = "INSERT INTO etiketler (etiket_adi, seo_url) VALUES (?, ?)";
                        $stmt = $conn->prepare($sql);
                        $etiket_seo = createSlug($etiket);
                        $stmt->bind_param("ss", $etiket, $etiket_seo);
                        $stmt->execute();
                        $etiket_id = $conn->insert_id;
                    }
                    
                    // Yazı-etiket ilişkisini ekle
                    $sql = "INSERT INTO yazi_etiketler (yazi_id, etiket_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $yazi_id, $etiket_id);
                    $stmt->execute();
                }
            }
            
            header("Location: yazilar.php?mesaj=eklendi");
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

// Kategorileri getir
$kategoriler = $conn->query("SELECT * FROM kategoriler ORDER BY kategori_adi ASC");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $sayfa_basligi; ?> - Blog Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
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
                                        <textarea name="meta_aciklama" class="form-control" rows="3" 
                                                  placeholder="Yazının kısa açıklaması (SEO için önemli)"></textarea>
                                    </div>
                                </div>
                                
                                <!-- Sağ Kolon -->
                                <div class="col-md-4">
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3">Yayın Ayarları</h6>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Durum</label>
                                                <select name="durum" class="form-select">
                                                    <option value="taslak">Taslak</option>
                                                    <option value="yayinda">Yayında</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
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
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Etiketler</label>
                                                <select name="etiketler[]" id="etiketler" class="form-select" multiple>
                                                </select>
                                                <small class="text-muted">Enter tuşu ile yeni etiket ekleyebilirsiniz</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card mb-4">
                                        <div class="card-body">
                                            <h6 class="card-title mb-3">Kapak Resmi</h6>
                                            
                                            <div class="mb-3">
                                                <input type="file" name="kapak_resmi" class="form-control" 
                                                       accept="image/jpeg,image/png,image/webp">
                                            </div>
                                            
                                            <div class="kapak-onizleme text-center">
                                                <img src="" class="img-fluid rounded d-none" alt="Kapak resmi önizleme">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Yazıyı Kaydet
                                        </button>
                                        <a href="yazilar.php" class="btn btn-light">
                                            <i class="fas fa-times me-2"></i>İptal
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-tr-TR.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    
    <script>
    $(document).ready(function() {
        // Summernote editör
        $('#editor').summernote({
            height: 300,
            lang: 'tr-TR',
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
                    // Resim yükleme işlemi
                    for(let i=0; i < files.length; i++) {
                        uploadImage(files[i], this);
                    }
                }
            }
        });
        
        // Select2 etiket seçici
        $('#etiketler').select2({
            tags: true,
            tokenSeparators: [','],
            placeholder: 'Etiket ekleyin...',
            allowClear: true,
            language: {
                noResults: function() {
                    return "Etiket bulunamadı";
                }
            }
        });
        
        // Kapak resmi önizleme
        $('input[name="kapak_resmi"]').change(function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = $('.kapak-onizleme img');
                    img.attr('src', e.target.result);
                    img.removeClass('d-none');
                }
                reader.readAsDataURL(file);
            }
        });
    });
    
    // Resim yükleme fonksiyonu
    function uploadImage(file, editor) {
        const formData = new FormData();
        formData.append('image', file);
        
        $.ajax({
            url: 'resim-yukle.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    $(editor).summernote('insertImage', data.url);
                } else {
                    alert('Resim yüklenirken bir hata oluştu: ' + data.error);
                }
            },
            error: function() {
                alert('Resim yüklenirken bir hata oluştu.');
            }
        });
    }
    </script>
</body>
</html> 