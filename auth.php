<?php
header('Content-Type: text/plain; charset=UTF-8');

$file = 'users.csv';
if (!file_exists($file)) {
    file_put_contents($file, "id,password\n");
}

$mode = $_POST['mode'] ?? '';
$id   = trim($_POST['id'] ?? '');
$pw   = $_POST['pw'] ?? '';

if ($id === '' || $pw === '') {
    exit("IDとパスワードを入力してください。");
}

$users = array_map('str_getcsv', file($file));
$found = false;

foreach ($users as $user) {
    if ($user[0] === $id) {
        $found = true;
        $stored_pw = $user[1];
        break;
    }
}

if ($mode === 'register') {
    if ($found) {
        exit("そのIDは使われています。");
    } else {
        file_put_contents($file, "$id,$pw\n", FILE_APPEND);
        mkdir("data", 0777, true);
        exit("登録完了しました。ログインしてください。");
    }
}

if ($mode === 'login') {
    if (!$found) exit("IDが存在しません。");
    if ($pw !== $stored_pw) exit("パスワードが間違っています。");

    mkdir("data", 0777, true);
    exit("ログイン成功：$id");
}

exit("不正なアクセスです。");
?>
<?php
header('Content-Type: text/plain; charset=UTF-8');

$file = 'users.csv';
if (!file_exists($file)) {
    file_put_contents($file, "id,password\n");
}

$mode = $_POST['mode'] ?? '';
$id   = trim($_POST['id'] ?? '');
$pw   = $_POST['pw'] ?? '';

if ($id === '' || $pw === '') {
    exit("IDとパスワードを入力してください。");
}

$users = array_map('str_getcsv', file($file));
$found = false;

foreach ($users as $user) {
    if ($user[0] === $id) {
        $found = true;
        $stored_pw = $user[1];
        break;
    }
}

if ($mode === 'register') {
    if ($found) {
        exit("そのIDは使われています。");
    } else {
        file_put_contents($file, "$id,$pw\n", FILE_APPEND);
        mkdir("data", 0777, true);
        file_put_contents("data/$id.csv", "ログイン日時," . date("Y-m-d H:i:s") . "\n");
        exit("登録完了しました。ログインしてください。");
    }
}

if ($mode === 'login') {
    if (!$found) exit("IDが存在しません。");
    if ($pw !== $stored_pw) exit("パスワードが間違っています。");

    mkdir("data", 0777, true);
    file_put_contents("data/$id.csv", "ログイン日時," . date("Y-m-d H:i:s") . "\n");
    exit("ログイン成功：$id");
}

exit("不正なアクセスです。");
?>
