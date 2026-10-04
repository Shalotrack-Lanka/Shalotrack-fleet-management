<?php

namespace App\Support;

/**
 * Bar charts for the PDF exports, drawn on the server.
 *
 * Primary renderer: GD + FreeType with the DejaVu Sans font that ships inside
 * dompdf — crisp text, exact alignment, rendered at 3x so it stays sharp on paper.
 * (dompdf's SVG text support proved unreliable: wrong fonts and mis-centred labels.)
 * Fallback: the SVG renderer in ChartSvg, if GD/FreeType isn't available on the host.
 *
 * Nothing client-supplied is embedded; the chart is built from API numbers only.
 */
final class Chart
{
    private const SCALE = 3;

    /**
     * @param string[] $labels
     * @param array<int, array{name:string,color:string,values:float[]}> $series
     * @return string a data: URI usable as <img src>
     */
    public static function bars(array $labels, array $series, int $width = 520, int $height = 170, string $unit = ''): string
    {
        $font = base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf');

        if (!function_exists('imagettftext') || !function_exists('imagecreatetruecolor') || !is_readable($font)) {
            return ChartSvg::dataUri(ChartSvg::bars($labels, $series, $width, $height, $unit));
        }

        $k = self::SCALE;
        $W = $width * $k;
        $H = $height * $k;
        $padL = 40 * $k; $padR = 8 * $k; $padT = 14 * $k; $padB = 24 * $k;
        $cw = $W - $padL - $padR;
        $ch = $H - $padT - $padB;

        $im = imagecreatetruecolor($W, $H);
        imageantialias($im, true);
        $white = imagecolorallocate($im, 255, 255, 255);
        imagefilledrectangle($im, 0, 0, $W, $H, $white);

        $grid = imagecolorallocate($im, 229, 231, 235);
        $axis = imagecolorallocate($im, 148, 163, 184);
        $text = imagecolorallocate($im, 100, 116, 139);

        $max = 0.0;
        foreach ($series as $s) foreach ($s['values'] as $v) $max = max($max, (float) $v);
        $max = self::niceMax($max);
        $ticks = 4;
        $fs = 7.2 * $k * 0.75;   // pt-ish size for imagettftext (points at 72dpi * scale)

        // grid + y labels
        for ($i = 0; $i <= $ticks; $i++) {
            $y = (int) round($padT + $ch - ($i / $ticks) * $ch);
            imageline($im, $padL, $y, $padL + $cw, $y, $grid);
            $lab = self::num($max * $i / $ticks);
            $bb = imagettfbbox($fs, 0, $font, $lab);
            $tw = $bb[2] - $bb[0];
            imagettftext($im, $fs, 0, $padL - 5 * $k - $tw, $y + (int) ($fs * 0.35), $text, $font, $lab);
        }

        $n = count($labels);
        if ($n > 0) {
            $groupW = $cw / $n;
            $m = max(1, count($series));
            $gap = min(6 * $k, $groupW * 0.25);
            $barW = max(2, ($groupW - $gap) / $m);
            $every = (int) max(1, ceil($n / 10));

            $colors = [];
            foreach ($series as $si => $s) {
                [$r, $g, $b] = self::rgb($s['color']);
                $colors[$si] = imagecolorallocate($im, $r, $g, $b);
            }

            foreach ($labels as $i => $label) {
                $gx = $padL + $i * $groupW + $gap / 2;
                foreach ($series as $si => $s) {
                    $v = (float) ($s['values'][$i] ?? 0);
                    if ($v <= 0 || $max <= 0) continue;
                    $bh = max(2 * $k * 0.5, ($v / $max) * $ch);
                    $x1 = (int) round($gx + $si * $barW);
                    $x2 = (int) round($gx + ($si + 1) * $barW - max(1, $k * 0.4));
                    $y1 = (int) round($padT + $ch - $bh);
                    imagefilledrectangle($im, $x1, $y1, max($x1, $x2), $padT + $ch, $colors[$si]);
                }
                if ($i % $every === 0 || $i === $n - 1) {
                    $bb = imagettfbbox($fs, 0, $font, (string) $label);
                    $tw = $bb[2] - $bb[0];
                    $cx = $padL + $i * $groupW + $groupW / 2;
                    $x = (int) max(0, min($W - $tw, $cx - $tw / 2));
                    imagettftext($im, $fs, 0, $x, $H - 7 * $k, $text, $font, (string) $label);
                }
            }
        }

        imageline($im, $padL, $padT, $padL, $padT + $ch, $axis);
        imageline($im, $padL, $padT + $ch, $padL + $cw, $padT + $ch, $axis);

        if ($unit !== '') {
            imagettftext($im, $fs, 0, $padL + 3 * $k, $padT - 4 * $k, $text, $font, $unit);
        }

        ob_start();
        imagepng($im, null, 6);
        $png = (string) ob_get_clean();
        imagedestroy($im);

        return 'data:image/png;base64,' . base64_encode($png);
    }

    private static function niceMax(float $max): float
    {
        if ($max <= 0) return 1.0;
        $base = 10 ** floor(log10($max));
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($max <= $m * $base) return $m * $base;
        }
        return 10 * $base;
    }

    private static function num(float $v): string
    {
        if ($v >= 100) return number_format($v, 0);
        if ($v >= 10)  return rtrim(rtrim(number_format($v, 1), '0'), '.');
        return rtrim(rtrim(number_format($v, 2), '0'), '.') ?: '0';
    }

    /** @return array{0:int,1:int,2:int} */
    private static function rgb(string $hex): array
    {
        if (!preg_match('/^#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})$/', $hex, $m)) {
            return [250, 105, 8];
        }
        return [hexdec($m[1]), hexdec($m[2]), hexdec($m[3])];
    }
}