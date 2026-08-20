<?php
require_once __DIR__ . '/../config/koneksi.php';
cekLogin(['petugas']);

$page_title = 'Edit Profil';

$id_user = $_SESSION['id_user'];
$error = '';
$sukses = $_GET['sukses'] ?? '';

// Ambil data user
$stmtUser = $koneksi->prepare("
    SELECT id_user, nama_lengkap, username, role, foto, created_at
    FROM tb_user
    WHERE id_user = ?
    LIMIT 1
");
$stmtUser->execute([$id_user]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: dashboard.php");
    exit;
}

/* =========================================================
   UPLOAD / GANTI FOTO PROFIL
========================================================= */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['aksi']) &&
    $_POST['aksi'] === 'upload_foto'
) {

    if (
        !isset($_FILES['foto']) ||
        $_FILES['foto']['error'] === UPLOAD_ERR_NO_FILE
    ) {
        $error = 'Silakan pilih foto terlebih dahulu.';

    } elseif ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Terjadi kesalahan saat upload foto.';

    } else {

        $file = $_FILES['foto'];

        $ekstensiValid = ['jpg', 'jpeg', 'png', 'webp'];
        $ekstensi = strtolower(
            pathinfo($file['name'], PATHINFO_EXTENSION)
        );

        $ukuranMaks = 2 * 1024 * 1024; // 2 MB

        if (!in_array($ekstensi, $ekstensiValid, true)) {

            $error = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';

        } elseif ($file['size'] > $ukuranMaks) {

            $error = 'Ukuran foto maksimal 2 MB.';

        } else {

            $folderUpload = __DIR__ . '/../uploads/profil/';

            if (!is_dir($folderUpload)) {
                mkdir($folderUpload, 0755, true);
            }

            /*
             * Nama file dibuat unik agar tidak bentrok.
             */
            $namaFileBaru =
                'user_' .
                $id_user .
                '_' .
                time() .
                '.' .
                $ekstensi;

            $tujuan = $folderUpload . $namaFileBaru;

            if (move_uploaded_file($file['tmp_name'], $tujuan)) {

                /*
                 * Hapus foto lama
                 */
                if (!empty($user['foto'])) {

                    $fotoLama =
                        $folderUpload .
                        basename($user['foto']);

                    if (file_exists($fotoLama)) {
                        @unlink($fotoLama);
                    }
                }

                /*
                 * Simpan foto baru ke database
                 */
                $stmtUpdate = $koneksi->prepare("
                    UPDATE tb_user
                    SET foto = ?
                    WHERE id_user = ?
                ");

                $stmtUpdate->execute([
                    $namaFileBaru,
                    $id_user
                ]);

                $_SESSION['foto'] = $namaFileBaru;

                header(
                    "Location: edit_profil.php?sukses=" .
                    urlencode('Foto profil berhasil diperbarui.') .
                    "&aksi=ubah"
                );
                exit;

            } else {

                $error = 'Gagal menyimpan foto. Coba lagi.';
            }
        }
    }
}


/* =========================================================
   HAPUS FOTO PROFIL
========================================================= */
if (isset($_GET['hapus_foto']) && $_GET['hapus_foto'] == '1') {

    if (!empty($user['foto'])) {

        $fotoLama =
            __DIR__ .
            '/../uploads/profil/' .
            basename($user['foto']);

        if (file_exists($fotoLama)) {
            @unlink($fotoLama);
        }

        $stmtUpdate = $koneksi->prepare("
            UPDATE tb_user
            SET foto = NULL
            WHERE id_user = ?
        ");

        $stmtUpdate->execute([$id_user]);

        $_SESSION['foto'] = null;
    }

    header(
        "Location: edit_profil.php?sukses=" .
        urlencode('Foto profil berhasil dihapus.') .
        "&aksi=hapus"
    );
    exit;
}


/* =========================================================
   REFRESH DATA USER
========================================================= */
$stmtUser->execute([$id_user]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);


/* =========================================================
   HEADER
========================================================= */
include __DIR__ . '/components/header.php';
?>

<style>

/* =========================================================
   EDIT PROFIL PETUGAS
========================================================= */

.profile-page {
    padding-bottom: 30px;
}

/* Judul halaman */
.profile-title {
    margin-bottom: 20px;
}

.profile-title h2 {
    font-size: 26px;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 4px;
}

.profile-title p {
    margin: 0;
    color: rgba(255,255,255,.6);
    font-size: 14px;
}


/* Card */
.profile-card {
    background: rgba(255,255,255,.045);
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 16px;
    box-shadow: 0 14px 34px rgba(3,6,16,.28);
    overflow: hidden;
    height: 100%;
}

.profile-card-header {
    padding: 17px 20px;
    border-bottom: 1px solid rgba(255,255,255,.1);
    font-weight: 700;
    color: #ffffff;
    font-size: 16px;
}

.profile-card-body {
    padding: 24px;
}


/* Foto */
.profile-photo-wrapper {
    text-align: center;
}

.profile-photo {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 5px solid #0e1830;
    box-shadow:
        0 0 0 3px #2fe6c8,
        0 8px 20px rgba(0, 0, 0, 0.35);
}

.profile-photo-placeholder {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    margin: auto;

    background: linear-gradient(
        135deg,
        #1ba893,
        #2fe6c8
    );

    display: flex;
    align-items: center;
    justify-content: center;

    color: #070b1a;
    font-size: 55px;
    font-weight: 700;

    box-shadow:
        0 0 0 3px #2fe6c8,
        0 8px 20px rgba(0, 0, 0, 0.35);
}


/* Nama */
.profile-name {
    margin-top: 17px;
    font-size: 20px;
    font-weight: 700;
    color: #ffffff;
}

.profile-role {
    display: inline-block;
    margin-top: 6px;
    padding: 5px 12px;

    background: rgba(47, 230, 200, 0.15);
    color: #2fe6c8;

    border-radius: 20px;

    font-size: 12px;
    font-weight: 700;
}


/* Upload */
.upload-area {
    margin-top: 25px;
    text-align: left;
}

.upload-label {
    font-size: 13px;
    font-weight: 600;
    color: rgba(255,255,255,.7);
    margin-bottom: 7px;
}

.upload-input {
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,.16);
    padding: 10px;
    font-size: 13px;
    background: rgba(255,255,255,.05);
    color: #fff;
}

.upload-info {
    display: block;
    margin-top: 7px;
    font-size: 12px;
    color: rgba(255,255,255,.55);
}


/* Tombol */
.btn-upload {
    width: 100%;
    margin-top: 15px;

    border: none;
    border-radius: 10px;

    padding: 11px 15px;

    background: linear-gradient(
        135deg,
        #2fe6c8,
        #8b7cfa
    );

    color: #070b1a;
    font-weight: 600;

    transition: 0.2s;
}

.btn-upload:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(47, 230, 200, 0.3);
}


/* Hapus */
.btn-delete-photo {
    display: block;
    width: 100%;
    margin-top: 10px;

    border-radius: 10px;
    padding: 10px;

    text-decoration: none;
    text-align: center;

    color: #dc3545;
    border: 1px solid #f1b5bc;

    font-size: 13px;
    font-weight: 600;

    transition: 0.2s;
}

.btn-delete-photo:hover {
    background: rgba(214, 69, 69, .12);
    color: #ff9d9d;
}


/* Informasi akun */
.account-info {
    width: 100%;
}

.account-row {
    display: flex;
    align-items: center;

    padding: 15px 0;

    border-bottom: 1px solid rgba(255,255,255,.08);
}

.account-row:last-child {
    border-bottom: none;
}

.account-label {
    width: 170px;
    color: rgba(255,255,255,.55);
    font-size: 13px;
}

.account-value {
    flex: 1;
    color: #ffffff;
    font-size: 14px;
    font-weight: 600;
}

.role-badge {
    display: inline-block;

    padding: 5px 12px;

    background: rgba(47, 230, 200, 0.15);
    color: #2fe6c8;

    border-radius: 20px;

    font-size: 12px;
    font-weight: 700;
}


/* Informasi */
.info-box {
    margin-top: 20px;
    padding: 14px 16px;

    background: rgba(47, 230, 200, 0.1);

    border-left: 4px solid #1ba893;

    border-radius: 8px;

    color: rgba(255,255,255,.75);
    font-size: 13px;
    line-height: 1.6;
}


/* Alert */
.profile-alert {
    border-radius: 10px;
    border: none;
    font-size: 13px;
}


/* Responsive */
@media (max-width: 768px) {

    .account-row {
        display: block;
    }

    .account-label {
        width: 100%;
        margin-bottom: 4px;
    }

    .profile-card-body {
        padding: 18px;
    }
}

</style>


<div class="profile-page">

    <!-- =====================================================
         JUDUL
    ====================================================== -->

    <div class="profile-title">

        <h2>
            <i class="bi bi-person-circle me-2"></i>
            Edit Profil
        </h2>

        <p>
            Kelola foto profil dan lihat informasi akun petugas.
        </p>

    </div>


    <!-- =====================================================
         ALERT
    ====================================================== -->

    <?php if ($error): ?>

        <div class="alert alert-danger profile-alert">
            <i class="bi bi-exclamation-circle me-2"></i>
            <?= htmlspecialchars($error) ?>
        </div>

    <?php endif; ?>


    <?php if ($sukses): ?>
        <?php
            $aksiSuara = $_GET['aksi'] ?? '';
            $jenisSuaraValid = in_array($aksiSuara, ['tambah', 'ubah', 'hapus'], true) ? $aksiSuara : 'ubah';
        ?>
        <div class="alert alert-success profile-alert" data-sound="<?= $jenisSuaraValid ?>">
            <i class="bi bi-check-circle me-2"></i>
            <?= htmlspecialchars($sukses) ?>
        </div>

    <?php endif; ?>


    <div class="row g-4">


        <!-- =================================================
             FOTO PROFIL
        ================================================== -->

        <div class="col-lg-5">

            <div class="profile-card">

                <div class="profile-card-header">

                    <i class="bi bi-camera me-2"></i>
                    Foto Profil

                </div>


                <div class="profile-card-body">

                    <div class="profile-photo-wrapper">

                        <?php if (!empty($user['foto'])): ?>

                            <img
                                src="<?= BASE_URL ?>uploads/profil/<?= htmlspecialchars($user['foto']) ?>?v=<?= time() ?>"
                                alt="Foto Profil"
                                class="profile-photo"
                            >

                        <?php else: ?>

                            <?php
                            $nama = trim($user['nama_lengkap'] ?? '');
                            $inisial = strtoupper(
                                substr($nama, 0, 1)
                            );
                            ?>

                            <div class="profile-photo-placeholder">
                                <?= htmlspecialchars($inisial ?: 'P') ?>
                            </div>

                        <?php endif; ?>


                        <div class="profile-name">

                            <?= htmlspecialchars(
                                $user['nama_lengkap']
                            ) ?>

                        </div>


                        <span class="profile-role">

                            <i class="bi bi-shield-check me-1"></i>

                            PETUGAS PARKIR

                        </span>

                    </div>


                    <!-- FORM UPLOAD -->

                    <div class="upload-area">

                        <form
                            method="POST"
                            enctype="multipart/form-data"
                        >

                            <input
                                type="hidden"
                                name="aksi"
                                value="upload_foto"
                            >


                            <div class="upload-label">

                                Pilih Foto Baru

                            </div>


                            <input
                                type="file"
                                name="foto"
                                accept=".jpg,.jpeg,.png,.webp"
                                class="form-control upload-input"
                                required
                            >


                            <small class="upload-info">

                                <i class="bi bi-info-circle me-1"></i>

                                JPG, JPEG, PNG atau WEBP.
                                Maksimal 2 MB.

                            </small>


                            <button
                                type="submit"
                                class="btn-upload"
                            >

                                <i class="bi bi-cloud-arrow-up me-2"></i>

                                Unggah Foto Baru

                            </button>

                        </form>


                        <?php if (!empty($user['foto'])): ?>

                            <a
                                href="?hapus_foto=1"
                                class="btn-delete-photo"
                                onclick="return confirm('Apakah Anda yakin ingin menghapus foto profil?');"
                            >

                                <i class="bi bi-trash me-2"></i>

                                Hapus Foto Profil

                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>


        <!-- =================================================
             INFORMASI AKUN
        ================================================== -->

        <div class="col-lg-7">

            <div class="profile-card">

                <div class="profile-card-header">

                    <i class="bi bi-person-vcard me-2"></i>

                    Informasi Akun

                </div>


                <div class="profile-card-body">


                    <div class="account-info">


                        <!-- Nama -->

                        <div class="account-row">

                            <div class="account-label">

                                <i class="bi bi-person me-2"></i>

                                Nama Lengkap

                            </div>

                            <div class="account-value">

                                <?= htmlspecialchars(
                                    $user['nama_lengkap']
                                ) ?>

                            </div>

                        </div>


                        <!-- Username -->

                        <div class="account-row">

                            <div class="account-label">

                                <i class="bi bi-at me-2"></i>

                                Username

                            </div>

                            <div class="account-value">

                                <?= htmlspecialchars(
                                    $user['username']
                                ) ?>

                            </div>

                        </div>


                        <!-- Role -->

                        <div class="account-row">

                            <div class="account-label">

                                <i class="bi bi-shield-check me-2"></i>

                                Role

                            </div>

                            <div class="account-value">

                                <span class="role-badge">

                                    <?= strtoupper(
                                        htmlspecialchars(
                                            $user['role']
                                        )
                                    ) ?>

                                </span>

                            </div>

                        </div>


                        <!-- Bergabung -->

                        <div class="account-row">

                            <div class="account-label">

                                <i class="bi bi-calendar3 me-2"></i>

                                Bergabung Sejak

                            </div>

                            <div class="account-value">

                                <?php
                                if (!empty($user['created_at'])) {
                                    echo date(
                                        'd M Y',
                                        strtotime(
                                            $user['created_at']
                                        )
                                    );
                                } else {
                                    echo '-';
                                }
                                ?>

                            </div>

                        </div>


                    </div>


                    <!-- INFO -->

                    <div class="info-box">

                        <i class="bi bi-info-circle-fill me-2"></i>

                        Untuk mengubah nama lengkap, username,
                        role, atau password, silakan hubungi
                        Administrator.

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


<?php
include __DIR__ . '/components/footer.php';
?>