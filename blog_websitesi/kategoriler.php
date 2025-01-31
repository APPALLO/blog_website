<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'baglan.php';

// Kategorileri getir
$sql = "SELECT k.*, COUNT(b.id) as yazi_sayisi 
        FROM kategoriler k 
        LEFT JOIN blog_yazilar b ON k.id = b.kategori_id AND b.durum = 'yayinda'
        GROUP BY k.id 
        ORDER BY k.kategori_adi ASC";
$kategoriler = $conn->query($sql);

// Kategori ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['kategori_ekle']) && isset($_SESSION['kullanici_id'])) {
    $kategori_adi = trim(htmlspecialchars($_POST['kategori_adi']));
    $aciklama = trim(htmlspecialchars($_POST['aciklama']));
    $ikon = trim(htmlspecialchars($_POST['ikon']));
    $olusturan_id = $_SESSION['kullanici_id'];

    if (!empty($kategori_adi)) {
        // Kategori adının benzersiz olup olmadığını kontrol et
        $kontrol_sql = "SELECT id FROM kategoriler WHERE kategori_adi = ?";
        $stmt = $conn->prepare($kontrol_sql);
        $stmt->bind_param("s", $kategori_adi);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            $ekle_sql = "INSERT INTO kategoriler (kategori_adi, aciklama, ikon, olusturan_id, created_at) 
                        VALUES (?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($ekle_sql);
            $stmt->bind_param("sssi", $kategori_adi, $aciklama, $ikon, $olusturan_id);
            
            if ($stmt->execute()) {
                $_SESSION['basari'] = "Kategori başarıyla eklendi.";
            } else {
                $_SESSION['hata'] = "Kategori eklenirken bir hata oluştu.";
            }
        } else {
            $_SESSION['hata'] = "Bu kategori adı zaten kullanılıyor.";
        }
        header("Location: kategoriler.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategoriler - Blog Sitesi</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --secondary-color: #7c3aed;
            --accent-color: #818cf8;
            --background-color: #f8fafc;
            --text-color: #0f172a;
            --light-text: #64748b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --hover-color: #6366f1;
            --gradient-start: #4f46e5;
            --gradient-end: #7c3aed;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
        }

        .page-header {
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            color: white;
            padding: 3rem 0;
            margin-bottom: 3rem;
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

        .category-card {
            background: var(--card-bg);
            border-radius: 1rem;
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
            overflow: hidden;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .category-card .card-body {
            padding: 1.5rem;
        }

        .category-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: white;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .category-card:hover .category-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .category-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .category-count {
            color: var(--light-text);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .category-description {
            color: var(--light-text);
            margin-top: 1rem;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .btn-category {
            color: var(--primary-color);
            background: transparent;
            border: 2px solid var(--primary-color);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-category:hover {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
        }

        .category-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .category-card:hover::before {
            opacity: 1;
        }

        .add-category-card {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(124, 58, 237, 0.1) 100%);
            border: 2px dashed var(--primary-color);
            border-radius: 1rem;
            transition: all 0.3s ease;
            height: 100%;
            cursor: pointer;
        }

        .add-category-card:hover {
            transform: translateY(-5px);
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.15) 0%, rgba(124, 58, 237, 0.15) 100%);
        }

        .add-category-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            text-align: center;
            height: 100%;
        }

        .add-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            color: white;
            font-size: 1.5rem;
            transition: all 0.3s ease;
        }

        .add-category-card:hover .add-icon {
            transform: scale(1.1) rotate(180deg);
        }

        .modal-content {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border-radius: 1rem 1rem 0 0;
            border: none;
        }

        .modal-title {
            font-weight: 600;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
        }

        .btn-add {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            color: white;
            border: none;
            border-radius: 0.5rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }

        .alert {
            border: none;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: rgba(34, 197, 94, 0.1);
            color: #22c55e;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="page-header">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-3">Kategoriler</h1>
                    <p class="lead mb-0">İlgilendiğiniz konulardaki yazıları keşfedin</p>
                </div>
            </div>
        </div>
    </div>

    <div class="container mb-5">
        <?php
        if (isset($_SESSION['basari'])) {
            echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' . $_SESSION['basari'] . '</div>';
            unset($_SESSION['basari']);
        }
        if (isset($_SESSION['hata'])) {
            echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' . $_SESSION['hata'] . '</div>';
            unset($_SESSION['hata']);
        }
        ?>

        <div class="row g-4">
            <?php while ($kategori = $kategoriler->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="category-card position-relative">
                        <div class="card-body">
                            <div class="category-icon">
                                <i class="<?php echo !empty($kategori['ikon']) ? $kategori['ikon'] : 'fas fa-folder'; ?>"></i>
                            </div>
                            <h3 class="category-title"><?php echo htmlspecialchars($kategori['kategori_adi']); ?></h3>
                            <div class="category-count">
                                <i class="fas fa-file-alt"></i>
                                <span><?php echo $kategori['yazi_sayisi']; ?> yazı</span>
                            </div>
                            <?php if (!empty($kategori['aciklama'])): ?>
                                <p class="category-description"><?php echo htmlspecialchars($kategori['aciklama']); ?></p>
                            <?php endif; ?>
                            <a href="kategori.php?id=<?php echo $kategori['id']; ?>" class="btn btn-category">
                                <span>Yazıları Gör</span>
                                <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if (isset($_SESSION['kullanici_id'])): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="add-category-card" data-bs-toggle="modal" data-bs-target="#kategoriEkleModal">
                        <div class="add-category-content">
                            <div class="add-icon">
                                <i class="fas fa-plus"></i>
                            </div>
                            <h3 class="category-title">Yeni Kategori Ekle</h3>
                            <p class="text-muted">Yeni bir yazı kategorisi oluşturun</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Kategori Ekleme Modal -->
    <div class="modal fade" id="kategoriEkleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Kategori Ekle</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label for="kategori_adi" class="form-label">Kategori Adı</label>
                            <input type="text" class="form-control" id="kategori_adi" name="kategori_adi" required>
                        </div>
                        <div class="mb-3">
                            <label for="aciklama" class="form-label">Açıklama</label>
                            <textarea class="form-control" id="aciklama" name="aciklama" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="ikon" class="form-label">İkon (Font Awesome Class)</label>
                            <input type="text" class="form-control" id="ikon" name="ikon" placeholder="Örn: fas fa-book">
                            <div class="form-text">
                                <a href="https://fontawesome.com/icons" target="_blank">Font Awesome</a> sitesinden ikon seçebilirsiniz.
                            </div>
                        </div>
                        <button type="submit" name="kategori_ekle" class="btn btn-add w-100">
                            <i class="fas fa-plus me-2"></i>Kategori Ekle
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 