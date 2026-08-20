<?php
/**
 * navbar_member.php
 * Navbar bersama untuk seluruh halaman member (index, booking, kendaraan, edit_profil).
 * Menyesuaikan persis dengan gaya kontainer melengkung (pill container) referensi.
 */
$currentNav = basename($_SERVER['PHP_SELF']);

if (!isset($fotoProfilNav)) {
    $fotoProfilNav = null;
    if (isset($_SESSION['id_user']) && isset($koneksi)) {
        $stmtFotoNav = $koneksi->prepare("SELECT foto FROM tb_user WHERE id_user = ?");
        $stmtFotoNav->execute([$_SESSION['id_user']]);
        $fotoProfilNav = $stmtFotoNav->fetch()['foto'] ?? null;
    }
}
?>
<style>
.tirta-header-wrapper {
    background-color: #0b0f19;
    padding: 15px 0;
    width: 100%;
}
.tirta-pill-container {
    background-color: #0e1422;
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 50rem;
    padding: 8px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
}
.tirta-brand {
    color: #ffffff;
    font-weight: 700;
    font-size: 15px;
    letter-spacing: 0.5px;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 10px;
}
.tirta-brand img {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
    background: #fff;
}
.tirta-brand span.title-main {
    color: #ffffff;
}
.tirta-brand span.title-sub {
    color: #2fe6c8;
}

.tirta-menu {
    display: flex;
    align-items: center;
    gap: 25px;
    list-style: none;
    margin: 0;
    padding: 0;
}
.tirta-menu a {
    color: rgba(255, 255, 255, 0.7);
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: color 0.2s;
    display: flex;
    align-items: center;
    gap: 6px;
}
.tirta-menu a:hover, 
.tirta-menu a.active {
    color: #ffffff;
    font-weight: 600;
}

.tirta-right-action {
    display: flex;
    align-items: center;
    gap: 15px;
}
.tirta-user-info {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #ffffff;
    font-size: 13px;
    font-weight: 500;
    text-decoration: none;
}
.tirta-user-info img, .tirta-user-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
}
.tirta-user-avatar {
    background: #8b7cfa;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: #fff;
}

/* Tombol Gradasi Khas Seperti Tombol Login pada Referensi */
.btn-gradient-pill {
    background: linear-gradient(135deg, #2fe6c8 0%, #4361ee 100%);
    border: none;
    color: #070b1a;
    font-weight: 600;
    font-size: 13px;
    padding: 7px 18px;
    border-radius: 50rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: opacity 0.2s;
}
.btn-gradient-pill:hover {
    opacity: 0.9;
    color: #070b1a;
}
.btn-danger-pill {
    background: rgba(220, 53, 69, 0.15);
    color: #ff6b6b;
    border: 1px solid rgba(220, 53, 69, 0.3);
    font-size: 13px;
    padding: 6px 14px;
    border-radius: 50rem;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s;
}
.btn-danger-pill:hover {
    background: #dc3545;
    color: #fff;
}

@media (max-width: 991px) {
    .tirta-menu { display: none; }
}
</style>

<div class="tirta-header-wrapper">
    <div class="container">
        <div class="tirta-pill-container">
            
            <!-- Logo & Nama Brand -->
            <a class="tirta-brand" href="<?= BASE_URL ?>member/index.php">
                <img src="<?= BASE_URL ?>img/kolam.jpg" alt="Logo" onerror="this.src='<?= BASE_URL ?>assets/img/logo.png'">
                <div>
                    <span class="title-main">PARKIR</span> <span class="title-sub">TAMANSARI</span>
                </div>
            </a>

            <!-- Menu Navigasi Tengah -->
            <ul class="tirta-menu">
                <li>
                    <a href="<?= BASE_URL ?>member/index.php" class="<?= $currentNav === 'index.php' ? 'active' : '' ?>">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>member/booking.php" class="<?= $currentNav === 'booking.php' ? 'active' : '' ?>">
                        Booking Parkir
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>member/kendaraan.php" class="<?= $currentNav === 'kendaraan.php' ? 'active' : '' ?>">
                        Kendaraan Saya
                    </a>
                </li>
                <li>
                    <a href="<?= BASE_URL ?>member/edit_profil.php" class="<?= $currentNav === 'edit_profil.php' ? 'active' : '' ?>">
                        Edit Profil
                    </a>
                </li>
            </ul>

            <!-- Bagian Kanan (Identitas User & Tombol Keluar) -->
            <div class="tirta-right-action">
                <div class="tirta-user-info d-none d-md-flex">
                    <?php if (!empty($fotoProfilNav)): ?>
                        <img src="<?= BASE_URL ?>uploads/profil/<?= htmlspecialchars($fotoProfilNav) ?>" alt="Profil">
                    <?php else: ?>
                        <div class="tirta-user-avatar"><?= strtoupper(substr($_SESSION['nama_lengkap'] ?? 'M', 0, 1)) ?></div>
                    <?php endif; ?>
                    <span><?= htmlspecialchars($_SESSION['nama_lengkap'] ?? 'Member') ?></span>
                </div>

                <a href="<?= BASE_URL ?>auth/logout.php" class="btn-danger-pill">
                    <i class="bi bi-box-arrow-right"></i> Keluar
                </a>
            </div>

        </div>
    </div>
</div>