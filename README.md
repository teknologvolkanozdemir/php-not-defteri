# php-not-defteri
localhostta çalışan, MySQL destekli, yüksek güvenlikli not defteri.

- Sınırsız not, sayfalama (sayfa başı `config.php` içindeki `per_page`)
- Kayıt/giriş (`password_hash`), oturum sertleştirme, CSRF koruması, PDO hazır sorgular, XSS çıktı kaçışı, güvenlik başlıkları
- Notlar isteğe bağlı olarak AES-256-GCM ile şifrelenir (anahtar: PBKDF2-SHA256 ile not parolasından türetilir; parola saklanmaz, unutulursa not kurtarılamaz)

## Kurulum
```
mysql -u root -p < schema.sql
DB_USER=root DB_PASS=... php -S localhost:8000
```
