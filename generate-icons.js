#!/usr/bin/env node
/**
 * PWA アイコン生成スクリプト
 * node generate-icons.js で実行 → icon-192.png / icon-512.png を生成
 * Apple 製品ライクなデザイン（青グラデ背景 + 白い手帳シンボル）
 */
const zlib = require('zlib');
const fs   = require('fs');
const path = require('path');

/* ── PNG ユーティリティ ─────────────────────────── */
function crc32(buf) {
    const t = (() => {
        const tbl = new Uint32Array(256);
        for (let n = 0; n < 256; n++) {
            let c = n;
            for (let k = 0; k < 8; k++) c = (c & 1) ? (0xEDB88320 ^ (c >>> 1)) : (c >>> 1);
            tbl[n] = c;
        }
        return tbl;
    })();
    let crc = 0xFFFFFFFF;
    for (let i = 0; i < buf.length; i++) crc = (crc >>> 8) ^ t[(crc ^ buf[i]) & 0xFF];
    return (crc ^ 0xFFFFFFFF) >>> 0;
}

function chunk(type, data) {
    const tb = Buffer.from(type, 'ascii');
    const lb = Buffer.alloc(4); lb.writeUInt32BE(data.length);
    const cb = Buffer.alloc(4); cb.writeUInt32BE(crc32(Buffer.concat([tb, data])));
    return Buffer.concat([lb, tb, data, cb]);
}

function encodePng(pixels, size) {
    const ihdr = Buffer.alloc(13);
    ihdr.writeUInt32BE(size, 0); ihdr.writeUInt32BE(size, 4);
    ihdr[8] = 8; ihdr[9] = 6; // RGBA

    const raw = Buffer.alloc(size * (size * 4 + 1));
    for (let y = 0; y < size; y++) {
        raw[y * (size * 4 + 1)] = 0;
        for (let x = 0; x < size; x++) {
            const s = (y * size + x) * 4, d = y * (size * 4 + 1) + 1 + x * 4;
            raw[d] = pixels[s]; raw[d+1] = pixels[s+1]; raw[d+2] = pixels[s+2]; raw[d+3] = pixels[s+3];
        }
    }

    return Buffer.concat([
        Buffer.from([137, 80, 78, 71, 13, 10, 26, 10]),
        chunk('IHDR', ihdr),
        chunk('IDAT', zlib.deflateSync(raw)),
        chunk('IEND', Buffer.alloc(0))
    ]);
}

/* ── 描画ユーティリティ ──────────────────────────── */
function setPixel(px, size, x, y, r, g, b, a) {
    if (x < 0 || x >= size || y < 0 || y >= size) return;
    const i = (y * size + x) * 4;
    // アルファブレンド (src over)
    const sa = a / 255, da = px[i+3] / 255;
    const oa = sa + da * (1 - sa);
    if (oa === 0) return;
    px[i]   = Math.round((r * sa + px[i]   * da * (1 - sa)) / oa);
    px[i+1] = Math.round((g * sa + px[i+1] * da * (1 - sa)) / oa);
    px[i+2] = Math.round((b * sa + px[i+2] * da * (1 - sa)) / oa);
    px[i+3] = Math.round(oa * 255);
}

// 塗り潰し矩形
function fillRect(px, size, x0, y0, w, h, r, g, b, a) {
    for (let y = y0; y < y0 + h; y++)
        for (let x = x0; x < x0 + w; x++)
            setPixel(px, size, x, y, r, g, b, a);
}

// アンチエイリアス付き円弧クリップ付き塗り潰し角丸矩形
function fillRoundRect(px, size, x0, y0, w, h, rad, r, g, b, a) {
    for (let y = y0; y < y0 + h; y++) {
        for (let x = x0; x < x0 + w; x++) {
            const dx = Math.min(x - x0, x0 + w - 1 - x);
            const dy = Math.min(y - y0, y0 + h - 1 - y);
            let alpha = a;
            if (dx < rad && dy < rad) {
                const dist = Math.hypot(rad - dx, rad - dy);
                if (dist > rad + 0.5) continue;
                if (dist > rad - 0.5) alpha = Math.round(a * (rad + 0.5 - dist));
            }
            setPixel(px, size, x, y, r, g, b, alpha);
        }
    }
}

/* ── アイコン描画 ────────────────────────────────── */
function generateIcon(size) {
    const px = new Uint8Array(size * size * 4);

    // 背景: 上 #4A8FE8 → 下 #2460C8 のリニアグラデ
    const topR=74, topG=143, topB=232, botR=36, botG=96, botB=200;
    for (let y = 0; y < size; y++) {
        const t = y / (size - 1);
        const r = Math.round(topR + (botR - topR) * t);
        const g = Math.round(topG + (botG - topG) * t);
        const b = Math.round(topB + (botB - topB) * t);
        for (let x = 0; x < size; x++) {
            const i = (y * size + x) * 4;
            px[i]=r; px[i+1]=g; px[i+2]=b; px[i+3]=255;
        }
    }

    // 白い手帳カード（軽いシャドウ風に半透明白をまず引く）
    const cx = Math.round(size * 0.18);
    const cy = Math.round(size * 0.16);
    const cw = Math.round(size * 0.64);
    const ch = Math.round(size * 0.68);
    const cr = Math.round(size * 0.07);

    // ドロップシャドウ（黒半透明）
    fillRoundRect(px, size, cx+Math.round(size*0.025), cy+Math.round(size*0.035), cw, ch, cr, 0, 0, 0, 45);
    // カード本体（白）
    fillRoundRect(px, size, cx, cy, cw, ch, cr, 255, 255, 255, 245);

    // カード上部アクセントバー（青）
    const barH = Math.round(size * 0.07);
    fillRoundRect(px, size, cx, cy, cw, barH, cr, 36, 96, 200, 245);
    // バー下半分を角丸なし四角で上書き（下辺をフラットに）
    fillRect(px, size, cx, cy + Math.round(barH/2), cw, Math.round(barH/2), 36, 96, 200, 245);

    // テキスト行（青灰色）
    const lx  = cx + Math.round(cw * 0.14);
    const lh  = Math.max(2, Math.round(size * 0.048));
    const lGap= Math.round(size * 0.108);
    const lineWidths = [0.72, 0.72, 0.55, 0.38];
    const lineYStart = cy + barH + Math.round(size * 0.075);
    for (let li = 0; li < lineWidths.length; li++) {
        const lw = Math.round(cw * lineWidths[li] * 0.85);
        const ly = lineYStart + li * lGap;
        // 行末を細く（丸み表現）
        fillRoundRect(px, size, lx, ly, lw, lh, Math.round(lh/2), 36, 96, 200, 120);
    }

    return encodePng(px, size);
}

/* ── 出力 ────────────────────────────────────────── */
const dir = path.dirname(process.argv[1]);
for (const size of [192, 512]) {
    const file = path.join(dir, `icon-${size}.png`);
    fs.writeFileSync(file, generateIcon(size));
    console.log(`Generated ${file} (${size}x${size})`);
}
