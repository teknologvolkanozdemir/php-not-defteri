<?php
declare(strict_types=1);

header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; frame-ancestors 'none'");
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store');

ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Strict', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

function config(): array { static $c; return $c ??= require __DIR__ . '/../config.php'; }

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $c = config();
        $pdo = new PDO("mysql:host={$c['db_host']};dbname={$c['db_name']};charset=utf8mb4", $c['db_user'], $c['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}

function h(?string $s): string { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function csrf_token(): string {
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">'; }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        exit('Geçersiz istek (CSRF).');
    }
}

function require_login(): int {
    if (empty($_SESSION['uid'])) { header('Location: login.php'); exit; }
    return (int)$_SESSION['uid'];
}

// AES-256-GCM; anahtar, not parolasından PBKDF2 ile türetilir.
function derive_key(string $password, string $salt): string {
    return hash_pbkdf2('sha256', $password, $salt, 200000, 32, true);
}
function encrypt_text(string $plain, string $password): array {
    $salt = random_bytes(16);
    $iv = random_bytes(12);
    $ct = openssl_encrypt($plain, 'aes-256-gcm', derive_key($password, $salt), OPENSSL_RAW_DATA, $iv, $tag);
    return ['body' => base64_encode($ct), 'salt' => $salt, 'iv' => $iv, 'tag' => $tag];
}
function decrypt_text(array $note, string $password): ?string {
    $r = openssl_decrypt(base64_decode($note['body']), 'aes-256-gcm', derive_key($password, $note['salt']), OPENSSL_RAW_DATA, $note['iv'], $note['tag']);
    return $r === false ? null : $r;
}

function page_head(string $title): void {
    echo '<!doctype html><html lang="tr"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'
        . h($title) . '</title><style>body{font-family:sans-serif;max-width:720px;margin:2em auto;padding:0 1em}input,textarea{width:100%;box-sizing:border-box;margin:.3em 0;padding:.4em}.note{border:1px solid #ccc;padding:.5em 1em;margin:.5em 0}.err{color:#b00}</style></head><body>';
    if (!empty($_SESSION['uid'])) {
        echo '<form method="post" action="logout.php" style="text-align:right">' . csrf_field() . '<button>Çıkış (' . h($_SESSION['user'] ?? '') . ')</button></form>';
    }
}
function page_foot(): void { echo '</body></html>'; }
