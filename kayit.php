<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Blog Sitesi</title>
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
            --error-color: #ef4444;
            --success-color: #22c55e;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .register-container {
            flex: 1;
            padding: 3rem 0;
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(124, 58, 237, 0.1) 100%);
        }

        .card {
            border: none;
            border-radius: 1.5rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
            background: var(--card-bg);
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-title {
            color: var(--text-color);
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            padding-bottom: 1rem;
        }

        .card-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border-radius: 3px;
        }

        .form-label {
            color: var(--text-color);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid var(--border-color);
            border-radius: 1rem;
            padding: 0.8rem 1.2rem;
            transition: all 0.3s ease;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.1);
        }

        .form-text {
            color: var(--light-text);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            border: none;
            border-radius: 1rem;
            padding: 0.8rem 2rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: all 0.5s ease;
        }

        .btn-primary:hover::before {
            left: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px -10px var(--primary-color);
        }

        .form-check-input {
            border-color: var(--border-color);
            width: 1.2rem;
            height: 1.2rem;
            margin-top: 0.2rem;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-label {
            color: var(--text-color);
            font-size: 0.95rem;
        }

        .form-check-label a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .form-check-label a:hover {
            color: var(--secondary-color);
        }

        .alert {
            border: none;
            border-radius: 1rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--error-color);
        }

        .alert-success {
            background-color: rgba(34, 197, 94, 0.1);
            color: var(--success-color);
        }

        .text-center a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }

        .text-center a::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 2px;
            background: var(--primary-color);
            bottom: -2px;
            left: 0;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .text-center a:hover::after {
            transform: scaleX(1);
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--light-text);
            transition: color 0.3s ease;
            cursor: pointer;
            z-index: 10;
        }

        .input-icon:hover {
            color: var(--primary-color);
        }

        .form-feedback {
            font-size: 0.875rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-feedback i {
            font-size: 1rem;
        }

        .form-feedback.valid {
            color: var(--success-color);
        }

        .form-feedback.invalid {
            color: var(--error-color);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.3s ease-in-out;
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="register-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body p-4 p-md-5">
                            <h2 class="card-title">Kayıt Ol</h2>
                            
                            <?php
                            if(isset($_SESSION['hata'])) {
                                echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' . $_SESSION['hata'] . '</div>';
                                unset($_SESSION['hata']);
                            }
                            if(isset($_SESSION['basari'])) {
                                echo '<div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>' . $_SESSION['basari'] . '</div>';
                                unset($_SESSION['basari']);
                            }
                            ?>

                            <form action="kayit_isle.php" method="POST" id="kayitForm">
                                <div class="mb-4">
                                    <label for="ad_soyad" class="form-label">Ad Soyad</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="ad_soyad" name="ad_soyad" required>
                                        <i class="fas fa-user input-icon"></i>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="kullanici_adi" class="form-label">Kullanıcı Adı</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="kullanici_adi" name="kullanici_adi" required>
                                        <i class="fas fa-at input-icon"></i>
                                    </div>
                                    <div id="kullaniciAdiGeri" class="form-feedback"></div>
                                </div>
                                <div class="mb-4">
                                    <label for="email" class="form-label">E-posta Adresi</label>
                                    <div class="input-group">
                                        <input type="email" class="form-control" id="email" name="email" required>
                                        <i class="fas fa-envelope input-icon"></i>
                                    </div>
                                    <div id="emailGeri" class="form-feedback"></div>
                                </div>
                                <div class="mb-4">
                                    <label for="sifre" class="form-label">Şifre</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="sifre" name="sifre" required 
                                               pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                                               title="En az 8 karakter, bir büyük harf, bir küçük harf ve bir rakam içermelidir">
                                        <i class="fas fa-eye-slash input-icon" id="sifreGoster"></i>
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Şifreniz en az 8 karakter uzunluğunda olmalı ve en az bir büyük harf, bir küçük harf ve bir rakam içermelidir.
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <label for="sifre_tekrar" class="form-label">Şifre Tekrar</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="sifre_tekrar" name="sifre_tekrar" required>
                                        <i class="fas fa-eye-slash input-icon" id="sifreTekrarGoster"></i>
                                    </div>
                                    <div id="sifreGeri" class="form-feedback"></div>
                                </div>
                                <div class="mb-4 form-check">
                                    <input type="checkbox" class="form-check-input" id="sozlesme" name="sozlesme" required>
                                    <label class="form-check-label" for="sozlesme">
                                        <a href="kullanici_sozlesmesi.php" target="_blank">Kullanıcı sözleşmesini</a> okudum ve kabul ediyorum
                                    </label>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mb-4">Kayıt Ol</button>
                                
                                <div class="text-center">
                                    <p class="mb-0">Zaten hesabınız var mı? <a href="giris.php">Giriş Yap</a></p>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/main.js"></script>
    <script>
        document.getElementById('kayitForm').addEventListener('submit', function(e) {
            const sifre = document.getElementById('sifre').value;
            const sifreTekrar = document.getElementById('sifre_tekrar').value;
            const sifreGeri = document.getElementById('sifreGeri');
            
            if (sifre !== sifreTekrar) {
                e.preventDefault();
                sifreGeri.innerHTML = '<i class="fas fa-times-circle"></i> Şifreler eşleşmiyor!';
                sifreGeri.className = 'form-feedback invalid';
                document.getElementById('sifre_tekrar').classList.add('shake');
                setTimeout(() => {
                    document.getElementById('sifre_tekrar').classList.remove('shake');
                }, 500);
            }
        });

        // Şifre göster/gizle
        document.getElementById('sifreGoster').addEventListener('click', function() {
            const sifreInput = document.getElementById('sifre');
            const icon = this;
            if (sifreInput.type === 'password') {
                sifreInput.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                sifreInput.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });

        document.getElementById('sifreTekrarGoster').addEventListener('click', function() {
            const sifreInput = document.getElementById('sifre_tekrar');
            const icon = this;
            if (sifreInput.type === 'password') {
                sifreInput.type = 'text';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            } else {
                sifreInput.type = 'password';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            }
        });

        // Kullanıcı adı kontrolü
        document.getElementById('kullanici_adi').addEventListener('blur', function() {
            const kullaniciAdi = this.value;
            const geri = document.getElementById('kullaniciAdiGeri');
            
            if (kullaniciAdi.length > 0) {
                fetch('kullanici_kontrol.php?kullanici_adi=' + encodeURIComponent(kullaniciAdi))
                    .then(response => response.json())
                    .then(data => {
                        if (data.mevcut) {
                            geri.innerHTML = '<i class="fas fa-times-circle"></i> Bu kullanıcı adı zaten kullanılıyor!';
                            geri.className = 'form-feedback invalid';
                        } else {
                            geri.innerHTML = '<i class="fas fa-check-circle"></i> Bu kullanıcı adı kullanılabilir.';
                            geri.className = 'form-feedback valid';
                        }
                    });
            }
        });

        // Email kontrolü
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            const geri = document.getElementById('emailGeri');
            
            if (email.length > 0) {
                fetch('email_kontrol.php?email=' + encodeURIComponent(email))
                    .then(response => response.json())
                    .then(data => {
                        if (data.mevcut) {
                            geri.innerHTML = '<i class="fas fa-times-circle"></i> Bu e-posta adresi zaten kullanılıyor!';
                            geri.className = 'form-feedback invalid';
                        } else {
                            geri.innerHTML = '<i class="fas fa-check-circle"></i> Bu e-posta adresi kullanılabilir.';
                            geri.className = 'form-feedback valid';
                        }
                    });
            }
        });
    </script>
</body>
</html> 