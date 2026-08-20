<?php
require_once __DIR__ . '/../config/koneksi.php';
cekLogin(['admin']);

$page_title = 'Edit Profil';

// Ambil data user saat ini
$stmtUser = $koneksi->prepare("SELECT * FROM tb_user WHERE id_user = ?");
$stmtUser->execute([$_SESSION['id_user']]);
$dataUser = $stmtUser->fetch();

if (!$dataUser) {
    header("Location: " . BASE_URL . "auth/logout.php");
    exit;
}

$errorPesan = null;

// ===== Proses simpan perubahan =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $namaBaru     = trim($_POST['nama_lengkap'] ?? '');
    $usernameBaru = trim($_POST['username'] ?? '');
    $passwordBaru = $_POST['password'] ?? '';
    $namaFotoBaru = $dataUser['foto']; // default: foto lama tidak berubah

    if ($namaBaru === '' || $usernameBaru === '') {
        $errorPesan = "Nama lengkap dan username wajib diisi.";
    } else {
        // Upload foto baru jika ada file yang dipilih
        if (!empty($_FILES['foto']['name'])) {
            $folderUpload = __DIR__ . '/../uploads/profil/';
            if (!is_dir($folderUpload)) {
                mkdir($folderUpload, 0755, true);
            }
            $extFoto  = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            $izinkan  = ['jpg', 'jpeg', 'png', 'webp'];

            if (!in_array($extFoto, $izinkan)) {
                $errorPesan = "Format foto harus JPG, JPEG, PNG, atau WEBP.";
            } else {
                $namaFotoBaru = 'profil_' . $_SESSION['id_user'] . '_' . time() . '.' . $extFoto;
                move_uploaded_file($_FILES['foto']['tmp_name'], $folderUpload . $namaFotoBaru);
            }
        }

        if (!$errorPesan) {
            try {
                if ($passwordBaru !== '') {
                    $hashBaru = password_hash($passwordBaru, PASSWORD_DEFAULT);
                    $stmtUpdate = $koneksi->prepare(
                        "UPDATE tb_user SET nama_lengkap = ?, username = ?, foto = ?, password = ? WHERE id_user = ?"
                    );
                    $stmtUpdate->execute([$namaBaru, $usernameBaru, $namaFotoBaru, $hashBaru, $_SESSION['id_user']]);
                } else {
                    $stmtUpdate = $koneksi->prepare(
                        "UPDATE tb_user SET nama_lengkap = ?, username = ?, foto = ? WHERE id_user = ?"
                    );
                    $stmtUpdate->execute([$namaBaru, $usernameBaru, $namaFotoBaru, $_SESSION['id_user']]);
                }

                // Sinkronkan session supaya sidebar & topbar langsung update
                $_SESSION['nama_lengkap'] = $namaBaru;
                $_SESSION['username']     = $usernameBaru;

                header("Location: " . BASE_URL . "admin/edit_profil.php?sukses=Profil berhasil diperbarui&aksi=ubah");
                exit;
            } catch (PDOException $e) {
                $errorPesan = "Gagal menyimpan perubahan. Username mungkin sudah dipakai.";
            }
        }
    }

    // Muat ulang data terbaru untuk ditampilkan bila terjadi error
    $stmtUser->execute([$_SESSION['id_user']]);
    $dataUser = $stmtUser->fetch();
}

include __DIR__ . '/template/header.php';
?>

            <?php if ($errorPesan): ?>
                <div class="alert alert-danger"><i class="bi bi-x-circle me-1"></i> <?= htmlspecialchars($errorPesan) ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center p-4">
                            <div class="mx-auto mb-3" style="width:100px;height:100px;border-radius:50%;overflow:hidden;border:3px solid #f2b84b;">
                                <img id="previewFoto"
                                     src="<?= $dataUser['foto'] ? BASE_URL . 'uploads/profil/' . htmlspecialchars($dataUser['foto']) : 'https://via.placeholder.com/100' ?>"
                                     alt="Foto Profil" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars($dataUser['nama_lengkap']) ?></h6>
                            <small class="text-muted text-uppercase"><?= htmlspecialchars(ucfirst($dataUser['role'])) ?></small>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header fw-bold bg-white">Ubah Data Profil</div>
                        <div class="card-body p-4">
                            <form method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Nama Lengkap</label>
                                    <input type="text" name="nama_lengkap" class="form-control"
                                           value="<?= htmlspecialchars($dataUser['nama_lengkap']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Username</label>
                                    <input type="text" name="username" class="form-control"
                                           value="<?= htmlspecialchars($dataUser['username']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Password Baru <span class="text-muted fw-normal">(kosongkan jika tidak diubah)</span></label>
                                    <input type="password" name="password" class="form-control" placeholder="••••••••">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">Foto Profil</label>
                                    <input type="file" name="foto" id="inputFoto" class="form-control" accept=".jpg,.jpeg,.png,.webp">
                                    <small class="text-muted">Format JPG, PNG, atau WEBP.</small>
                                </div>
                                <button type="submit" class="btn" style="background:#2fe6c8;color:#070b1a;font-weight:700;">
                                    <i class="bi bi-check-circle me-1"></i> Simpan Perubahan
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

<script>
    // Pratinjau foto sebelum diunggah
    document.getElementById('inputFoto')?.addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            document.getElementById('previewFoto').src = URL.createObjectURL(file);
        }
    });
</script>

<?php include __DIR__ . '/template/footer.php'; ?>