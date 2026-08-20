/**
 * Efek suara notifikasi, dipisah per jenis event (masing-masing beda nada,
 * TIDAK sama dengan suara login "benar"):
 * - Login berhasil / gagal    -> mainkanSuaraBenar() / mainkanSuaraSalah()
 * - Tambah data berhasil      -> mainkanSuaraTambah()
 * - Edit/update data berhasil -> mainkanSuaraUbah()
 * - Hapus data berhasil       -> mainkanSuaraHapus()
 * - Booking berhasil          -> mainkanSuaraBookingBerhasil()
 * - Pembayaran berhasil       -> mainkanSuaraPembayaranBerhasil()
 */

// Path lokasi audio
// PENTING: sebelumnya path ini di-hardcode ke '/parkir/...', jadi kalau folder
// project di htdocs/www diberi nama lain (mis. parkir_tirta_tamansari), semua
// file audio gagal dimuat (404) dan otomatis jatuh ke nada sintesis (fallback)
// -- inilah sebabnya suara hapus terdengar "beda"/"lama" padahal sampah.mp3
// sudah benar. Sekarang base URL diambil dari window.APP_BASE_URL yang
// di-inject oleh PHP (BASE_URL, auto-detect), sehingga selalu cocok dengan
// nama folder project yang sebenarnya di server manapun.
const AUDIO_BASE_URL = (typeof window !== 'undefined' && window.APP_BASE_URL)
    ? window.APP_BASE_URL
    : '/parkir/'; // fallback kalau APP_BASE_URL belum sempat di-set
const SUARA_BENAR_URL = AUDIO_BASE_URL + 'assets/audio/benar_baru.mp3';
const SUARA_SALAH_URL = AUDIO_BASE_URL + 'assets/audio/salah_baru.mp3';
const SUARA_BAYAR_URL = AUDIO_BASE_URL + 'assets/audio/bayar.mp3';
const SUARA_HAPUS_URL = AUDIO_BASE_URL + 'assets/audio/sampah.mp3';

function _mainkanFileAtauFallback(url, fallbackFn) {
    const audio = new Audio(url);
    let sudahFallback = false;

    audio.addEventListener('error', function () {
        if (!sudahFallback) { sudahFallback = true; fallbackFn(); }
    });

    audio.play().catch(function (err) {
        console.warn('Autoplay diblokir atau file gagal dimuat:', err);
        if (!sudahFallback) { sudahFallback = true; fallbackFn(); }
    });
}

// ---- Suara Login ----
function mainkanSuaraBenar() {
    _mainkanFileAtauFallback(SUARA_BENAR_URL, _fallbackSuaraBenar);
}

function mainkanSuaraSalah() {
    _fallbackSuaraSalah();
}

// ---- Suara Tambah Data Berhasil (nada naik pendek, beda dari login) ----
function mainkanSuaraTambah() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        [523.25, 698.46].forEach(function (freq, i) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            const start = now + i * 0.1;
            gain.gain.setValueAtTime(0.001, start);
            gain.gain.exponentialRampToValueAtTime(0.16, start + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.2);
            osc.connect(gain).connect(ctx.destination);
            osc.start(start);
            osc.stop(start + 0.22);
        });
    } catch (e) { /* abaikan */ }
}

// ---- Suara Edit/Update Data Berhasil (nada tunggal pendek, beda dari
// login, tambah, dan hapus) ----
function mainkanSuaraUbah() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'triangle';
        osc.frequency.setValueAtTime(440, now);
        osc.frequency.linearRampToValueAtTime(554.37, now + 0.12);
        gain.gain.setValueAtTime(0.001, now);
        gain.gain.exponentialRampToValueAtTime(0.16, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.24);
        osc.connect(gain).connect(ctx.destination);
        osc.start(now);
        osc.stop(now + 0.26);
    } catch (e) { /* abaikan */ }
}

// ---- Suara Booking Berhasil (nada dua ketuk, beda dari login) ----
function mainkanSuaraBookingBerhasil() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        [659.25, 987.77].forEach(function (freq, i) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'triangle';
            osc.frequency.value = freq;
            const start = now + i * 0.14;
            gain.gain.setValueAtTime(0.001, start);
            gain.gain.exponentialRampToValueAtTime(0.2, start + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.22);
            osc.connect(gain).connect(ctx.destination);
            osc.start(start);
            osc.stop(start + 0.24);
        });
    } catch (e) { /* abaikan */ }
}

// ---- Suara Pembayaran Berhasil (pakai file bayar.mp3) ----
function mainkanSuaraPembayaranBerhasil() {
    _mainkanFileAtauFallback(SUARA_BAYAR_URL, _fallbackSuaraPembayaran);
}

// ---- Suara Hapus Item (pakai file sampah.mp3, beda dari suara login "benar",
// booking, dan pembayaran; fallback ke nada sintesis kalau file gagal dimuat) ----
function mainkanSuaraHapus() {
    _mainkanFileAtauFallback(SUARA_HAPUS_URL, _fallbackSuaraHapus);
}

function _fallbackSuaraHapus() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(392, now);          // G4
        osc.frequency.exponentialRampToValueAtTime(196, now + 0.22); // turun ke G3
        gain.gain.setValueAtTime(0.001, now);
        gain.gain.exponentialRampToValueAtTime(0.14, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.28);
        osc.connect(gain).connect(ctx.destination);
        osc.start(now);
        osc.stop(now + 0.3);
    } catch (e) { /* abaikan */ }
}

// Supaya bisa dipanggil manual dari HTML/PHP: onclick="mainkanSuaraBenar()" dsb.
window.mainkanSuaraBenar = mainkanSuaraBenar;
window.mainkanSuaraSalah = mainkanSuaraSalah;
window.mainkanSuaraTambah = mainkanSuaraTambah;
window.mainkanSuaraUbah = mainkanSuaraUbah;
window.mainkanSuaraBookingBerhasil = mainkanSuaraBookingBerhasil;
window.mainkanSuaraPembayaranBerhasil = mainkanSuaraPembayaranBerhasil;
window.mainkanSuaraHapus = mainkanSuaraHapus;

// ---- Nada sintesis cadangan untuk login (dipakai jika file audio tidak ditemukan/gagal) ----
function _fallbackSuaraBenar() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        [523.25, 659.25, 783.99].forEach(function (freq, i) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = freq;
            const start = now + i * 0.09;
            gain.gain.setValueAtTime(0.001, start);
            gain.gain.exponentialRampToValueAtTime(0.18, start + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.28);
            osc.connect(gain).connect(ctx.destination);
            osc.start(start);
            osc.stop(start + 0.3);
        });
    } catch (e) { /* abaikan */ }
}

function _fallbackSuaraSalah() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'square';
        osc.frequency.setValueAtTime(220, now);
        osc.frequency.linearRampToValueAtTime(110, now + 0.35);
        gain.gain.setValueAtTime(0.001, now);
        gain.gain.exponentialRampToValueAtTime(0.15, now + 0.02);
        gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.4);
        osc.connect(gain).connect(ctx.destination);
        osc.start(now);
        osc.stop(now + 0.4);
    } catch (e) { /* abaikan */ }
}

// Fallback pembayaran (nada "cha-ching") kalau bayar.mp3 gagal dimuat
function _fallbackSuaraPembayaran() {
    try {
        const ctx = new (window.AudioContext || window.webkitAudioContext)();
        const now = ctx.currentTime;
        [880, 1318.51].forEach(function (freq, i) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'square';
            osc.frequency.value = freq;
            const start = now + i * 0.06;
            gain.gain.setValueAtTime(0.001, start);
            gain.gain.exponentialRampToValueAtTime(0.12, start + 0.015);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.18);
            osc.connect(gain).connect(ctx.destination);
            osc.start(start);
            osc.stop(start + 0.2);
        });
        const osc2 = ctx.createOscillator();
        const gain2 = ctx.createGain();
        osc2.type = 'sine';
        osc2.frequency.value = 1567.98;
        const start2 = now + 0.16;
        gain2.gain.setValueAtTime(0.001, start2);
        gain2.gain.exponentialRampToValueAtTime(0.18, start2 + 0.02);
        gain2.gain.exponentialRampToValueAtTime(0.0001, start2 + 0.4);
        osc2.connect(gain2).connect(ctx.destination);
        osc2.start(start2);
        osc2.stop(start2 + 0.42);
    } catch (e) { /* abaikan */ }
}

// Deteksi otomatis alert Bootstrap pada halaman.
document.addEventListener('DOMContentLoaded', function () {
    const elDanger = document.querySelector('.alert-danger');
    const elSuccess = document.querySelector('.alert-success');

    if (elDanger) {
        mainkanSuaraSalah();
        return;
    }

    if (elSuccess) {
        const jenis = elSuccess.getAttribute('data-sound');
        if (jenis === 'booking') {
            mainkanSuaraBookingBerhasil();
        } else if (jenis === 'pembayaran') {
            mainkanSuaraPembayaranBerhasil();
        } else if (jenis === 'hapus') {
            mainkanSuaraHapus();
        } else if (jenis === 'tambah') {
            mainkanSuaraTambah();
        } else if (jenis === 'ubah') {
            mainkanSuaraUbah();
        } else if (jenis === 'login') {
            mainkanSuaraBenar();
        } else {
            // Default kalau tidak ada data-sound sama sekali (mis. alert lama
            // yang belum ditandai): pakai nada ubah, BUKAN suara login, supaya
            // tidak tertukar dengan suara login berhasil.
            mainkanSuaraUbah();
        }
    }
});