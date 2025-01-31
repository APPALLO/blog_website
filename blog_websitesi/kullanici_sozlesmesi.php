<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'baglan.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Sözleşmesi - Blog Sitesi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4F46E5;
            --secondary-color: #9B2C2C;
            --accent-color: #6366F1;
            --success-color: #059669;
            --warning-color: #FBBF24;
            --danger-color: #DC2626;
            --gray-dark: #374151;
            --gray-medium: #9CA3AF;
            --gray-light: #F3F4F6;
            --white: #FFFFFF;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F9FAFB;
            color: var(--gray-dark);
            line-height: 1.7;
        }

        .agreement-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--accent-color) 100%);
            padding: 3rem 0;
            margin-bottom: 2rem;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }

        .agreement-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><circle cx="2" cy="2" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
            opacity: 0.3;
        }

        .agreement-card {
            background: var(--white);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid var(--gray-light);
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 60px;
            height: 2px;
            background: var(--primary-color);
        }

        .agreement-content h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-dark);
            margin: 1.5rem 0 1rem;
        }

        .agreement-content p {
            margin-bottom: 1rem;
            color: var(--gray-dark);
        }

        .agreement-content ul {
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
        }

        .agreement-content li {
            margin-bottom: 0.5rem;
        }

        .agreement-content strong {
            color: var(--primary-color);
        }

        .agreement-footer {
            background: var(--gray-light);
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-top: 2rem;
        }

        .agreement-footer p {
            margin-bottom: 0;
            font-size: 0.875rem;
            color: var(--gray-medium);
        }

        @media (max-width: 768px) {
            .agreement-header {
                padding: 2rem 0;
            }

            .agreement-card {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="agreement-header">
        <div class="container">
            <h1 class="h2 mb-2">Kullanıcı Sözleşmesi</h1>
            <p class="mb-0">Lütfen sitemizi kullanmadan önce bu sözleşmeyi dikkatlice okuyunuz.</p>
        </div>
    </div>

    <div class="container">
        <div class="agreement-card">
            <div class="agreement-content">
                <h2 class="section-title">1. Genel Hükümler</h2>
                <p>Bu kullanıcı sözleşmesi ("Sözleşme"), blog sitemizin ("Site") kullanımına ilişkin şartları ve koşulları düzenlemektedir. Siteye üye olarak veya siteyi kullanarak bu sözleşmedeki tüm şartları kabul etmiş sayılırsınız.</p>

                <h3>1.1. Tanımlar</h3>
                <ul>
                    <li><strong>Site:</strong> Blog websitesi ve tüm alt sayfalarını,</li>
                    <li><strong>Kullanıcı:</strong> Siteye üye olan veya olmayan tüm ziyaretçileri,</li>
                    <li><strong>Üye:</strong> Siteye kayıt olmuş kullanıcıları,</li>
                    <li><strong>İçerik:</strong> Sitede yer alan tüm yazı, resim, video ve diğer materyalleri ifade eder.</li>
                </ul>

                <h2 class="section-title">2. Üyelik Koşulları</h2>
                <h3>2.1. Üyelik Şartları</h3>
                <ul>
                    <li>18 yaşını doldurmuş olmak</li>
                    <li>Gerçek ve doğru bilgiler vermek</li>
                    <li>Tek bir hesap oluşturmak</li>
                    <li>Hesap bilgilerini gizli tutmak</li>
                </ul>

                <h3>2.2. Üyelik İptali</h3>
                <p>Site yönetimi, aşağıdaki durumlarda üyeliği tek taraflı olarak sonlandırma hakkına sahiptir:</p>
                <ul>
                    <li>Yanlış bilgi verilmesi</li>
                    <li>Site kurallarının ihlal edilmesi</li>
                    <li>Diğer kullanıcılara zarar verecek davranışlarda bulunulması</li>
                    <li>Spam veya zararlı içerik paylaşımı</li>
                </ul>

                <h2 class="section-title">3. İçerik Politikası</h2>
                <h3>3.1. İçerik Paylaşım Kuralları</h3>
                <p>Kullanıcılar tarafından paylaşılan içerikler:</p>
                <ul>
                    <li>Telif haklarına uygun olmalıdır</li>
                    <li>Nefret söylemi içermemelidir</li>
                    <li>Şiddet ve müstehcenlik içermemelidir</li>
                    <li>Yasa dışı faaliyetleri teşvik etmemelidir</li>
                </ul>

                <h2 class="section-title">4. Gizlilik Politikası</h2>
                <p>Kullanıcıların gizliliği bizim için önemlidir. Kişisel verileriniz 6698 sayılı KVKK kapsamında korunmaktadır.</p>

                <h3>4.1. Toplanan Veriler</h3>
                <ul>
                    <li>Ad ve soyad</li>
                    <li>E-posta adresi</li>
                    <li>Profil bilgileri</li>
                    <li>IP adresi ve çerez bilgileri</li>
                </ul>

                <h2 class="section-title">5. Sorumluluk Reddi</h2>
                <p>Site yönetimi:</p>
                <ul>
                    <li>Kullanıcılar tarafından paylaşılan içeriklerden sorumlu değildir</li>
                    <li>Sitenin kesintisiz çalışacağını garanti etmez</li>
                    <li>İçeriklerin doğruluğunu garanti etmez</li>
                    <li>Önceden haber vermeksizin site özelliklerinde değişiklik yapma hakkını saklı tutar</li>
                </ul>

                <div class="agreement-footer">
                    <p>Son güncelleme tarihi: <?php echo date('d.m.Y'); ?></p>
                    <p>Bu sözleşme, önceden haber verilmeksizin güncellenebilir. Güncel sözleşme şartları için bu sayfayı düzenli olarak kontrol etmeniz önerilir.</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 