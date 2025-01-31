<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kullanıcı girişi kontrolü
if (!isset($_SESSION['kullanici_id'])) {
    header("Location: giris.php");
    exit();
}

include 'baglan.php';

// POST isteği kontrolü
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['hesap_sil'])) {
    $kullanici_id = $_SESSION['kullanici_id'];
    $sifre = $_POST['sifre'];

    // Kullanıcı şifresini doğrula
    $sql = "SELECT sifre FROM kullanicilar WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $kullanici_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($sifre, $user['sifre'])) {
        // Transaction başlat
        $conn->begin_transaction();

        try {
            // Şifre sıfırlama kayıtlarını sil
            $delete_reset = "DELETE FROM sifre_sifirlama WHERE kullanici_id = ?";
            $stmt = $conn->prepare($delete_reset);
            $stmt->bind_param("i", $kullanici_id);
            $stmt->execute();

            // Kullanıcının blog yazılarını güncelle
            $update_posts = "UPDATE blog_yazilar SET yazar_id = NULL WHERE yazar_id = ?";
            $stmt = $conn->prepare($update_posts);
            $stmt->bind_param("i", $kullanici_id);
            $stmt->execute();

            // Kullanıcının yorumlarını güncelle
            $update_comments = "UPDATE yorumlar SET kullanici_id = NULL WHERE kullanici_id = ?";
            $stmt = $conn->prepare($update_comments);
            $stmt->bind_param("i", $kullanici_id);
            $stmt->execute();

            // Kullanıcı hesabını sil
            $delete_user = "DELETE FROM kullanicilar WHERE id = ?";
            $stmt = $conn->prepare($delete_user);
            $stmt->bind_param("i", $kullanici_id);
            $stmt->execute();

            // Transaction'ı onayla
            $conn->commit();

            // Oturumu sonlandır
            session_destroy();
            header("Location: index.php?mesaj=hesap_silindi");
            exit();
        } catch (Exception $e) {
            // Hata durumunda transaction'ı geri al
            $conn->rollback();
            $_SESSION['hata'] = "Hesap silinirken bir hata oluştu: " . $e->getMessage();
        }
    } else {
        $_SESSION['hata'] = "Şifre yanlış.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hesabı Sil - Blog Sitesi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #ef4444;
            --secondary-color: #dc2626;
            --background-color: #f8fafc;
            --text-color: #0f172a;
            --light-text: #64748b;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
        }

        .delete-account-card {
            background: var(--card-bg);
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            max-width: 500px;
            margin: 2rem auto;
        }

        .warning-icon {
            color: var(--primary-color);
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .btn-delete {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-delete:hover {
            background-color: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-cancel {
            background-color: var(--light-text);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-cancel:hover {
            background-color: var(--text-color);
            transform: translateY(-2px);
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(239, 68, 68, 0.1);
        }

        .alert {
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container">
        <div class="delete-account-card">
            <div class="text-center">
                <i class="fas fa-exclamation-triangle warning-icon"></i>
                <h2 class="mb-4">Hesabı Sil</h2>
            </div>

            <?php if (isset($_SESSION['hata'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $_SESSION['hata']; unset($_SESSION['hata']); ?>
                </div>
            <?php endif; ?>

            <div class="alert alert-warning">
                <h5 class="alert-heading"><i class="fas fa-exclamation-circle me-2"></i>Önemli Uyarı!</h5>
                <p class="mb-0">Hesabınızı silmek geri alınamaz bir işlemdir. Tüm verileriniz kalıcı olarak silinecektir:</p>
                <ul class="mt-2 mb-0">
                    <li>Profil bilgileriniz</li>
                    <li>Blog yazılarınız (anonim olarak kalacak)</li>
                    <li>Yorumlarınız (anonim olarak kalacak)</li>
                    <li>Diğer tüm hesap verileri</li>
                </ul>
            </div>

            <form action="" method="POST" onsubmit="return confirm('Hesabınızı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!');">
                <div class="mb-4">
                    <label for="sifre" class="form-label">Onaylamak için şifrenizi girin</label>
                    <input type="password" class="form-control" id="sifre" name="sifre" required>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" name="hesap_sil" class="btn btn-delete">
                        <i class="fas fa-trash-alt me-2"></i>Hesabımı Kalıcı Olarak Sil
                    </button>
                    <a href="profil.php" class="btn btn-cancel">
                        <i class="fas fa-times me-2"></i>İptal Et
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 