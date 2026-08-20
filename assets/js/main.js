// Parkir Tirta Tamansari - main.js (Aquatic Blue Theme Engine)

document.addEventListener('DOMContentLoaded', function () {
    
    // ============================================================
    // 1. FORCE DOMINANT BLUE POOL THEME (Secara Programmatic)
    // ============================================================
    function applyAquaticBlueTheme() {
        const root = document.documentElement;
        root.style.setProperty('--tirta-dark', '#003049');
        root.style.setProperty('--tirta-dark-2', '#0077b6');
        root.style.setProperty('--tirta-teal', '#00b4d8');
        root.style.setProperty('--tirta-teal-dark', '#03045e');
        root.style.setProperty('--tirta-gold', '#48cae4');
        root.style.setProperty('--tirta-gold-dark', '#023e8a');
        root.style.setProperty('--tirta-light', '#f0f8ff');
        root.style.setProperty('--tirta-muted', '#52796f');
    }
    applyAquaticBlueTheme();

    // ============================================================
    // 2. TOGGLE SIDEBAR MOBILE (Penyesuaian Responsif)
    // ============================================================
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.tirta-sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            sidebar.classList.toggle('show');
        });

        document.addEventListener('click', function (e) {
            if (sidebar.classList.contains('show') && !sidebar.contains(e.target) && e.target !== toggleBtn) {
                sidebar.classList.remove('show');
            }
        });
    }

    // ============================================================
    // 3. KONFIRMASI HAPUS DATA
    // ============================================================
    document.querySelectorAll('.btn-hapus-konfirmasi').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm('Yakin ingin menghapus data ini? Tindakan tidak dapat dibatalkan.')) {
                e.preventDefault();
            }
        });
    });

    // ============================================================
    // 4. AUTO HITUNG TARIF KENDARAAN (Aksen Biru)
    // ============================================================
    const selectJenis = document.getElementById('jenis_kendaraan_select');
    const tarifInfo = document.getElementById('info_tarif');
    if (selectJenis && tarifInfo) {
        selectJenis.addEventListener('change', function () {
            const opt = selectJenis.options[selectJenis.selectedIndex];
            const tarif = opt ? opt.getAttribute('data-tarif') : null;
            if (tarif && !isNaN(tarif)) {
                tarifInfo.innerText = 'Tarif: Rp ' + Number(tarif).toLocaleString('id-ID') + ' / jam';
                tarifInfo.style.color = '#0077b6';
                tarifInfo.style.fontWeight = 'bold';
            } else {
                tarifInfo.innerText = '';
            }
        });
    }

    // ============================================================
    // 5. AUTO DISMISS ALERT
    // ============================================================
    document.querySelectorAll('.alert-auto-dismiss').forEach(function (el) {
        setTimeout(function () {
            el.classList.remove('show');
            el.classList.add('fade');
            setTimeout(() => { if (el.parentNode) el.remove(); }, 400);
        }, 4000);
    });

    // ============================================================
    // 6. SAFE MODAL HANDLER
    // ============================================================
    document.querySelectorAll('[data-bs-toggle="modal"]').forEach(function (trigger) {
        trigger.addEventListener('click', function (e) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                return;
            }

            e.preventDefault();
            const targetSelector = trigger.getAttribute('data-bs-target');
            const modalEl = document.querySelector(targetSelector);
            if (!modalEl) return;

            modalEl.style.display = 'block';
            modalEl.classList.add('show');
            document.body.classList.add('modal-open');

            let backdrop = document.querySelector('[data-manual-backdrop="true"]');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.setAttribute('data-manual-backdrop', 'true');
                document.body.appendChild(backdrop);
            }

            function closeModal() {
                modalEl.style.display = 'none';
                modalEl.classList.remove('show');
                document.body.classList.remove('modal-open');
                const bd = document.querySelector('[data-manual-backdrop="true"]');
                if (bd) bd.remove();
            }

            modalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function (btn) {
                btn.onclick = closeModal;
            });
            backdrop.onclick = closeModal;
        });
    });
});

function cetakStruk() {
    window.print();
}