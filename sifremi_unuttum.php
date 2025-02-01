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
    <title>Şifremi Unuttum - Blog Sitesi</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #3B82F6;
            --secondary-color: #9B2C2C;
            --accent-color: #4F46E5;
            --text-color: #1F2937;
            --light-bg: #F3F4F6;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-color);
            background: var(--light-bg);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .forgot-password-container {
            flex: 1;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 1rem 2rem rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            padding: 2rem;
            text-align: center;
            border: none;
        }

        .card-header h2 {
            color: white;
            font-weight: 600;
            margin: 0;
        }

        .card-header p {
            color: rgba(255,255,255,0.8);
            margin: 0.5rem 0 0;
            font-size: 0.95rem;
        }

        .card-body {
            padding: 2rem;
        }

        .form-label {
            font-weight: 500;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 2px solid var(--light-bg);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.1);
        }

        .input-group-text {
            background: var(--light-bg);
            border: 2px solid var(--light-bg);
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--primary-color);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.1);
        }

        .alert {
            border: none;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.1);
            color: #DC2626;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #10B981;
        }

        .text-center a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .text-center a:hover {
            color: var(--accent-color);
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="forgot-password-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-5">
                    <div class="card animate-fade-in">
                        <div class="card-header">
                            <h2 class="h3">
                                <i class="fas fa-key me-2"></i>Şifremi Unuttum
                            </h2>
                            <p>E-posta adresinizi girin, size şifre sıfırlama bağlantısı gönderelim.</p>
                        </div>
                        <div class="card-body">
                            <?php
                            if(isset($_SESSION['hata'])) {
                                echo '<div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>' . $_SESSION['hata'] . '
                                </div>';
                                unset($_SESSION['hata']);
                            }
                            if(isset($_SESSION['basari'])) {
                                echo '<div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>' . $_SESSION['basari'] . '
                                </div>';
                                unset($_SESSION['basari']);
                            }
                            ?>

                            <form action="sifre_sifirla.php" method="POST">
                                <div class="mb-4">
                                    <label for="email" class="form-label">E-posta Adresi</label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-envelope"></i>
                                        </span>
                                        <input type="email" class="form-control" id="email" name="email" required 
                                               placeholder="ornek@email.com">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100 mb-4">
                                    <i class="fas fa-paper-plane me-2"></i>Sıfırlama Bağlantısı Gönder
                                </button>
                                <div class="text-center">
                                    <a href="giris.php" class="text-muted">
                                        <i class="fas fa-arrow-left me-1"></i>Giriş Sayfasına Dön
                                    </a>
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
</body>
</html> 