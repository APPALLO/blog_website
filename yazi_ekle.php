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

// Kategorileri getir
$sql = "SELECT * FROM kategoriler ORDER BY kategori_adi ASC";
$kategoriler = $conn->query($sql);

// Form gönderildiğinde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $baslik = trim(htmlspecialchars($_POST['baslik']));
    $ozet = trim(htmlspecialchars($_POST['ozet']));
    $icerik = trim($_POST['icerik']);
    $kategori_id = (int)$_POST['kategori_id'];
    $durum = $_POST['durum'];
    $seo_url = trim(htmlspecialchars($_POST['seo_url']));
    
    // Validasyon
    $hata = false;
    if (empty($baslik) || empty($icerik) || empty($kategori_id)) {
        $_SESSION['hata'] = "Lütfen tüm zorunlu alanları doldurun.";
        $hata = true;
    }
    
    if (!$hata) {
        // Kapak resmini yükle
        $kapak_resmi = '';
        if (isset($_FILES['kapak_resmi']) && $_FILES['kapak_resmi']['error'] === 0) {
            $izin_verilen_uzantilar = ['jpg', 'jpeg', 'png', 'webp'];
            $dosya_uzantisi = strtolower(pathinfo($_FILES['kapak_resmi']['name'], PATHINFO_EXTENSION));
            
            if (in_array($dosya_uzantisi, $izin_verilen_uzantilar)) {
                $yeni_isim = 'kapak_' . time() . '_' . uniqid() . '.' . $dosya_uzantisi;
                $hedef_dizin = 'uploads/kapak/';
                
                if (!file_exists($hedef_dizin)) {
                    mkdir($hedef_dizin, 0777, true);
                }
                
                if (move_uploaded_file($_FILES['kapak_resmi']['tmp_name'], $hedef_dizin . $yeni_isim)) {
                    $kapak_resmi = $hedef_dizin . $yeni_isim;
                }
            }
        }
        
        // Yazıyı ekle
        $sql = "INSERT INTO blog_yazilar (baslik, ozet, icerik, kategori_id, yazar_id, kapak_resmi, seo_url, durum, tarih) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssiisss", $baslik, $ozet, $icerik, $kategori_id, $_SESSION['kullanici_id'], $kapak_resmi, $seo_url, $durum);
        
        if ($stmt->execute()) {
            $yazi_id = $conn->insert_id;
            
            // Etiketleri ekle
            if (!empty($_POST['etiketler'])) {
                foreach ($_POST['etiketler'] as $etiket) {
                    if (is_numeric($etiket)) {
                        // Mevcut etiket
                        $etiket_id = $etiket;
                    } else {
                        // Yeni etiket ekle
                        $sql = "INSERT INTO etiketler (etiket_adi, seo_url) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                        $etiket_seo = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $etiket), '-'));
                        $stmt->bind_param("ss", $etiket, $etiket_seo);
                    $stmt->execute();
                        $etiket_id = $conn->insert_id;
                    }
                    
                    // Yazı-etiket ilişkisini ekle
                    $sql = "INSERT IGNORE INTO yazi_etiketler (yazi_id, etiket_id) VALUES (?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ii", $yazi_id, $etiket_id);
                    $stmt->execute();
                }
            }
            
            // Taslağı sil
            $sql = "DELETE FROM yazi_taslaklar WHERE kullanici_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $_SESSION['kullanici_id']);
            $stmt->execute();
            
            $_SESSION['basari'] = "Yazı başarıyla eklendi.";
            header("Location: yazilarim.php");
            exit();
        } else {
            $_SESSION['hata'] = "Yazı eklenirken bir hata oluştu.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Yazı Ekle - Blog Sitesi</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #7952b3;
            --secondary-color: #61428f;
            --accent-color: #8c68c9;
            --background-color: #f8f9fa;
            --text-color: #212529;
            --border-color: #dee2e6;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding: 3rem 0;
            margin-bottom: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.1)" x="0" y="0" width="100" height="100"/></svg>') repeat;
            opacity: 0.1;
            animation: backgroundMove 20s linear infinite;
        }

        @keyframes backgroundMove {
            from { background-position: 0 0; }
            to { background-position: 100px 100px; }
        }

        .card {
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            font-weight: 600;
            padding: 1rem;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .form-control, .form-select {
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(124, 58, 237, 0.1);
        }

        .select2-container--default .select2-selection--multiple {
            border: 1px solid var(--border-color);
            border-radius: 0.375rem;
        }

        .ql-container {
            height: 300px;
            font-family: inherit;
        }

        .ql-toolbar {
            border-radius: 0.375rem 0.375rem 0 0;
        }

        .ql-container {
            border-radius: 0 0 0.375rem 0.375rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .preview-image {
            max-width: 100%;
            border-radius: 0.375rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .toast-container {
            z-index: 1050;
        }

        .toast {
            background-color: var(--primary-color);
        }

        .content-wrapper {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .content-card {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .content-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .editor-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 0;
        }

        #editor {
            flex: 1;
            overflow-y: auto;
            min-height: 200px;
            max-height: 400px;
        }

        .ql-container {
            height: auto;
            flex: 1;
            overflow-y: auto;
        }

        .right-column {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .right-cards {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-3">Yeni Yazı Ekle</h1>
                    <p class="lead mb-0">Düşüncelerinizi paylaşın, deneyimlerinizi aktarın</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container py-4">
        <div class="row" style="min-height: calc(100vh - 400px);">
            <div class="col-lg-8">
                <!-- Yazı Detayları -->
                <div class="card content-card h-100">
                    <div class="card-header">
                        <i class="fas fa-edit me-2"></i>Yazı Detayları
                    </div>
                    <div class="card-body content-body">
                        <form id="yaziForm" action="yazi_kaydet.php" method="POST" enctype="multipart/form-data" class="h-100">
                            <div class="content-wrapper">
                                <div class="mb-3">
                                    <label class="form-label">Başlık</label>
                                    <input type="text" class="form-control" name="baslik" required 
                                           placeholder="Yazınız için etkileyici bir başlık girin">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Özet</label>
                                    <textarea class="form-control" name="ozet" rows="3" required
                                              placeholder="Yazınızın kısa bir özetini girin"></textarea>
                        </div>
                        
                                <div class="editor-wrapper">
                                    <label class="form-label">İçerik</label>
                                    <div id="editor"></div>
                                    <input type="hidden" name="icerik" id="icerik">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Kapak Görseli -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-image me-2"></i>Kapak Görseli
                    </div>
                    <div class="card-body">
                        <input type="file" class="form-control" name="kapak_resmi" form="yaziForm" 
                               accept="image/*" onchange="previewImage(this)">
                        <div id="imagePreview" class="text-center mt-3" style="display: none;">
                            <img src="" alt="Önizleme" class="preview-image">
                        </div>
                                    </div>
                                </div>
                                
                <!-- Kategoriler ve Etiketler -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-tags me-2"></i>Kategoriler ve Etiketler
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="kategori_id" form="yaziForm" required>
                                            <option value="">Kategori Seçin</option>
                                <?php
                                $kategoriler = $conn->query("SELECT * FROM kategoriler ORDER BY kategori_adi");
                                while ($kategori = $kategoriler->fetch_assoc()) {
                                    echo '<option value="' . $kategori['id'] . '">' . 
                                         htmlspecialchars($kategori['kategori_adi']) . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Etiketler</label>
                            <select class="form-select" name="etiketler[]" id="etiketler" form="yaziForm" multiple>
                                <?php
                                $etiketler = $conn->query("SELECT * FROM etiketler ORDER BY etiket_adi");
                                while ($etiket = $etiketler->fetch_assoc()) {
                                    echo '<option value="' . $etiket['id'] . '">' . 
                                         htmlspecialchars($etiket['etiket_adi']) . '</option>';
                                }
                                ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                <!-- Yayın Ayarları -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-cog me-2"></i>Yayın Ayarları
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Durum</label>
                            <select class="form-select" name="durum" form="yaziForm">
                                <option value="taslak">Taslak Olarak Kaydet</option>
                                <option value="yayinda">Hemen Yayınla</option>
                            </select>
                            </div>
                            
                        <div class="mb-3">
                            <label class="form-label">SEO Dostu URL</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-link"></i></span>
                                <input type="text" class="form-control" name="seo_url" form="yaziForm" id="seoUrl" readonly>
                            </div>
                            <small class="text-muted">URL otomatik oluşturulacaktır.</small>
                            </div>
                            
                        <div class="d-grid gap-2">
                            <button type="submit" form="yaziForm" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Yazıyı Kaydet
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="önizlemeGöster()">
                                <i class="fas fa-eye me-2"></i>Önizle
                            </button>
                        </div>
                    </div>
                                    </div>
                                    </div>
                                </div>
                            </div>

    <!-- Önizleme Modal -->
    <div class="modal fade" id="onizlemeModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yazı Önizleme</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="preview-container">
                        <h1 id="previewTitle" class="mb-4"></h1>
                        <div class="preview-image-container text-center mb-4" style="display: none;">
                            <img src="" alt="Kapak Görseli" class="img-fluid rounded">
                            </div>
                        <div class="mb-3">
                            <span class="badge bg-primary me-2" id="previewCategory"></span>
                            <span id="previewTags"></span>
                            </div>
                        <div class="preview-content"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Otomatik Kaydetme Toast -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div class="toast align-items-center text-white bg-success border-0" id="autoSaveToast" role="alert">
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-check-circle me-2"></i>Taslak otomatik kaydedildi
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        // Quill editör ayarları
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'align': [] }],
                    ['link', 'image', 'video'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['clean']
                ]
            }
        });

        // Otomatik kaydetme değişkenleri
        let autoSaveTimeout;
        let lastSavedContent = '';
        const autoSaveDelay = 30000; // 30 saniye
        const autoSaveToast = new bootstrap.Toast(document.getElementById('autoSaveToast'));

        // Otomatik kaydetme fonksiyonu
        function autoSave() {
            const currentContent = {
                baslik: document.querySelector('input[name="baslik"]').value,
                ozet: document.querySelector('textarea[name="ozet"]').value,
                icerik: quill.root.innerHTML,
                kategori_id: document.querySelector('select[name="kategori_id"]').value,
                etiketler: $('#etiketler').val(),
                durum: 'taslak'
            };

            // İçerik değişmişse kaydet
            if (JSON.stringify(currentContent) !== lastSavedContent) {
                fetch('yazi_otomatik_kaydet.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(currentContent)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        lastSavedContent = JSON.stringify(currentContent);
                        autoSaveToast.show();
                    }
                });
            }
        }

        // İçerik değişikliklerini izle
        function startAutoSave() {
            clearTimeout(autoSaveTimeout);
            autoSaveTimeout = setTimeout(autoSave, autoSaveDelay);
        }

        // İçerik değişikliklerini dinle
        quill.on('text-change', startAutoSave);
        document.querySelector('input[name="baslik"]').addEventListener('input', startAutoSave);
        document.querySelector('textarea[name="ozet"]').addEventListener('input', startAutoSave);
        document.querySelector('select[name="kategori_id"]').addEventListener('change', startAutoSave);
        $('#etiketler').on('change', startAutoSave);

        // Form gönderilmeden önce içeriği gizli alana aktar
        document.getElementById('yaziForm').onsubmit = function() {
            document.getElementById('icerik').value = quill.root.innerHTML;
            return true;
        };

        // Select2 etiket seçici
        $(document).ready(function() {
            $('#etiketler').select2({
                placeholder: 'Etiket seçin veya yeni ekleyin',
                tags: true,
                tokenSeparators: [',', ' '],
                language: {
                    noResults: function() {
                        return "Etiket bulunamadı";
                    }
                }
            });
        });

        // Kapak resmi önizleme
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.querySelector('#imagePreview img').src = e.target.result;
                    document.querySelector('#imagePreview').style.display = 'block';
                    document.querySelector('.preview-image-container img').src = e.target.result;
                    document.querySelector('.preview-image-container').style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // SEO URL oluşturucu
        document.querySelector('input[name="baslik"]').addEventListener('input', function(e) {
            var url = e.target.value
                .toLowerCase()
                .replace(/[^a-z0-9\s]/g, '')
                .replace(/\s+/g, '-');
            document.getElementById('seoUrl').value = url;
        });

        // Önizleme fonksiyonu
        function önizlemeGöster() {
            const title = document.querySelector('input[name="baslik"]').value;
            const content = quill.root.innerHTML;
            const category = document.querySelector('select[name="kategori_id"] option:checked').text;
            const tags = Array.from(document.querySelectorAll('#etiketler option:checked')).map(opt => opt.text);
            
            document.getElementById('previewTitle').textContent = title;
            document.getElementById('previewCategory').textContent = category;
            document.getElementById('previewTags').innerHTML = tags.map(tag => 
                `<span class="badge bg-secondary me-1">${tag}</span>`
            ).join('');
            document.querySelector('.preview-content').innerHTML = content;

            const modal = new bootstrap.Modal(document.getElementById('onizlemeModal'));
            modal.show();
        }

        // Sayfa yüklendiğinde taslağı yükle
        window.addEventListener('load', function() {
            fetch('yazi_taslak_yukle.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.taslak) {
                        document.querySelector('input[name="baslik"]').value = data.taslak.baslik;
                        document.querySelector('textarea[name="ozet"]').value = data.taslak.ozet;
                        quill.root.innerHTML = data.taslak.icerik;
                        document.querySelector('select[name="kategori_id"]').value = data.taslak.kategori_id;
                        $('#etiketler').val(data.taslak.etiketler).trigger('change');
                        lastSavedContent = JSON.stringify(data.taslak);
                    }
                });
        });
    </script>
</body>
</html> 