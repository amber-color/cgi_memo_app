<?php
/**
 * アイコン生成スクリプト（初回セットアップ時に実行）
 * ブラウザで /cgi_memo/generate-icons.php にアクセスして実行
 */
function generateIcon($size, $filename) {
    $img = imagecreatetruecolor($size, $size);
    imagesavealpha($img, true);

    // 角丸背景 (青)
    $bg = imagecolorallocate($img, 44, 111, 191);
    $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
    imagefill($img, 0, 0, $transparent);

    $r = (int)($size * 0.195);
    imagefilledrectangle($img, $r, 0, $size - $r, $size, $bg);
    imagefilledrectangle($img, 0, $r, $size, $size - $r, $bg);
    imagefilledellipse($img, $r, $r, $r * 2, $r * 2, $bg);
    imagefilledellipse($img, $size - $r, $r, $r * 2, $r * 2, $bg);
    imagefilledellipse($img, $r, $size - $r, $r * 2, $r * 2, $bg);
    imagefilledellipse($img, $size - $r, $size - $r, $r * 2, $r * 2, $bg);

    // 白い線（メモ）
    $white = imagecolorallocate($img, 255, 255, 255);
    $lh = (int)($size * 0.063);
    $lx = (int)($size * 0.25);
    $lw = (int)($size * 0.5);
    $ly1 = (int)($size * 0.28);
    $ly2 = (int)($size * 0.41);
    $ly3 = (int)($size * 0.54);
    $ly4 = (int)($size * 0.67);

    imagefilledrectangle($img, $lx, $ly1, $lx + $lw, $ly1 + $lh, $white);
    imagefilledrectangle($img, $lx, $ly2, $lx + $lw, $ly2 + $lh, $white);
    imagefilledrectangle($img, $lx, $ly3, $lx + (int)($lw * 0.75), $ly3 + $lh, $white);
    imagefilledrectangle($img, $lx, $ly4, $lx + (int)($lw * 0.5), $ly4 + $lh, $white);

    imagepng($img, $filename);
    imagedestroy($img);
}

if (!extension_loaded('gd')) {
    echo "GD拡張が利用できません。";
    exit;
}

generateIcon(192, __DIR__ . '/icon-192.png');
generateIcon(512, __DIR__ . '/icon-512.png');

echo "アイコンを生成しました: icon-192.png, icon-512.png";
