// Sayfa yüklendiğinde çalışacak fonksiyonlar
document.addEventListener('DOMContentLoaded', function() {
    // Navbar scroll efekti
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            document.querySelector('.navbar').classList.add('navbar-scrolled');
        } else {
            document.querySelector('.navbar').classList.remove('navbar-scrolled');
        }
    });

    // Blog kartları için hover efekti
    const blogCards = document.querySelectorAll('.card');
    blogCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Form doğrulama (iletişim sayfası için)
    const contactForm = document.querySelector('#contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Form verilerini al
            const formData = new FormData(this);
            
            // AJAX ile form gönderimi
            fetch('iletisim_gonder.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Mesajınız başarıyla gönderildi!');
                    contactForm.reset();
                } else {
                    alert('Bir hata oluştu. Lütfen tekrar deneyin.');
                }
            })
            .catch(error => {
                console.error('Hata:', error);
                alert('Bir hata oluştu. Lütfen tekrar deneyin.');
            });
        });
    }
}); 