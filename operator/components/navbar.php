<?php
$current = basename($_SERVER['PHP_SELF']);

// Badge jumlah kendaraan yang sedang parkir (dipakai di item "Kendaraan Masuk", seperti badge count di sidebar referensi)
$tirtaJumlahMasuk = 0;
if (isset($koneksi)) {
    try {
        $tirtaJumlahMasuk = (int) $koneksi->query("SELECT COUNT(*) c FROM tb_transaksi WHERE status='masuk'")->fetch()['c'];
    } catch (Throwable $e) {
        $tirtaJumlahMasuk = 0;
    }
}
?>

<!-- Import Font & Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
.tirta-sidebar, .tirta-sidebar * {
    box-sizing: border-box !important;
    font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
}
.tirta-sidebar .brand-text strong,
.tirta-sidebar .sidebar-profile-info strong {
    font-family: 'Space Grotesk', sans-serif !important;
}

html {
    background: #05070f !important;
    overflow-x: hidden !important;
}

body {
    background: #05070f !important;
    min-height: 100vh !important;
    overflow-x: hidden !important;
}

.tirta-sidebar {
    width: 264px !important;
    flex-shrink: 0 !important;
    height: calc(100vh - 20px) !important;
    background: #101b3d !important;
    position: fixed !important;
    left: 10px !important;
    top: 10px !important;
    overflow: visible !important;
    z-index: 999 !important;
    display: flex !important;
    flex-direction: column !important;
    padding: 0 !important;
    margin: 0 !important;
    border-radius: 22px !important;
    box-shadow:
        0 20px 45px rgba(0, 0, 0, 0.55),
        inset 1px 1px 0 rgba(255, 255, 255, 0.04),
        inset -1px -1px 0 rgba(0, 0, 0, 0.3) !important;
}
.tirta-sidebar-scroll {
    flex: 1 1 auto !important;
    display: flex !important;
    flex-direction: column !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    border-radius: 22px !important;
}

.tirta-content {
    margin-left: 284px !important;
    min-width: 0 !important;
    overflow-x: hidden !important;
    transition: margin-left 0.25s ease !important;
}
.main-content {
    margin-left: 284px !important;
    min-width: 0 !important;
    overflow-x: hidden !important;
}

/* ==== Mode collapse (icon-only) — rel sempit dengan tombol aktif berbentuk
   squircle bercahaya, seperti referensi. Hanya berlaku di layar desktop. ==== */
.tirta-sidebar.collapsed { width: 84px !important; }
.tirta-sidebar.collapsed ~ .tirta-content { margin-left: 104px !important; }

.tirta-sidebar.collapsed .brand { justify-content: center !important; padding: 18px 0 !important; }
.tirta-sidebar.collapsed .logo-box { margin-right: 0 !important; }
.tirta-sidebar.collapsed .brand-text,
.tirta-sidebar.collapsed .sidebar-profile-info,
.tirta-sidebar.collapsed .nav-section,
.tirta-sidebar.collapsed .nav-link span:not(.icon-box),
.tirta-sidebar.collapsed .nav-count {
    display: none !important;
}
.tirta-sidebar.collapsed .sidebar-profile {
    justify-content: center !important;
    padding: 10px !important;
    gap: 0 !important;
}
.tirta-sidebar.collapsed .nav-link {
    justify-content: center !important;
    padding: 12px !important;
    margin: 6px 16px !important;
    gap: 0 !important;
}
.tirta-sidebar.collapsed .nav-link .icon-box {
    width: 34px !important;
    height: 34px !important;
}
.tirta-sidebar.collapsed .nav-link .icon-box i { font-size: 17px !important; }
.tirta-sidebar.collapsed .nav-link.active {
    border-radius: 18px !important;
    box-shadow:
        inset 0 2px 2px rgba(255, 255, 255, 0.35),
        inset 0 -3px 5px rgba(7, 11, 26, 0.3),
        0 0 0 4px rgba(47, 230, 200, 0.14),
        0 8px 22px rgba(47, 230, 200, 0.45) !important;
}
.tirta-sidebar.collapsed .nav-link.active .icon-box { background: transparent !important; }
.tirta-sidebar.collapsed .nav-link.active .icon-box i { color: #070b1a !important; }

/* Tombol lipat/buka — bulatan kecil menempel di tepi kanan sidebar */
.sidebar-collapse-btn {
    position: absolute !important;
    top: 84px !important;
    right: -12px !important;
    width: 26px !important;
    height: 26px !important;
    border-radius: 50% !important;
    border: 2px solid #05070f !important;
    background: #16224a !important;
    color: #2fe6c8 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    z-index: 5 !important;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.4) !important;
    transition: transform 0.25s ease !important;
}
.sidebar-collapse-btn i { font-size: 12px !important; transition: transform 0.25s ease !important; }
.tirta-sidebar.collapsed .sidebar-collapse-btn i { transform: rotate(180deg) !important; }
@media (max-width: 991.98px) {
    .sidebar-collapse-btn { display: none !important; }
}

/* Sidebar off-canvas di layar kecil — sesuai breakpoint tombol toggle (d-lg-none = < 992px).
   Default: disembunyikan di luar layar; class .show (ditambah lewat JS) menggesernya masuk. */
.sidebar-backdrop {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    z-index: 1040;
}
.sidebar-backdrop.show { display: block; }

.sidebar-close-btn {
    display: none;
    width: 30px !important;
    height: 30px !important;
    border-radius: 50% !important;
    border: none !important;
    background: rgba(255, 255, 255, 0.08) !important;
    color: #fff !important;
    align-items: center !important;
    justify-content: center !important;
    margin-left: auto !important;
    flex-shrink: 0 !important;
}

@media (max-width: 991.98px) {
    .tirta-sidebar {
        left: 0 !important;
        top: 0 !important;
        height: 100vh !important;
        width: 260px !important;
        border-radius: 0 !important;
        transform: translateX(-105%) !important;
        transition: transform 0.28s ease !important;
        z-index: 1050 !important;
    }
    .tirta-sidebar.show { transform: translateX(0) !important; }
    .main-content { margin-left: 0 !important; }
    .sidebar-close-btn { display: flex !important; }
}

/* Brand */
.tirta-sidebar .brand {
    display: flex !important;
    align-items: center !important;
    padding: 18px 18px !important;
}
.tirta-sidebar .logo-box {
    width: 40px !important;
    height: 40px !important;
    border-radius: 50% !important;
    overflow: hidden !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin-right: 12px !important;
    flex-shrink: 0 !important;
    background-color: #0e1830 !important;
}
.tirta-sidebar .logo-box img {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
}
.tirta-sidebar .logo-box .logo-fallback {
    width: 100% !important;
    height: 100% !important;
    background: linear-gradient(135deg, #2fe6c8, #8b7cfa) !important;
    color: #ffffff !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    font-weight: 700 !important;
    font-size: 15px !important;
}
.tirta-sidebar .brand-text { display: flex !important; flex-direction: column !important; }
.tirta-sidebar .brand-text strong {
    color: #ffffff !important;
    font-size: 13px !important;
    font-weight: 700 !important;
    letter-spacing: 0.5px !important;
    line-height: 1.2 !important;
}
.tirta-sidebar .brand-text small {
    color: #f2b84b !important;
    font-size: 10px !important;
    font-weight: 600 !important;
    letter-spacing: 0.8px !important;
    margin-top: 2px !important;
}

/* Profile card (foto + nama + role, seperti referensi) — permukaan "menekan" (pressed) ke dalam,
   senada dengan surface sidebar (bukan kartu terpisah yang menonjol) */
.tirta-sidebar .sidebar-profile {
    display: flex !important;
    align-items: center !important;
    gap: 12px !important;
    padding: 12px 16px !important;
    margin: 8px 12px 6px 12px !important;
    border-radius: 16px !important;
    background: transparent !important;
    box-shadow:
        inset 2px 2px 5px rgba(0, 0, 0, 0.4),
        inset -2px -2px 5px rgba(255, 255, 255, 0.03) !important;
    text-decoration: none !important;
    transition: background 0.2s !important;
}
.tirta-sidebar .sidebar-profile:hover { background: rgba(255, 255, 255, 0.03) !important; }
.tirta-sidebar .avatar-wrap {
    width: 40px !important;
    height: 40px !important;
    flex-shrink: 0 !important;
    border-radius: 50% !important;
    overflow: hidden !important;
    background: #0e1830 !important;
}
.tirta-sidebar .avatar-wrap img {
    width: 100% !important;
    height: 100% !important;
    border-radius: 50% !important;
    object-fit: cover !important;
}
.tirta-sidebar .sidebar-profile-info { display: flex !important; flex-direction: column !important; overflow: hidden !important; }
.tirta-sidebar .sidebar-profile-info strong {
    color: #ffffff !important;
    font-size: 14px !important;
    font-weight: 700 !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}
.tirta-sidebar .role-badge {
    color: #2fe6c8 !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    margin-top: 2px !important;
}

/* Nav section label — gaya "MENU"/"DISCOVERY" dari referensi neomorphic */
.tirta-sidebar .nav-section {
    color: rgba(255, 255, 255, 0.35) !important;
    font-size: 10px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 1.4px !important;
    padding: 16px 22px 8px 22px !important;
}
.tirta-sidebar .nav-section:first-of-type { padding-top: 6px !important; }

/* Nav item — meniru pola video: item non-aktif menyatu polos dengan permukaan sidebar
   (tanpa kotak/lingkaran ikon, hampir "tak terlihat"), item aktif jadi pill solid
   dengan efek timbul (neomorphic: highlight terang di atas, bayangan gelap di bawah,
   drop shadow di luar), dan ikon aktif ditaruh dalam lingkaran putih kecil seperti video */
.tirta-sidebar .nav-link {
    position: relative !important;
    display: flex !important;
    align-items: center !important;
    gap: 11px !important;
    padding: 9px 12px !important;
    margin: 2px 12px !important;
    color: rgba(255, 255, 255, 0.55) !important;
    text-decoration: none !important;
    font-size: 13.5px !important;
    font-weight: 600 !important;
    border-radius: 999px !important;
    background: transparent !important;
    box-shadow: none !important;
    transition: all 0.18s ease !important;
}
.tirta-sidebar .nav-link .icon-box {
    width: 26px !important;
    height: 26px !important;
    border-radius: 50% !important;
    background: transparent !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    flex-shrink: 0 !important;
    transition: all 0.18s ease !important;
}
.tirta-sidebar .nav-link .icon-box i {
    font-size: 15px !important;
    color: rgba(255, 255, 255, 0.45) !important;
}
.tirta-sidebar .nav-link span:not(.icon-box):not(.nav-count) {
    flex: 1 1 auto !important;
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
}

.tirta-sidebar .nav-link:hover {
    color: rgba(255, 255, 255, 0.92) !important;
    box-shadow:
        inset 2px 2px 4px rgba(0, 0, 0, 0.4),
        inset -2px -2px 4px rgba(255, 255, 255, 0.025) !important;
}
.tirta-sidebar .nav-link:hover .icon-box i { color: rgba(255, 255, 255, 0.92) !important; }

.tirta-sidebar .nav-link.active {
    background: linear-gradient(135deg, #2fe6c8 0%, #8b7cfa 100%) !important;
    color: #070b1a !important;
    box-shadow:
        inset 0 2px 2px rgba(255, 255, 255, 0.35),
        inset 0 -3px 5px rgba(7, 11, 26, 0.3),
        0 10px 22px rgba(47, 230, 200, 0.35) !important;
}
.tirta-sidebar .nav-link.active .icon-box {
    background: rgba(255, 255, 255, 0.92) !important;
    box-shadow: 0 2px 4px rgba(7, 11, 26, 0.25) !important;
}
.tirta-sidebar .nav-link.active .icon-box i { color: #2fa896 !important; }

/* Badge count di ujung item nav, seperti bulatan angka pada referensi */
.tirta-sidebar .nav-count {
    flex-shrink: 0 !important;
    min-width: 19px !important;
    height: 19px !important;
    padding: 0 5px !important;
    border-radius: 999px !important;
    background: #f2b84b !important;
    color: #070b1a !important;
    font-size: 10px !important;
    font-weight: 800 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow:
        inset 0 1px 1px rgba(255, 255, 255, 0.4),
        0 2px 5px rgba(0, 0, 0, 0.35) !important;
}
.tirta-sidebar .nav-link.active .nav-count {
    background: #070b1a !important;
    color: #f2b84b !important;
}

/* Topbar & body — disamakan dengan gaya "kaca gelap" navbar landing page,
   avatar disamakan ukurannya dengan header admin (36px, border 2px, ada fallback inisial) */
.tirta-content { display: flex; flex-direction: column; min-height: 100vh; }
.tirta-topbar {
    background: rgba(14, 24, 48, .72) !important;
    backdrop-filter: blur(16px);
    padding: 0.7rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 900;
}
.page-title { font-weight: 700; margin: 0; color: #ffffff !important; font-family: 'Space Grotesk', sans-serif; }
.tirta-body { padding: 1.25rem !important; background: #070b1a !important; flex: 1 1 auto !important; }

.user-chip {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 4px 10px 4px 4px;
    border-radius: 999px;
    background: rgba(255,255,255,.06);
    cursor: pointer;
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
    background: #0e1830;
    border: 2px solid #f2b84b;
}
.avatar-mini img { width: 100%; height: 100%; object-fit: cover; }
.avatar-fallback { color: #f2b84b; font-weight: 700; font-size: .85rem; }
.user-chip-text { display: flex; flex-direction: column; line-height: 1.2; text-align: left; }
.user-chip-text strong { font-size: .85rem; color: #ffffff; }
.user-chip-text small { font-size: .7rem; color: rgba(255,255,255,.55); }

.bell-btn {
    position: relative;
    width: 38px;
    height: 38px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,.12);
    background: rgba(255,255,255,.06);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}
.bell-btn:hover { background: rgba(255,255,255,.12); }
.bell-btn i { font-size: 1.05rem; color: #2fe6c8; }
.bell-badge {
    position: absolute;
    top: -2px;
    right: -2px;
    background: #d64545;
    color: #fff;
    font-size: .62rem;
    font-weight: 700;
    min-width: 16px;
    height: 16px;
    border-radius: 999px;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 0 3px;
}
.notif-dropdown {
    width: 320px;
    max-height: 360px;
    overflow-y: auto;
    padding: 0;
    background: #0e1830;
    border: 1px solid rgba(255,255,255,.12);
}
.notif-item {
    display: block;
    padding: 10px 14px;
    font-size: .78rem;
    color: #fff;
    border-bottom: 1px solid rgba(255,255,255,.08);
    white-space: normal;
}
.notif-item.belum-dibaca { background: rgba(47, 230, 200, 0.1); }
.notif-item .notif-waktu { display: block; font-size: .68rem; color: rgba(255,255,255,.5); margin-top: 2px; }
.notif-kosong { padding: 20px 14px; text-align: center; font-size: .8rem; color: rgba(255,255,255,.5); }
.dropdown-menu { background: #0e1830; border: 1px solid rgba(255,255,255,.12); }
.dropdown-item { color: rgba(255,255,255,.8); }
.dropdown-item:hover, .dropdown-item:focus { background: rgba(255,255,255,.08); color: #fff; }
.dropdown-divider { border-color: rgba(255,255,255,.1); }

/* Matikan dekorasi titik-titik putih pada stat-card (dari style.css, dibuat untuk
   tema admin yang terang) — di tema operator yang gelap dekorasi itu tampil sebagai
   bintik putih yang tidak cocok */
.stat-card::before { content: none !important; }
</style>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="tirta-sidebar" id="tirtaSidebar">
    <button type="button" class="sidebar-collapse-btn" id="sidebarCollapseBtn" aria-label="Lipat/buka menu">
        <i class="bi bi-chevron-left"></i>
    </button>
    <div class="tirta-sidebar-scroll">
    <div class="brand">
        <div class="logo-box">
            <img src="<?= BASE_URL ?>img/kolam.jpg" alt="Logo Tirta Tamansari"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <span class="logo-fallback">TT</span>
        </div>
        <div class="brand-text">
            <strong>TIRTA TAMANSARI</strong>
            <small>PARKING SYSTEM</small>
        </div>
        <button type="button" class="sidebar-close-btn" id="sidebarCloseBtn" aria-label="Tutup menu">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <!-- Profile card, mengikuti gaya referensi (foto/inisial + nama + role) -->
    <a href="<?= BASE_URL ?>operator/edit_profil.php" class="sidebar-profile">
        <div class="avatar-wrap">
            <?php if (!empty($_SESSION['foto'])): ?>
                <img src="<?= BASE_URL ?>uploads/profil/<?= htmlspecialchars($_SESSION['foto']) ?>"
                     alt="Foto Profil"
                     onerror="this.style.display='none';">
            <?php endif; ?>
        </div>
        <div class="sidebar-profile-info">
            <strong><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Petugas') ?></strong>
            <span class="role-badge">Petugas Parkir</span>
        </div>
    </a>

    <div class="nav-section">Menu Utama</div>
    <a href="<?= BASE_URL ?>operator/index.php" class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-speedometer2"></i></span> <span>Dashboard</span>
    </a>

    <div class="nav-section">Transaksi</div>
    <a href="<?= BASE_URL ?>operator/transaksi_masuk.php" class="nav-link <?= $current === 'transaksi_masuk.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-box-arrow-in-right"></i></span> <span>Kendaraan Masuk</span>
        <?php if ($tirtaJumlahMasuk > 0): ?><span class="nav-count"><?= $tirtaJumlahMasuk ?></span><?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>operator/transaksi_keluar.php" class="nav-link <?= $current === 'transaksi_keluar.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-box-arrow-right"></i></span> <span>Kendaraan Keluar</span>
    </a>
    <a href="<?= BASE_URL ?>operator/riwayat_transaksi.php" class="nav-link <?= $current === 'riwayat_transaksi.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-clock-history"></i></span> <span>Riwayat Transaksi</span>
    </a>

    <div class="nav-section">Akun</div>
    <a href="<?= BASE_URL ?>operator/edit_profil.php" class="nav-link <?= $current === 'edit_profil.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-person-circle"></i></span> <span>Edit Profil</span>
    </a>
    <a href="<?= BASE_URL ?>auth/logout.php" class="nav-link">
        <span class="icon-box"><i class="bi bi-box-arrow-right"></i></span> <span>Logout</span>
    </a>
    </div>
</div>

<script>
(function () {
    var sidebar  = document.getElementById('tirtaSidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    var openBtn  = document.getElementById('sidebarToggle');
    var closeBtn = document.getElementById('sidebarCloseBtn');
    var collapseBtn = document.getElementById('sidebarCollapseBtn');
    if (!sidebar || !backdrop) return;

    function bukaSidebar() {
        sidebar.classList.add('show');
        backdrop.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function tutupSidebar() {
        sidebar.classList.remove('show');
        backdrop.classList.remove('show');
        document.body.style.overflow = '';
    }

    if (openBtn) openBtn.addEventListener('click', bukaSidebar);
    if (closeBtn) closeBtn.addEventListener('click', tutupSidebar);
    backdrop.addEventListener('click', tutupSidebar);

    // Tutup otomatis saat salah satu menu dipilih (khusus layar kecil)
    sidebar.querySelectorAll('.nav-link, .sidebar-profile').forEach(function (a) {
        a.addEventListener('click', tutupSidebar);
    });

    // Tutup dengan tombol Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') tutupSidebar();
    });

    // Kalau layar dilebarkan balik ke desktop, pastikan state off-canvas direset
    window.addEventListener('resize', function () {
        if (window.innerWidth > 991.98) tutupSidebar();
    });

    // ==== Mode collapse (icon-only) — hanya untuk desktop, tersimpan di localStorage ====
    if (collapseBtn) {
        if (window.innerWidth >= 992 && localStorage.getItem('tirtaSidebarCollapsed') === '1') {
            sidebar.classList.add('collapsed');
        }
        collapseBtn.addEventListener('click', function () {
            var kini = sidebar.classList.toggle('collapsed');
            localStorage.setItem('tirtaSidebarCollapsed', kini ? '1' : '0');
        });
    }
})();
</script>

<div class="tirta-content">
    <div class="tirta-topbar">
        <div class="d-flex align-items-center gap-3">
            <button id="sidebarToggle" class="btn btn-sm btn-light d-lg-none"><i class="bi bi-list"></i></button>
            <h1 class="page-title"><?= isset($page_title) ? htmlspecialchars($page_title) : 'Dashboard' ?></h1>
        </div>
        <div class="d-flex align-items-center gap-2">
        <div class="dropdown">
            <button class="bell-btn" type="button" data-bs-toggle="dropdown" id="tombolLonceng" aria-label="Notifikasi">
                <i class="bi bi-bell"></i>
                <span class="bell-badge" id="bellBadge">0</span>
            </button>
            <div class="dropdown-menu dropdown-menu-end notif-dropdown mt-2" id="daftarNotifikasi">
                <div class="notif-kosong">Memuat notifikasi...</div>
            </div>
        </div>
        <div class="dropdown">
            <div class="user-chip" role="button" data-bs-toggle="dropdown">
                <div class="avatar-mini">
                    <?php $namaPetugasTopbar = $_SESSION['nama_lengkap'] ?? 'Petugas'; ?>
                    <?php if (!empty($_SESSION['foto'])): ?>
                        <img src="<?= BASE_URL ?>uploads/profil/<?= htmlspecialchars($_SESSION['foto']) ?>"
                             alt="Foto Profil"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <?php endif; ?>
                    <span class="avatar-fallback" style="<?= !empty($_SESSION['foto']) ? 'display:none;' : 'display:flex;' ?>">
                        <?= strtoupper(substr($namaPetugasTopbar, 0, 1)) ?>
                    </span>
                </div>
                <div class="user-chip-text">
                    <strong><?= htmlspecialchars($namaPetugasTopbar) ?></strong>
                    <small>Petugas Parkir</small>
                </div>
                <i class="bi bi-chevron-down small text-muted"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end mt-2">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>operator/edit_profil.php"><i class="bi bi-person-circle me-2"></i>Edit Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
        </div>
    </div>
    <div class="tirta-body">
        <?php if (isset($_GET['sukses'])): ?>
            <?php
                $aksiSuara = $_GET['aksi'] ?? '';
                $jenisSuaraValid = in_array($aksiSuara, ['tambah', 'ubah', 'hapus'], true) ? $aksiSuara : 'ubah';
            ?>
            <div class="alert alert-success alert-auto-dismiss" data-sound="<?= $jenisSuaraValid ?>"><i class="bi bi-check-circle me-1"></i> <?= htmlspecialchars($_GET['sukses']) ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['gagal'])): ?>
            <div class="alert alert-danger alert-auto-dismiss"><i class="bi bi-x-circle me-1"></i> <?= htmlspecialchars($_GET['gagal']) ?></div>
        <?php endif; ?>