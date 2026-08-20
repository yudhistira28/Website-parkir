<?php
$current = basename($_SERVER['PHP_SELF']);
$namaOwner = $_SESSION['nama_lengkap'] ?? 'Owner';
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

/* ==== Mode collapse (icon-only) ==== */
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

/* Tombol lipat/buka */
.sidebar-collapse-btn {
    position: absolute !important;
    top: 84px !important;
    right: -12px !important;
    width: 26px !important;
    height: 26px !important;
    border-radius: 50% !important;
    border: 2px solid #05070f !important;
    background: #0c1530 !important;
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

/* Sidebar off-canvas di layar kecil */
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
    .tirta-content { margin-left: 0 !important; }
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
    background-color: #0c1530 !important;
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
    color: #2fe6c8 !important;
    font-size: 10px !important;
    font-weight: 600 !important;
    letter-spacing: 0.8px !important;
    margin-top: 2px !important;
}

/* Profile card */
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
    background: #0c1530 !important;
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

/* Nav section label */
.tirta-sidebar .nav-section {
    color: rgba(255, 255, 255, 0.35) !important;
    font-size: 10px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 1.4px !important;
    padding: 16px 22px 8px 22px !important;
}
.tirta-sidebar .nav-section:first-of-type { padding-top: 6px !important; }

/* Nav item */
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
    background: linear-gradient(135deg, #2fe6c8 0%, #1ba893 100%) !important;
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

/* Topbar & body */
.tirta-content { display: flex; flex-direction: column; min-height: 100vh; }
.tirta-topbar {
    background: rgba(14, 24, 48, .72) !important;
    backdrop-filter: blur(16px);
    padding: 0.7rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,.09);
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 900;
}
.page-title { font-weight: 700; margin: 0; color: #ffffff !important; font-family: 'Space Grotesk', sans-serif; }
.tirta-body { padding: 1.25rem !important; background: #070b1a !important; flex: 1 1 auto !important; }

/* FIX: ikon notifikasi lonceng ditambahkan di topbar Owner supaya
   konsisten dengan topbar Petugas yang sudah lebih dulu punya ikon ini. */
.topbar-notif-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,.06);
    color: rgba(255,255,255,.75);
    border: none;
    font-size: 1rem;
    cursor: pointer;
    transition: background .2s, color .2s;
}
.topbar-notif-btn:hover {
    background: rgba(255,255,255,.12);
    color: #fff;
}

.user-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 3px 8px 3px 3px;
    border-radius: 999px;
    background: rgba(255,255,255,.06);
    cursor: pointer;
}
.user-chip:hover { background: rgba(255,255,255,.12); }
.avatar-mini {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0c1530;
    border: 1.5px solid #2fe6c8;
}
.avatar-mini img { width: 100%; height: 100%; object-fit: cover; }
.user-chip-text { display: flex; flex-direction: column; line-height: 1.2; text-align: left; }
.user-chip-text strong { font-size: .75rem; color: #ffffff; }
.user-chip-text small { font-size: .62rem; color: rgba(255,255,255,.55); }

.dropdown-menu { background: #0c1530; border: 1px solid rgba(255,255,255,.12); }
.dropdown-item { color: rgba(255,255,255,.8); }
.dropdown-item:hover, .dropdown-item:focus { background: rgba(255,255,255,.08); color: #fff; }
.dropdown-divider { border-color: rgba(255,255,255,.1); }
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

    <a href="<?= BASE_URL ?>owner/edit_profil.php" class="sidebar-profile">
        <div class="avatar-wrap">
            <?php if (!empty($_SESSION['foto'])): ?>
                <img src="<?= BASE_URL ?>uploads/profil/<?= htmlspecialchars($_SESSION['foto']) ?>"
                     alt="Foto Profil"
                     onerror="this.style.display='none';">
            <?php endif; ?>
        </div>
        <div class="sidebar-profile-info">
            <strong><?= htmlspecialchars($namaOwner) ?></strong>
            <span class="role-badge">Owner</span>
        </div>
    </a>

    <div class="nav-section">Owner</div>
    <a href="<?= BASE_URL ?>owner/index.php" class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-speedometer2"></i></span> <span>Dashboard</span>
    </a>
    <a href="<?= BASE_URL ?>owner/rekap_transaksi.php" class="nav-link <?= $current === 'rekap_transaksi.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-bar-chart-line"></i></span> <span>Rekap Transaksi</span>
    </a>

    <div class="nav-section">Akun</div>
    <a href="<?= BASE_URL ?>owner/edit_profil.php" class="nav-link <?= $current === 'edit_profil.php' ? 'active' : '' ?>">
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

    sidebar.querySelectorAll('.nav-link, .sidebar-profile').forEach(function (a) {
        a.addEventListener('click', tutupSidebar);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') tutupSidebar();
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 991.98) tutupSidebar();
    });

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
        <button type="button" class="topbar-notif-btn" aria-label="Notifikasi">
            <i class="bi bi-bell"></i>
        </button>
        <div class="dropdown">
            <div class="user-chip" role="button" data-bs-toggle="dropdown">
                <div class="avatar-mini">
                    <?php if (!empty($_SESSION['foto'])): ?>
                        <img src="<?= BASE_URL ?>uploads/profil/<?= htmlspecialchars($_SESSION['foto']) ?>"
                             alt="Foto Profil"
                             onerror="this.style.display='none';">
                    <?php endif; ?>
                </div>
                <div class="user-chip-text">
                    <strong><?= htmlspecialchars($namaOwner) ?></strong>
                    <small>Owner</small>
                </div>
                <i class="bi bi-chevron-down small text-muted"></i>
            </div>
            <ul class="dropdown-menu dropdown-menu-end mt-2">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>owner/edit_profil.php"><i class="bi bi-person-circle me-2"></i>Edit Profil</a></li>
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