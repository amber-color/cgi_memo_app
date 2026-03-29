<?php
header('Content-Type: application/json; charset=UTF-8');

// === 基本設定 ===
$dir = __DIR__ . '/data/';
if (!file_exists($dir)) mkdir($dir, 0777, true);

// === ファイル指定 ===
$user = $_POST['user'] ?? $_GET['user'] ?? '';
if ($user === '') {
    echo json_encode([]);
    exit;
}

$file = "data/" . basename($user) . ".csv"; // ← ユーザーごとのファイルに切り替え
if (!file_exists(dirname($file))) mkdir("data", 0777, true);

// === 共通関数 ===
function read_csv($file) {
    if (!file_exists($file)) return [];

    $memos = [];
    $fp = fopen($file, 'r');
    while (($row = fgetcsv($fp)) !== false) {
        if (count($row) >= 4) {
            $memos[] = [
                'id' => (int)$row[0],
                'title' => $row[1],
                'body' => str_replace(["\\r", "\\n"], ["\r", "\n"], $row[2]), // 改行復元
                'updated_at' => $row[3]
            ];
        }
    }
    fclose($fp);
    return $memos;
}

function get_new_id($file) {
    $memos = read_csv($file);
    $max_id = 0;
    foreach ($memos as $memo) {
        if ($memo["id"] > $max_id) {
            $max_id = $memo["id"];
        }
    }
    return $max_id + 1;
}

// === アクション処理 ===
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'read') {
    echo json_encode(read_csv($file), JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'save') {
    $memos = read_csv($file);
    $new_memos = [];

    // 新しいメモのID
    if ($_POST['id'] === "new") {
        $id = get_new_id($file);
    } else {
        $id = (int)$_POST['id'];
    }

    $title = base64_decode($_POST['title']);
    $body  = str_replace(["\r", "\n"], ["\\r", "\\n"], base64_decode($_POST['body']));
    $updated_at = date('Y-m-d H:i:s');
    $found = false;

    foreach ($memos as $memo) {
        if ($memo['id'] == $id) {
            $new_memos[] = [$id, $title, $body, $updated_at];
            $found = true;
        } else {
            $new_memos[] = [$memo['id'], $memo['title'], $memo['body'], $memo['updated_at']];
        }
    }

    if (!$found) {
        $new_memos[] = [$id, $title, $body, $updated_at];
    }

    $fp = fopen($file, 'w');
    foreach ($new_memos as $memo) {
        fputcsv($fp, $memo);
    }
    fclose($fp);

    echo json_encode(['id' => $id]);
    exit;
}

if ($action === 'delete') {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    $memos = read_csv($file);
    $new_memos = array_filter($memos, function($memo) use ($id) {
        return $memo['id'] != $id;
    });

    $fp = fopen($file, 'w');
    foreach ($new_memos as $memo) {
        fputcsv($fp, [$memo['id'], $memo['title'], str_replace("\n", "\\n", $memo['body']), $memo['updated_at']]);
    }
    fclose($fp);

    echo json_encode(['status' => 'deleted']);
    exit;
}

echo json_encode(['error' => 'unknown action']);
?>
