<?php
// =====================================================
// TEMPLATE : REPORT_PDF
// Digunakan untuk generate laporan PDF di semua modul
// =====================================================

if (!defined('SECURE')) {
    die('Direct access not allowed');
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

// 🧱 Include komponen template
require_once __DIR__ . '/report_header.php';

// ✅ Pastikan library ReportLab / Dompdf sudah dikonfigurasi
require_once __DIR__ . '/../../includes/pdf_generator.php';

// ----------------------------
// Fungsi utama generate PDF
// ----------------------------
function generate_report_pdf($title, $subtitle, $table_headers, $table_data, $filename = 'laporan.pdf') {
    ob_start();
    ?>

    <html>
    <head>
        <style>
            body {
                font-family: Arial, Helvetica, sans-serif;
                font-size: 12px;
                margin: 30px;
                color: #333;
            }
            h1 {
                font-size: 20px;
                text-align: center;
                margin-bottom: 0;
            }
            h2 {
                font-size: 14px;
                text-align: center;
                margin-top: 5px;
                color: #555;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
            }
            th, td {
                border: 1px solid #ccc;
                padding: 6px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
                font-weight: bold;
            }
            footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                text-align: center;
                font-size: 10px;
                color: #777;
            }
        </style>
    </head>
    <body>

        <?php render_report_header($title, $subtitle); ?>

        <table>
            <thead>
                <tr>
                    <?php foreach ($table_headers as $header): ?>
                        <th><?= htmlspecialchars($header); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($table_data)): ?>
                    <?php foreach ($table_data as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?>
                                <td><?= htmlspecialchars($cell); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="<?= count($table_headers); ?>" style="text-align:center;">Tidak ada data</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    </body>
    </html>

    <?php
    $html = ob_get_clean();

    // 🔧 Generate PDF
    generate_pdf($html, $filename);
}
