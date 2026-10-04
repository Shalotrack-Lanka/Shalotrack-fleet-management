<?php

namespace App\Support;

/**
 * Tiny server-side bar-chart renderer for the PDF exports.
 *
 * Why not the browser's canvas screenshots (what the old export posted)?
 *  - Vector output stays sharp when printed; PNG screenshots were blurry.
 *  - Nothing client-supplied gets embedded into a server-rendered document.
 *  - No multi-megabyte base64 form fields.
 *  - Same data, same chart, every time — no dependence on what happened to be
 *    on screen (a chart that hadn't finished animating exported blank).
 *
 * Output is a data: URI usable as <img src="…"> in dompdf (php-svg-lib).
 */
final class ChartSvg
{
    private const FONT = 'sans-serif';

    /**
     * @param string[]               $labels   x labels (already formatted)
     * @param array<int, array{name:string,color:string,values:float[]}> $series 1..2 series
     */
    public static function bars(array $labels, array $series, int $width = 520, int $height = 190, string $unit = ''): string
    {
        $n = count($labels);
        $padL = 38; $padR = 8; $padT = 12; $padB = 26;
        $cw = $width - $padL - $padR;
        $ch = $height - $padT - $padB;

        $max = 0.0;
        foreach ($series as $s) {
            foreach ($s['values'] as $v) $max = max($max, (float) $v);
        }
        $max = self::niceMax($max);
        $ticks = 4;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '">';
        $svg .= '<rect x="0" y="0" width="' . $width . '" height="' . $height . '" fill="#ffffff"/>';

        // Grid + y labels
        for ($i = 0; $i <= $ticks; $i++) {
            $y = $padT + $ch - ($i / $ticks) * $ch;
            $val = $max * $i / $ticks;
            $svg .= '<line x1="' . $padL . '" y1="' . self::n($y) . '" x2="' . ($padL + $cw) . '" y2="' . self::n($y) . '" stroke="#e5e7eb" stroke-width="0.6"/>';
            $svg .= '<text x="' . ($padL - 5) . '" y="' . self::n($y + 3) . '" text-anchor="end" font-family="' . self::FONT . '" font-size="8" fill="#64748b">' . self::label($val) . '</text>';
        }

        if ($n > 0) {
            $groupW = $cw / $n;
            $k = max(1, count($series));
            $gap = min(6.0, $groupW * 0.25);
            $barW = max(1.5, ($groupW - $gap) / $k);
            $everyLabel = (int) max(1, ceil($n / 12));

            foreach ($labels as $i => $label) {
                $gx = $padL + $i * $groupW + $gap / 2;
                foreach ($series as $si => $s) {
                    $v = (float) ($s['values'][$i] ?? 0);
                    $bh = $max > 0 ? ($v / $max) * $ch : 0;
                    if ($v > 0) $bh = max(1.2, $bh);
                    $x = $gx + $si * $barW;
                    $y = $padT + $ch - $bh;
                    if ($bh > 0) {
                        $svg .= '<rect x="' . self::n($x) . '" y="' . self::n($y) . '" width="' . self::n($barW - 0.6) . '" height="' . self::n($bh) . '" fill="' . self::color($s['color']) . '"/>';
                    }
                }
                if ($i % $everyLabel === 0 || $i === $n - 1) {
                    $svg .= '<text x="' . self::n($padL + $i * $groupW + $groupW / 2) . '" y="' . ($height - 8) . '" text-anchor="middle" font-family="' . self::FONT . '" font-size="7.5" fill="#64748b">' . htmlspecialchars((string) $label, ENT_XML1) . '</text>';
                }
            }
        }

        // Axes
        $svg .= '<line x1="' . $padL . '" y1="' . $padT . '" x2="' . $padL . '" y2="' . ($padT + $ch) . '" stroke="#94a3b8" stroke-width="0.8"/>';
        $svg .= '<line x1="' . $padL . '" y1="' . ($padT + $ch) . '" x2="' . ($padL + $cw) . '" y2="' . ($padT + $ch) . '" stroke="#94a3b8" stroke-width="0.8"/>';

        if ($unit !== '') {
            $svg .= '<text x="' . ($padL + 2) . '" y="' . ($padT - 3) . '" font-family="' . self::FONT . '" font-size="7.5" fill="#94a3b8">' . htmlspecialchars($unit, ENT_XML1) . '</text>';
        }

        $svg .= '</svg>';
        return $svg;
    }

    /** data: URI for an <img src>. */
    public static function dataUri(string $svg): string
    {
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /** Round the axis maximum up to a "nice" number (1, 2, 2.5, 5, 10 × 10^k). */
    private static function niceMax(float $max): float
    {
        if ($max <= 0) return 1.0;
        $exp = floor(log10($max));
        $base = 10 ** $exp;
        foreach ([1, 2, 2.5, 5, 10] as $m) {
            if ($max <= $m * $base) return $m * $base;
        }
        return 10 * $base;
    }

    private static function label(float $v): string
    {
        if ($v >= 100) return number_format($v, 0);
        if ($v >= 10) return rtrim(rtrim(number_format($v, 1), '0'), '.');
        return rtrim(rtrim(number_format($v, 2), '0'), '.') ?: '0';
    }

    private static function n(float $v): string
    {
        return number_format($v, 2, '.', '');
    }

    private static function color(string $c): string
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $c) ? $c : '#FA6908';
    }
}