<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Yazı ID kontrolü
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: yazilar.php");
    exit();
}

$yazi_id = (int)$_GET['id'];

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
    if (isset($_FILES['kapak_resmi']) && $_FILES['kapak_resmi']['error'] === 0) {
        $izin_verilen_uzantilar = ['jpg', 'jpeg', 'png', 'webp'];
        $dosya_uzantisi = strtolower(pathinfo($_FILES['kapak_resmi']['name'], PATHINFO_EXTENSION));
        
        if (in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
            $yeni_isim = uniqid() . '.' . $dosya_uzantisi;
            $hedef_klasor = '../uploads/';
            $hedef_dosya = $hedef_klasor . $yeni_isim;
            
            if (move_uploaded_file($_FILES['kapak_resmi']['tmp_name'], $hedef_dosya)) {
                // Eski kapak resmini sil
                $sql = "SELECT kapak_resmi FROM blog_yazilar WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $yazi_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $eski_resim = $result->fetch_assoc()['kapak_resmi'];
                
                if (!empty($eski_resim) && file_exists('../' . $eski_resim)) {
                    unlink('../' . $eski_resim);
                }
                
                $kapak_resmi = 'uploads/' . $yeni_isim;
                
                // Kapak resmini güncelle
                $sql = "UPDATE blog_yazilar SET kapak_resmi = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $kapak_resmi, $yazi_id);
                $stmt->execute();
            } else {
                $hata = 'Kapak resmi yüklenirken bir hata oluştu.';
            }
        } else {
            $hata = 'Geçersiz dosya formatı. Sadece JPG, JPEG, PNG ve WEBP dosyaları yüklenebilir.';
        }
    }

    if (empty($hata)) {
        // Yazıyı güncelle
        $sql = "UPDATE blog_yazilar SET 
                baslik = ?, 
                seo_url = ?, 
                icerik = ?, 
                meta_aciklama = ?, 
                kategori_id = ?, 
                durum = ? 
                WHERE id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssi", $baslik, $seo_url, $icerik, $meta_aciklama, $kategori_id, $durum, $yazi_id);
        
        if ($stmt->execute()) {
            // Mevcut etiketleri temizle
            $sql = "DELETE FROM yazi_etiketler WHERE yazi_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $yazi_id);
            $stmt->execute();
            
            // Yeni etiketleri ekle
            if (!empty($etiketler)) {
                foreach ($etiketler as $etiket) {
                    // Önce etiketi kontrol et veya ekle
                    $etiket_seo = createSlug($etiket);
                    $sql = "INSERT INTO etiketler (etiket_adi, seo_url) 
                            VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE 
                            id = LAST_INSERT_ID(id), 
                            kullanim_sayisi = kullanim_sayisi + 1";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ss", $etiket, $etiket_seo);
                    $stmt->execute();
                    $etiket_id = $stmt->insert_id;
                    
                    // Yazı-etiket ilişkisini ekle
                    $sql = "INSERT IGNORE INTO yazi_etiketler (yazi_id, etiket_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $yazi_id, $etiket_id);
                    $stmt->execute();
                }
            }
            
            // Kullanılmayan etiketleri temizle
            $sql = "DELETE e FROM etiketler e 
                    LEFT JOIN yazi_etiketler ye ON e.id = ye.etiket_id 
                    WHERE ye.etiket_id IS NULL";
            $conn->query($sql);
            
            header("Location: yazilar.php?mesaj=guncellendi");
            exit();
        } else {
            $hata = 'Yazı güncellenirken bir hata oluştu.';
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

// Yazı bilgilerini al
$sql = "SELECT y.*, GROUP_CONCAT(e.etiket_adi) as etiketler 
        FROM blog_yazilar y 
        LEFT JOIN yazi_etiketler ye ON y.id = ye.yazi_id 
        LEFT JOIN etiketler e ON ye.etiket_id = e.id 
        WHERE y.id = ? 
        GROUP BY y.id";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $yazi_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: yazilar.php");
    exit();
}

$yazi = $result->fetch_assoc();

// Kategorileri al
$kategoriler = $conn->query("SELECT * FROM kategoriler ORDER BY kategori_adi");

// Popüler etiketleri al
$populer_etiketler = $conn->query("SELECT e.etiket_adi, COUNT(ye.yazi_id) as kullanim_sayisi 
                                  FROM etiketler e 
                                  LEFT JOIN yazi_etiketler ye ON e.id = ye.etiket_id 
                                  GROUP BY e.id, e.etiket_adi 
                                  ORDER BY kullanim_sayisi DESC 
                                  LIMIT 10");
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yazı Düzenle - Admin Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
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
                        <h2 class="h4 mb-0">Yazı Düzenle</h2>
                        
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
                                        <input type="text" name="baslik" class="form-control form-control-lg" required value="<?php echo htmlspecialchars($yazi['baslik']); ?>">
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">İçerik</label>
                                        <textarea name="icerik" id="editor" class="form-control" rows="10"><?php echo htmlspecialchars($yazi['icerik']); ?></textarea>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Meta Açıklama</label>
                                        <textarea name="meta_aciklama" class="form-control" rows="3" maxlength="160" placeholder="Yazının kısa açıklaması (SEO için önemli)"><?php echo isset($yazi['meta_aciklama']) ? htmlspecialchars($yazi['meta_aciklama']) : ''; ?></textarea>
                                        <div class="form-text">En fazla 160 karakter</div>
                                    </div>
                                </div>
                                
                                <!-- Sağ Kolon -->
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label class="form-label">Durum</label>
                                        <select name="durum" class="form-select">
                                            <option value="taslak" <?php echo $yazi['durum'] == 'taslak' ? 'selected' : ''; ?>>Taslak</option>
                                            <option value="yayinda" <?php echo $yazi['durum'] == 'yayinda' ? 'selected' : ''; ?>>Yayında</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Kategori</label>
                                        <select name="kategori_id" class="form-select" required>
                                            <option value="">Kategori Seçin</option>
                                            <?php while ($kategori = $kategoriler->fetch_assoc()): ?>
                                            <option value="<?php echo $kategori['id']; ?>" <?php echo $yazi['kategori_id'] == $kategori['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($kategori['kategori_adi']); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Etiketler</label>
                                        <select name="etiketler[]" id="etiketler" class="form-control" multiple="multiple">
                                            <?php
                                            // Mevcut etiketleri seçili olarak göster
                                            if (!empty($yazi['etiketler'])) {
                                                $mevcut_etiketler = explode(',', $yazi['etiketler']);
                                                foreach ($mevcut_etiketler as $etiket) {
                                                    echo '<option value="' . htmlspecialchars(trim($etiket)) . '" selected>' . htmlspecialchars(trim($etiket)) . '</option>';
                                                }
                                            }
                                            
                                            // Popüler etiketleri göster
                                            if ($populer_etiketler->num_rows > 0) {
                                                while ($etiket = $populer_etiketler->fetch_assoc()) {
                                                    if (!in_array($etiket['etiket_adi'], $mevcut_etiketler ?? [])) {
                                                        echo '<option value="' . htmlspecialchars($etiket['etiket_adi']) . '">' . 
                                                             htmlspecialchars($etiket['etiket_adi']) . ' (' . $etiket['kullanim_sayisi'] . ')</option>';
                                                    }
                                                }
                                            }
                                            ?>
                                        </select>
                                        <div class="form-text">Etiket eklemek için yazın veya listeden seçin. Birden fazla etiket ekleyebilirsiniz.</div>
                                        
                                        <?php if ($populer_etiketler->num_rows > 0): ?>
                                        <div class="mt-3">
                                            <small class="text-muted d-block mb-2">Popüler Etiketler:</small>
                                            <div class="popular-tags">
                                                <?php
                                                $populer_etiketler->data_seek(0); // Sonuç kümesini başa sar
                                                while ($etiket = $populer_etiketler->fetch_assoc()): 
                                                ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary me-1 mb-1 etiket-ekle" 
                                                        data-etiket="<?php echo htmlspecialchars($etiket['etiket_adi']); ?>">
                                                    <?php echo htmlspecialchars($etiket['etiket_adi']); ?>
                                                    <span class="badge bg-secondary ms-1"><?php echo $etiket['kullanim_sayisi']; ?></span>
                                                </button>
                                                <?php endwhile; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">Kapak Resmi</label>
                                        <input type="file" name="kapak_resmi" class="form-control" accept="image/*">
                                        <div class="form-text">Önerilen boyut: 1200x630px</div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <?php if (!empty($yazi['kapak_resmi'])): ?>
                                        <div class="kapak-onizleme">
                                            <img src="../<?php echo htmlspecialchars($yazi['kapak_resmi']); ?>" alt="Kapak Resmi" class="img-fluid rounded">
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle me-1"></i>
                                                    Son Güncelleme: <?php echo date('d.m.Y H:i', strtotime($yazi['tarih'])); ?>
                                                </small>
                                            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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
                    for(let i=0; i < files.length; i++) {
                        uploadImage(files[i]);
                    }
                }
            }
        });
        
        // Select2 ile etiket yönetimi
        $('#etiketler').select2({
            tags: true,
            tokenSeparators: [',', ' '],
            placeholder: 'Etiketleri yazın veya seçin...',
            allowClear: true,
            theme: 'bootstrap-5',
            language: {
                noResults: function() {
                    return "Etiket bulunamadı";
                },
                searching: function() {
                    return "Aranıyor...";
                },
                inputTooShort: function() {
                    return "Lütfen aramak için daha fazla karakter girin...";
                }
            },
            minimumInputLength: 1,
            maximumSelectionLength: 10,
            selectOnClose: true,
            createTag: function(params) {
                var term = $.trim(params.term);
                
                if (term === '') {
                    return null;
                }
                
                // Türkçe karakterleri ve özel karakterleri temizle
                term = term.toLowerCase()
                          .replace(/[^a-z0-9ğüşıöç\s]/g, '')
                          .trim();
                
                return {
                    id: term,
                    text: term,
                    newTag: true
                }
            }
        });
        
        // Popüler etiket butonları
        $('.etiket-ekle').click(function() {
            var etiket = $(this).data('etiket');
            
            // Etiket zaten seçili mi kontrol et
            var mevcut = $('#etiketler').val() || [];
            if (!mevcut.includes(etiket)) {
                // Yeni bir option oluştur ve seç
                var newOption = new Option(etiket, etiket, true, true);
                $('#etiketler').append(newOption).trigger('change');
                
                // Görsel feedback
                $(this).removeClass('btn-outline-secondary').addClass('btn-secondary')
                       .delay(500).queue(function(next) {
                           $(this).removeClass('btn-secondary').addClass('btn-outline-secondary');
                           next();
                       });
            }
        });
        
        // Form gönderilmeden önce etiketleri düzenle
        $('form').on('submit', function() {
            var etiketler = $('#etiketler').val();
            if (etiketler) {
                // Etiketleri temizle ve düzenle
                etiketler = etiketler.map(function(etiket) {
                    return etiket.toLowerCase()
                               .replace(/[^a-z0-9ğüşıöç\s]/g, '')
                               .trim();
                }).filter(Boolean); // Boş etiketleri filtrele
                
                // Tekrarlayan etiketleri kaldır
                etiketler = [...new Set(etiketler)];
                
                // Select2'yi güncelle
                $('#etiketler').val(etiketler).trigger('change');
            }
        });
    });

    // Kapak Resmi Önizleme
    document.querySelector('input[name="kapak_resmi"]').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.querySelector('.kapak-onizleme img');
                if (img) {
                    img.src = e.target.result;
                } else {
                    const newImg = document.createElement('img');
                    newImg.src = e.target.result;
                    newImg.alt = 'Kapak Resmi Önizleme';
                    newImg.className = 'img-fluid rounded';
                    document.querySelector('.kapak-onizleme').appendChild(newImg);
                }
                document.querySelector('.kapak-onizleme').style.display = 'block';
            }
            reader.readAsDataURL(file);
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