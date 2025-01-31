# Blog Sitesi

Modern ve kullanıcı dostu bir blog sitesi. PHP, MySQL, HTML, CSS ve JavaScript kullanılarak geliştirilmiştir.

## Özellikler

- Responsive tasarım
- Blog yazıları yönetimi
- Kategori sistemi
- Etiket sistemi
- Kullanıcı yönetimi
- Yorum sistemi
- İletişim formu
- Admin paneli

## Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Apache/Nginx web sunucusu

## Kurulum

1. Dosyaları web sunucunuza yükleyin
2. `blog_db.sql` dosyasını MySQL veritabanınıza import edin
3. `baglan.php` dosyasındaki veritabanı bağlantı bilgilerini düzenleyin:
   ```php
   $servername = "localhost";
   $username = "root";
   $password = "";
   $dbname = "blog_db";
   ```
4. Admin paneline giriş yapmak için:
   - Kullanıcı adı: admin
   - Şifre: admin123

## Güvenlik

- Tüm form girişleri XSS ve SQL enjeksiyonlarına karşı korunmaktadır
- Şifreler güvenli bir şekilde hash'lenmektedir
- Oturum güvenliği sağlanmıştır

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır. 