<?php
// =======================================================
// FILE: includes/templates/report_header.php
// Fungsi: Header laporan profesional (untuk PDF SIAMKA)
// =======================================================

if (!defined('SECURE')) {
    die('Direct access not allowed');
}

if (!function_exists('render_report_header')) {
    /**
     * Render header laporan SIAMKA
     * 
     * @param string $report_title Judul laporan
     */
    function render_report_header($report_title = 'Laporan Data')
    {
        // 🔍 Cari logo secara absolut agar bisa dibaca Dompdf
        $logo_path = realpath(__DIR__ . '/../../assets/images/logo2.png');

        // Jika file ditemukan, ubah ke base64 agar bisa langsung ditanam di PDF
        if ($logo_path && file_exists($logo_path)) {
            $logo_data = base64_encode(file_get_contents($logo_path));
            $logo_src = 'data:image/png;base64,' . $logo_data;
        } else {
            $logo_src = ''; // fallback jika tidak ada logo
        }

        ?>
        <div class="report-header" 
             style="display: flex; align-items: center; justify-content: center; flex-direction: column; text-align: center; margin-bottom: 20px;">

            <?php if ($logo_src): ?>
                <img src="<?= $logo_src ?>" alt="Logo" 
                     style="height: 70px; width: 70px; border-radius: 10px; margin-bottom: 10px;">
            <?php endif; ?>

            <h2 style="margin: 0; font-size: 20px; font-weight: bold; color: #222;">
                SISTEM INFORMASI ASET MANAJEMEN KAMPUS
            </h2>

            <h4 style="margin: 6px 0 0; font-size: 15px; color: #444;">
                <?= htmlspecialchars($report_title) ?>
            </h4>

            <p style="margin: 4px 0 0; font-size: 11px; color: #777;">
                Dicetak pada: <?= date('d-m-Y') ?>
            </p>

            <hr style="border: none; border-top: 1px solid #888; width: 90%; margin-top: 10px;">
        </div>
        <?php
    }
}
?>
