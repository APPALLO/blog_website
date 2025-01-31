<?php
include 'cevrimici_kontrol.php';
?>
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

    .navbar {
        background: var(--card-bg);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        padding: 1rem 0;
        position: sticky;
        top: 0;
        z-index: 1000;
        transition: all 0.3s ease;
    }

    .navbar-brand {
        font-weight: 700;
        font-size: 1.5rem;
        color: var(--text-color);
        transition: all 0.3s ease;
        position: relative;
    }

    .navbar-brand:hover {
        color: var(--primary-color);
    }

    .navbar-brand::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 2px;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        bottom: -4px;
        left: 0;
        transform: scaleX(0);
        transition: transform 0.3s ease;
    }

    .navbar-brand:hover::after {
        transform: scaleX(1);
    }

    .nav-link {
        color: var(--text-color);
        font-weight: 500;
        padding: 0.5rem 1rem;
        margin: 0 0.25rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
        position: relative;
    }

    .nav-link:hover {
        color: var(--primary-color);
        background: rgba(79, 70, 229, 0.1);
    }

    .nav-link.active {
        color: var(--primary-color);
        background: rgba(79, 70, 229, 0.1);
    }

    .navbar-toggler {
        border: none;
        padding: 0.5rem;
        border-radius: 0.5rem;
        transition: all 0.3s ease;
    }

    .navbar-toggler:focus {
        box-shadow: none;
        background: rgba(79, 70, 229, 0.1);
    }

    .navbar-toggler-icon {
        background-image: none;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-color);
    }

    .navbar-toggler-icon::before {
        content: '\f0c9';
        font-family: 'Font Awesome 6 Free';
        font-weight: 900;
        font-size: 1.5rem;
    }

    .dropdown-menu {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        padding: 1rem 0;
        background: var(--card-bg);
    }

    .dropdown-item {
        color: var(--text-color);
        font-weight: 500;
        padding: 0.75rem 1.5rem;
        transition: all 0.3s ease;
    }

    .dropdown-item:hover {
        color: var(--primary-color);
        background: rgba(79, 70, 229, 0.1);
    }

    .dropdown-item i {
        margin-right: 0.5rem;
        width: 1.25rem;
        text-align: center;
    }

    .nav-btn {
        padding: 0.5rem 1.25rem;
        border-radius: 0.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
        margin-left: 0.5rem;
    }

    .nav-btn-outline {
        color: var(--primary-color);
        border: 2px solid var(--primary-color);
        background: transparent;
    }

    .nav-btn-outline:hover {
        color: white;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        border-color: transparent;
        transform: translateY(-2px);
    }

    .nav-btn-filled {
        color: white;
        background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
        border: none;
    }

    .nav-btn-filled:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--primary-color);
        transition: all 0.3s ease;
    }

    .user-avatar:hover {
        transform: scale(1.1);
        border-color: var(--secondary-color);
    }

    @media (max-width: 991.98px) {
        .navbar-collapse {
            background: var(--card-bg);
            padding: 1rem;
            border-radius: 1rem;
            margin-top: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .nav-btn {
            margin: 0.5rem 0;
            display: block;
            text-align: center;
        }
    }
</style>

<!DOCTYPE html>
<html>
<header class="bg-white shadow-sm mb-4">
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">Blog Sitesi</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                            <i class="fas fa-home me-1"></i>Ana Sayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kategoriler.php' ? 'active' : ''; ?>" href="kategoriler.php">
                            <i class="fas fa-th-large me-1"></i>Kategoriler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'yazarlar.php' ? 'active' : ''; ?>" href="yazarlar.php">
                            <i class="fas fa-users me-1"></i>Yazarlar
                        </a>
                    </li>
                </ul>

                <div class="d-flex align-items-center">
                    <!-- Çevrimiçi Kullanıcılar -->
                    <div class="dropdown me-3">
                        <button class="btn btn-outline-success btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-users"></i> Çevrimiçi (<?php echo cevrimici_kullanici_sayisi(); ?>)
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php
                            $cevrimici = cevrimici_kullanicilar();
                            if ($cevrimici->num_rows > 0) {
                                while ($kullanici = $cevrimici->fetch_assoc()) {
                                    echo '<li class="dropdown-item">';
                                    echo '<i class="fas fa-circle text-success me-2"></i>';
                                    echo htmlspecialchars($kullanici['ad_soyad']);
                                    if ($kullanici['rol'] === 'admin') {
                                        echo ' <span class="badge bg-danger">Admin</span>';
                                    }
                                    echo '</li>';
                                }
                            } else {
                                echo '<li class="dropdown-item text-muted">Çevrimiçi kullanıcı yok</li>';
                            }
                            ?>
                        </ul>
                    </div>

                    <?php if(isset($_SESSION['kullanici_id'])): ?>
                        <div class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                                <img src="<?php 
                                    echo !empty($_SESSION['profil_resmi']) 
                                        ? htmlspecialchars($_SESSION['profil_resmi']) 
                                        : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['ad_soyad']) . '&size=40';
                                ?>" alt="Profil" class="user-avatar me-2">
                                <span><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="profil.php">
                                        <i class="fas fa-user"></i>Profilim
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="yazilarim.php">
                                        <i class="fas fa-file-alt"></i>Yazılarım
                                    </a>
                                </li>
                                <?php if($_SESSION['rol'] == 'admin'): ?>
                                    <li>
                                        <a class="dropdown-item" href="admin/index.php">
                                            <i class="fas fa-cog"></i>Yönetim Paneli
                                        </a>
                                    </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="cikis.php">
                                        <i class="fas fa-sign-out-alt"></i>Çıkış Yap
                                    </a>
                                </li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="nav-item d-flex">
                            <a href="giris.php" class="nav-btn nav-btn-outline">
                                <i class="fas fa-sign-in-alt me-1"></i>Giriş Yap
                            </a>
                            <a href="kayit.php" class="nav-btn nav-btn-filled">
                                <i class="fas fa-user-plus me-1"></i>Kayıt Ol
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>
</html> 