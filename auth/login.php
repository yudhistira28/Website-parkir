<?php
require_once __DIR__ . '/../config/koneksi.php';

// Jika sudah login, langsung arahkan ke dashboard sesuai role
if (isset($_SESSION['id_user'])) {
    switch ($_SESSION['role']) {
        case 'admin': header("Location: " . BASE_URL . "admin/index.php"); exit;
        case 'petugas': header("Location: " . BASE_URL . "operator/index.php"); exit;
        case 'owner': header("Location: " . BASE_URL . "owner/index.php"); exit;
        case 'member': header("Location: " . BASE_URL . "member/index.php"); exit;
    }
}

$error = '';
if (isset($_GET['err']) && $_GET['err'] === 'akses_ditolak') {
    $error = 'Akses ditolak. Silakan login dengan akun yang sesuai.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $stmt = $koneksi->prepare("SELECT * FROM tb_user WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ((int)$user['status_aktif'] === 0) {
                $error = 'Akun anda dinonaktifkan. Hubungi administrator.';
            } else {
                $_SESSION['id_user'] = $user['id_user'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['foto'] = $user['foto'];

                $_SESSION['notif_login'] = [
                    'pesan' => 'Selamat datang, ' . $user['nama_lengkap'] . '! Anda berhasil masuk sebagai ' . ucfirst($user['role']) . '.',
                    'tipe'  => 'success'
                ];

                catatLog($koneksi, $user['id_user'], 'Login ke sistem');

                switch ($user['role']) {
                    case 'admin': header("Location: " . BASE_URL . "admin/index.php"); exit;
                    case 'petugas': header("Location: " . BASE_URL . "operator/index.php"); exit;
                    case 'owner': header("Location: " . BASE_URL . "owner/index.php"); exit;
                    case 'member': header("Location: " . BASE_URL . "member/index.php"); exit;
                }
            }
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login - Parkir Tirta Tamansari</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700;800&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <script>window.APP_BASE_URL = "<?= BASE_URL ?>";</script>
    <script src="<?= BASE_URL ?>assets/js/sound-effect.js"></script>
    <link rel="icon" type="image/jpeg" href="<?= BASE_URL ?>img/kolam.jpg">

    <style>
        :root {
            --ink: #060a16;
            --ink-2: #0d1730;
            --ink-3: #101c3c;
            --aqua: #2fe6c8;
            --aqua-dim: #1ba893;
            --violet: #8b7cfa;
            --text-soft: rgba(255,255,255,.6);
            --glass-border: rgba(255,255,255,.09);
        }
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: var(--ink);
            color: #fff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
            position: relative;
            overflow: hidden;
        }
        h1, h3, .brand { font-family: 'Space Grotesk', system-ui, sans-serif; }

        /* ---------- Latar ambient: orb blur + riak air (signature) ---------- */
        .ambient-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            z-index: 0;
            opacity: .55;
        }
        .ambient-orb.o1 {
            width: 480px; height: 480px;
            background: radial-gradient(circle, rgba(139,124,250,.55), transparent 70%);
            top: -180px; right: -120px;
            animation: floatOrb 14s ease-in-out infinite;
        }
        .ambient-orb.o2 {
            width: 420px; height: 420px;
            background: radial-gradient(circle, rgba(47,230,200,.4), transparent 70%);
            bottom: -160px; left: -140px;
            animation: floatOrb 16s ease-in-out infinite reverse;
        }
        @keyframes floatOrb {
            0%, 100% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(30px, -25px) scale(1.06); }
        }

        .ripple-lines {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            opacity: .35;
            background-image:
                repeating-radial-gradient(circle at 15% 85%, transparent 0, transparent 38px, rgba(47,230,200,.06) 39px, transparent 40px),
                repeating-radial-gradient(circle at 90% 15%, transparent 0, transparent 46px, rgba(139,124,250,.06) 47px, transparent 48px);
        }

        .auth-wrapper { width: 100%; max-width: 780px; position: relative; z-index: 1; animation: riseIn .6s cubic-bezier(.2,.8,.2,1); }
        @keyframes riseIn {
            from { opacity: 0; transform: translateY(18px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .auth-card {
            display: flex;
            background: linear-gradient(180deg, rgba(16,28,60,.7), rgba(13,23,48,.7));
            backdrop-filter: blur(22px);
            -webkit-backdrop-filter: blur(22px);
            border: 1px solid var(--glass-border);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: 0 40px 90px rgba(0,0,0,.55), inset 0 1px 0 rgba(255,255,255,.05);
            min-height: 440px;
            clip-path: polygon(0 0, 100% 0, 100% calc(100% - 34px), calc(100% - 34px) 100%, 0 100%);
            position: relative;
        }
        .auth-card::after {
            content: "";
            position: absolute;
            right: 0; bottom: 0;
            width: 34px; height: 34px;
            background: linear-gradient(135deg, var(--aqua), var(--violet));
            clip-path: polygon(100% 0, 100% 100%, 0 100%);
        }

        /* Panel Kiri — background foto kolam renang, overlay dibuat lebih tipis agar foto terlihat jelas */
        .auth-side {
            flex: 1;
            background:
                linear-gradient(160deg, rgba(6,10,22,.55), rgba(6,10,22,.72) 78%),
                url('<?= BASE_URL ?>img/foto_login.jpg') center/cover no-repeat;
            padding: 34px 30px;
            color: #fff;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            border-right: 1px solid rgba(255,255,255,.06);
        }

        /* Lapisan tambahan supaya teks tetap kontras & mudah dibaca di atas foto yang lebih terang */
        .auth-side::before {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(6,10,22,.15) 0%, rgba(6,10,22,.05) 35%, rgba(6,10,22,.65) 100%);
            pointer-events: none;
        }
        .auth-side > * { position: relative; z-index: 1; }

        .side-logo {
            width: 46px; height: 46px;
            border-radius: 14px;
            overflow: hidden;
            display: flex; align-items: center; justify-content: center;
            background: #fff;
            box-shadow: 0 8px 22px rgba(0,0,0,.35);
            margin-bottom: 16px;
        }
        .side-logo img { width: 100%; height: 100%; object-fit: cover; }
        .side-logo .side-logo-fallback {
            width: 100%; height: 100%;
            display: none;
            align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--aqua), var(--violet));
            color: var(--ink);
            font-weight: 800;
            font-size: 1rem;
        }

        .btn-back-inside {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #fff;
            font-size: .78rem;
            font-weight: 600;
            text-decoration: none;
            background: rgba(255,255,255,.08);
            padding: 7px 14px;
            border-radius: 999px;
            border: 1px solid rgba(255,255,255,.14);
            transition: background .2s ease, transform .2s ease;
        }
        .btn-back-inside:hover {
            color: #fff;
            background: rgba(255,255,255,.16);
            transform: translateX(-3px);
        }

        .badge-tag {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-family: 'IBM Plex Mono', monospace;
            font-size: .68rem;
            font-weight: 500;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--aqua);
            margin-top: 4px;
            text-shadow: 0 2px 8px rgba(0,0,0,.6);
        }
        .badge-tag::before {
            content: "";
            width: 18px; height: 1px;
            background: var(--aqua);
            display: inline-block;
        }

        .auth-side h1 {
            font-size: 1.6rem;
            font-weight: 700;
            letter-spacing: -.3px;
            margin: 10px 0 8px;
            line-height: 1.15;
            text-shadow: 0 2px 12px rgba(0,0,0,.55);
        }
        .auth-side h1 em {
            font-style: normal;
            background: linear-gradient(120deg, var(--aqua), var(--violet));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .auth-side p { font-size: .82rem; color: rgba(255,255,255,.82); line-height: 1.55; margin: 0; text-shadow: 0 2px 8px rgba(0,0,0,.5); }

        .feature-list { display: flex; flex-direction: column; gap: 10px; }
        .feature-item { display: flex; align-items: center; gap: 9px; font-size: .78rem; color: rgba(255,255,255,.9); text-shadow: 0 2px 8px rgba(0,0,0,.5); }
        .feature-item i {
            font-size: .82rem;
            color: var(--ink);
            background: var(--aqua);
            width: 22px; height: 22px;
            border-radius: 7px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }

        /* Panel Kanan (Form) */
        .auth-form {
            flex: 1;
            padding: 36px 34px 46px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: transparent;
        }
        .auth-form h3 { font-size: 1.3rem; font-weight: 700; color: #fff; margin-bottom: 4px; }
        .auth-form .subtitle { color: var(--text-soft); font-size: .82rem; margin-bottom: 24px; }

        .form-label { font-size: .78rem; font-weight: 600; color: rgba(255,255,255,.78); margin-bottom: 5px; }

        .input-wrap { position: relative; }
        .input-wrap i.field-icon {
            position: absolute;
            left: 14px; top: 50%;
            transform: translateY(-50%);
            font-size: .95rem;
            color: rgba(255,255,255,.4);
            pointer-events: none;
        }
        .form-control {
            border-radius: 12px;
            padding: 10px 14px 10px 38px;
            font-size: .875rem;
            border: 1.5px solid rgba(255,255,255,.12);
            background-color: rgba(255,255,255,.045);
            color: #fff;
            transition: all .2s ease;
        }
        .form-control::placeholder { color: rgba(255,255,255,.32); }
        .form-control:focus {
            background-color: rgba(255,255,255,.075);
            border-color: var(--aqua);
            box-shadow: 0 0 0 4px rgba(47,230,200,.14);
            color: #fff;
        }

        .btn-tirta {
            position: relative;
            overflow: hidden;
            background: linear-gradient(120deg, var(--aqua), var(--violet));
            color: var(--ink);
            font-weight: 700;
            font-size: .88rem;
            border: none;
            border-radius: 12px;
            padding: 11px;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .btn-tirta:hover {
            color: var(--ink);
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(47,230,200,.28);
        }
        .btn-tirta::after {
            content: "";
            position: absolute;
            top: 0; left: -60%;
            width: 40%; height: 100%;
            background: linear-gradient(120deg, transparent, rgba(255,255,255,.55), transparent);
            transform: skewX(-20deg);
            transition: left .5s ease;
        }
        .btn-tirta:hover::after { left: 130%; }

        .auth-form small.text-muted { color: rgba(255,255,255,.45) !important; font-size: .78rem; }
        .auth-form small a { color: var(--aqua); }

        .alert-danger {
            font-size: .8rem;
            background: rgba(214,69,69,.12) !important;
            color: #ff9d9d !important;
            border: 1px solid rgba(214,69,69,.3);
            border-radius: 10px;
        }

        @media (max-width: 700px) {
            .auth-card { flex-direction: column; min-height: 0; }
            .auth-side { padding: 26px 24px; border-right: none; border-bottom: 1px solid rgba(255,255,255,.06); }
            .auth-form { padding: 28px 24px; }
        }
    </style>
</head>
<body>

<div class="ambient-orb o1"></div>
<div class="ambient-orb o2"></div>
<div class="ripple-lines"></div>

<div class="auth-wrapper">
    <div class="auth-card">
        <!-- Panel Kiri -->
        <div class="auth-side">
            <div>
                <div class="side-logo">
                    <img src="<?= BASE_URL ?>img/kolam.jpg" alt="Logo Tirta Tamansari"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <span class="side-logo-fallback">TT</span>
                </div>
                <div class="badge-tag">Tirta &amp; Pool Parking System</div>
                <h1>TIRTA <em>TAMANSARI</em></h1>
                <p>Sistem manajemen parkir terpadu untuk member &amp; tamu. Cepat, rapi, dan terpantau real-time.</p>
            </div>

            <div class="mt-3">
                <hr style="border-color: rgba(255,255,255,.08); margin: 14px 0;">
                <div class="feature-list">
                    <div class="feature-item"><i class="bi bi-shield-check"></i> Akses berbasis peran</div>
                    <div class="feature-item"><i class="bi bi-p-circle"></i> Pantau slot real-time</div>
                    <div class="feature-item"><i class="bi bi-receipt"></i> Struk &amp; rekap otomatis</div>
                </div>
            </div>
        </div>

        <!-- Panel Kanan (Form Input) -->
        <div class="auth-form">
            <h3>Selamat Datang 👋</h3>
            <p class="subtitle">Masuk untuk mengelola parkir Tirta Tamansari</p>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2 px-3 mb-3 border-0">
                    <i class="bi bi-exclamation-circle-fill me-1"></i> <?= htmlspecialchars($error) ?>
                    <?php if (strpos($error, 'dinonaktifkan') !== false): ?>
                        <div class="mt-2">
                            <a href="<?= BASE_URL ?>ajukan_aktivasi.php" class="fw-semibold" style="color:#ffd479; text-decoration:underline;">
                                <i class="bi bi-life-preserver me-1"></i>Bantuan
                            </a>
                            <span style="color: rgba(255,255,255,.5);">— ajukan aktivasi ulang akun Anda</span>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="mb-3">
                    <label class="form-label">Username</label>
                    <div class="input-wrap">
                        <i class="bi bi-person field-icon"></i>
                        <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-wrap">
                        <i class="bi bi-lock field-icon"></i>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-tirta w-100 mt-1">
                    Masuk <i class="bi bi-box-arrow-in-right ms-1"></i>
                </button>
            </form>

            <div class="text-center mt-3">
                <small class="text-muted">Belum punya akun? <a href="<?= BASE_URL ?>auth/register.php" class="text-decoration-none fw-semibold">Daftar di sini</a></small>
            </div>
            <div class="text-center mt-3">
                <a href="<?= BASE_URL ?>index.php" class="btn-back-inside">
                    <i class="bi bi-arrow-left"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>