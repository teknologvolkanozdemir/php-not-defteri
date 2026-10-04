<?php
require __DIR__ . '/src/bootstrap.php';
$uid = require_login();
$id = (int)($_REQUEST['id'] ?? 0);
$st = db()->prepare('SELECT * FROM notes WHERE id = ? AND user_id = ?');
$st->execute([$id, $uid]);
$note = $st->fetch();
if (!$note) { http_response_code(404); exit('Not bulunamadı.'); }
$err = '';
$text = $note['is_encrypted'] ? null : $note['body'];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        db()->prepare('DELETE FROM notes WHERE id = ? AND user_id = ?')->execute([$id, $uid]);
        header('Location: index.php'); exit;
    }
    if ($note['is_encrypted']) {
        $text = decrypt_text($note, (string)($_POST['note_password'] ?? ''));
        if ($text === null) $err = 'Parola hatalı.';
    }
}
page_head($note['title']); ?>
<h1><?= h($note['title']) ?></h1>
<?php if ($err) echo '<p class="err">' . h($err) . '</p>'; ?>
<?php if ($text !== null): ?>
<pre style="white-space:pre-wrap"><?= h($text) ?></pre>
<?php else: ?>
<form method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
<input name="note_password" type="password" placeholder="Not parolası" required autocomplete="off">
<button>Aç</button></form>
<?php endif; ?>
<form method="post" onsubmit="return confirm('Silinsin mi?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><button name="delete" value="1">Sil</button></form>
<p><a href="index.php">&laquo; Geri</a></p>
<?php page_foot();
