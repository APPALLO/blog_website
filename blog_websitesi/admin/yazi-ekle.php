<?php
session_start();
require_once('../baglan.php');

// Admin kontrolü
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Kategorileri çek
$sql = "SELECT * FROM kategoriler ORDER BY kategori_adi ASC";
$kategoriler = $conn->query($sql);

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $baslik = $_POST['baslik'];
    $icerik = $_POST['icerik'];
    $kategori_id = $_POST['kategori_id'];
    $etiketler = $_POST['etiketler'];
    $durum = $_POST['durum'];
    
    // Kapak resmi yükleme
    if (isset($_FILES['kapak_resmi']) && $_FILES['kapak_resmi']['error'] == 0) {
        $izin_verilen_uzantilar = array('jpg', 'jpeg', 'png', 'webp');
        $dosya_uzantisi = strtolower(pathinfo($_FILES['kapak_resmi']['name'], PATHINFO_EXTENSION));
        
        if (in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
            $yeni_isim = 'kapak_' . time() . '.' . $dosya_uzantisi;
            $hedef_klasor = '../uploads/';
            $hedef_dosya = $hedef_klasor . $yeni_isim;
            
            if (move_uploaded_file($_FILES['kapak_resmi']['tmp_name'], $hedef_dosya)) {
                $kapak_resmi = $yeni_isim;
            }
        }
    }
    
    // Yazıyı veritabanına ekle
    $sql = "INSERT INTO blog_yazilar (baslik, icerik, kategori_id, etiketler, kapak_resmi, durum, yazar_id, tarih) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisssi", $baslik, $icerik, $kategori_id, $etiketler, $kapak_resmi, $durum, $_SESSION['admin_id']);
    
    if ($stmt->execute()) {
        $mesaj = array(
            'tip' => 'success',
            'icerik' => 'Yazı başarıyla eklendi.'
        );
        header("Location: yazilar.php?mesaj=eklendi");
        exit();
    } else {
        $mesaj = array(
            'tip' => 'danger',
            'icerik' => 'Yazı eklenirken bir hata oluştu.'
        );
    }
}
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
    <style>
        .editor-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: none;
            margin-bottom: 2rem;
        }

        .editor-card .card-body {
            padding: 2rem;
        }

        .editor-header {
            color: #4361ee;
            font-weight: 600;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .editor-header i {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(67, 97, 238, 0.1);
            border-radius: 8px;
            color: #4361ee;
        }

        .note-editor {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .note-toolbar {
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .form-label {
            font-weight: 500;
            color: #444;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #4361ee;
            box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.1);
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

        .preview-image {
            max-width: 200px;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .tag-input {
            background: #f8f9fa;
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
                        <h2 class="h4 mb-0">Yeni Yazı Ekle</h2>
                        
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

                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <!-- Sol Kolon -->
                                <div class="col-md-8">
                                    <div class="editor-card">
                                        <div class="card-body">
                                            <div class="editor-header">
                                                <i class="fas fa-edit"></i>
                                                Yazı İçeriği
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Yazı Başlığı</label>
                                                <input type="text" class="form-control" name="baslik" required>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">İçerik</label>
                                                <textarea id="summernote" name="icerik"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Sağ Kolon -->
                                <div class="col-md-4">
                                    <div class="editor-card">
                                        <div class="card-body">
                                            <div class="editor-header">
                                                <i class="fas fa-cog"></i>
                                                Yazı Ayarları
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Kategori</label>
                                                <select class="form-select" name="kategori_id" required>
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
                                                <input type="text" class="form-control tag-input" name="etiketler" placeholder="Virgülle ayırarak yazın">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Kapak Resmi</label>
                                                <input type="file" class="form-control" name="kapak_resmi" accept="image/*" onchange="previewImage(this)">
                                                <img id="preview" class="preview-image d-none">
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Durum</label>
                                                <select class="form-select" name="durum">
                                                    <option value="taslak">Taslak</option>
                                                    <option value="aktif">Yayında</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Yazıyı Kaydet
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/lang/summernote-tr-TR.min.js"></script>
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

        // Summernote editör
        $(document).ready(function() {
            $('#summernote').summernote({
                height: 300,
                lang: 'tr-TR',
                toolbar: [
                    ['style', ['style']],
                    ['font', ['bold', 'underline', 'clear']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['table', ['table']],
                    ['insert', ['link', 'picture']],
                    ['view', ['fullscreen', 'codeview', 'help']]
                ]
            });
        });

        // Resim önizleme
        function previewImage(input) {
            var preview = document.getElementById('preview');
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html> 