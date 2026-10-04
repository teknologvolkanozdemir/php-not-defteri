<?php
require __DIR__ . '/src/bootstrap.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $user = trim($_POST['username'] ?? '');
    $pass = (string)($_POST['password'] ?? '');
    if (isset($_POST['register'])) {
        if (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $user)) $err = 'Kullanıcı adı 3-50 karakter (harf, rakam, _) olmalı.';
        elseif (strlen($pass) < 10) $err = 'Parola en az 10 karakter olmalı.';
        else {
            try {
                db()->prepare('INSERT INTO users (username, password_hash) VALUES (?, ?)')
                    ->execute([$user, password_hash($pass, PASSWORD_DEFAULT)]);
                $err = 'Kayıt oluşturuldu, giriş yapabilirsiniz.';
            } catch (PDOException $e) { $err = 'Bu kullanıcı adı alınmış.'; }
        }
    } else {
        if (($_SESSION['fails'] ?? 0) >= 5 && time() - ($_SESSION['fail_t'] ?? 0) < 60) {
            $err = 'Çok fazla deneme. Bir dakika bekleyin.';
        } else {
            $st = db()->prepare('SELECT id, password_hash FROM users WHERE username = ?');
            $st->execute([$user]);
            $u = $st->fetch();
            if ($u && password_verify($pass, $u['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION = ['uid' => (int)$u['id'], 'user' => $user];
                header('Location: index.php'); exit;
            }
            $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
            $_SESSION['fail_t'] = time();
            $err = 'Hatalı kullanıcı adı veya parola.';
        }
    }
}
page_head('Giriş'); ?>
<h1>Not Defteri</h1>
<?php if ($err) echo '<p class="err">' . h($err) . '</p>'; ?>
<form method="post"><?= csrf_field() ?>
<input name="username" placeholder="Kullanıcı adı" required autocomplete="username">
<input name="password" type="password" placeholder="Parola" required autocomplete="current-password">
<button>Giriş</button> <button name="register" value="1">Kayıt ol</button>
</form>
<?php page_foot();
