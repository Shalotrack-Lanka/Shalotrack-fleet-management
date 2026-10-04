<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

/**
 * One place that turns a Blade view into a branded PDF download:
 * A4, remote resources disabled (no SSRF through a crafted value), and a
 * "Page X of Y" footer drawn on every page.
 */
final class PdfExport
{
    public static function download(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        $filename = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'report.pdf';

        $pdf = Pdf::setOptions([
                'isRemoteEnabled'      => false,
                'isPhpEnabled'         => false,
                'defaultFont'          => 'DejaVu Sans',
                'dpi'                  => 110,
                'isHtml5ParserEnabled' => true,
            ])
            ->loadView($view, $data)
            ->setPaper('a4', $orientation);

        // Page numbers: need the laid-out document, so render first.
        $dompdf = $pdf->getDomPDF();
        $dompdf->render();
        $canvas = $dompdf->getCanvas();
        $font   = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $w = $canvas->get_width();
        $h = $canvas->get_height();
        $canvas->page_text($w - 90, $h - 26, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 7.5, [0.58, 0.64, 0.72]);

        return response($dompdf->output(), 200, [
            'Content-Type'           => 'application/pdf',
            'Content-Disposition'    => 'attachment; filename="' . $filename . '"',
            'Cache-Control'          => 'no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}