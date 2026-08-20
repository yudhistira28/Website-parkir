<?php
require_once __DIR__ . '/config/koneksi.php';

// Jika sudah login, langsung arahkan ke dashboard sesuai role
if (isset($_SESSION['id_user'])) {
    switch ($_SESSION['role']) {
        case 'admin': header("Location: " . BASE_URL . "admin/index.php"); exit;
        case 'petugas': header("Location: " . BASE_URL . "operator/index.php"); exit;
        case 'owner': header("Location: " . BASE_URL . "owner/index.php"); exit;
    }
}

// ===== Ambil testimoni yang sudah disetujui (approved) dari database =====
$daftarTestimoni = [];
try {
    $stmtTesti = $koneksi->prepare(
        "SELECT nama, role, rating, komentar, created_at
         FROM testimoni
         WHERE status = 'approved'
         ORDER BY created_at DESC
         LIMIT 6"
    );
    $stmtTesti->execute();
    $daftarTestimoni = $stmtTesti->fetchAll();
} catch (PDOException $e) {
    // Tabel testimoni mungkin belum dibuat -> tampilkan data contoh (fallback) di bawah
    $daftarTestimoni = [];
}

// ===== Ambil data kepadatan parkir per jam untuk grafik =====
$labelJam = ['08:00','10:00','12:00','14:00','16:00','18:00','20:00'];
$dataKepadatan = [12, 28, 45, 38, 52, 67, 30]; // data contoh (fallback)

try {
    $stmtGrafik = $koneksi->prepare(
        "SELECT DATE_FORMAT(waktu_masuk, '%H:00') AS jam, COUNT(*) AS jumlah
         FROM tb_transaksi
         WHERE DATE(waktu_masuk) = CURDATE()
         GROUP BY jam
         ORDER BY jam ASC"
    );
    $stmtGrafik->execute();
    $hasilGrafik = $stmtGrafik->fetchAll();

    if (!empty($hasilGrafik)) {
        $labelJam = array_column($hasilGrafik, 'jam');
        $dataKepadatan = array_map('intval', array_column($hasilGrafik, 'jumlah'));
    }
} catch (PDOException $e) {
    // Tabel transaksi mungkin belum tersedia/berbeda struktur -> gunakan data contoh di atas
}

// ===== Pendapatan HARIAN (7 hari terakhir) =====
$labelHarian = [];
$dataPendapatanHarian = [];
try {
    $stmtH = $koneksi->prepare(
        "SELECT DATE(waktu_keluar) AS tanggal, SUM(biaya_total) AS total
         FROM tb_transaksi
         WHERE status = 'keluar' AND waktu_keluar >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
         GROUP BY tanggal ORDER BY tanggal ASC"
    );
    $stmtH->execute();
    $petaHarian = array_column($stmtH->fetchAll(), 'total', 'tanggal');
} catch (PDOException $e) { $petaHarian = []; }

for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', strtotime("-$i day"));
    $labelHarian[] = date('d/m', strtotime($tgl));
    $dataPendapatanHarian[] = isset($petaHarian[$tgl]) ? (int) $petaHarian[$tgl] : 0;
}

// ===== Pendapatan BULANAN (12 bulan terakhir) =====
$labelBulanan = [];
$dataPendapatanBulanan = [];
try {
    $stmtB = $koneksi->prepare(
        "SELECT DATE_FORMAT(waktu_keluar, '%Y-%m') AS bulan, SUM(biaya_total) AS total
         FROM tb_transaksi
         WHERE status = 'keluar' AND waktu_keluar >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
         GROUP BY bulan ORDER BY bulan ASC"
    );
    $stmtB->execute();
    $petaBulanan = array_column($stmtB->fetchAll(), 'total', 'bulan');
} catch (PDOException $e) { $petaBulanan = []; }

$namaBulanSingkat = ['01'=>'Jan','02'=>'Feb','03'=>'Mar','04'=>'Apr','05'=>'Mei','06'=>'Jun','07'=>'Jul','08'=>'Agu','09'=>'Sep','10'=>'Okt','11'=>'Nov','12'=>'Des'];
for ($i = 11; $i >= 0; $i--) {
    $bln = date('Y-m', strtotime("-$i month"));
    $mm  = substr($bln, 5, 2);
    $labelBulanan[] = $namaBulanSingkat[$mm] . ' ' . substr($bln, 2, 2);
    $dataPendapatanBulanan[] = isset($petaBulanan[$bln]) ? (int) $petaBulanan[$bln] : 0;
}

// ===== Ambil data seluruh area parkir beserta sisa slotnya (real-time: kapasitas - terisi) =====
$daftarAreaLanding = [];
try {
    $stmtAreaLanding = $koneksi->query("SELECT * FROM tb_area_parkir ORDER BY id_area");
    $daftarAreaLanding = $stmtAreaLanding->fetchAll();
} catch (PDOException $e) {
    $daftarAreaLanding = [];
}

// Total kapasitas & sisa slot keseluruhan (untuk visual utama di hero)
$totalKapasitas = 0;
$totalTerisi = 0;
foreach ($daftarAreaLanding as $al) {
    $totalKapasitas += (int) $al['kapasitas'];
    $totalTerisi    += (int) $al['terisi'];
}
$totalSisa = max(0, $totalKapasitas - $totalTerisi);

// Susun teks ticker area (dipakai di pita berjalan bawah hero)
$tickerParts = [];
foreach ($daftarAreaLanding as $al) {
    $kap = (int) $al['kapasitas'];
    $ter = (int) $al['terisi'];
    $sisaTicker = max(0, $kap - $ter);
    $tickerParts[] = htmlspecialchars($al['nama_area']) . ' — sisa ' . $sisaTicker . '/' . $kap;
}
if (empty($tickerParts)) {
    $tickerParts = ['Data area parkir belum tersedia'];
}

// ===== Cek keberadaan file video & poster di server (fix: video kosong/hitam) =====
// FIX: file video sebelumnya (img/vidio.mp3) sebenarnya berisi video H.265/HEVC yang
// disamarkan pakai ekstensi .mp3. Kebanyakan browser (Chrome/Firefox/Edge di Windows,
// Linux, Android) tidak bisa decode H.265 di tag <video>, sehingga muncul frame
// corrupt/blur/blok gelap alih-alih video normal. File sudah di-transcode ulang ke
// H.264 dan disimpan sebagai img/vidio.mp4 — silakan upload file hasil transcode ini
// menggantikan file lama di folder img/.
$videoFile  = __DIR__ . '/img/vidio.mp4';
$posterFile = __DIR__ . '/img/poster_parkir.jpg';
$videoAda   = file_exists($videoFile) && filesize($videoFile) > 0;
$posterAda  = file_exists($posterFile) && filesize($posterFile) > 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Parkir Tirta Tamansari</title>
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>img/kolam.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Fraunces:ital,opsz,wght@1,9..144,500;1,9..144,600&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <style>
        /* ============================================================
           TIRTA TAMANSARI — desain ulang: konsep "kolam malam"
           Palet: tinta indigo dalam, aqua elektrik, violet lembut,
                  abu-kebiruan sejuk untuk section terang, emas tipis
                  hanya untuk aksen bintang/CTA.
           Tipografi: Space Grotesk (display, tegas) + Inter (body)
                      + Fraunces italic (kutipan) + IBM Plex Mono (data)
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
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--ink);
            color: #fff;
            -webkit-font-smoothing: antialiased;
        }
        .display, h1, h2, h3, .brand {
            font-family: 'Space Grotesk', system-ui, sans-serif;
        }
        .quote-face { font-family: 'Fraunces', Georgia, serif; font-style: italic; }
        .mono, .stat-num, .lane-num { font-family: 'IBM Plex Mono', monospace; }
        a { text-decoration: none; }
        section[id] { scroll-margin-top: 84px; }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: .72rem;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            color: var(--aqua);
        }
        .eyebrow::before {
            content: "";
            width: 22px;
            height: 1px;
            background: var(--aqua);
            display: inline-block;
        }
        .eyebrow.violet { color: var(--violet); }
        .eyebrow.violet::before { background: var(--violet); }

        /* ---------- NAVBAR (floating glass pill) ---------- */
        .navbar-tirta {
            background: linear-gradient(180deg, rgba(7,11,26,.55), transparent);
            padding: 16px 0 0;
            position: sticky;
            top: 0;
            z-index: 1030;
            transition: transform .35s ease;
        }
        .navbar-tirta.nav-hidden {
            transform: translateY(-130%);
        }
        .navbar-shell {
            background: rgba(14, 24, 48, .72);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,.09);
            border-radius: 999px;
            padding: 9px 12px 9px 18px;
            box-shadow: 0 18px 40px rgba(3,6,16,.4);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar-tirta .brand {
            color: #fff;
            font-weight: 700;
            font-size: 1.12rem;
            letter-spacing: .2px;
            flex-shrink: 0;
        }
        .navbar-tirta .brand em {
            font-style: normal;
            background: linear-gradient(120deg, var(--aqua), var(--violet));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .navbar-logo-mark {
            height: 34px; width: 34px; flex-shrink: 0;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid rgba(255,255,255,.18);
            background: #fff;
        }
        .nav-pill-group { gap: 2px; }
        .navbar-tirta .nav-link {
            color: rgba(255,255,255,.68);
            font-weight: 500;
            font-size: .88rem;
            padding: 8px 15px !important;
            border-radius: 999px;
            transition: background .18s ease, color .18s ease;
        }
        .navbar-tirta .nav-link:hover,
        .navbar-tirta .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,.08);
        }
        .navbar-tirta .navbar-toggler {
            border-color: rgba(255,255,255,.3);
            padding: 4px 9px;
        }
        .navbar-tirta .navbar-toggler-icon { filter: invert(1) grayscale(100%) brightness(200%); width: 1.2em; height: 1.2em; }
        .nav-login-btn { flex-shrink: 0; }
        .nav-login-btn i { transition: transform .18s ease; display: inline-block; }
        .nav-login-btn:hover i { transform: translateX(3px); }
        @media (max-width: 991px) {
            .navbar-shell { border-radius: 24px; align-items: stretch; flex-wrap: wrap; padding: 14px 16px; }
            .nav-pill-group { flex-direction: column; gap: 2px; margin: 10px 0; }
            .navbar-tirta .nav-link { padding: 10px 14px !important; }
        }

        .btn-tirta {
            background: linear-gradient(120deg, var(--aqua), var(--violet));
            color: var(--ink);
            border: none;
            font-weight: 700;
            border-radius: 999px;
            padding: 10px 22px;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .btn-tirta:hover { color: var(--ink); transform: translateY(-2px); box-shadow: 0 12px 26px rgba(47,230,200,.28); }
        .btn-outline-tirta {
            border: 1.5px solid rgba(255,255,255,.5);
            color: #fff;
            font-weight: 600;
            border-radius: 999px;
            padding: 9px 20px;
        }
        .btn-outline-tirta:hover { background: #fff; color: var(--ink); border-color: #fff; }

        /* ---------- HERO (foto area parkir sebagai latar + overlay gelap) ---------- */
        .hero {
            background:
                linear-gradient(160deg, rgba(10,19,48,.55) 0%, rgba(7,11,26,.62) 55%, rgba(5,8,16,.72) 100%),
                radial-gradient(60% 55% at 82% 18%, rgba(139,124,250,.2) 0%, transparent 60%),
                radial-gradient(70% 60% at 100% 100%, rgba(47,230,200,.12) 0%, transparent 55%),
                url('<?= BASE_URL ?>img/tampilan.jpg') center center / cover no-repeat;
            color: #fff;
            padding: 68px 0 0;
            position: relative;
            overflow: hidden;
            isolation: isolate; /* fix: cegah ring/blur hero "bocor" menimpa section di bawahnya */
        }
        .hero-grid {
            display: grid;
            grid-template-columns: 1.05fr .95fr;
            gap: 32px;
            align-items: center;
            padding-bottom: 54px;
            position: relative;
            z-index: 2;
        }
        .hero h1 {
            font-weight: 700;
            font-size: clamp(2.3rem, 4.2vw, 3.5rem);
            line-height: 1.06;
            margin: 18px 0 20px;
            letter-spacing: -.5px;
            text-shadow: 0 2px 18px rgba(0,0,0,.55);
        }
        .hero h1 .quote-face { color: var(--aqua); font-weight: 500; font-size: .92em; }
        .hero p.lead {
            color: rgba(255,255,255,.82);
            max-width: 460px;
            font-size: 1.04rem;
            margin-bottom: 28px;
            text-shadow: 0 1px 10px rgba(0,0,0,.5);
        }
        .hero-cta { display: flex; gap: 14px; flex-wrap: wrap; }

        /* Visual sonar: cincin konsentris + angka slot besar di tengah */
        .sonar {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            max-width: 380px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .sonar .ring {
            position: absolute;
            border-radius: 50%;
            border: 1px solid rgba(47,230,200,.28);
        }
        .sonar .ring.r1 { inset: 0; }
        .sonar .ring.r2 { inset: 14%; border-color: rgba(139,124,250,.3); }
        .sonar .ring.r3 { inset: 28%; animation: pulse-ring 3.2s ease-out infinite; }
        .sonar .ring.r4 { inset: 28%; animation: pulse-ring 3.2s ease-out 1.6s infinite; }
        @keyframes pulse-ring {
            0%   { transform: scale(.72); opacity: .9; border-color: rgba(47,230,200,.55); }
            80%  { transform: scale(1.35); opacity: 0; border-color: rgba(47,230,200,0); }
            100% { opacity: 0; }
        }
        .sonar-core {
            position: relative;
            z-index: 2;
            width: 62%;
            aspect-ratio: 1/1;
            border-radius: 50%;
            background: linear-gradient(160deg, rgba(255,255,255,.1), rgba(255,255,255,.03));
            border: 1px solid rgba(255,255,255,.18);
            backdrop-filter: blur(6px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 10px;
        }
        .sonar-core .num {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 800;
            font-size: clamp(2.6rem, 6vw, 3.6rem);
            color: var(--aqua);
            line-height: 1;
        }
        .sonar-core .lbl {
            font-size: .78rem;
            color: rgba(255,255,255,.65);
            margin-top: 6px;
            max-width: 150px;
        }
        .sonar-core .sub {
            font-family: 'IBM Plex Mono', monospace;
            font-size: .72rem;
            color: rgba(255,255,255,.4);
            margin-top: 10px;
        }
        @media (prefers-reduced-motion: reduce) {
            .sonar .ring.r3, .sonar .ring.r4 { animation: none; }
        }

        /* Pita ticker berjalan: status area parkir real-time */
        .ticker-strip {
            border-top: 1px solid rgba(255,255,255,.1);
            background: rgba(7,11,26,.45);
            backdrop-filter: blur(4px);
            overflow: hidden;
            position: relative;
            z-index: 2;
            padding: 13px 0;
        }
        .ticker-track {
            display: flex;
            gap: 0;
            width: max-content;
            animation: ticker-scroll 28s linear infinite;
        }
        .ticker-track span {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: .8rem;
            color: rgba(255,255,255,.7);
            padding: 0 28px;
            white-space: nowrap;
            border-right: 1px solid rgba(255,255,255,.12);
        }
        .ticker-track span i { color: var(--aqua); font-size: .6rem; }
        @keyframes ticker-scroll {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }
        @media (prefers-reduced-motion: reduce) {
            .ticker-track { animation: none; }
        }

        .wave-divider { display: block; width: 100%; line-height: 0; }
        .wave-divider svg { width: 100%; height: 54px; display: block; }
        .wave-divider.to-dark { background: var(--stone); }
        .wave-divider.to-light { background: var(--ink); }

        /* ---------- FITUR (rel bertahap, zig-zag) ---------- */
        .section-pad { padding: 84px 0; }
        .section-head { max-width: 620px; margin-bottom: 46px; }
        .section-head h2 {
            font-weight: 700;
            font-size: clamp(1.65rem, 2.6vw, 2.2rem);
            margin: 10px 0 12px;
            letter-spacing: -.3px;
        }
        .section-head p { color: rgba(255,255,255,.6); font-size: 1.02rem; margin: 0; }

        .rel-fitur { position: relative; }
        .rel-track {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            position: relative;
            z-index: 1;
            align-items: stretch;
        }
        .card-fitur {
            background: rgba(255,255,255,.04);
            border-radius: 20px;
            padding: 32px 28px;
            border: 1px solid rgba(255,255,255,.12);
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease;
            display: flex;
            flex-direction: column;
            gap: 14px;
            height: 100%;
        }
        .card-fitur:hover {
            transform: translateY(-6px);
            box-shadow: 0 22px 44px rgba(0,0,0,.35);
            border-color: var(--aqua);
        }
        .card-fitur .fitur-node {
            width: 42px; height: 42px;
            border-radius: 50%;
            background: var(--ink-2);
            color: var(--aqua);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.15rem;
            border: 3px solid var(--ink);
            box-shadow: 0 0 0 1px rgba(255,255,255,.1);
        }
        .card-fitur h5 { font-weight: 700; margin: 0; font-family: 'Space Grotesk'; color: #fff; }
        .card-fitur p { color: rgba(255,255,255,.6); margin: 0; }
        .card-fitur .go { font-size: .85rem; font-weight: 700; color: var(--aqua); margin-top: auto; }

        .modal-content { border-radius: 20px; border: none; overflow: hidden; color: var(--ink); }
        .modal-header { background: var(--ink); color: #fff; border-bottom: none; }
        .modal-header .btn-close { filter: invert(1) grayscale(100%) brightness(200%); }
        .modal-icon-lg {
            width: 60px; height: 60px; border-radius: 15px;
            background: var(--ink-2);
            color: var(--aqua);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.7rem; margin-bottom: 16px;
        }

        .modal-body { color: #333; }
        .modal-body p,
        .modal-body ul,
        .modal-body li { color: #444; }
        .modal-body .form-label {
            color: var(--ink);
            font-weight: 600;
            margin-bottom: 6px;
        }
        .modal-body .form-control,
        .modal-body textarea.form-control {
            border: 1px solid var(--stone-2);
            border-radius: 10px;
            padding: 10px 14px;
            color: var(--ink);
            background: #fff;
        }
        .modal-body .form-control:focus {
            border-color: var(--aqua);
            box-shadow: 0 0 0 .2rem rgba(47,230,200,.15);
        }
        .modal-body .text-muted { color: var(--text-soft) !important; }
        .rating-input { margin-top: 4px; }
        .rating-input label { font-size: 1.7rem; }
        .modal-footer {
            border-top: 1px solid var(--stone-2) !important;
            padding: 16px 24px;
        }

        /* ---------- PERAN (tumpukan kartu bertumpang, bukan kolom lurus) ---------- */
        .peran-section { background: var(--ink-2); position: relative; }
        .peran-stack { position: relative; max-width: 900px; margin: 0 auto; }
        .peran-card {
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 20px;
            padding: 30px 32px;
            display: flex;
            align-items: center;
            gap: 22px;
            box-shadow: 0 18px 40px rgba(0,0,0,.25);
            transition: transform .2s ease, box-shadow .2s ease;
        }
        .peran-card:hover { transform: translateX(6px); box-shadow: 0 22px 50px rgba(0,0,0,.35); }
        .peran-card:nth-child(1) { margin-right: 12%; }
        .peran-card:nth-child(2) { margin: -14px 6% 0 6%; }
        .peran-card:nth-child(3) { margin: -14px 0 0 12%; }
        .peran-icon {
            flex-shrink: 0;
            width: 54px; height: 54px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem;
            color: var(--ink);
        }
        .peran-card:nth-child(1) .peran-icon { background: var(--aqua); }
        .peran-card:nth-child(2) .peran-icon { background: var(--violet); color: #fff; }
        .peran-card:nth-child(3) .peran-icon { background: var(--brass); }
        .peran-card h5 { font-weight: 700; margin: 0 0 4px; font-family: 'Space Grotesk'; color: #fff; }
        .peran-card p { color: rgba(255,255,255,.6); margin: 0; font-size: .95rem; }

        /* ---------- VIDEO (split: teks kiri, video kanan) ---------- */
        .video-section { background: var(--ink); color: #fff; }
        .video-split { display: grid; grid-template-columns: .85fr 1.15fr; gap: 40px; align-items: center; }
        .video-split .section-head p { color: rgba(255,255,255,.6); }
        .video-parkir-wrap {
            position: relative;
            border-radius: 24px;
            overflow: hidden; /* Memastikan video tetap mengikuti sudut melengkung container secara rapi */
            box-shadow: 0 24px 60px rgba(0,0,0,.35);
            border: 1px solid rgba(255,255,255,.1);
            max-width: 640px;
            width: 100%;
            margin: 0 auto;
            background: var(--ink-2);
            aspect-ratio: 16 / 9; /* Menjaga agar kotak video selalu proporsional */
        }
        /* Fallback untuk browser/engine lama yang belum mendukung aspect-ratio,
           supaya tinggi kotak video tidak pernah runtuh jadi 0px (video/pesan
           fallback hilang total seperti sebelumnya). */
        @supports not (aspect-ratio: 16 / 9) {
            .video-parkir-wrap { height: 0; padding-bottom: 56.25%; }
        }
        .video-parkir-wrap video {
            position: absolute;
            inset: 0;
            z-index: 1;
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover; /* Menyesuaikan video agar memenuhi area tanpa distorsi aneh */
            background: var(--ink-2);
        }
        .video-caption {
            position: absolute;
            left: 22px; bottom: 22px;
            color: #fff;
            background: rgba(7,11,26,.6);
            backdrop-filter: blur(6px);
            padding: 9px 16px;
            border-radius: 999px;
            font-size: .84rem;
            font-family: 'IBM Plex Mono', monospace;
            z-index: 2;
        }
        .video-fallback {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(160deg, var(--ink-2), var(--ink));
            color: rgba(255,255,255,.55);
            text-align: center;
            padding: 24px;
        }
        .video-fallback i { font-size: 2.2rem; color: var(--aqua); }
        .video-fallback span { font-size: .88rem; max-width: 340px; }
        .video-points { list-style: none; padding: 0; margin: 22px 0 0; }
        .video-points li {
            display: flex; gap: 12px; align-items: flex-start;
            padding: 12px 0;
            border-top: 1px solid rgba(255,255,255,.1);
            color: rgba(255,255,255,.72);
            font-size: .93rem;
        }
        .video-points li:first-child { border-top: none; }
        .video-points i { color: var(--aqua); margin-top: 3px; }

        /* ---------- INFORMASI ---------- */
        .info-section { background: var(--ink); }
        .info-section .section-head p { color: rgba(255,255,255,.6); }
        .token-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 999px;
            padding: 14px 30px;
            margin-bottom: 56px;
            flex-wrap: wrap;
            background: rgba(255,255,255,.04);
        }
        .token-item { display: flex; align-items: center; gap: 10px; }
        .token-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--aqua); flex-shrink: 0; }
        .token-item .stat-num { font-size: 1.3rem; font-weight: 600; color: #fff; }
        .token-item .stat-label { color: rgba(255,255,255,.55); font-size: .78rem; }
        .token-sep { color: rgba(255,255,255,.2); font-size: 1.2rem; }

        .panel-card {
            background: rgba(255,255,255,.04);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255,255,255,.12);
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .panel-card h6 { font-weight: 700; margin-bottom: 4px; font-family: 'Space Grotesk'; color: #fff; }
        .panel-card .sub { color: rgba(255,255,255,.55); font-size: .88rem; margin-bottom: 22px; }
        /* chart-wrap mengisi sisa ruang vertikal panel-card, jadi tinggi grafik kiri & kanan
           selalu sama meski salah satu panel punya baris tombol tambahan (Harian/Bulanan) */
        .chart-wrap { position: relative; flex: 1 1 auto; min-height: 220px; }
        .chart-wrap canvas { height: 100% !important; }
        .chart-tab-btn {
            border: 1px solid rgba(255,255,255,.15);
            background: rgba(255,255,255,.04);
            color: rgba(255,255,255,.6);
            border-radius: 999px;
            padding: 5px 16px;
            font-size: .78rem;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s ease;
        }
        .chart-tab-btn.active {
            background: var(--aqua);
            color: var(--ink);
            border-color: var(--aqua);
        }

        .lane-row {
            display: flex;
            align-items: center;
            gap: 18px;
            padding: 14px 6px;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }
        .lane-row:last-child { border-bottom: none; }
        .lane-row .lane-name { font-weight: 700; width: 160px; flex-shrink: 0; color: #fff; }
        .lane-row .lane-track { flex: 1; height: 8px; border-radius: 4px; background: rgba(255,255,255,.1); overflow: hidden; }
        .lane-row .lane-fill { height: 100%; border-radius: 4px; }
        .lane-fill.ok { background: var(--aqua-dim); }
        .lane-fill.warn { background: var(--brass); }
        .lane-fill.full { background: #d64545; }
        .lane-row .lane-num { font-size: .78rem; color: rgba(255,255,255,.55); width: 130px; text-align: right; flex-shrink: 0; }
        .lane-row .badge-status { flex-shrink: 0; }

        .info-box {
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 18px;
            padding: 26px;
            height: 100%;
            background: rgba(255,255,255,.04);
        }
        .info-box i { color: var(--aqua); font-size: 1.3rem; margin-right: 8px; }
        .info-box h6 { color: #fff; }
        .info-box p { color: rgba(255,255,255,.6) !important; }
        .info-box.clickable { cursor: pointer; transition: transform .2s ease, box-shadow .2s ease; }
        .info-box.clickable:hover { transform: translateY(-5px); box-shadow: 0 16px 32px rgba(0,0,0,.3); }

        .help-float-btn {
            position: fixed; right: 24px; bottom: 24px;
            width: 56px; height: 56px; border-radius: 50%;
            background: var(--ink); color: var(--aqua);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.4rem; border: 1px solid rgba(255,255,255,.15);
            box-shadow: 0 12px 30px rgba(7,11,26,.4);
            z-index: 1040;
        }
        .help-float-btn:hover { color: #fff; }

        #modalBantuan .accordion-button { font-weight: 600; }
        #modalBantuan .accordion-button:not(.collapsed) {
            background: var(--stone); color: var(--aqua-dim); box-shadow: none;
        }
        #modalBantuan .accordion-button:focus { box-shadow: none; }
        .bantuan-contact-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 14px; border-radius: 12px;
            background: var(--ink-2); margin-bottom: 10px;
            text-decoration: none; color: #fff;
        }
        .bantuan-contact-item:hover { background: #16223f; }
        .bantuan-contact-item i { font-size: 1.25rem; color: var(--aqua); }

        /* ---------- TESTIMONI (kutipan besar bergaya editorial) ---------- */
        .testi-section { background: var(--ink); color: #fff; }
        .testi-section .section-head p { color: rgba(255,255,255,.6); }
        .testi-scroll {
            display: flex;
            gap: 20px;
            overflow-x: auto;
            padding-bottom: 14px;
            scroll-snap-type: x mandatory;
        }
        .testi-scroll::-webkit-scrollbar { height: 6px; }
        .testi-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 4px; }
        .testi-card {
            flex: 0 0 340px;
            scroll-snap-align: start;
            background: rgba(255,255,255,.04);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 18px;
            padding: 28px;
        }
        .testi-card .quote-mark {
            font-family: 'Fraunces', serif;
            font-style: italic;
            font-size: 2.6rem;
            color: var(--aqua);
            line-height: 1;
            display: block;
            margin-bottom: 4px;
        }
        .testi-card .stars { color: var(--brass); margin-bottom: 10px; font-size: .85rem; }
        .testi-card p.testi-text { color: rgba(255,255,255,.82); font-family: 'Fraunces', serif; font-style: italic; min-height: 84px; font-size: 1.02rem; }
        .testi-user { display: flex; align-items: center; margin-top: 16px; }
        .testi-avatar {
            width: 42px; height: 42px; border-radius: 50%;
            background: var(--aqua); color: var(--ink);
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; margin-right: 12px; flex-shrink: 0;
        }
        .testi-user h6 { margin: 0; font-weight: 700; font-family: 'Space Grotesk'; color: #fff; }
        .testi-user small { color: rgba(255,255,255,.5); }

        .rating-input { display: flex; flex-direction: row-reverse; justify-content: flex-end; gap: 4px; }
        .rating-input input { display: none; }
        .rating-input label { font-size: 1.9rem; color: var(--stone-2); cursor: pointer; transition: color .15s ease; }
        .rating-input input:checked ~ label,
        .rating-input label:hover,
        .rating-input label:hover ~ label { color: var(--brass); }

        /* ---------- CTA ---------- */
        .cta-section {
            background: linear-gradient(135deg, #0a1330, var(--ink) 60%);
            color: #fff;
            border-radius: 28px;
            padding: 60px 44px;
            margin: 10px 0 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,.08);
        }
        .cta-section::before {
            content: "";
            position: absolute;
            width: 420px; height: 420px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(47,230,200,.22), transparent 70%);
            top: -160px; right: -120px;
        }
        .cta-section h3 { font-weight: 700; font-size: clamp(1.5rem,3vw,2.1rem); margin-bottom: 10px; position: relative; }
        .cta-section p, .cta-section .eyebrow, .cta-section a, .cta-section small { position: relative; }

        /* ---------- FOOTER (ringkas, satu kolom) ---------- */
        footer.footer-tirta {
            background: var(--ink);
            color: rgba(255,255,255,.55);
            padding: 36px 0 20px;
            border-top: 1px solid rgba(255,255,255,.08);
            text-align: center;
        }
        .footer-brand-mark {
            width: 34px; height: 34px;
            border-radius: 9px;
            object-fit: cover;
            border: 1px solid rgba(255,255,255,.15);
            background: #fff;
            margin-bottom: 8px;
        }
        .footer-brand-name {
            font-family: 'Space Grotesk', sans-serif;
            font-weight: 700;
            color: #fff;
            font-size: .92rem;
            letter-spacing: .3px;
        }

        .footer-link-row {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
            margin: 14px 0;
        }
        .footer-link-row a {
            color: rgba(255,255,255,.6);
            font-size: .8rem;
            font-weight: 500;
            transition: color .15s ease;
        }
        .footer-link-row a:hover { color: var(--aqua); }

        .footer-credit {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-size: .76rem;
            color: rgba(255,255,255,.4);
        }
        .footer-credit-logo {
            width: 30px; height: 30px;
            object-fit: cover;
            background: #fff;
            border-radius: 8px;
            border: 1px solid rgba(255,255,255,.15);
            flex-shrink: 0;
        }
        .footer-credit strong { color: rgba(255,255,255,.65); font-weight: 600; }

        .footer-bottom {
            font-size: .74rem;
            color: rgba(255,255,255,.28);
            margin-top: 14px;
        }

        @media (max-width: 991px) {
            .hero-grid { grid-template-columns: 1fr; text-align: left; }
            .sonar { margin-top: 20px; }
            .rel-track { grid-template-columns: 1fr; }
            .card-fitur { height: auto; min-height: 200px; }
            .peran-card, .peran-card:nth-child(1), .peran-card:nth-child(2), .peran-card:nth-child(3) { margin: 0 0 16px 0; }
            .video-split { grid-template-columns: 1fr; }
            .token-row { justify-content: flex-start; }
            .lane-row { flex-wrap: wrap; }
            .lane-row .lane-name { width: 100%; }
            .lane-row .lane-num { width: auto; text-align: left; }
            .chart-wrap { min-height: 220px; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-tirta">
    <div class="container">
        <div class="navbar-shell w-100">
            <a class="brand navbar-brand d-flex align-items-center gap-2 mb-0" href="#beranda">
                <img src="<?= BASE_URL ?>img/kolam.jpg" alt="Logo Kolam Renang Tirta Tamansari" class="navbar-logo-mark">
                TIRTA <em>TAMANSARI</em>
            </a>
            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTirta">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarTirta">
                <ul class="navbar-nav nav-pill-group mx-lg-auto align-items-lg-center">
                    <li class="nav-item"><a class="nav-link" href="#beranda">Beranda</a></li>
                    <li class="nav-item"><a class="nav-link" href="#fitur">Fitur Unggulan</a></li>
                    <li class="nav-item"><a class="nav-link" href="#informasi">Informasi</a></li>
                    <li class="nav-item"><a class="nav-link" href="#video-parkir">Tentang Parkir</a></li>
                    <li class="nav-item"><a class="nav-link" href="#testimoni">Testimoni</a></li>
                </ul>
                <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-tirta btn-sm nav-login-btn mt-3 mt-lg-0">
                    Login <i class="bi bi-arrow-right-short"></i>
                </a>
            </div>
        </div>
    </div>
</nav>

<section class="hero" id="beranda">
    <div class="container hero-grid">
        <div>
            <span class="eyebrow">Tirta&amp; Pool Parking System</span>
            <h1>Satu sistem untuk<br><span class="quote-face">memantau</span> setiap slot parkir</h1>
            <p class="lead">
                Kelola parkir member &amp; tamu Tirta Tamansari secara rapi dan real-time —
                satu sistem untuk Admin, Petugas, dan Owner.
            </p>
            <div class="hero-cta">
                <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-tirta">
                    Masuk ke Sistem <i class="bi bi-arrow-right-circle"></i>
                </a>
                <a href="#fitur" class="btn btn-outline-tirta">Lihat Fitur</a>
            </div>
        </div>
        <div class="sonar">
            <div class="ring r1"></div>
            <div class="ring r2"></div>
            <div class="ring r3"></div>
            <div class="ring r4"></div>
            <div class="sonar-core">
                <span class="num mono"><?= $totalSisa ?></span>
                <span class="lbl">slot tersedia sekarang</span>
                <span class="sub">/ <?= $totalKapasitas ?> total kapasitas</span>
            </div>
        </div>
    </div>
    <div class="ticker-strip">
        <div class="ticker-track">
            <?php foreach (array_merge($tickerParts, $tickerParts) as $tp): ?>
                <span><i class="bi bi-record-fill"></i> <?= $tp ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="wave-divider">
        <svg viewBox="0 0 1200 60" preserveAspectRatio="none">
            <path d="M0 30 C 200 60, 400 0, 600 30 C 800 60, 1000 0, 1200 30 L1200 60 L0 60 Z" fill="#eef1f6"/>
        </svg>
    </div>
</section>

<section class="section-pad" id="fitur">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow violet">Alur satu sistem</span>
            <h2>Dari akses masuk sampai struk keluar</h2>
            <p>Tiga tahap yang menjaga parkir gym tetap rapi, cepat, dan terpantau — berurutan sesuai alur penggunaan sehari-hari.</p>
        </div>
        <div class="rel-fitur">
            <div class="rel-track">
                <div class="rel-item">
                    <div class="card-fitur" data-bs-toggle="modal" data-bs-target="#modalFitur1">
                        <div class="fitur-node"><i class="bi bi-shield-check"></i></div>
                        <h5>Akses Berbasis Peran</h5>
                        <p>Role-based access untuk Admin, Petugas, dan Owner dengan hak akses masing-masing.</p>
                        <span class="go">Lihat detail <i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>
                <div class="rel-item">
                    <div class="card-fitur" data-bs-toggle="modal" data-bs-target="#modalFitur2">
                        <div class="fitur-node"><i class="bi bi-p-circle"></i></div>
                        <h5>Pantau Slot Real-Time</h5>
                        <p>Ketahui ketersediaan area parkir secara langsung, kapan saja dibutuhkan.</p>
                        <span class="go">Lihat detail <i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>
                <div class="rel-item">
                    <div class="card-fitur" data-bs-toggle="modal" data-bs-target="#modalFitur3">
                        <div class="fitur-node"><i class="bi bi-receipt"></i></div>
                        <h5>Struk &amp; Rekap Otomatis</h5>
                        <p>Cetak struk transaksi dan rekap laporan parkir secara otomatis — tanpa pencatatan manual.</p>
                        <span class="go">Lihat detail <i class="bi bi-arrow-right"></i></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal Fitur 1: Akses Berbasis Peran -->
<div class="modal fade" id="modalFitur1" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Akses Berbasis Peran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="modal-icon-lg"><i class="bi bi-shield-check"></i></div>
                <p>Sistem membagi hak akses ke dalam tiga peran, masing-masing dengan tampilan dan kewenangan berbeda:</p>
                <ul>
                    <li><strong>Admin</strong> &mdash; kelola akun pengguna, master data, dan konfigurasi sistem.</li>
                    <li><strong>Petugas</strong> &mdash; proses transaksi parkir harian dan cetak struk.</li>
                    <li><strong>Owner</strong> &mdash; pantau laporan dan performa operasional secara keseluruhan.</li>
                </ul>
                <p class="mb-0 text-muted">Setiap login otomatis diarahkan ke dashboard sesuai peran, sehingga tidak ada akses yang tumpang tindih.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Fitur 2: Pantau Slot Real-Time -->
<div class="modal fade" id="modalFitur2" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Pantau Slot Real-Time</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="modal-icon-lg"><i class="bi bi-p-circle"></i></div>
                <p>Ketersediaan area parkir dapat dipantau secara langsung, sehingga petugas dan owner selalu tahu:</p>
                <ul>
                    <li>Jumlah slot yang masih kosong.</li>
                    <li>Kendaraan mana saja yang sedang parkir.</li>
                    <li>Estimasi kepadatan area parkir pada jam tertentu.</li>
                </ul>
                <p class="mb-0 text-muted">Membantu menghindari penumpukan kendaraan dan mempercepat pengambilan keputusan operasional.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Fitur 3: Struk & Rekap Otomatis -->
<div class="modal fade" id="modalFitur3" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Struk &amp; Rekap Otomatis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="modal-icon-lg"><i class="bi bi-receipt"></i></div>
                <p>Setiap transaksi parkir tercatat otomatis dan dapat langsung dicetak dalam bentuk struk. Sistem juga menyediakan:</p>
                <ul>
                    <li>Rekap transaksi harian, mingguan, hingga bulanan.</li>
                    <li>Ringkasan pendapatan yang siap dilihat owner.</li>
                    <li>Riwayat transaksi yang mudah ditelusuri kembali.</li>
                </ul>
                <p class="mb-0 text-muted">Mengurangi pencatatan manual dan risiko kesalahan hitung.</p>
            </div>
        </div>
    </div>
</div>

<section class="peran-section section-pad">
    <div class="container">
        <div class="section-head" style="max-width:640px;">
            <span class="eyebrow violet">Untuk setiap peran</span>
            <h2 class="mb-0">Satu sistem, tiga cara kerja</h2>
        </div>
        <div class="peran-stack">
            <div class="peran-card">
                <div class="peran-icon"><i class="bi bi-person-gear"></i></div>
                <div>
                    <h5>Admin</h5>
                    <p>Kelola akun pengguna, master data, dan konfigurasi sistem secara penuh.</p>
                </div>
            </div>
            <div class="peran-card">
                <div class="peran-icon"><i class="bi bi-person-badge"></i></div>
                <div>
                    <h5>Petugas</h5>
                    <p>Proses transaksi parkir harian, cetak struk, dan input data kendaraan.</p>
                </div>
            </div>
            <div class="peran-card">
                <div class="peran-icon"><i class="bi bi-graph-up-arrow"></i></div>
                <div>
                    <h5>Owner</h5>
                    <p>Pantau laporan, rekap pendapatan, dan performa operasional parkir.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Informasi -->
<section class="info-section section-pad" id="informasi">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow violet">Informasi</span>
            <h2>Sekilas tentang layanan parkir</h2>
        </div>

        <div class="token-row">
            <div class="token-item"><span class="token-dot"></span><span class="stat-num">24/7</span><span class="stat-label">&nbsp;Real-Time</span></div>
            <span class="token-sep">·</span>
            <div class="token-item"><span class="token-dot"></span><span class="stat-num">3</span><span class="stat-label">&nbsp;Peran Pengguna</span></div>
            <span class="token-sep">·</span>
            <div class="token-item"><span class="token-dot"></span><span class="stat-num">100%</span><span class="stat-label">&nbsp;Struk Otomatis</span></div>
            <span class="token-sep">·</span>
            <div class="token-item"><span class="token-dot"></span><span class="stat-num">0</span><span class="stat-label">&nbsp;Pencatatan Manual</span></div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="panel-card">
                    <h6><i class="bi bi-p-circle"></i> Ketersediaan Slot Area Parkir</h6>
                    <p class="sub">Pantau langsung sisa slot tiap area parkir Tirta Tamansari saat ini.</p>
                    <?php if (count($daftarAreaLanding) === 0): ?>
                        <p class="text-muted text-center py-3 mb-0">Data area parkir belum tersedia.</p>
                    <?php else: ?>
                        <?php foreach ($daftarAreaLanding as $al):
                            $kapasitasLanding = (int) $al['kapasitas'];
                            $terisiLanding    = (int) $al['terisi'];
                            $sisaLanding      = max(0, $kapasitasLanding - $terisiLanding);
                            $persenLanding    = $kapasitasLanding > 0 ? round(($terisiLanding / $kapasitasLanding) * 100) : 0;
                            $kelasLanding     = $persenLanding >= 90 ? 'full' : ($persenLanding >= 60 ? 'warn' : 'ok');
                        ?>
                        <div class="lane-row">
                            <span class="lane-name"><?= htmlspecialchars($al['nama_area']) ?></span>
                            <div class="lane-track">
                                <div class="lane-fill <?= $kelasLanding ?>" style="width: <?= $persenLanding ?>%"></div>
                            </div>
                            <span class="lane-num">Terisi <?= $terisiLanding ?> / <?= $kapasitasLanding ?></span>
                            <?php if ($sisaLanding === 0): ?>
                                <span class="badge badge-status" style="background:#d64545;">Penuh</span>
                            <?php else: ?>
                                <span class="badge badge-status" style="background:var(--aqua-dim);">Sisa <?= $sisaLanding ?></span>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="panel-card">
                    <h6><i class="bi bi-bar-chart-line"></i> Kepadatan Parkir per Jam</h6>
                    <p class="sub">Grafik jumlah kendaraan masuk berdasarkan jam (hari ini).</p>
                    <div class="chart-wrap">
                        <canvas id="chartKepadatan"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="panel-card">
                    <h6><i class="bi bi-cash-coin"></i> Pendapatan Parkir</h6>
                    <p class="sub" id="labelPeriodePendapatan">Grafik total pendapatan 7 hari terakhir.</p>
                    <div class="chart-tab-row" style="display:flex;gap:8px;margin-bottom:14px;">
                        <button type="button" class="chart-tab-btn active" data-periode="harian">Harian</button>
                        <button type="button" class="chart-tab-btn" data-periode="bulanan">Bulanan</button>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="chartPendapatan"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="info-box">
                    <h6 class="fw-bold"><i class="bi bi-clock-history"></i>Jam Operasional</h6>
                    <p class="text-muted mb-0">Sistem parkir aktif mengikuti jam operasional Tirta Tamansari setiap hari.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <h6 class="fw-bold"><i class="bi bi-person-plus"></i>Akun Pengguna</h6>
                    <p class="text-muted mb-0">Akun baru untuk Admin, Petugas, atau Owner hanya dapat dibuat oleh Administrator.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box clickable" data-bs-toggle="modal" data-bs-target="#modalBantuan">
                    <h6 class="fw-bold"><i class="bi bi-headset"></i>Bantuan</h6>
                    <p class="text-muted mb-0">Kendala login atau transaksi dapat dilaporkan langsung ke Admin sistem.</p>
                    <p class="go mb-0" style="color:var(--aqua-dim);font-weight:700;font-size:.85rem;">Lihat FAQ &amp; kontak <i class="bi bi-arrow-right"></i></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Video Area Parkir (di atas Testimoni) -->
<section class="video-section section-pad" id="video-parkir">
    <div class="container video-split">
        <div>
            <span class="eyebrow">Tentang parkir</span>
            <h2>Lihat area parkir kami</h2>
            <p style="color:rgba(255,255,255,.6);">Tonton video singkat suasana dan tata letak area parkir Tirta Tamansari sebelum Anda datang.</p>
            <ul class="video-points">
                <li><i class="bi bi-check-circle"></i> Akses masuk &amp; keluar yang jelas ditandai</li>
                <li><i class="bi bi-check-circle"></i> Area terpisah untuk motor dan mobil</li>
                <li><i class="bi bi-check-circle"></i> Petugas siaga di jam operasional</li>
            </ul>
        </div>
        <div class="video-parkir-wrap">
            <?php if ($videoAda): ?>
                <!-- Video demo area parkir Tirta Tamansari -->
                <video id="videoAreaParkir" controls preload="metadata"
                       <?= $posterAda ? 'poster="' . BASE_URL . 'img/poster_parkir.jpg"' : '' ?>>
                    <source src="<?= BASE_URL ?>img/vidio.mp4" type="video/mp4">

                    <a href="<?= BASE_URL ?>img/vidio.mp4">mengunduh video di sini</a>.
                </video>
                <div class="video-fallback" id="videoFallback" style="display:none;">
                    <i class="bi bi-camera-video-off"></i>
                    <span>Video gagal dimuat. Coba muat ulang halaman.</span>
                </div>
                <script>
                (function () {
                    var vid = document.getElementById('videoAreaParkir');
                    var fb  = document.getElementById('videoFallback');
                    if (!vid || !fb) return;
                    vid.addEventListener('error', function () {
                        fb.style.display = 'flex';
                        vid.style.display = 'none';
                    });
                })();
                </script>
            <?php else: ?>
                <!-- Fallback: file video belum ada di server (img/vidio.mp4) -->
                <div class="video-fallback">
                    <i class="bi bi-camera-video-off"></i>
                    <span>Video area parkir belum tersedia. Silakan upload file <code>vidio.mp4</code> ke folder <code>img/</code> di server.</span>
                </div>
            <?php endif; ?>
            <span class="video-caption"><i class="bi bi-play-circle"></i> Area Parkir — Tirta Tamansari</span>
        </div>
    </div>
</section>

<!-- Section Testimoni -->
<section class="testi-section section-pad" id="testimoni">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
            <div class="section-head mb-0">
                <span class="eyebrow">Testimoni</span>
                <h2 class="mb-0">Apa kata pengguna kami</h2>
            </div>
            <button type="button" class="btn btn-tirta" data-bs-toggle="modal" data-bs-target="#modalTestimoni">
                <i class="bi bi-chat-left-text"></i> Tulis Komentar &amp; Rating
            </button>
        </div>

        <?php if (isset($_GET['testimoni']) && $_GET['testimoni'] === 'sukses'): ?>
            <div class="alert alert-success text-center" role="alert">
                Terima kasih! Komentar Anda sudah terkirim dan langsung tampil di bagian testimoni.
            </div>
        <?php elseif (isset($_GET['testimoni']) && $_GET['testimoni'] === 'gagal'): ?>
            <div class="alert alert-danger text-center" role="alert">
                Gagal mengirim komentar. Pastikan nama, rating, dan komentar terisi dengan benar.
            </div>
        <?php endif; ?>

        <div class="testi-scroll">
            <?php if (!empty($daftarTestimoni)): ?>
                <?php foreach ($daftarTestimoni as $t): ?>
                    <div class="testi-card">
                        <span class="quote-mark">&ldquo;</span>
                        <div class="stars">
                            <?php
                            $r = (int)$t['rating'];
                            for ($i = 1; $i <= 5; $i++) {
                                echo $i <= $r
                                    ? '<i class="bi bi-star-fill"></i>'
                                    : '<i class="bi bi-star"></i>';
                            }
                            ?>
                        </div>
                        <p class="testi-text"><?= htmlspecialchars($t['komentar']) ?></p>
                        <div class="testi-user">
                            <div class="testi-avatar"><?= strtoupper(substr($t['nama'], 0, 1)) ?></div>
                            <div>
                                <h6><?= htmlspecialchars($t['nama']) ?></h6>
                                <small><?= htmlspecialchars($t['role']) ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Testimoni contoh (tampil jika belum ada data approved di database) -->
                <div class="testi-card">
                    <span class="quote-mark">&ldquo;</span>
                    <div class="stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                    <p class="testi-text">Sejak pakai sistem ini, transaksi parkir jadi lebih cepat dan struk langsung tercetak otomatis.</p>
                    <div class="testi-user">
                        <div class="testi-avatar">R</div>
                        <div>
                            <h6>Rian</h6>
                            <small>Petugas Parkir</small>
                        </div>
                    </div>
                </div>
                <div class="testi-card">
                    <span class="quote-mark">&ldquo;</span>
                    <div class="stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                    <p class="testi-text">Laporan pendapatan parkir bisa saya pantau kapan saja tanpa harus datang langsung ke lokasi.</p>
                    <div class="testi-user">
                        <div class="testi-avatar">D</div>
                        <div>
                            <h6>Pak Dedi</h6>
                            <small>Owner Tirta Tamansari</small>
                        </div>
                    </div>
                </div>
                <div class="testi-card">
                    <span class="quote-mark">&ldquo;</span>
                    <div class="stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                    </div>
                    <p class="testi-text">Sebagai Admin, mengelola akun dan data pengguna jadi jauh lebih rapi dan terstruktur.</p>
                    <div class="testi-user">
                        <div class="testi-avatar">A</div>
                        <div>
                            <h6>Admin D</h6>
                            <small>Administrator</small>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Modal Tulis Testimoni (Komentar & Rating) -->
<div class="modal fade" id="modalTestimoni" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-chat-left-text"></i> Tulis Komentar &amp; Rating</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?= BASE_URL ?>testimoni_submit.php" method="POST">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label for="inputNama" class="form-label fw-semibold">Nama</label>
                        <input type="text" class="form-control" id="inputNama" name="nama" maxlength="100" required>
                    </div>
                    <div class="mb-3">
                        <label for="inputRole" class="form-label fw-semibold">Peran / Status <span class="text-muted fw-normal">(opsional)</span></label>
                        <input type="text" class="form-control" id="inputRole" name="role" maxlength="50" placeholder="Contoh: Member Kolam, Petugas, Owner">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold d-block">Rating</label>
                        <div class="rating-input">
                            <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="5 bintang"><i class="bi bi-star-fill"></i></label>
                            <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="4 bintang"><i class="bi bi-star-fill"></i></label>
                            <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="3 bintang"><i class="bi bi-star-fill"></i></label>
                            <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="2 bintang"><i class="bi bi-star-fill"></i></label>
                            <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="1 bintang"><i class="bi bi-star-fill"></i></label>
                        </div>
                    </div>
                    <div class="mb-1">
                        <label for="inputKomentar" class="form-label fw-semibold">Komentar</label>
                        <textarea class="form-control" id="inputKomentar" name="komentar" rows="4" maxlength="1000" required placeholder="Ceritakan pengalaman Anda menggunakan sistem parkir Tirta Tamansari..."></textarea>
                    </div>
                    <p class="text-muted small mb-0">Komentar Anda akan ditinjau oleh Admin sebelum tampil di halaman ini.</p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-tirta px-4">Kirim <i class="bi bi-send"></i></button>
                </div>
            </form>
        </div>
    </div>
</div>

<section class="container">
    <div class="cta-section">
        <span class="eyebrow" style="justify-content:center;">Mulai sekarang</span>
        <h3 class="mt-2 mb-2">Siap mengelola parkir Tirta Tamansari?</h3>
        <p class="opacity-75 mb-4">Masuk ke sistem untuk mulai memantau dan mengelola transaksi parkir.</p>
        <a href="<?= BASE_URL ?>auth/login.php" class="btn btn-tirta px-5 py-2">
            Masuk Sekarang <i class="bi bi-box-arrow-in-right"></i>
        </a>
        <div class="mt-3">
            <small class="opacity-50">Akun Petugas/Owner hanya dapat dibuat oleh Administrator.</small>
        </div>
    </div>
</section>

<footer class="footer-tirta">
    <div class="container">
        <img src="<?= BASE_URL ?>img/kolam.jpg" alt="Logo Tirta Tamansari" class="footer-brand-mark">
        <div class="footer-brand-name">TIRTA TAMANSARI</div>

        <div class="footer-link-row">
            <a href="#fitur">Fitur</a>
            <a href="#testimoni">Testimoni</a>
            <a href="#" data-bs-toggle="modal" data-bs-target="#modalBantuan">Bantuan</a>
        </div>

        <div class="footer-credit">
            <img src="<?= BASE_URL ?>img/logo.jpg" alt="Logo SMK Negeri 1 Sanden" class="footer-credit-logo">
            Dikembangkan oleh siswa <strong>SMK Negeri 1 Sanden, Bantul</strong>
        </div>

        <div class="footer-bottom">
            &copy; <?= date('Y') ?> Parkir Tirta Tamansari
        </div>
    </div>
</footer>

<!-- Modal Bantuan -->
<div class="modal fade" id="modalBantuan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-headset"></i> Pusat Bantuan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <h6 class="fw-bold mb-3">Pertanyaan yang Sering Diajukan</h6>
                <div class="accordion mb-4" id="accordionBantuan">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Saya lupa password akun, bagaimana cara reset?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                            <div class="accordion-body text-muted">
                                Reset password hanya dapat dilakukan oleh Administrator. Silakan hubungi Admin melalui kontak di bawah dengan menyertakan username akun Anda.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Bagaimana cara membuat akun baru?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                            <div class="accordion-body text-muted">
                                Akun untuk Admin, Petugas, maupun Owner hanya dapat dibuat oleh Administrator melalui menu kelola pengguna. Pengguna baru tidak dapat mendaftar sendiri.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Struk transaksi tidak tercetak, apa yang harus dilakukan?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                            <div class="accordion-body text-muted">
                                Periksa koneksi printer terlebih dahulu. Jika masih bermasalah, transaksi tetap tersimpan di sistem dan struk dapat dicetak ulang oleh Petugas melalui riwayat transaksi.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Data slot parkir tidak update secara real-time?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#accordionBantuan">
                            <div class="accordion-body text-muted">
                                Pastikan koneksi internet perangkat stabil, lalu muat ulang (refresh) halaman. Jika masalah berlanjut, laporkan ke Admin sistem.
                            </div>
                        </div>
                    </div>
                </div>

                <h6 class="fw-bold mb-3">Hubungi Kami</h6>
                <a href="https://wa.me/6285736374971?text=Halo%2C%20min%20saya%20perlu%20bantuan%20Anda" target="_blank" rel="noopener" class="bantuan-contact-item">
                    <i class="bi bi-whatsapp"></i>
                    <div>
                        <strong>WhatsApp Admin</strong>
                        <div class="small text-muted">Respon cepat untuk kendala teknis</div>
                    </div>
                </a>
                <a href="mailto:ammarhayyan80@gmail.com" class="bantuan-contact-item">
                    <i class="bi bi-envelope"></i>
                    <div>
                        <strong>Email</strong>
                        <div class="small text-muted">ammarhayyan80@gmail.com</div>
                    </div>
                </a>
                <div class="bantuan-contact-item" style="cursor:default;">
                    <i class="bi bi-geo-alt"></i>
                    <div>
                        <strong>Lokasi</strong>
                        <div class="small text-muted">Tirta Tamansari, area loket</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tombol Bantuan Mengambang -->
<button type="button" class="help-float-btn" data-bs-toggle="modal" data-bs-target="#modalBantuan" title="Bantuan">
    <i class="bi bi-headset"></i>
</button>

<?php if (isset($_GET['testimoni'])): ?>
<script>
    // Scroll otomatis ke bagian testimoni setelah kirim komentar
    document.addEventListener('DOMContentLoaded', function () {
        const el = document.getElementById('testimoni');
        if (el) el.scrollIntoView({ behavior: 'instant', block: 'start' });
    });
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    function formatSingkatRupiah(angka) {
        angka = Number(angka);
        if (Math.abs(angka) >= 1000000) return 'Rp ' + (angka / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + 'jt';
        if (Math.abs(angka) >= 1000) return 'Rp ' + (angka / 1000).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + 'rb';
        return 'Rp ' + angka.toLocaleString('id-ID');
    }

    const ctxKepadatan = document.getElementById('chartKepadatan');
    if (ctxKepadatan) {
        const gradienArea = ctxKepadatan.getContext('2d').createLinearGradient(0, 0, 0, 260);
        gradienArea.addColorStop(0, 'rgba(47,230,200,.35)');
        gradienArea.addColorStop(1, 'rgba(47,230,200,0)');

        new Chart(ctxKepadatan, {
            type: 'line',
            data: {
                labels: <?= json_encode($labelJam) ?>,
                datasets: [{
                    label: 'Jumlah Kendaraan',
                    data: <?= json_encode($dataKepadatan) ?>,
                    borderColor: '#2fe6c8',
                    backgroundColor: gradienArea,
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 5,
                    pointBackgroundColor: '#070b1a',
                    pointBorderColor: '#2fe6c8',
                    pointBorderWidth: 2,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { font: { size: 11 } } },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, font: { size: 11 }, maxTicksLimit: 6 }
                    }
                }
            }
        });
    }

    const dataHarian = {
        label: <?= json_encode($labelHarian) ?>,
        nilai: <?= json_encode($dataPendapatanHarian) ?>,
        keterangan: 'Grafik total pendapatan 7 hari terakhir.'
    };
    const dataBulanan = {
        label: <?= json_encode($labelBulanan) ?>,
        nilai: <?= json_encode($dataPendapatanBulanan) ?>,
        keterangan: 'Grafik total pendapatan 12 bulan terakhir.'
    };

    const ctxPendapatan = document.getElementById('chartPendapatan');
    let chartPendapatan = null;

    function formatRupiah(angka) {
        return 'Rp ' + Number(angka).toLocaleString('id-ID');
    }

    function renderChartPendapatan(periode) {
        const sumber = periode === 'bulanan' ? dataBulanan : dataHarian;
        const warna  = periode === 'bulanan' ? '#f2b84b' : '#8b7cfa';

        const gradien = ctxPendapatan.getContext('2d').createLinearGradient(0, 0, 0, 260);
        gradien.addColorStop(0, warna + '59');
        gradien.addColorStop(1, warna + '00');

        if (chartPendapatan) chartPendapatan.destroy();

        chartPendapatan = new Chart(ctxPendapatan, {
            type: 'line',
            data: {
                labels: sumber.label,
                datasets: [{
                    label: 'Pendapatan (Rp)',
                    data: sumber.nilai,
                    borderColor: warna,
                    backgroundColor: gradien,
                    fill: true,
                    tension: 0.35,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointBackgroundColor: '#070b1a',
                    pointBorderColor: warna,
                    pointBorderWidth: 2,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: (ctx) => formatRupiah(ctx.parsed.y) } }
                },
                scales: {
                    x: { ticks: { font: { size: 11 } } },
                    y: {
                        beginAtZero: true,
                        ticks: { font: { size: 11 }, maxTicksLimit: 6, callback: (v) => formatSingkatRupiah(v) }
                    }
                }
            }
        });

        document.getElementById('labelPeriodePendapatan').textContent = sumber.keterangan;
    }

    if (ctxPendapatan) {
        renderChartPendapatan('harian');
        document.querySelectorAll('.chart-tab-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.chart-tab-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                renderChartPendapatan(this.dataset.periode);
            });
        });
    }
</script>
<script>
    // Navbar otomatis sembunyi saat scroll ke bawah, muncul lagi saat scroll ke atas
    (function () {
        const navbarTirta = document.querySelector('.navbar-tirta');
        let posisiScrollTerakhir = 0;
        const batasScrollAtas = 80; // navbar selalu terlihat sebelum melewati jarak ini dari atas halaman

        window.addEventListener('scroll', function () {
            const posisiScrollSaatIni = window.scrollY;

            if (posisiScrollSaatIni <= batasScrollAtas) {
                navbarTirta.classList.remove('nav-hidden');
            } else if (posisiScrollSaatIni > posisiScrollTerakhir) {
                // scroll ke bawah -> sembunyikan
                navbarTirta.classList.add('nav-hidden');
            } else {
                // scroll ke atas -> tampilkan lagi
                navbarTirta.classList.remove('nav-hidden');
            }

            posisiScrollTerakhir = posisiScrollSaatIni;
        }, { passive: true });
    })();
</script>
</body>
</html>