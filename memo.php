<?php
header('Content-Type: application/json; charset=UTF-8');

$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) mkdir($dataDir, 0755, true);

$dbPath = $dataDir . '/memo.sqlite';
$db = new PDO('sqlite:' . $dbPath);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY,
    password TEXT NOT NULL
)");
$db->exec("CREATE TABLE IF NOT EXISTS memos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user TEXT NOT NULL,
    title TEXT NOT NULL DEFAULT '',
    body TEXT NOT NULL DEFAULT '',
    updated_at TEXT NOT NULL
)");

$user = $_POST['user'] ?? $_GET['user'] ?? '';
if ($user === '') {
    echo json_encode([]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'read') {
    $stmt = $db->prepare("SELECT id, title, body, updated_at FROM memos WHERE user = ? ORDER BY updated_at DESC");
    $stmt->execute([$user]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save') {
    $id    = $_POST['id'] ?? 'new';
    $title = base64_decode($_POST['title'] ?? '');
    $body  = base64_decode($_POST['body'] ?? '');
    $updated_at = date('Y-m-d H:i:s');

    if ($id === 'new') {
        $stmt = $db->prepare("INSERT INTO memos (user, title, body, updated_at) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user, $title, $body, $updated_at]);
        $newId = (int)$db->lastInsertId();
    } else {
        $stmt = $db->prepare("UPDATE memos SET title = ?, body = ?, updated_at = ? WHERE id = ? AND user = ?");
        $stmt->execute([$title, $body, $updated_at, (int)$id, $user]);
        $newId = (int)$id;
    }

    echo json_encode(['id' => $newId]);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $stmt = $db->prepare("DELETE FROM memos WHERE id = ? AND user = ?");
    $stmt->execute([$id, $user]);
    echo json_encode(['status' => 'deleted']);
    exit;
}

echo json_encode(['error' => 'unknown action']);
