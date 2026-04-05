<?php
header('Content-Type: text/plain; charset=UTF-8');

$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

$dbPath = $dataDir . '/memo.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    password TEXT NOT NULL
)");

$mode = $_POST['mode'] ?? '';
$id   = trim($_POST['id'] ?? '');
$pw   = $_POST['pw'] ?? '';

if ($id === '' || $pw === '') exit("IDとパスワードを入力してください。");

if ($mode === 'register') {
    $stmt = $db->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetch()) exit("そのIDは使われています。");
    $stmt = $db->prepare("INSERT INTO users (id, password) VALUES (?, ?)");
    $stmt->execute([$id, $pw]);
    exit("登録完了しました。ログインしてください。");
}

if ($mode === 'login') {
    $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) exit("IDが存在しません。");
    if ($pw !== $user['password']) exit("パスワードが間違っています。");
    exit("ログイン成功：$id");
}

exit("不正なアクセスです。");
