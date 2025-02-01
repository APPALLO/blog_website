<?php
// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('../baglan.php');

// Veritabanı bağlantısını kontrol et
if ($conn->connect_error) {
    die("Veritabanı bağlantı hatası: " . $conn->connect_error);
}

// blog_db veritabanının varlığını kontrol et
$check_db = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'blog_db'";
$db_exists = $conn->query($check_db);

if ($db_exists->num_rows == 0) {
    die("blog_db veritabanı bulunamadı. Lütfen önce veritabanını oluşturun.");
}

echo "Veritabanı bağlantısı başarılı.<br>";

// Admin tablosunu oluştur - Gelişmiş özelliklerle
$sql = "CREATE TABLE IF NOT EXISTS adminler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kullanici_adi VARCHAR(50) UNIQUE NOT NULL,
    sifre VARCHAR(255) NOT NULL,
    ad_soyad VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    telefon VARCHAR(20),
    profil_resmi VARCHAR(255),
    rol ENUM('super_admin', 'admin', 'editor') DEFAULT 'admin',
    durum ENUM('aktif', 'pasif', 'askida') DEFAULT 'aktif',
    son_giris DATETIME,
    son_ip VARCHAR(45),
    giris_denemeleri INT DEFAULT 0,
    sifre_sifirlama_token VARCHAR(100),
    sifre_sifirlama_son_tarih DATETIME,
    oturum_token VARCHAR(100),
    tema_tercihi VARCHAR(20) DEFAULT 'light',
    dil VARCHAR(10) DEFAULT 'tr',
    bildirimler JSON,
    izinler JSON,
    notlar TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "<div style='max-width: 600px; margin: 50px auto; font-family: Arial, sans-serif;'>";
    echo "<div style='background-color: #d4edda; color: #155724; padding: 20px; border-radius: 10px; margin-bottom: 20px;'>";
    echo "✅ Admin tablosu başarıyla oluşturuldu</div>";
    
    // Varsayılan admin hesabı oluştur
    $admin_username = "admin";
    $admin_password = password_hash("admin123", PASSWORD_DEFAULT);
    $admin_permissions = json_encode([
        'dashboard' => true,
        'posts' => ['view', 'create', 'edit', 'delete'],
        'categories' => ['view', 'create', 'edit', 'delete'],
        'comments' => ['view', 'approve', 'delete'],
        'users' => ['view', 'create', 'edit', 'delete'],
        'settings' => ['view', 'edit']
    ]);
    $admin_notifications = json_encode([
        'email' => true,
        'browser' => true,
        'mobile' => false
    ]);
    
    $check_sql = "SELECT * FROM adminler WHERE kullanici_adi = 'admin'";
    $result = $conn->query($check_sql);
    
    if ($result->num_rows == 0) {
        $insert_sql = "INSERT INTO adminler (
            kullanici_adi, 
            sifre, 
            ad_soyad,
            email,
            rol,
            durum,
            izinler,
            bildirimler
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_sql);
        
        if ($stmt === false) {
            die("Hazırlama hatası: " . $conn->error);
        }
        
        $ad_soyad = "Site Yöneticisi";
        $email = "admin@example.com";
        $rol = "super_admin";
        $durum = "aktif";
        
        $stmt->bind_param("ssssssss", 
            $admin_username, 
            $admin_password, 
            $ad_soyad,
            $email,
            $rol,
            $durum,
            $admin_permissions,
            $admin_notifications
        );
        
        if ($stmt->execute()) {
            echo "<div style='background-color: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);'>";
            echo "<h2 style='color: #333; margin-bottom: 20px;'>🎉 Kurulum Tamamlandı!</h2>";
            
            echo "<div style='background-color: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
            echo "<h3 style='color: #0d6efd; margin-bottom: 15px;'>Giriş Bilgileri</h3>";
            echo "<p style='margin-bottom: 10px;'><strong>👤 Kullanıcı adı:</strong> admin</p>";
            echo "<p style='margin-bottom: 10px;'><strong>🔑 Şifre:</strong> admin123</p>";
            echo "<p style='margin-bottom: 10px;'><strong>📧 E-posta:</strong> admin@example.com</p>";
            echo "<p style='margin-bottom: 10px;'><strong>👑 Rol:</strong> Süper Admin</p>";
            echo "</div>";
            
            echo "<div style='background-color: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
            echo "⚠️ <strong>Önemli:</strong> Güvenliğiniz için lütfen varsayılan şifreyi değiştirin!";
            echo "</div>";
            
            echo "<div style='margin-top: 30px;'>";
            echo "<a href='index.php' style='display: inline-block; background-color: #0d6efd; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; transition: all 0.3s;'>Yönetim Paneline Git</a>";
            echo "</div>";
            
            echo "<div style='margin-top: 20px; font-size: 14px; color: #6c757d;'>";
            echo "💡 <strong>İpucu:</strong> Yönetim panelinde şunları yapabilirsiniz:";
            echo "<ul style='list-style-type: none; padding-left: 0; margin-top: 10px;'>";
            echo "<li>✓ İçerik yönetimi</li>";
            echo "<li>✓ Kullanıcı yönetimi</li>";
            echo "<li>✓ Yorum moderasyonu</li>";
            echo "<li>✓ Site ayarları</li>";
            echo "<li>✓ İstatistik takibi</li>";
            echo "</ul>";
            echo "</div>";
            
            echo "</div>";
        } else {
            echo "<div style='background-color: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px;'>";
            echo "❌ Hata: Admin hesabı oluşturulamadı: " . $stmt->error;
            echo "</div>";
        }
        
        $stmt->close();
    } else {
        echo "<div style='background-color: #fff3cd; color: #856404; padding: 20px; border-radius: 10px; margin-bottom: 20px;'>";
        echo "ℹ️ Admin hesabı zaten mevcut.";
        echo "</div>";
        
        echo "<div style='text-align: center;'>";
        echo "<a href='index.php' style='display: inline-block; background-color: #0d6efd; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; transition: all 0.3s;'>Yönetim Paneline Git</a>";
        echo "</div>";
    }
    echo "</div>";
} else {
    echo "<div style='background-color: #f8d7da; color: #721c24; padding: 20px; border-radius: 10px;'>";
    echo "❌ Hata: Tablo oluşturulamadı: " . $conn->error;
    echo "</div>";
}

$conn->close();
?> 