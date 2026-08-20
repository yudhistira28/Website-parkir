</div> <!-- Penutup .tirta-body -->
</div> <!-- Penutup .tirta-content -->

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.APP_BASE_URL = "<?= BASE_URL ?>";</script>
<script src="<?= BASE_URL ?>assets/js/sound-effect.js"></script>

<script>
// ==== LONCENG NOTIFIKASI OPERATOR (booking baru & pembatalan oleh member) ====
(function () {
    const BASE = '<?= BASE_URL ?>operator/';
    const bellBadge   = document.getElementById('bellBadge');
    const daftarEl    = document.getElementById('daftarNotifikasi');
    const tombolLonceng = document.getElementById('tombolLonceng');
    if (!bellBadge || !daftarEl || !tombolLonceng) return;

    let idTerbesarDiketahui = 0;
    let pertamaKali = true;

    function bunyikanNotifikasiLonceng() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.type = 'sine';
            osc.frequency.value = 760;
            gain.gain.setValueAtTime(0.2, ctx.currentTime);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } catch (e) { /* browser belum mengizinkan audio */ }
    }

    function waktuRelatif(waktuStr) {
        const detik = Math.floor((Date.now() - new Date(waktuStr.replace(' ', 'T'))) / 1000);
        if (detik < 60) return 'Baru saja';
        if (detik < 3600) return Math.floor(detik / 60) + ' menit lalu';
        if (detik < 86400) return Math.floor(detik / 3600) + ' jam lalu';
        return Math.floor(detik / 86400) + ' hari lalu';
    }

    function renderDaftar(daftar) {
        if (!daftar.length) {
            daftarEl.innerHTML = '<div class="notif-kosong">Belum ada notifikasi.</div>';
            return;
        }
        daftarEl.innerHTML = daftar.map(function (n) {
            return '<div class="notif-item' + (n.dibaca == 0 ? ' belum-dibaca' : '') + '">'
                 + n.pesan
                 + '<span class="notif-waktu">' + waktuRelatif(n.waktu_notifikasi) + '</span>'
                 + '</div>';
        }).join('');
    }

    function cekNotifikasi() {
        fetch(BASE + 'ambil_notifikasi.php')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                bellBadge.textContent = data.jumlah_belum_dibaca;
                bellBadge.style.display = data.jumlah_belum_dibaca > 0 ? 'flex' : 'none';
                renderDaftar(data.daftar);

                const idTerbaruSekarang = data.daftar.length ? data.daftar[0].id_notifikasi : 0;
                if (!pertamaKali && idTerbaruSekarang > idTerbesarDiketahui) {
                    bunyikanNotifikasiLonceng();
                }
                idTerbesarDiketahui = Math.max(idTerbesarDiketahui, idTerbaruSekarang);
                pertamaKali = false;
            })
            .catch(function (err) { console.error(err); });
    }

    tombolLonceng.addEventListener('click', function () {
        fetch(BASE + 'tandai_notifikasi_dibaca.php').then(function () {
            bellBadge.style.display = 'none';
        });
    });

    cekNotifikasi();
    setInterval(cekNotifikasi, 8000);
})();
</script>

</body>
</html>