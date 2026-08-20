<?php
/**
 * =====================================================
 * Konfigurasi Koneksi Database
 * Aplikasi Parkir - Tirta Tamansari
 * =====================================================
 * BASE_URL dideteksi otomatis di bawah, jadi TIDAK perlu diedit manual
 * walau nama folder project di htdocs/www berbeda-beda di tiap komputer.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ==== Konfigurasi Database ====
define('DB_HOST', 'localhost');
define('DB_NAME', 'db_tirta_tamansari_parkir');
define('DB_USER', 'root');
define('DB_PASS', '');

// ==== Konfigurasi URL Dasar Aplikasi (auto-detect, tidak perlu diedit manual) ====
// Menghitung otomatis dari lokasi folder project ini di server, jadi tetap
// benar walaupun nama foldernya diganti (misal parkir_tirta_tamansari,
// parkir, atau nama lain apa pun).
$autoScheme  = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? 'https' : 'http';
$autoHost    = $_SERVER['HTTP_HOST'] ?? 'localhost';
$autoDocRoot = isset($_SERVER['DOCUMENT_ROOT']) ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/') : '';
$autoProjectRoot = str_replace('\\', '/', dirname(__DIR__)); // folder project ini (satu level di atas /config)
$autoRelativePath = ($autoDocRoot !== '' && strpos($autoProjectRoot, $autoDocRoot) === 0)
    ? substr($autoProjectRoot, strlen($autoDocRoot))
    : '/parkir_tirta_tamansari'; // fallback kalau DOCUMENT_ROOT tidak terdeteksi

define('BASE_URL', $autoScheme . '://' . $autoHost . $autoRelativePath . '/');

// ==== Konfigurasi Denda Keterlambatan Booking ====
// Toleransi keterlambatan (menit) sebelum booking dianggap telat & kena denda
define('TOLERANSI_TELAT_MENIT', 15);

try {
    $koneksi = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS
    );
    $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $koneksi->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}

/**
 * Memastikan user sudah login & memiliki role yang diizinkan.
 * @param array $allowed_roles daftar role yang boleh mengakses halaman ini
 */
function cekLogin($allowed_roles = []) {
    if (!isset($_SESSION['id_user'])) {
        header("Location: " . BASE_URL . "auth/login.php");
        exit;
    }
    if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
        header("Location: " . BASE_URL . "auth/login.php?err=akses_ditolak");
        exit;
    }
}

/**
 * Mencatat aktivitas user ke tabel log
 */
function catatLog($koneksi, $id_user, $aktivitas) {
    $stmt = $koneksi->prepare("INSERT INTO tb_log_aktivitas (id_user, aktivitas, waktu_aktivitas) VALUES (?, ?, NOW())");
    $stmt->execute([$id_user, $aktivitas]);
}

/**
 * Menampilkan notifikasi toast (misalnya pesan "berhasil login") satu kali saja.
 * Panggil fungsi ini di halaman yang membutuhkan (contoh: dashboard setelah login).
 * Pesan diambil dari $_SESSION['notif_login'] lalu langsung dihapus agar tidak
 * muncul lagi saat halaman di-refresh.
 */
function tampilkanNotifikasiLogin() {
    if (empty($_SESSION['notif_login'])) {
        return;
    }

    $notif  = $_SESSION['notif_login'];
    $pesan  = htmlspecialchars($notif['pesan'] ?? '', ENT_QUOTES);
    $tipe   = $notif['tipe'] ?? 'success'; // success | danger | warning | info

    $warnaBg = [
        'success' => 'bg-success',
        'danger'  => 'bg-danger',
        'warning' => 'bg-warning',
        'info'    => 'bg-info',
    ][$tipe] ?? 'bg-success';

    $icon = [
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-x-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'info'    => 'bi-info-circle-fill',
    ][$tipe] ?? 'bi-check-circle-fill';

    echo '
<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1090;">
    <div id="toastLoginNotif" class="toast align-items-center text-white ' . $warnaBg . ' border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="bi ' . $icon . ' me-2"></i>' . $pesan . '
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    var elToastLogin = document.getElementById("toastLoginNotif");
    if (elToastLogin && window.bootstrap) {
        var toastLogin = new bootstrap.Toast(elToastLogin, { delay: 4000 });
        toastLogin.show();
    }
    if (typeof mainkanSuaraBenar === "function" && typeof mainkanSuaraSalah === "function") {
        (' . (($tipe === 'success') ? 'mainkanSuaraBenar' : 'mainkanSuaraSalah') . ')();
    }
});
</script>';

    // Hapus supaya notifikasi cuma tampil sekali (tidak muncul lagi saat refresh)
    unset($_SESSION['notif_login']);
}

/**
 * Format Rupiah
 */
function rupiah($angka) {
    return "Rp " . number_format((float)$angka, 0, ',', '.');
}

/**
 * Hitung selisih keterlambatan (menit) antara waktu terjadwal (booking)
 * dan waktu aktual. Mengembalikan 0 jika belum/tidak terlambat.
 *
 * @param string|DateTime $waktuTerjadwal
 * @param string|DateTime $waktuAktual
 * @return int menit terlambat (0 jika tidak telat)
 */
function hitungMenitTelat($waktuTerjadwal, $waktuAktual) {
    $jadwal = $waktuTerjadwal instanceof DateTime ? $waktuTerjadwal : new DateTime($waktuTerjadwal);
    $aktual = $waktuAktual instanceof DateTime ? $waktuAktual : new DateTime($waktuAktual);

    if ($aktual <= $jadwal) return 0;

    $selisihDetik = $aktual->getTimestamp() - $jadwal->getTimestamp();
    return (int) floor($selisihDetik / 60);
}

/**
 * Hitung nominal denda berdasarkan jumlah menit terlambat, dikurangi
 * toleransi (TOLERANSI_TELAT_MENIT), dibulatkan ke atas per jam.
 *
 * @param int $menitTelat
 * @param float $dendaPerJam
 * @return int nominal denda (0 jika masih dalam toleransi)
 */
function hitungDenda($menitTelat, $dendaPerJam) {
    $menitKena = $menitTelat - TOLERANSI_TELAT_MENIT;
    if ($menitKena <= 0 || $dendaPerJam <= 0) return 0;

    $jamKena = (int) ceil($menitKena / 60);
    return $jamKena * (float) $dendaPerJam;
}