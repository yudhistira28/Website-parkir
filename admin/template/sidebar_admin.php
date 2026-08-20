<?php
/**
 * sidebar_admin.php
 * SATU-SATUNYA sumber kebenaran untuk layout .app-wrapper / .main-content /
 * lebar & offset sidebar. Rule sejenis di style.css SUDAH DIHAPUS supaya
 * tidak ada lagi dua file yang sama-sama pasang !important untuk margin-left
 * / width lalu menang-kalah tergantung urutan <link>/<style> — itu penyebab
 * konten "kepotong" & grafik salah ukur di load pertama kemarin.
 *
 * Disamakan dengan navbar.php (operator): kartu sidebar mengambang, off-canvas
 * di mobile (backdrop + tombol X), dan mode collapse icon-only di desktop
 * (tombol panah, status tersimpan di localStorage). Warna tetap tema Tirta
 * Tamansari (navy gelap + aksen teal/violet/emas).
 */
$current = basename($_SERVER['PHP_SELF']);

// Ambil foto profil terbaru dari database
$fotoAdmin = null;
if (isset($_SESSION['id_user'])) {
    $stmtFotoAdmin = $koneksi->prepare("SELECT foto FROM tb_user WHERE id_user = ?");
    $stmtFotoAdmin->execute([$_SESSION['id_user']]);
    $rowFotoAdmin = $stmtFotoAdmin->fetch();
    $fotoAdmin = $rowFotoAdmin['foto'] ?? null;
}

$namaSidebar = !empty($_SESSION['nama_lengkap']) ? $_SESSION['nama_lengkap'] : ($_SESSION['username'] ?? 'User');
$roleSidebar = !empty($_SESSION['role']) ? ucfirst($_SESSION['role']) : 'Admin';

// Hitung jumlah pengajuan aktivasi yang masih menunggu, untuk badge di sidebar
$jumlahPengajuanMenunggu = 0;
try {
    $jumlahPengajuanMenunggu = (int) $koneksi->query(
        "SELECT COUNT(*) c FROM tb_pengajuan_aktivasi WHERE status = 'menunggu'"
    )->fetch()['c'];
} catch (Exception $e) {
    // Tabel mungkin belum dibuat; abaikan agar sidebar tetap tampil normal
    $jumlahPengajuanMenunggu = 0;
}
?>

<!-- Import Font & Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

<!-- Anti-flicker: baca preferensi collapse SEBELUM sidebar/body dirender.
     Ditaruh sedini mungkin (sebelum tag <body> & elemen sidebar dicetak)
     supaya class .sidebar-collapsed / .collapsed sudah menempel di HTML
     sejak awal — bukan ditambahkan belakangan oleh script di bawah.
     Inilah yang menghilangkan efek "kedip" sidebar lebar dulu baru
     menyempit saat pindah halaman. Skrip di bagian bawah file tetap ada
     untuk menangani KLIK tombol collapse (toggle real-time). -->
<script>
(function () {
    try {
        if (window.innerWidth >= 992 && localStorage.getItem('tirtaSidebarCollapsed') === '1') {
            document.documentElement.classList.add('tirta-pre-collapsed');
        }
    } catch (e) {}
})();
</script>

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
}

/* ====================================================================
   LAYOUT UTAMA — satu-satunya definisi lebar/offset sidebar & konten.
   Nilai sidebar: left:10px + width:264px => tepi kanan sidebar ada di
   274px. body diberi margin-left 284px (274px + 10px jarak nafas).
   ==================================================================== */
:root {
    --tirta-sidebar-width: 264px;
    --tirta-sidebar-gap: 10px;
    --tirta-sidebar-offset: 284px; /* left + width + gap */
}

body {
    background: #05070f !important;
    min-height: 100vh !important;
    margin-left: var(--tirta-sidebar-offset) !important;
    transition: margin-left 0.25s ease !important;
}
body.sidebar-collapsed { margin-left: 104px !important; }

/* State collapsed yang diterapkan sejak render pertama (lihat script
   anti-flicker di atas). Selector via html.tirta-pre-collapsed supaya
   tidak menunggu class body/sidebar ditambahkan oleh script bawah —
   sidebar & body langsung "lahir" dalam keadaan sempit, tidak pernah
   sempat terlihat lebar. Begitu script bawah jalan, class collapsed asli
   ditambahkan dan hasilnya identik, jadi tidak ada lompatan tampilan. */
html.tirta-pre-collapsed body { margin-left: 104px !important; }
html.tirta-pre-collapsed .tirta-sidebar { width: 84px !important; }
html.tirta-pre-collapsed .tirta-sidebar .brand { justify-content: center !important; padding: 18px 0 !important; }
html.tirta-pre-collapsed .tirta-sidebar .logo-box { margin-right: 0 !important; }
html.tirta-pre-collapsed .tirta-sidebar .brand-text,
html.tirta-pre-collapsed .tirta-sidebar .sidebar-profile-info,
html.tirta-pre-collapsed .tirta-sidebar .nav-section,
html.tirta-pre-collapsed .tirta-sidebar .nav-link span:not(.icon-box),
html.tirta-pre-collapsed .tirta-sidebar .nav-count {
    display: none !important;
}
html.tirta-pre-collapsed .tirta-sidebar .sidebar-profile {
    justify-content: center !important;
    padding: 10px !important;
    gap: 0 !important;
}
html.tirta-pre-collapsed .tirta-sidebar .nav-link {
    justify-content: center !important;
    padding: 12px !important;
    margin: 6px 16px !important;
    gap: 0 !important;
}
html.tirta-pre-collapsed .tirta-sidebar .nav-link .icon-box {
    width: 34px !important;
    height: 34px !important;
}
html.tirta-pre-collapsed .tirta-sidebar .nav-link .icon-box i { font-size: 17px !important; }

.app-wrapper {
    display: block !important;
    min-height: auto !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
}

/* Hanya .main-content yang di-reset lebarnya — TIDAK menyasar semua
   direct child .app-wrapper lagi, supaya .tirta-sidebar (yang juga anak
   .app-wrapper) tidak pernah kena override width:100% secara tidak
   sengaja walau urutan CSS berubah di kemudian hari. */
.app-wrapper > .main-content {
    margin-left: 0 !important;
    padding-left: 0 !important;
    max-width: 100% !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

/* Reset umum jaga-jaga kalau masih ada wrapper lain yang juga menyimpan
   offset peninggalan sidebar lama. CATATAN PENTING: .tp-body & .tp-topbar
   SENGAJA TIDAK dipaksa padding-left:0 di sini — keduanya punya padding
   desain asli (jarak judul/kartu dari tepi) yang didefinisikan di
   header.php (.tp-body { padding: 1.25rem 1.25rem !important; }). Kalau
   ikut di-nolkan di sini, karena style block ini dimuat belakangan di
   DOM, padding-left:0 !important ini akan MENANG dan menghapus jarak
   tadi — itu yang bikin judul "Dashboard" & kartu nempel rapat ke tepi
   kiri (nggak selaras dengan dashboard Petugas). Reset margin-left &
   max-width tetap perlu untuk keduanya (itu soal offset sidebar), tapi
   padding-left biarkan warisan dari header.php. */
.tp-body,
.tp-topbar {
    margin-left: 0 !important;
    max-width: 100% !important;
    width: 100% !important;
    box-sizing: border-box !important;
}
.content,
.content-wrapper,
main,
.wrapper {
    margin-left: 0 !important;
    padding-left: 0 !important;
    max-width: 100% !important;
    width: 100% !important;
    box-sizing: border-box !important;
}
.tp-body .container,
.tp-body .container-fluid,
.app-wrapper .container,
.app-wrapper .container-fluid {
    max-width: 100% !important;
}

/* Kartu sidebar mengambang — sama seperti navbar.php operator */
.tirta-sidebar {
    width: var(--tirta-sidebar-width) !important;
    flex-shrink: 0 !important;
    height: calc(100vh - 20px) !important;
    background: linear-gradient(165deg, #131f45 0%, #0d1730 100%) !important;
    position: fixed !important;
    left: var(--tirta-sidebar-gap) !important;
    top: 10px !important;
    overflow: visible !important;
    z-index: 999 !important;
    display: flex !important;
    flex-direction: column !important;
    padding: 0 !important;
    margin: 0 !important;
    border-radius: 26px !important;
    box-shadow:
        0 24px 60px rgba(0, 0, 0, 0.6),
        0 4px 14px rgba(0, 0, 0, 0.35),
        inset 1px 1px 0 rgba(255, 255, 255, 0.06),
        inset -1px -1px 0 rgba(0, 0, 0, 0.35) !important;
    transition: width 0.28s ease !important;
}
.tirta-sidebar-scroll {
    flex: 1 1 auto !important;
    display: flex !important;
    flex-direction: column !important;
    overflow-y: auto !important;
    overflow-x: hidden !important;
    border-radius: 22px !important;
}
.tirta-sidebar-scroll::-webkit-scrollbar { width: 4px !important; }
.tirta-sidebar-scroll::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.12) !important;
    border-radius: 4px !important;
}

/* ==== Mode collapse (icon-only) — sama seperti navbar.php operator ==== */
.tirta-sidebar.collapsed { width: 84px !important; }

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
    right: -13px !important;
    width: 27px !important;
    height: 27px !important;
    border-radius: 50% !important;
    border: 2px solid #05070f !important;
    background: linear-gradient(150deg, #1c2c58, #101b3d) !important;
    color: #2fe6c8 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    cursor: pointer !important;
    z-index: 5 !important;
    box-shadow:
        0 4px 10px rgba(0, 0, 0, 0.45),
        inset 1px 1px 1px rgba(255, 255, 255, 0.12),
        inset -1px -1px 1px rgba(0, 0, 0, 0.4) !important;
    transition: transform 0.25s ease, box-shadow 0.2s ease !important;
}
.sidebar-collapse-btn:hover {
    box-shadow:
        0 5px 14px rgba(47, 230, 200, 0.25),
        inset 1px 1px 1px rgba(255, 255, 255, 0.15),
        inset -1px -1px 1px rgba(0, 0, 0, 0.4) !important;
}
.sidebar-collapse-btn i { font-size: 12px !important; transition: transform 0.25s ease !important; }
.tirta-sidebar.collapsed .sidebar-collapse-btn i { transform: rotate(180deg) !important; }
@media (max-width: 991.98px) {
    .sidebar-collapse-btn { display: none !important; }
}

/* Sidebar off-canvas di layar kecil — backdrop + tombol X, sama seperti navbar.php operator.
   Kalau halaman admin punya tombol hamburger dengan id="sidebarToggle" di topbar-nya,
   itu otomatis akan memicu buka sidebar ini. */
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
    html, body {
        margin-left: 0 !important;
        max-width: 100vw !important;
        overflow-x: hidden !important;
    }
    body.sidebar-collapsed { margin-left: 0 !important; }
    .tirta-sidebar {
        left: 0 !important;
        top: 0 !important;
        height: 100vh !important;
        width: 260px !important;
        max-width: 82vw !important;
        border-radius: 0 !important;
        transform: translateX(-105%) !important;
        transition: transform 0.28s ease !important;
        z-index: 1050 !important;
    }
    .tirta-sidebar.show { transform: translateX(0) !important; }
    .sidebar-close-btn { display: flex !important; }

    /* Safety net: konten (bukan sidebar/backdrop) dipaksa selebar viewport
       dan tidak boleh menyusut akibat flex/grid parent — ini yang bikin
       angka/grafik/tabel "kepotong" kalau sampai ada elemen leluhur yang
       masih menyisakan lebar sempit. Target EKSPLISIT (bukan lagi
       ".app-wrapper > *") supaya tidak ikut menimpa .tirta-sidebar
       (width:260px di atas) atau .sidebar-backdrop — kalau pakai selector
       wildcard, rule ini datang belakangan di source order dan akan
       menang, jadi sidebar ikut kepaksa width:100% padahal off-canvas-nya
       harusnya tetap 260px. */
    .app-wrapper,
    .main-content,
    .tp-topbar,
    .tp-body {
        margin-left: 0 !important;
        width: 100% !important;
        max-width: 100vw !important;
        min-width: 0 !important;
    }
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
    min-width: 40px !important;
    min-height: 40px !important;
    max-width: 40px !important;
    max-height: 40px !important;
    border-radius: 50% !important;
    overflow: hidden !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    margin-right: 12px !important;
    flex-shrink: 0 !important;
    background-color: #0e1830 !important;
    position: relative !important;
}
.tirta-sidebar .logo-box img {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    object-position: center !important;
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

/* Profile card — permukaan "menekan" (pressed) ke dalam, senada dengan navbar.php operator */
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
    min-width: 40px !important;
    min-height: 40px !important;
    max-width: 40px !important;
    max-height: 40px !important;
    flex-shrink: 0 !important;
    border-radius: 50% !important;
    overflow: hidden !important;
    background: #0e1830 !important;
    position: relative !important;
}
.tirta-sidebar .avatar-wrap img {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    border-radius: 50% !important;
    object-fit: cover !important;
    object-position: center !important;
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

/* Nav item — item non-aktif menyatu polos dengan permukaan sidebar, item aktif jadi
   pill solid dengan efek timbul (neomorphic), ikon aktif dalam lingkaran putih kecil.
   Persis pola navbar.php operator. */
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
    white-space: nowrap !important;
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
        inset 2px 2px 5px rgba(0, 0, 0, 0.45),
        inset -2px -2px 5px rgba(255, 255, 255, 0.03) !important;
}
.tirta-sidebar .nav-link:hover .icon-box i { color: rgba(255, 255, 255, 0.92) !important; }

.tirta-sidebar .nav-link.active {
    background: linear-gradient(135deg, #2fe6c8 0%, #8b7cfa 100%) !important;
    color: #070b1a !important;
    box-shadow:
        inset 0 2px 2px rgba(255, 255, 255, 0.4),
        inset 0 -3px 5px rgba(7, 11, 26, 0.3),
        0 0 0 3px rgba(47, 230, 200, 0.1),
        0 12px 26px rgba(47, 230, 200, 0.4) !important;
}
.tirta-sidebar .nav-link.active .icon-box {
    background: rgba(255, 255, 255, 0.92) !important;
    box-shadow: 0 2px 4px rgba(7, 11, 26, 0.25) !important;
}
.tirta-sidebar .nav-link.active .icon-box i { color: #2fa896 !important; }

/* Badge count — dipakai di "Pengajuan Aktivasi" (jumlah menunggu) */
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
/* Saat collapsed, badge tampil sebagai titik kecil di pojok icon */
.tirta-sidebar.collapsed .nav-link .nav-count {
    display: block !important;
    position: absolute !important;
    top: 4px !important;
    right: 14px !important;
    min-width: 9px !important;
    width: 9px !important;
    height: 9px !important;
    padding: 0 !important;
    font-size: 0 !important;
}
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

    <!-- Profile card -->
    <a href="<?= BASE_URL ?>admin/edit_profil.php" class="sidebar-profile">
        <div class="avatar-wrap">
            <?php if ($fotoAdmin): ?>
                <img src="<?= BASE_URL ?>uploads/profil/<?= htmlspecialchars($fotoAdmin) ?>"
                     alt="Foto Profil"
                     onerror="this.style.display='none';">
            <?php endif; ?>
        </div>
        <div class="sidebar-profile-info">
            <strong><?= htmlspecialchars($namaSidebar) ?></strong>
            <span class="role-badge"><?= htmlspecialchars($roleSidebar) ?></span>
        </div>
    </a>

    <div class="nav-section">Menu Utama</div>
    <a href="<?= BASE_URL ?>admin/index.php" class="nav-link <?= $current === 'index.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-speedometer2"></i></span> <span>Dashboard</span>
    </a>

    <div class="nav-section">Master Data</div>
    <a href="<?= BASE_URL ?>admin/kelola_user.php" class="nav-link <?= $current === 'kelola_user.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-people"></i></span> <span>Kelola User</span>
    </a>
    <a href="<?= BASE_URL ?>admin/pengajuan_aktivasi.php" class="nav-link <?= $current === 'pengajuan_aktivasi.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-life-preserver"></i></span> <span>Pengajuan Aktivasi</span>
        <?php if ($jumlahPengajuanMenunggu > 0): ?><span class="nav-count"><?= $jumlahPengajuanMenunggu ?></span><?php endif; ?>
    </a>
    <a href="<?= BASE_URL ?>admin/kelola_tarif.php" class="nav-link <?= $current === 'kelola_tarif.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-cash-coin"></i></span> <span>Tarif Parkir</span>
    </a>
    <a href="<?= BASE_URL ?>admin/kelola_area.php" class="nav-link <?= $current === 'kelola_area.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-p-square"></i></span> <span>Area Parkir</span>
    </a>
    <a href="<?= BASE_URL ?>admin/kelola_kendaraan.php" class="nav-link <?= $current === 'kelola_kendaraan.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-car-front"></i></span> <span>Data Kendaraan</span>
    </a>

    <div class="nav-section">Monitoring</div>
    <a href="<?= BASE_URL ?>admin/log_aktivitas.php" class="nav-link <?= $current === 'log_aktivitas.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-clock-history"></i></span> <span>Log Aktivitas</span>
    </a>
    <a href="<?= BASE_URL ?>admin/testimoni.php" class="nav-link <?= $current === 'testimoni.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-chat-left-text"></i></span> <span>Testimoni</span>
    </a>

    <div class="nav-section">Akun</div>
    <a href="<?= BASE_URL ?>admin/edit_profil.php" class="nav-link <?= $current === 'edit_profil.php' ? 'active' : '' ?>">
        <span class="icon-box"><i class="bi bi-person-gear"></i></span> <span>Edit Profil</span>
    </a>
    <a href="<?= BASE_URL ?>auth/logout.php" class="nav-link">
        <span class="icon-box"><i class="bi bi-box-arrow-right"></i></span> <span>Logout</span>
    </a>
    </div>
</div>

<script>
(function () {
    var sidebar     = document.getElementById('tirtaSidebar');
    var backdrop     = document.getElementById('sidebarBackdrop');
    var openBtn      = document.getElementById('sidebarToggle'); // tombol hamburger di topbar admin, kalau ada
    var closeBtn     = document.getElementById('sidebarCloseBtn');
    var collapseBtn  = document.getElementById('sidebarCollapseBtn');
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

    // ==== Mode collapse (icon-only) — hanya desktop, tersimpan di localStorage ====
    // Memakai key yang sama dengan navbar.php operator supaya preferensinya konsisten
    // untuk user yang sama di kedua panel.
    if (collapseBtn) {
        var sudahPreCollapsed = document.documentElement.classList.contains('tirta-pre-collapsed');
        if (window.innerWidth >= 992 && localStorage.getItem('tirtaSidebarCollapsed') === '1') {
            // Matikan transisi width sesaat saat memasang class asli, supaya
            // perpindahan dari state "tirta-pre-collapsed" (CSS murni, sudah
            // sempit sejak awal) ke state "collapsed" (yang punya transition)
            // tidak sempat memicu animasi melebar-lalu-menyempit.
            if (sudahPreCollapsed) sidebar.style.transition = 'none';
            sidebar.classList.add('collapsed');
            document.body.classList.add('sidebar-collapsed');
            if (sudahPreCollapsed) {
                // Paksa reflow lalu kembalikan transisi normal untuk toggle berikutnya
                void sidebar.offsetWidth;
                sidebar.style.transition = '';
            }
        }
        // Class sementara sudah tidak diperlukan lagi setelah class asli terpasang
        document.documentElement.classList.remove('tirta-pre-collapsed');

        collapseBtn.addEventListener('click', function () {
            var kini = sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('sidebar-collapsed', kini);
            localStorage.setItem('tirtaSidebarCollapsed', kini ? '1' : '0');
        });
    } else {
        document.documentElement.classList.remove('tirta-pre-collapsed');
    }
})();
</script>