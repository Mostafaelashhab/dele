<?php

/**
 * Generates the raster brand assets from code.
 *
 * The mark is defined once here rather than in a design file, so the icons,
 * the touch icon and the share card cannot drift apart from each other. The
 * SVG favicon is hand-written alongside; this covers the formats that have to
 * be raster because crawlers and home screens will not take an SVG.
 *
 * Run with: php resources/brand/generate.php
 */
const EMBER = [0xf9, 0x5c, 0x13];
const INK = [0x0d, 0x11, 0x17];
const WHITE = [0xff, 0xff, 0xff];

function canvas(int $w, int $h, array $rgb): GdImage
{
    $im = imagecreatetruecolor($w, $h);
    imagealphablending($im, true);
    imagesavealpha($im, true);
    imagefilledrectangle($im, 0, 0, $w, $h, imagecolorallocate($im, ...$rgb));

    return $im;
}

/** A rounded square, drawn by hand because GD has no primitive for it. */
function roundedSquare(GdImage $im, int $x, int $y, int $size, int $radius, int $colour): void
{
    imagefilledrectangle($im, $x + $radius, $y, $x + $size - $radius, $y + $size, $colour);
    imagefilledrectangle($im, $x, $y + $radius, $x + $size, $y + $size - $radius, $colour);

    foreach ([[$x + $radius, $y + $radius], [$x + $size - $radius, $y + $radius],
        [$x + $radius, $y + $size - $radius], [$x + $size - $radius, $y + $size - $radius]] as [$cx, $cy]) {
        imagefilledellipse($im, $cx, $cy, $radius * 2, $radius * 2, $colour);
    }
}

/**
 * The delivery mark: a cargo box, a cab, and two wheels.
 *
 * Deliberately blunt geometry — this has to survive being drawn at 16px in a
 * browser tab, where a faithful line-art truck turns to mud.
 */
function mark(GdImage $im, int $x, int $y, int $size): void
{
    $white = imagecolorallocate($im, ...WHITE);
    $ink = imagecolorallocate($im, ...INK);
    $u = $size / 32; // the SVG's 32-unit grid

    $round = (int) max(1, round(2 * $u));

    // Cargo box.
    roundedSquare($im, (int) ($x + 3 * $u), (int) ($y + 9 * $u), 0, 0, $white);
    imagefilledrectangle(
        $im,
        (int) ($x + 3 * $u), (int) ($y + 9 * $u),
        (int) ($x + 17 * $u), (int) ($y + 21 * $u),
        $white
    );

    // Cab, stepped down from the box.
    imagefilledrectangle(
        $im,
        (int) ($x + 17 * $u), (int) ($y + 13 * $u),
        (int) ($x + 23 * $u), (int) ($y + 21 * $u),
        $white
    );
    imagefilledrectangle(
        $im,
        (int) ($x + 23 * $u), (int) ($y + 16 * $u),
        (int) ($x + 28 * $u), (int) ($y + 21 * $u),
        $white
    );

    // Wheels, punched out of the body so they read at small sizes.
    foreach ([[8.5, 22.5], [22.5, 22.5]] as [$cx, $cy]) {
        imagefilledellipse($im, (int) ($x + $cx * $u), (int) ($y + $cy * $u),
            (int) (6 * $u), (int) (6 * $u), $white);
        imagefilledellipse($im, (int) ($x + $cx * $u), (int) ($y + $cy * $u),
            (int) (2.6 * $u), (int) (2.6 * $u), $ink);
    }

    unset($round);
}

/**
 * An app tile.
 *
 * The mark is inset rather than filling the tile: Android crops a maskable
 * icon to whatever shape the launcher uses, and a glyph running edge to edge
 * loses its wheels. Sixty-four percent leaves the safe area every platform
 * asks for and still reads at 32px.
 */
function icon(int $size, bool $rounded = true): GdImage
{
    $im = canvas($size, $size, EMBER);
    $ember = imagecolorallocate($im, ...EMBER);

    imagefilledrectangle($im, 0, 0, $size, $size, imagecolorallocatealpha($im, 0, 0, 0, 127));

    if ($rounded) {
        roundedSquare($im, 0, 0, $size - 1, (int) round($size * 0.22), $ember);
    } else {
        imagefilledrectangle($im, 0, 0, $size, $size, $ember);
    }

    $glyph = (int) round($size * 0.64);
    $offset = (int) round(($size - $glyph) / 2);

    mark($im, $offset, $offset, $glyph);

    return $im;
}

/**
 * A .ico wrapping a PNG.
 *
 * Every browser still asking for favicon.ico accepts PNG-encoded data inside
 * the container, so the file is a header plus the PNG bytes rather than a
 * legacy bitmap. The one in the repo before this was zero bytes.
 */
function writeIco(string $path, int $size): void
{
    $im = icon($size, rounded: false);

    ob_start();
    imagepng($im, null, 9);
    $png = (string) ob_get_clean();
    imagedestroy($im);

    $header = pack('vvv', 0, 1, 1);
    $entry = pack('CCCCvvVV',
        $size >= 256 ? 0 : $size,
        $size >= 256 ? 0 : $size,
        0, 0, 1, 32,
        strlen($png),
        22
    );

    file_put_contents($path, $header.$entry.$png);
}

$out = __DIR__.'/../../public';

writeIco($out.'/favicon.ico', 32);
echo "wrote favicon.ico (32px)\n";

foreach ([180 => 'apple-touch-icon.png', 192 => 'icons/icon-192.png', 512 => 'icons/icon-512.png'] as $size => $path) {
    $im = icon($size);
    imagepng($im, $out.'/'.$path, 9);
    imagedestroy($im);
    echo "wrote {$path} ({$size}px)\n";
}

/*
 * The share card.
 *
 * Brand-led rather than wordy: GD renders glyphs without shaping, so Arabic
 * would come out as disconnected reversed letters. The Arabic message lives in
 * og:title and og:description, which every crawler renders as real text beside
 * this image.
 */
$w = 1200;
$h = 630;
$card = canvas($w, $h, INK);
$ember = imagecolorallocate($card, ...EMBER);
$white = imagecolorallocate($card, ...WHITE);
$grid = imagecolorallocate($card, 0x1a, 0x21, 0x2b);

for ($x = 0; $x < $w; $x += 72) {
    imageline($card, $x, 0, $x, $h, $grid);
}
for ($y = 0; $y < $h; $y += 72) {
    imageline($card, 0, $y, $w, $y, $grid);
}

$tile = 148;
$tx = (int) (($w - $tile) / 2);
$ty = 168;
roundedSquare($card, $tx, $ty, $tile, (int) round($tile * 0.22), $ember);
$glyph = (int) round($tile * 0.64);
mark($card, $tx + (int) (($tile - $glyph) / 2), $ty + (int) (($tile - $glyph) / 2), $glyph);

$font = '/System/Library/Fonts/Supplemental/Arial Bold.ttf';

if (is_readable($font)) {
    $text = 'banha.shop';
    $box = imagettfbbox(58, 0, $font, $text);
    imagettftext($card, 58, 0, (int) (($w - ($box[2] - $box[0])) / 2), $ty + $tile + 108, $white, $font, $text);

    $sub = 'Delivery network  ·  Banha, Egypt';
    $box = imagettfbbox(24, 0, $font, $sub);
    imagettftext($card, 24, 0, (int) (($w - ($box[2] - $box[0])) / 2), $ty + $tile + 162,
        imagecolorallocate($card, 0x84, 0x92, 0xa6), $font, $sub);
} else {
    fwrite(STDERR, "note: no TTF available, share card rendered without text\n");
}

imagefilledrectangle($card, 0, $h - 8, $w, $h, $ember);
imagepng($card, $out.'/og-image.png', 9);
imagedestroy($card);
echo "wrote og-image.png (1200x630)\n";
