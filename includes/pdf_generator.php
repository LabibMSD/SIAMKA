<?php
// =======================================================
// FILE: includes/pdf_generator.php
// Fungsi: Mengubah HTML menjadi file PDF (Reusable Template)
// Library: Dompdf
// =======================================================

if (!defined('SECURE')) {
    die('Direct access not allowed');
}

require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * generate_pdf
 * ------------------------------------------
 * Fungsi untuk membuat file PDF dari HTML
 * dengan template header, footer, dan style SIAMKA.
 *
 * @param string $content  Isi konten utama laporan (HTML)
 * @param string $filename Nama file hasil PDF
 * @param string $report_title Judul laporan (opsional)
 */
function generate_pdf($content, $filename = 'laporan.pdf', $report_title = 'Laporan SIAMKA')
{
    // 🔧 Konfigurasi Dompdf
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new Dompdf($options);

    // 🧩 Path file template
    $basePath = __DIR__ . '/templates/';
    $stylePath = $basePath . 'report_style.css';

    // 🔹 Include header & footer file agar fungsi render bisa dipakai
    require_once $basePath . 'report_header.php';

    // 🎨 Muat CSS (jika ada)
    $style = file_exists($stylePath) ? file_get_contents($stylePath) : '';

    // 🧱 Bangun HTML laporan
    ob_start();
    ?>
    <html>
    <head>
        <meta charset="UTF-8">
        <style><?= $style ?></style>
    </head>
    <body>
        <?php render_report_header($report_title); ?>

        <div class="report-content">
            <?= $content ?>
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();

    // 💾 Render ke Dompdf
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // 📥 Output PDF ke browser
    $dompdf->stream($filename, ["Attachment" => false]);
}
?>
