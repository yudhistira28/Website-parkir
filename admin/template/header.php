<?php
/**
 * Dipakai di dalam admin/*.php setelah $page_title didefinisikan.
 * Membutuhkan variabel $koneksi & session admin aktif (cekLogin(['admin'])).
 */

// Ambil foto profil terbaru dari database (biar langsung update tanpa perlu login ulang)
$fotoAdmin = null;
if (isset($_SESSION['id_user'])) {
    $stmtFotoAdmin = $koneksi->prepare("SELECT foto FROM tb_user WHERE id_user = ?");
    $stmtFotoAdmin->execute([$_SESSION['id_user']]);
    $rowFotoAdmin = $stmtFotoAdmin->fetch();
    $fotoAdmin = $rowFotoAdmin['foto'] ?? null;
}

$namaTopbar = !empty($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : ($_SESSION['username'] ?? 'User');
$roleTopbar = !empty($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Admin';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= isset($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>Tirta Tamansari</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        /* ============================================================
           TIRTA TAMANSARI ADMIN — mengikuti tema "kolam malam" landing page
           ============================================================ */
        :root {
            --ink: #070b1a;
            --ink-2: #0e1830;
            --deep: #101b3d;
            --aqua: #2fe6c8;
            --aqua-dim: #1ba893;
            --violet: #8b7cfa;
            --brass: #f2b84b;
            --paper: #ffffff;
            --stone: #eef1f6;
            --stone-2: #dfe4ee;
            --text-soft: #566178;
            --danger: #d64545;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        .app-wrapper { display: flex; min-height: 100vh; width: 100%; }
        .main-content {
            flex: 1; min-width: 0;
            background:
                radial-gradient(60% 45% at 92% 0%, rgba(139,124,250,.12) 0%, transparent 60%),
                radial-gradient(65% 45% at 0% 100%, rgba(47,230,200,.08) 0%, transparent 55%),
                var(--ink);
        }

        /* ---------- Topbar (kaca gelap, aksen aqua/brass) ---------- */
        .tp-topbar {
            background: rgba(14, 24, 48, .78);
            backdrop-filter: blur(14px);
            padding: 0.7rem 1.4rem;
            border-bottom: 1px solid rgba(255,255,255,.08);
            box-shadow: 0 1px 0 rgba(242,184,75,.4);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 900;
        }
        .page-title {
            font-weight: 700;
            margin: 0;
            color: #ffffff !important; /* fix: paksa putih agar tidak ketiban warna default h1/utility lain yang bikin teks nyaris tak terlihat di topbar gelap */
            font-family: 'Space Grotesk', sans-serif;
            letter-spacing: -.2px;
        }

        /* Chip profil di topbar: foto + nama & role di sampingnya */
        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 4px 10px 4px 4px;
            border-radius: 999px;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.08);
            cursor: pointer;
            transition: background .18s ease;
        }
        .user-chip:hover { background: rgba(255,255,255,.12); }
        .avatar-mini {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            overflow: hidden;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--ink-2);
            border: 2px solid var(--aqua);
        }
        .avatar-mini img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-fallback { color: var(--aqua); font-weight: 700; font-size: .85rem; }
        .user-chip-text { display: flex; flex-direction: column; line-height: 1.2; text-align: left; }
        .user-chip-text strong { font-size: .85rem; color: #fff; }
        .user-chip-text small { font-size: .7rem; color: rgba(255,255,255,.5); }
        .user-chip .bi-chevron-down { color: rgba(255,255,255,.5) !important; }
        .dropdown-menu { background: var(--ink-2); border: 1px solid rgba(255,255,255,.1); border-radius: 14px; padding: 8px; box-shadow: 0 18px 40px rgba(0,0,0,.4); }
        .dropdown-item { color: rgba(255,255,255,.75); border-radius: 8px; font-size: .88rem; padding: 8px 12px; }
        .dropdown-item:hover, .dropdown-item:focus { background: rgba(255,255,255,.08); color: #fff; }
        .dropdown-divider { border-color: rgba(255,255,255,.1); }

        /* Mengatur padding body konten agar rapat dengan topbar */
        .tp-body {
            padding: 1.25rem 1.25rem !important;
        }

        /* ---------- Alert ---------- */
        .alert-success { background: rgba(47,230,200,.12); border: 1px solid rgba(47,230,200,.35); color: #d9fff7; }
        .alert-danger { background: rgba(214,69,69,.15); border: 1px solid rgba(214,69,69,.4); color: #ffe1e1; }

        /* ---------- Kartu (mengikuti gaya panel-card landing page) ---------- */
        .card, .card-tirta {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 18px;
            color: #fff;
            box-shadow: 0 18px 40px rgba(3,6,16,.25);
        }
        .card-header, .card-tirta .card-header {
            background: transparent !important;
            border-bottom: 1px solid rgba(255,255,255,.1) !important;
            color: #fff;
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            padding: 1rem 1.25rem;
        }
        .card-body { color: rgba(255,255,255,.82); }
        .text-muted { color: rgba(255,255,255,.5) !important; }

        /* ---------- Tabel ---------- */
        .table { color: rgba(255,255,255,.85); margin-bottom: 0; }
        .table > :not(caption) > * > * { background-color: transparent; border-bottom-color: rgba(255,255,255,.08); color: inherit; box-shadow: none; }
        .table thead, .table > thead, .table-light { background: rgba(255,255,255,.06) !important; color: rgba(255,255,255,.55) !important; font-size: .78rem; text-transform: uppercase; letter-spacing: .5px; }
        .table-hover > tbody > tr:hover > * { background: rgba(255,255,255,.06); color: #fff; }

        /* ---------- Badge status ---------- */
        .badge.bg-success { background: var(--aqua-dim) !important; }
        .badge.bg-danger { background: var(--danger) !important; }
        .badge.bg-warning { background: var(--brass) !important; color: var(--ink) !important; }
        .badge.bg-secondary { background: var(--violet) !important; color: #fff !important; }

        /* ---------- Tombol ---------- */
        .btn-tirta {
            background: linear-gradient(120deg, var(--aqua), var(--violet));
            color: var(--ink) !important;
            border: none;
            font-weight: 700;
            border-radius: 999px;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .btn-tirta:hover { transform: translateY(-2px); box-shadow: 0 12px 26px rgba(47,230,200,.28); }
        .btn-outline-secondary { border-color: rgba(255,255,255,.35); color: rgba(255,255,255,.85); }
        .btn-outline-secondary:hover { background: rgba(255,255,255,.1); color: #fff; border-color: #fff; }
        .btn-outline-danger { border-color: var(--danger); color: #ff9d9d; }
        .btn-outline-danger:hover { background: var(--danger); color: #fff; }
        .btn-light { background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.15); color: #fff; }
        .btn-light:hover { background: rgba(255,255,255,.16); color: #fff; }

        /* ---------- Form (di luar modal) ---------- */
        .tp-body .form-control, .tp-body .form-select {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.15);
            color: #fff;
            border-radius: 10px;
        }
        .tp-body .form-control::placeholder { color: rgba(255,255,255,.4); }
        .tp-body .form-control:focus, .tp-body .form-select:focus {
            background: rgba(255,255,255,.09);
            border-color: var(--aqua);
            box-shadow: 0 0 0 .2rem rgba(47,230,200,.15);
            color: #fff;
        }
        .tp-body .form-label { color: rgba(255,255,255,.85); font-weight: 600; }

        /* ---------- Modal (tetap terang untuk keterbacaan form) ---------- */
        .modal-content { border-radius: 20px; border: none; overflow: hidden; color: var(--ink); }
        .modal-header { background: var(--ink); color: #fff; border-bottom: none; }
        .modal-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        .modal-body { color: #333; }
        .modal-body .form-label { color: var(--ink); font-weight: 600; }
        .modal-body .form-control, .modal-body .form-select {
            border: 1px solid var(--stone-2); border-radius: 10px; padding: 9px 14px; color: var(--ink); background: #fff;
        }
        .modal-body .form-control:focus { border-color: var(--aqua); box-shadow: 0 0 0 .2rem rgba(47,230,200,.15); }
        .modal-footer { border-top: 1px solid var(--stone-2) !important; }

        /* ---------- Pagination ---------- */
        .page-link { background: rgba(255,255,255,.05); border-color: rgba(255,255,255,.12); color: rgba(255,255,255,.75); }
        .page-link:hover { background: rgba(255,255,255,.12); color: #fff; }
        .page-item.active .page-link { background: var(--aqua); border-color: var(--aqua); color: var(--ink); }

        /* ---------- Elemen dashboard ---------- */
        .eyebrow {
            display: inline-flex; align-items: center; gap: 8px;
            font-family: 'IBM Plex Mono', monospace; font-size: .68rem;
            letter-spacing: 2px; text-transform: uppercase; color: var(--aqua);
        }
        .eyebrow::before { content: ""; width: 18px; height: 1px; background: var(--aqua); display: inline-block; }
        .eyebrow.violet { color: var(--violet); }
        .eyebrow.violet::before { background: var(--violet); }

        .stat-tile {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.1);
            border-radius: 18px;
            padding: 24px 22px;
            height: 100%;
            position: relative;
            overflow: hidden;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
        }
        .stat-tile:hover { transform: translateY(-4px); box-shadow: 0 18px 40px rgba(0,0,0,.3); border-color: var(--tile-accent, var(--aqua)); }
        .stat-tile::after {
            content: ""; position: absolute; right: -28px; top: -28px; width: 90px; height: 90px; border-radius: 50%;
            background: radial-gradient(circle, var(--tile-accent, var(--aqua)) 0%, transparent 70%); opacity: .18;
        }
        .stat-tile .stat-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem; background: rgba(255,255,255,.08);
            color: var(--tile-accent, var(--aqua)); margin-bottom: 16px; position: relative; z-index: 1;
        }
        .stat-tile .stat-num { font-family: 'IBM Plex Mono', monospace; font-weight: 600; font-size: 1.75rem; color: #fff; line-height: 1; position: relative; z-index: 1; }
        .stat-tile .stat-label { color: rgba(255,255,255,.55); font-size: .8rem; margin-top: 6px; display: block; position: relative; z-index: 1; }
        .stat-tile.accent-aqua { --tile-accent: var(--aqua); }
        .stat-tile.accent-violet { --tile-accent: var(--violet); }
        .stat-tile.accent-brass { --tile-accent: var(--brass); }
        .stat-tile.accent-aquadim { --tile-accent: var(--aqua-dim); }

        .lane-row { display: flex; align-items: center; gap: 16px; padding: 13px 4px; border-bottom: 1px solid rgba(255,255,255,.08); }
        .lane-row:last-child { border-bottom: none; }
        .lane-row .lane-name { font-weight: 700; width: 140px; flex-shrink: 0; color: #fff; font-size: .87rem; }
        .lane-row .lane-track { flex: 1; height: 8px; border-radius: 4px; background: rgba(255,255,255,.1); overflow: hidden; }
        .lane-row .lane-fill { height: 100%; border-radius: 4px; }
        .lane-fill.ok { background: var(--aqua-dim); }
        .lane-fill.warn { background: var(--brass); }
        .lane-fill.full { background: var(--danger); }
        .lane-row .lane-num { font-size: .74rem; color: rgba(255,255,255,.5); width: 100px; text-align: right; flex-shrink: 0; font-family: 'IBM Plex Mono', monospace; }
    </style>
</head>
<body>
<?php if (function_exists('tampilkanNotifikasiLogin')) tampilkanNotifikasiLogin(); ?>

<div class="app-wrapper">
    <?php include __DIR__ . '/sidebar_admin.php'; ?>

    <div class="main-content">
        <div class="tp-topbar">
            <div class="d-flex align-items-center gap-3">
                <button id="sidebarToggle" class="btn btn-sm btn-light d-lg-none"><i class="bi bi-list"></i></button>
                <h1 class="page-title fs-4"><?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard' ?></h1>
            </div>

            <!-- Foto profil di header, dengan nama & role di sampingnya -->
            <div class="dropdown">
                <div class="user-chip" role="button" data-bs-toggle="dropdown">
                    <div class="avatar-mini">
                        <?php if ($fotoAdmin): ?>
                            <img src="<?= BASE_URL ?>uploads/profil/<?= htmlspecialchars($fotoAdmin) ?>"
                                 alt="Foto Profil"
                                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <?php endif; ?>
                        <span class="avatar-fallback" style="<?= $fotoAdmin ? 'display:none;' : 'display:flex;' ?>">
                            <?= strtoupper(substr($namaTopbar, 0, 1)) ?>
                        </span>
                    </div>
                    <div class="user-chip-text">
                        <strong><?= htmlspecialchars($namaTopbar) ?></strong>
                        <small><?= htmlspecialchars($roleTopbar) ?></small>
                    </div>
                    <i class="bi bi-chevron-down small text-muted"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end mt-2">
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>admin/edit_profil.php"><i class="bi bi-person-circle me-2"></i>Edit Profil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                </ul>
            </div>
        </div>

        <div class="tp-body">
            <?php if (isset($_GET['sukses'])): ?>
                <?php
                    $aksiSuara = $_GET['aksi'] ?? '';
                    $jenisSuaraValid = in_array($aksiSuara, ['tambah', 'ubah', 'hapus'], true) ? $aksiSuara : 'ubah';
                ?>
                <div class="alert alert-success alert-auto-dismiss mb-3" data-sound="<?= $jenisSuaraValid ?>"><i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($_GET['sukses']) ?></div>
            <?php endif; ?>
            <?php if (isset($_GET['gagal'])): ?>
                <div class="alert alert-danger alert-auto-dismiss mb-3"><i class="bi bi-x-circle me-1"></i> <?= htmlspecialchars($_GET['gagal']) ?></div>
            <?php endif; ?>