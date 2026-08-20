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
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        /* ============================================================
           TEMA PANEL OPERATOR — disamakan dengan landing page "kolam malam"
           Palet: ink gelap, aksen aqua/violet/brass, tipografi
           Space Grotesk (judul) + Inter (isi) + IBM Plex Mono (angka)
           ============================================================ */
        :root {
            --ink: #070b1a;
            --ink-2: #0e1830;
            --deep: #101b3d;
            --aqua: #2fe6c8;
            --aqua-dim: #1ba893;
            --violet: #8b7cfa;
            --brass: #f2b84b;
            --stone-2: #dfe4ee;
            --text-soft: #9aa4bb;
        }
        html {
            background: var(--ink) !important;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
            background:
                radial-gradient(60% 50% at 85% 0%, rgba(139,124,250,.14) 0%, transparent 60%),
                radial-gradient(60% 55% at 0% 100%, rgba(47,230,200,.10) 0%, transparent 55%),
                var(--ink) !important;
            background-color: var(--ink) !important;
            color: #fff;
            min-height: 100vh;
        }
        .tirta-content, .tirta-body {
            background: var(--ink) !important;
            min-height: 100vh;
        }
        h1, h2, h3, h4, h5, h6, .page-title { font-family: 'Space Grotesk', system-ui, sans-serif; }

        /* Kartu */
        .card, .card-tirta {
            background: rgba(255,255,255,.045) !important;
            background-color: #0e1830 !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            border-radius: 16px !important;
            color: #fff !important;
            box-shadow: 0 14px 34px rgba(3,6,16,.28);
        }
        .card-body, .card-body * { color: inherit; }
        .card-body h1, .card-body h2, .card-body h3, .card-body h4, .card-body h5, .card-body h6,
        .card-body p, .card-body span, .card-body strong, .card-body small, .card-body div {
            color: #fff;
        }
        .stat-card .label, .card-body .text-muted, .card-body small.text-muted { color: rgba(255,255,255,.6) !important; }
        .card-header {
            background: rgba(255,255,255,.03) !important;
            border-bottom: 1px solid rgba(255,255,255,.1) !important;
            color: #fff !important;
            font-weight: 700;
        }
        .card.border-warning { border-color: rgba(242,184,75,.5) !important; }
        .card.border-primary { border-color: rgba(139,124,250,.5) !important; }
        .card-tirta h6, .card-tirta strong { color: #fff !important; }
        .text-dark { color: #fff !important; }
        .text-muted { color: var(--text-soft) !important; }

        /* Kartu statistik lama (bg-grad-*) -> diselaraskan ke palet aqua/violet/brass,
           gaya badge ikon + blob blur seperti dashboard Admin */
        .stat-card {
            position: relative;
            overflow: hidden;
            background: rgba(255,255,255,.04) !important;
            border-radius: 18px !important;
            border: 1px solid rgba(255,255,255,.12) !important;
            padding: 22px !important;
            color: #fff !important;
            box-shadow: 0 14px 34px rgba(3,6,16,.3);
        }
        .stat-card::after {
            content: "";
            position: absolute;
            width: 130px; height: 130px;
            border-radius: 50%;
            filter: blur(38px);
            top: -40px; right: -30px;
            opacity: .35;
            z-index: 0;
        }
        .stat-card h3, .stat-card .stat-icon, .stat-card .label { position: relative; z-index: 1; }
        .stat-card h3 { font-family: 'IBM Plex Mono', monospace; font-weight: 600; margin: 0 0 4px; }
        .stat-card .label { color: rgba(255,255,255,.6) !important; font-size: .84rem; }
        .stat-card .stat-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
            margin-bottom: 14px;
        }
        .stat-card.bg-grad-red   { border-color: rgba(47,230,200,.4) !important; box-shadow: 0 0 0 1px rgba(47,230,200,.15), 0 14px 34px rgba(3,6,16,.3); }
        .stat-card.bg-grad-red::after   { background: var(--aqua); }
        .stat-card.bg-grad-red .stat-icon   { background: rgba(47,230,200,.14); color: var(--aqua) !important; }
        .stat-card.bg-grad-orange::after { background: var(--violet); }
        .stat-card.bg-grad-orange .stat-icon { background: rgba(139,124,250,.16); color: var(--violet) !important; }
        .stat-card.bg-grad-green::after  { background: var(--brass); }
        .stat-card.bg-grad-green .stat-icon  { background: rgba(242,184,75,.16); color: var(--brass) !important; }
        .stat-card.bg-grad-blue::after   { background: var(--aqua-dim); }
        .stat-card.bg-grad-blue .stat-icon   { background: rgba(27,168,147,.18); color: var(--aqua-dim) !important; }

        /* Ikon di kartu selain stat-card (mis. aksi cepat) tetap solid seperti semula */
        .quick-action-icon.bg-grad-red    { background: linear-gradient(135deg, var(--aqua), var(--aqua-dim)) !important; color: var(--ink) !important; }
        .quick-action-icon.bg-grad-dark   { background: linear-gradient(135deg, var(--ink-2), var(--ink)) !important; border: 1px solid rgba(255,255,255,.15) !important; }

        /* Label eyebrow kecil (mis. "RINGKASAN HARI INI") */
        .eyebrow-op {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: .72rem;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--aqua);
            margin-bottom: 14px;
        }
        .eyebrow-op::before { content: ""; width: 22px; height: 1px; background: var(--aqua); display: inline-block; }

        /* Tabel */
        .table { color: #fff !important; --bs-table-color: #fff; }
        .table > :not(caption) > * > * { background: transparent !important; color: #fff !important; border-bottom-color: rgba(255,255,255,.1) !important; }
        .table thead th { color: rgba(255,255,255,.55) !important; font-size: .78rem; text-transform: uppercase; letter-spacing: .5px; font-weight: 700; border-bottom-color: rgba(255,255,255,.15) !important; }
        .table-hover > tbody > tr:hover > * { background: rgba(255,255,255,.05) !important; }
        .table-responsive { border-radius: 0 0 16px 16px; }

        /* Form */
        .form-control, .form-select, .form-select.field-otomatis, select.field-otomatis {
            background-color: rgba(255,255,255,.05) !important;
            border: 1px solid rgba(255,255,255,.16) !important;
            color: #fff !important;
            border-radius: 10px !important;
        }
        .form-control:focus, .form-select:focus {
            background: rgba(255,255,255,.07) !important;
            border-color: var(--aqua) !important;
            box-shadow: 0 0 0 .2rem rgba(47,230,200,.15) !important;
            color: #fff !important;
        }
        .form-control::placeholder { color: rgba(255,255,255,.35) !important; }
        .form-label { color: rgba(255,255,255,.85) !important; font-weight: 600; }
        .input-group-text { background: rgba(255,255,255,.06) !important; border: 1px solid rgba(255,255,255,.16) !important; color: rgba(255,255,255,.7) !important; }

        /* Dropdown <select> — opsi & panah ikut gelap (sebelumnya putih polos bawaan browser) */
        select.form-select, select.form-control {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%232fe6c8'%3E%3Cpath d='M8 11 3 6h10z'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 0.75rem center !important;
            background-size: 14px 14px !important;
        }
        select.form-select option, select.form-control option {
            background: #0e1830 !important;
            color: #fff !important;
        }

        /* Badge & label kecil "Otomatis / Default terpilih" agar tidak putih-di-atas-putih */
        .label-otomatis-badge {
            background: rgba(47,230,200,.15) !important;
            color: var(--aqua) !important;
            border: 1px solid rgba(47,230,200,.3);
            border-radius: 999px;
            padding: 1px 9px;
            font-size: .68rem;
            font-weight: 600;
        }
        .badge.bg-info-subtle { background: rgba(139,124,250,.18) !important; color: var(--violet) !important; }
        .badge.bg-primary-subtle { background: rgba(47,230,200,.15) !important; color: var(--aqua) !important; }
        .text-info-emphasis { color: var(--violet) !important; }
        .badge.bg-primary { background: var(--violet) !important; }
        .badge.bg-danger { background: #d64545 !important; }
        .alert-primary { background: rgba(139,124,250,.14) !important; border: 1px solid rgba(139,124,250,.35) !important; color: #e6e2ff !important; }
        .card-header.bg-primary-subtle, .card-header.bg-warning-subtle {
            background: rgba(255,255,255,.03) !important;
        }

        /* Tombol */
        .btn-tirta {
            background: linear-gradient(120deg, var(--aqua), var(--violet)) !important;
            color: var(--ink) !important;
            border: none !important;
            font-weight: 700 !important;
            border-radius: 999px !important;
        }
        .btn-tirta:hover { filter: brightness(1.08); }
        .btn-outline-secondary { color: rgba(255,255,255,.75) !important; border-color: rgba(255,255,255,.3) !important; }
        .btn-outline-secondary:hover { background: rgba(255,255,255,.1) !important; color: #fff !important; }
        .btn-light { background: rgba(255,255,255,.1) !important; border-color: rgba(255,255,255,.2) !important; color: #fff !important; }

        /* Badge & progress */
        .badge.bg-success { background-color: var(--aqua-dim) !important; }
        .badge.bg-warning { background-color: var(--brass) !important; color: var(--ink) !important; }
        .progress { background: rgba(255,255,255,.1) !important; }

        /* Modal */
        .modal-content { background: var(--ink-2) !important; color: #fff !important; border-radius: 18px !important; border: 1px solid rgba(255,255,255,.12) !important; }
        .modal-header, .modal-footer { border-color: rgba(255,255,255,.1) !important; }
        .modal-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }

        /* Alert */
        .alert-success { background: rgba(47,230,200,.12) !important; border: 1px solid rgba(47,230,200,.3) !important; color: #d8fff7 !important; }
        .alert-danger { background: rgba(214,69,69,.14) !important; border: 1px solid rgba(214,69,69,.35) !important; color: #ffd9d9 !important; }

        /* Lane row: dipakai untuk panel "Kapasitas Area" agar sama seperti landing page */
        .lane-row { display: flex; align-items: center; gap: 16px; padding: 12px 4px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .lane-row:last-child { border-bottom: none; }
        .lane-row .lane-name { font-weight: 700; width: 110px; flex-shrink: 0; color: #fff; font-size: .85rem; }
        .lane-row .lane-track { flex: 1; height: 7px; border-radius: 4px; background: rgba(255,255,255,.1); overflow: hidden; }
        .lane-row .lane-fill { height: 100%; border-radius: 4px; }
        .lane-fill.ok { background: var(--aqua-dim); }
        .lane-fill.warn { background: var(--brass); }
        .lane-fill.full { background: #d64545; }
        .lane-row .lane-num { font-family: 'IBM Plex Mono', monospace; font-size: .72rem; color: rgba(255,255,255,.55); flex-shrink: 0; }

        /* Quick-action card di dashboard */
        .quick-action-icon {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
    </style>
</head>
<body>
<?php tampilkanNotifikasiLogin(); ?>
<?php include __DIR__ . '/navbar.php'; ?>