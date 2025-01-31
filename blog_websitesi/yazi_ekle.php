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
        // Yazıyı ekle
        $sql = "INSERT INTO blog_yazilar (baslik, icerik, kategori_id, yazar_id, durum, tarih) 
                VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiis", $baslik, $icerik, $kategori_id, $_SESSION['kullanici_id'], $durum);
        
        if ($stmt->execute()) {
            $yazi_id = $conn->insert_id;
            
            // Etiketleri ekle
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- CKEditor CDN -->
    <script src="https://cdn.ckeditor.com/ckeditor5/40.0.0/classic/ckeditor.js"></script>
    <style>
        :root {
            --primary-color: #4F46E5;
            --secondary-color: #9B2C2C;
            --accent-color: #6366F1;
            --success-color: #059669;
            --gray-dark: #374151;
            --gray-medium: #9CA3AF;
            --gray-light: #F3F4F6;
            --white: #FFFFFF;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB;
            color: var(--gray-dark);
        }

        .card {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            background: var(--white);
            overflow: hidden;
        }

        .card-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--gray-dark);
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
        }

        .card-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px;
        }

        .form-label {
            font-weight: 600;
            color: var(--gray-dark);
            margin-bottom: 0.75rem;
        }

        .form-control, .form-select {
            border: 2px solid var(--gray-light);
            border-radius: 1rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.1);
        }

        .ck-editor__editable {
            min-height: 300px;
            border-radius: 0 0 1rem 1rem !important;
        }

        .ck.ck-editor__main>.ck-editor__editable {
            background: var(--white);
        }

        .ck.ck-toolbar {
            border-radius: 1rem 1rem 0 0 !important;
            background: var(--gray-light);
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
        }

        .btn-primary:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 15px rgba(99, 102, 241, 0.2);
        }

        .btn-secondary {
            background: var(--gray-light);
            border: none;
            color: var(--gray-dark);
        }

        .btn-secondary:hover {
            background: var(--gray-medium);
            color: var(--white);
            transform: translateY(-2px);
        }

        .form-check-input {
            width: 1.2em;
            height: 1.2em;
            margin-top: 0.25em;
            border: 2px solid var(--gray-medium);
            transition: all 0.2s ease;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            font-weight: 500;
            color: var(--gray-dark);
            margin-left: 0.5rem;
        }

        .alert {
            border: none;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
        }

        .alert-danger {
            background: #FEE2E2;
            color: #991B1B;
        }

        .form-text {
            color: var(--gray-medium);
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .tag-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .tag {
            background: var(--gray-light);
            color: var(--gray-dark);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tag .remove-tag {
            cursor: pointer;
            color: var(--gray-medium);
            transition: color 0.2s ease;
        }

        .tag .remove-tag:hover {
            color: var(--secondary-color);
        }

        .preview-container {
            margin-top: 2rem;
            padding: 2rem;
            background: var(--gray-light);
            border-radius: 1rem;
            display: none;
        }

        .preview-container.active {
            display: block;
        }

        .preview-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--gray-dark);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container mt-5 mb-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h1 class="card-title mb-0">Yeni Yazı Ekle</h1>
                            <button type="button" class="btn btn-outline-primary" id="previewButton">
                                <i class="fas fa-eye me-2"></i>Önizleme
                            </button>
                        </div>
                        
                        <?php if(isset($_SESSION['hata'])): ?>
                            <div class="alert alert-danger d-flex align-items-center">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php 
                                    echo $_SESSION['hata'];
                                    unset($_SESSION['hata']);
                                ?>
                            </div>
                        <?php endif; ?>

                        <form action="yazi_ekle.php" method="POST" id="yaziForm">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-4">
                                        <label for="baslik" class="form-label">
                                            <i class="fas fa-heading me-2"></i>Başlık
                                        </label>
                                        <input type="text" class="form-control" id="baslik" name="baslik" 
                                               value="<?php echo isset($_POST['baslik']) ? htmlspecialchars($_POST['baslik']) : ''; ?>" 
                                               required>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label for="kategori_id" class="form-label">
                                            <i class="fas fa-folder me-2"></i>Kategori
                                        </label>
                                        <select class="form-select" id="kategori_id" name="kategori_id" required>
                                            <option value="">Kategori Seçin</option>
                                            <?php while ($kategori = $kategoriler->fetch_assoc()): ?>
                                                <option value="<?php echo $kategori['id']; ?>" 
                                                    <?php echo isset($_POST['kategori_id']) && $_POST['kategori_id'] == $kategori['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($kategori['kategori_adi']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="editor" class="form-label">
                                    <i class="fas fa-pen me-2"></i>İçerik
                                </label>
                                <textarea class="form-control" id="editor" name="icerik" required>
                                    <?php echo isset($_POST['icerik']) ? htmlspecialchars($_POST['icerik']) : ''; ?>
                                </textarea>
                            </div>
                            
                            <div class="mb-4">
                                <label for="etiketler" class="form-label">
                                    <i class="fas fa-tags me-2"></i>Etiketler
                                </label>
                                <input type="text" class="form-control" id="etiketler" name="etiketler" 
                                       value="<?php echo isset($_POST['etiketler']) ? htmlspecialchars($_POST['etiketler']) : ''; ?>" 
                                       placeholder="Enter tuşuna basarak etiket ekleyin">
                                <div class="form-text">Örnek: teknoloji, yazılım, php</div>
                                <div class="tag-container" id="tagContainer"></div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-globe me-2"></i>Yayın Durumu
                                </label>
                                <div class="d-flex gap-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="durum" id="durum_taslak" 
                                               value="taslak" <?php echo (!isset($_POST['durum']) || $_POST['durum'] === 'taslak') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="durum_taslak">
                                            <i class="fas fa-save me-1"></i>Taslak olarak kaydet
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="durum" id="durum_yayinda" 
                                               value="yayinda" <?php echo (isset($_POST['durum']) && $_POST['durum'] === 'yayinda') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="durum_yayinda">
                                            <i class="fas fa-globe me-1"></i>Hemen yayınla
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="preview-container" id="previewContainer">
                                <h3 class="preview-title">Yazı Önizleme</h3>
                                <div id="previewContent"></div>
                            </div>
                            
                            <div class="d-flex gap-3 mt-5">
                                <button type="submit" class="btn btn-primary flex-grow-1">
                                    <i class="fas fa-save me-2"></i>Yazıyı Kaydet
                                </button>
                                <a href="yazilarim.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-2"></i>İptal
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
        let editor;
        
        ClassicEditor
            .create(document.querySelector('#editor'), {
                toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', 'blockQuote', 'insertTable', 'mediaEmbed'],
                language: 'tr'
            })
            .then(newEditor => {
                editor = newEditor;
            })
            .catch(error => {
                console.error(error);
            });

        // Etiket sistemi
        const etiketlerInput = document.getElementById('etiketler');
        const tagContainer = document.getElementById('tagContainer');
        const tags = new Set();

        etiketlerInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const tag = this.value.trim();
                if (tag && !tags.has(tag)) {
                    addTag(tag);
                    this.value = '';
                }
            }
        });

        function addTag(tag) {
            tags.add(tag);
            const tagElement = document.createElement('span');
            tagElement.className = 'tag';
            tagElement.innerHTML = `
                ${tag}
                <span class="remove-tag" onclick="removeTag('${tag}', this)">
                    <i class="fas fa-times"></i>
                </span>
            `;
            tagContainer.appendChild(tagElement);
            updateHiddenInput();
        }

        function removeTag(tag, element) {
            tags.delete(tag);
            element.parentElement.remove();
            updateHiddenInput();
        }

        function updateHiddenInput() {
            etiketlerInput.value = Array.from(tags).join(', ');
        }

        // Önizleme sistemi
        const previewButton = document.getElementById('previewButton');
        const previewContainer = document.getElementById('previewContainer');
        const previewContent = document.getElementById('previewContent');

        previewButton.addEventListener('click', function() {
            const title = document.getElementById('baslik').value;
            const content = editor.getData();
            
            if (previewContainer.classList.contains('active')) {
                previewContainer.classList.remove('active');
                previewButton.innerHTML = '<i class="fas fa-eye me-2"></i>Önizleme';
            } else {
                previewContent.innerHTML = `
                    <h2 class="mb-4">${title}</h2>
                    ${content}
                `;
                previewContainer.classList.add('active');
                previewButton.innerHTML = '<i class="fas fa-eye-slash me-2"></i>Önizlemeyi Kapat';
            }
        });
    </script>
</body>
</html> 