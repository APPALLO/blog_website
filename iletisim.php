<?php
session_start();
require_once('baglan.php');

// Mesaj gönderme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad = trim(htmlspecialchars($_POST['ad']));
    $email = trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL));
    $konu = trim(htmlspecialchars($_POST['konu']));
    $mesaj = trim(htmlspecialchars($_POST['mesaj']));
    $hata = false;

    // Form doğrulama
    if (empty($ad) || empty($email) || empty($konu) || empty($mesaj)) {
        $_SESSION['iletisim_hata'] = "Lütfen tüm alanları doldurun.";
        $hata = true;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['iletisim_hata'] = "Geçerli bir e-posta adresi girin.";
        $hata = true;
    }

    if (!$hata) {
        $sql = "INSERT INTO iletisim_mesajlari (ad, email, konu, mesaj, durum, tarih) VALUES (?, ?, ?, ?, 'okunmamis', NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $ad, $email, $konu, $mesaj);

        if ($stmt->execute()) {
            $_SESSION['iletisim_basari'] = "Mesajınız başarıyla gönderildi. En kısa sürede size dönüş yapacağız.";
            header("Location: iletisim.php");
            exit();
        } else {
            $_SESSION['iletisim_hata'] = "Mesaj gönderilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İletişim - Blog Sitesi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .contact-form {
            background: var(--beyaz);
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-top: 2rem;
        }

        .form-control {
            border: 2px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--mavi-orta);
            box-shadow: 0 0 0 0.2rem rgba(66, 153, 225, 0.25);
        }

        .contact-icon {
            width: 48px;
            height: 48px;
            background: var(--mavi-pastel);
            color: var(--mavi-koyu);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-bottom: 1rem;
        }

        .contact-info {
            padding: 2rem;
            background: var(--mavi-pastel);
            border-radius: 1rem;
            height: 100%;
        }

        .contact-info h3 {
            color: var(--mavi-koyu);
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .contact-item {
            margin-bottom: 1.5rem;
        }

        .contact-item p {
            color: var(--gri-koyu);
            margin: 0;
        }

        .btn-gonder {
            background: linear-gradient(45deg, var(--mavi-koyu), var(--mavi-orta));
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-gonder:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .alert {
            border: none;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container py-5">
        <div class="row">
            <div class="col-lg-8">
                <h1 class="mb-4">İletişim</h1>
                
                <?php if (isset($_SESSION['iletisim_hata'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['iletisim_hata']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                </div>
                <?php unset($_SESSION['iletisim_hata']); endif; ?>

                <?php if (isset($_SESSION['iletisim_basari'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['iletisim_basari']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
                </div>
                <?php unset($_SESSION['iletisim_basari']); endif; ?>

                <div class="contact-form">
                    <form method="POST" action="" id="iletisimForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="ad" class="form-label">Adınız Soyadınız</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="ad" name="ad" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-posta Adresiniz</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="konu" class="form-label">Konu</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" id="konu" name="konu" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="mesaj" class="form-label">Mesajınız</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-comment"></i></span>
                                <textarea class="form-control" id="mesaj" name="mesaj" rows="5" required></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-gonder">
                            <i class="fas fa-paper-plane me-2"></i>Mesaj Gönder
                        </button>
                    </form>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="contact-info">
                    <h3><i class="fas fa-info-circle me-2"></i>İletişim Bilgileri</h3>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h5>Adres</h5>
                        <p>İstanbul, Türkiye</p>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h5>E-posta</h5>
                        <p>info@blogsite.com</p>
                    </div>
                    <div class="contact-item">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h5>Telefon</h5>
                        <p>+90 (212) 123 45 67</p>
                    </div>
                    <div class="contact-item mb-0">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h5>Çalışma Saatleri</h5>
                        <p>Pazartesi - Cuma: 09:00 - 18:00</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form doğrulama
        document.getElementById('iletisimForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailPattern.test(email)) {
                e.preventDefault();
                alert('Lütfen geçerli bir e-posta adresi girin.');
            }
        });
    </script>
</body>
</html> 