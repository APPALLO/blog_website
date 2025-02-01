<?php
include 'cevrimici_kontrol.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

    .search-container {
        width: 400px;
    }

    .search-input {
        border-radius: 20px 0 0 20px !important;
        border: 1px solid var(--border-color);
        padding-left: 1rem;
        transition: all 0.3s ease;
    }

    .search-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(79, 70, 229, 0.15);
        border-color: var(--primary-color);
    }

    .btn-outline-primary {
        border-color: var(--border-color);
        color: var(--text-color);
        border-left: none;
        border-radius: 0 !important;
    }

    .btn-outline-primary:hover {
        background-color: transparent;
        color: var(--primary-color);
        border-color: var(--border-color);
    }

    .btn-primary {
        border-radius: 0 20px 20px 0 !important;
    }

    .dropdown-menu {
        border: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }

    .dropdown-header {
        color: var(--primary-color);
        font-weight: 600;
    }

    .form-select-sm {
        font-size: 0.875rem;
        border-radius: 5px;
    }

    @media (max-width: 768px) {
        .search-container {
            width: 100%;
            margin: 1rem 0;
        }
    }

    .online-users-widget {
        position: fixed;
        right: 20px;
        bottom: 20px;
        z-index: 1000;
    }

    .online-users-toggle {
        width: 50px;
        height: 50px;
        background: #4361ee;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(67, 97, 238, 0.3);
        position: relative;
        transition: transform 0.3s ease;
    }

    .online-users-toggle:hover {
        transform: scale(1.05);
    }

    .online-users-toggle i {
        color: white;
        font-size: 1.2rem;
    }

    .online-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #2ecc71;
        color: white;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .online-users-panel {
        position: fixed;
        right: 80px;
        bottom: 80px;
        width: 300px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        display: none;
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .online-users-panel.active {
        display: block;
        opacity: 1;
        transform: translateY(0);
    }

    .panel-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .panel-header h5 {
        margin: 0;
        font-size: 1rem;
        color: #333;
    }

    .close-btn {
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        padding: 5px;
    }

    .panel-body {
        max-height: 400px;
        overflow-y: auto;
    }

    .online-users-list {
        padding: 10px;
    }

    .online-user-item {
        display: flex;
        align-items: center;
        padding: 10px;
        border-radius: 8px;
        margin-bottom: 5px;
        transition: background 0.2s ease;
    }

    .online-user-item:hover {
        background: #f8f9fa;
    }

    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #e9ecef;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 12px;
        font-weight: 600;
        color: #4361ee;
    }

    .user-avatar img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-info {
        flex: 1;
    }

    .user-name {
        font-weight: 500;
        color: #333;
        margin: 0;
        font-size: 0.9rem;
    }

    .user-status {
        font-size: 0.8rem;
        color: #666;
        margin-top: 2px;
    }

    .user-role {
        font-size: 0.75rem;
        padding: 2px 8px;
        border-radius: 12px;
        background: rgba(67, 97, 238, 0.1);
        color: #4361ee;
        margin-left: auto;
    }
</style>

<!DOCTYPE html>
<html>
<header class="header-section">
    <nav class="navbar navbar-expand-lg bg-white shadow-sm py-3">
        <div class="container">
            <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="index.php">
                <i class="fas fa-blog me-2"></i>
                Blog Sitesi
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active fw-semibold' : ''; ?>" href="index.php">
                            <i class="fas fa-home me-1"></i>Ana Sayfa
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'kategoriler.php' ? 'active fw-semibold' : ''; ?>" href="kategoriler.php">
                            <i class="fas fa-folder me-1"></i>Kategoriler
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'yazarlar.php' ? 'active fw-semibold' : ''; ?>" href="yazarlar.php">
                            <i class="fas fa-users me-1"></i>Yazarlar
                        </a>
                    </li>
                </ul>
                
                <form class="d-flex me-3" action="blog.php" method="GET">
                    <div class="input-group">
                        <input type="text" 
                               name="arama" 
                               class="form-control" 
                               placeholder="İçerik ara..." 
                               value="<?php echo isset($_GET['arama']) ? htmlspecialchars($_GET['arama']) : ''; ?>"
                               style="width: 200px;">
                        <button class="btn btn-outline-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                
                <div class="nav-item dropdown">
                    <?php if (isset($_SESSION['kullanici_id'])): ?>
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <?php
                            $profil_resmi = isset($_SESSION['profil_resmi']) && !empty($_SESSION['profil_resmi']) 
                                ? $_SESSION['profil_resmi'] 
                                : 'https://ui-avatars.com/api/?name=' . urlencode($_SESSION['ad_soyad']) . '&size=32';
                            ?>
                            <img src="<?php echo htmlspecialchars($profil_resmi); ?>" 
                                 class="rounded-circle me-2" 
                                 width="32" 
                                 height="32" 
                                 alt="<?php echo htmlspecialchars($_SESSION['ad_soyad']); ?>">
                            <span><?php echo htmlspecialchars($_SESSION['ad_soyad']); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profil.php"><i class="fas fa-user me-2"></i>Profilim</a></li>
                            <li><a class="dropdown-item" href="yazilarim.php"><i class="fas fa-pencil-alt me-2"></i>Yazılarım</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="cikis.php"><i class="fas fa-sign-out-alt me-2"></i>Çıkış Yap</a></li>
                        </ul>
                    <?php else: ?>
                        <div class="d-flex">
                            <a href="giris.php" class="btn btn-outline-primary me-2">
                                <i class="fas fa-sign-in-alt me-1"></i>Giriş Yap
                            </a>
                            <a href="kayit.php" class="btn btn-primary">
                                <i class="fas fa-user-plus me-1"></i>Kayıt Ol
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>

<div class="online-users-widget">
    <div class="online-users-toggle" id="onlineUsersToggle">
        <i class="fas fa-users"></i>
        <span class="online-count">0</span>
    </div>
    
    <div class="online-users-panel" id="onlineUsersPanel">
        <div class="panel-header">
            <h5>Çevrimiçi Kullanıcılar</h5>
            <button class="close-btn" id="closeOnlinePanel">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="panel-body">
            <div class="online-users-list" id="onlineUsersList">
                <!-- Kullanıcılar AJAX ile yüklenecek -->
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const onlineUsersToggle = document.getElementById('onlineUsersToggle');
    const onlineUsersPanel = document.getElementById('onlineUsersPanel');
    const closeOnlinePanel = document.getElementById('closeOnlinePanel');
    const onlineUsersList = document.getElementById('onlineUsersList');
    const onlineCount = document.querySelector('.online-count');
    
    // Panel'i aç/kapat
    onlineUsersToggle.addEventListener('click', function() {
        onlineUsersPanel.classList.toggle('active');
    });
    
    closeOnlinePanel.addEventListener('click', function() {
        onlineUsersPanel.classList.remove('active');
    });
    
    // Çevrimiçi kullanıcıları güncelle
    function updateOnlineUsers() {
        fetch('cevrimici_kontrol.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=cevrimici_kullanicilar'
        })
        .then(response => response.json())
        .then(users => {
            onlineCount.textContent = users.length;
            onlineUsersList.innerHTML = '';
            
            users.forEach(user => {
                const lastActivity = user.son_aktivite_sure < 60 
                    ? 'Az önce'
                    : Math.floor(user.son_aktivite_sure / 60) + ' dakika önce';
                
                const userHtml = `
                    <div class="online-user-item">
                        <div class="user-avatar">
                            ${user.profil_resmi 
                                ? `<img src="${user.profil_resmi}" alt="${user.ad_soyad}">`
                                : user.ad_soyad.charAt(0).toUpperCase()}
                        </div>
                        <div class="user-info">
                            <h6 class="user-name">${user.ad_soyad}</h6>
                            <div class="user-status">
                                <small>${lastActivity}</small>
                                ${user.son_sayfa ? `<small>• ${user.son_sayfa}</small>` : ''}
                            </div>
                        </div>
                        <span class="user-role">${user.rol}</span>
                    </div>
                `;
                
                onlineUsersList.insertAdjacentHTML('beforeend', userHtml);
            });
        })
        .catch(error => console.error('Çevrimiçi kullanıcılar güncellenirken hata:', error));
    }
    
    // Kullanıcı durumunu güncelle
    function updateUserStatus() {
        const currentPage = document.title;
        fetch('cevrimici_kontrol.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=durum_guncelle&sayfa=${encodeURIComponent(currentPage)}`
        })
        .catch(error => console.error('Kullanıcı durumu güncellenirken hata:', error));
    }
    
    // Sayfa yüklendiğinde ve her 15 saniyede bir güncelle
    updateOnlineUsers();
    updateUserStatus();
    setInterval(updateOnlineUsers, 15000); // 15 saniyede bir güncelle
    setInterval(updateUserStatus, 15000);
    
    // Sayfa görünür olduğunda güncelle
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            updateOnlineUsers();
            updateUserStatus();
        }
    });
});
</script>
</html> 