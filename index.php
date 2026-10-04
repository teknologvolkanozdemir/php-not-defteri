<?php
require __DIR__ . '/src/bootstrap.php';
$uid = require_login();
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    $body = (string)($_POST['body'] ?? '');
    $np = (string)($_POST['note_password'] ?? '');
    if ($title === '' || mb_strlen($title) > 200) $err = 'Başlık 1-200 karakter olmalı.';
    elseif (mb_strlen($body) > 100000) $err = 'Not çok uzun.';
    elseif (!empty($_POST['encrypt']) && strlen($np) < 8) $err = 'Şifreleme parolası en az 8 karakter olmalı.';
    else {
        if (!empty($_POST['encrypt'])) {
            $e = encrypt_text($body, $np);
            $q = 'INSERT INTO notes (user_id,title,body,is_encrypted,salt,iv,tag) VALUES (?,?,?,1,?,?,?)';
            db()->prepare($q)->execute([$uid, $title, $e['body'], $e['salt'], $e['iv'], $e['tag']]);
        } else {
            db()->prepare('INSERT INTO notes (user_id,title,body) VALUES (?,?,?)')->execute([$uid, $title, $body]);
        }
        header('Location: index.php'); exit;
    }
}
$per = (int)config()['per_page'];
$st = db()->prepare('SELECT COUNT(*) FROM notes WHERE user_id = ?');
$st->execute([$uid]);
$total = (int)$st->fetchColumn();
$pages = max(1, (int)ceil($total / $per));
$page = min($pages, max(1, (int)($_GET['page'] ?? 1)));
$st = db()->prepare('SELECT id,title,is_encrypted,created_at FROM notes WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?');
$st->bindValue(1, $uid, PDO::PARAM_INT);
$st->bindValue(2, $per, PDO::PARAM_INT);
$st->bindValue(3, ($page - 1) * $per, PDO::PARAM_INT);
$st->execute();
page_head('Notlarım'); ?>
<h1>Notlarım (<?= $total ?>)</h1>
<?php if ($err) echo '<p class="err">' . h($err) . '</p>'; ?>
<form method="post"><?= csrf_field() ?>
<input name="title" placeholder="Başlık" maxlength="200" required>
<textarea name="body" rows="5" placeholder="Not"></textarea>
<label><input type="checkbox" name="encrypt" value="1" style="width:auto"> Şifrele</label>
<input name="note_password" type="password" placeholder="Şifreleme parolası (şifrelenecekse)" autocomplete="new-password">
<button>Ekle</button>
</form>
<?php foreach ($st as $n): ?>
<div class="note"><a href="note.php?id=<?= (int)$n['id'] ?>"><?= h($n['title']) ?></a>
<?= $n['is_encrypted'] ? '🔒' : '' ?> <small><?= h($n['created_at']) ?></small></div>
<?php endforeach; ?>
<p>
<?php if ($page > 1) echo '<a href="?page=' . ($page - 1) . '">&laquo; Önceki</a> '; ?>
Sayfa <?= $page ?> / <?= $pages ?>
<?php if ($page < $pages) echo ' <a href="?page=' . ($page + 1) . '">Sonraki &raquo;</a>'; ?>
</p>
<?php page_foot();
