<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>Parkir Tirta Tamansari</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <?php
        // FIX: cache-busting otomatis. Sebelumnya href CSS statis
        // (assets/css/style.css) sehingga browser terus memakai versi
        // lama dari cache walau file di server sudah diperbarui —
        // itu sebabnya Dashboard Owner masih menampilkan garis putus-putus
        // lama padahal style.css sudah diperbaiki. Dengan menambahkan
        // ?v=<waktu file terakhir diubah>, browser otomatis mengambil
        // ulang file setiap kali isinya berubah.
        $cssPath = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/assets/css/style.css';
        $cssVersion = file_exists($cssPath) ? filemtime($cssPath) : time();
    ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css?v=<?= $cssVersion ?>">
    <style>
        /* Tipografi global: sebelumnya font Inter/Space Grotesk dipakai di CSS
           tapi tidak pernah diimpor, jadi jatuh ke font default browser yang
           kurang jelas & tidak konsisten. */
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            font-size: 15px;
            line-height: 1.55;
            color: rgba(255, 255, 255, .88);
            -webkit-font-smoothing: antialiased;
        }
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            color: #ffffff;
            letter-spacing: .1px;
        }
        .card-tirta .card-header,
        .card-header {
            font-size: .95rem;
        }
        .stat-card h3 {
            font-family: 'Space Grotesk', 'Inter', sans-serif;
            font-weight: 700;
            font-size: 1.6rem;
            color: #ffffff !important;
            letter-spacing: .2px;
        }
        .stat-card .label {
            font-size: .78rem;
            font-weight: 500;
            color: rgba(255, 255, 255, .92) !important;
        }
        .table {
            font-size: .88rem;
        }
        .text-muted {
            color: rgba(255, 255, 255, .55) !important;
        }
        label, .form-label {
            font-weight: 500;
        }
        small {
            letter-spacing: .1px;
        }
    </style>
</head>
<body>
<?php tampilkanNotifikasiLogin(); ?>
<?php include __DIR__ . '/sidebar_owner.php'; ?>