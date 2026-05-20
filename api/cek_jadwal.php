<?php
// ==========================================
// OBAT ANTI-CACHE TINGKAT DEWA (PHP HEADER)
// ==========================================
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");

session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$my_id = $_SESSION['user_id'] ?? 0;

// --- TAMBAHAN: AMBIL WALLPAPER USER DARI DATABASE ---
$q_theme = pg_query($conn, "SELECT theme_wallpaper FROM users WHERE id = '$my_id'");
$data_theme = pg_fetch_assoc($q_theme);
$user_wallpaper = $data_theme['theme_wallpaper'] ?? "";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>E-VISION - Eksplorasi Jadwal</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root { --theme-primary: #10b981; }

        /* 💡 PERBAIKAN: Pisahin style body dan bikin layar kaca bayangan (body::before) buat anti-lompat */
        body { 
            background-color: #f1f5f9; 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
        }
        
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            height: 100dvh; /* Dynamic Viewport Height (Kunci Utama) */
            z-index: -99;
            background-color: #f1f5f9;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: background 0.5s ease;
        }

        .page-overlay { background: rgba(255, 255, 255, 0.2); min-height: 100vh; display: flex; flex-direction: column; width: 100%; }

        .dynamic-logo {
            height: 28px; width: 100px; background-color: var(--theme-primary);
            -webkit-mask: url('logo_evision1.png') no-repeat left center; mask: url('logo_evision1.png') no-repeat left center;
            -webkit-mask-size: contain; mask-size: contain;
            transition: background-color 0.5s ease; display: inline-block; vertical-align: middle;
        }

        .sticky-header { position: sticky; top: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(5px); z-index: 100; padding: 20px 0; border-bottom: 2px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }        
        .card { border: none; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2); background: rgba(255, 255, 255, 0.95) !important; }
        
        /* --- 💡 CSS BARU UNTUK TOMBOL KEMBALI --- */
        .btn-back-custom { 
            color: var(--theme-primary); 
            border: 1px solid var(--theme-primary); 
            background-color: transparent;
            transition: all 0.3s ease;
        }
        .btn-back-custom:hover { 
            background-color: var(--theme-primary); 
            color: white !important; 
        }
        
        .table thead { background-color: var(--theme-primary); color: white; }
        .table td, .table th { color: #000000 !important; white-space: nowrap; vertical-align: middle;} 
        .badge-ruang { background-color: #e0f2fe; color: #0369a1; font-weight: 700; padding: 5px 10px; border-radius: 8px; font-size: 12px; }
        
        .pagination .page-link { border: none; color: #64748b; cursor: pointer; background: #fff; margin: 0 3px; border-radius: 8px !important; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .pagination .page-item.active .page-link { background-color: var(--theme-primary); color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        
        /* Custom Alert Info */
        .alert-custom { background: rgba(224, 242, 254, 0.85); border: 1px solid #bae6fd; color: #0369a1; border-radius: 12px; font-weight: 500; backdrop-filter: blur(5px); }

        @media (max-width: 768px) {
            body { padding-bottom: 0 !important; }
            .page-overlay { padding-bottom: 20px; }
            .dynamic-logo { height: 22px; width: 85px; }
            .header-title-text { font-size: 14px !important; }
            .sticky-header .btn { font-size: 0.85rem !important; padding: 5px 12px !important; }
            .alert-custom { font-size: 0.8rem; padding: 10px; margin-bottom: 15px !important; }
            .card { padding: 15px !important; border-radius: 16px !important; }
            .table-responsive { flex-grow: 1; border-radius: 8px; box-shadow: inset 0 0 5px rgba(0,0,0,0.05); -webkit-overflow-scrolling: touch; }
            .table th, .table td { font-size: 0.85rem !important; padding: 10px 12px !important; }
            .badge-ruang { font-size: 0.7rem; padding: 4px 8px; }
            .pagination .page-link { padding: 5px 10px; font-size: 0.85rem; }
        }
    </style>
    
    <style>
        <?php if(!empty($user_wallpaper)): ?>
        body::before { background-image: url('<?php echo htmlspecialchars($user_wallpaper); ?>') !important; }
        <?php endif; ?>
    </style>
    
    <script>
        const currentWp = "<?php echo htmlspecialchars($user_wallpaper ?? ''); ?>";
        const savedWp = localStorage.getItem('evision_wp_final');
        const savedColor = localStorage.getItem('evision_color_final');
        if(currentWp && currentWp === savedWp && savedColor) { document.documentElement.style.setProperty('--theme-primary', savedColor); }
    </script>    
</head>
<body id="bodyIntip">
<div class="page-overlay"> 

<div class="sticky-header mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="dynamic-logo" aria-label="E-VISION"></div>
            <span class="text-secondary fw-normal ms-2 border-start ps-2 header-title-text" style="font-size: 18px;">Eksplorasi Jadwal</span>
        </div>
        <a href="input.php" class="btn btn-back-custom btn-sm px-3 rounded-pill shadow-sm fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="container flex-grow-1"> 
    
    <div class="alert alert-custom d-flex align-items-center shadow-sm mb-4" role="alert">
        <i class="bi bi-info-circle-fill fs-5 me-3" style="color: var(--theme-primary);"></i>
        <div id="infoTanggal">Menghitung jadwal...</div>
    </div>

    <div class="card p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="py-3 px-3" style="border-radius: 12px 0 0 0;"><i class="bi bi-calendar-check"></i> Tanggal</th>
                        <th class="py-3 px-3"><i class="bi bi-clock-history me-1"></i> Waktu</th>
                        <th class="py-3 px-3"><i class="bi bi-door-open me-1"></i> Ruangan</th>
                        <th class="py-3 px-3"><i class="bi bi-journal-text me-1"></i> Agenda</th>
                        <th class="py-3 px-3" style="border-radius: 0 12px 0 0;"><i class="bi bi-person-badge me-1"></i> Pengusul</th>
                    </tr>
                </thead>
                <tbody id="isiTabelIntip">
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">
                            <span class="spinner-border spinner-border-sm me-2" style="color: var(--theme-primary);"></span>
                            Mengambil data jadwal...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav class="mt-4 pt-3 border-top" id="wadahPagination" style="display: none;">
            <ul class="pagination justify-content-center mb-0" id="isiPagination"></ul>
        </nav>
    </div>

</div>

</div> 
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.3.0/color-thief.umd.js"></script>

<script>
// --- LOGIKA TEMA PINTAR ---
const wallpaperPath = "<?php echo htmlspecialchars($user_wallpaper ?? ''); ?>";
if (wallpaperPath) {
    const savedWp = localStorage.getItem('evision_wp_final');
    const savedColor = localStorage.getItem('evision_color_final');
    if (wallpaperPath !== savedWp || !savedColor) {
        const img = new Image(); img.src = wallpaperPath; img.crossOrigin = "Anonymous";
        img.onload = function() {
            const colorThief = new ColorThief(); const color = colorThief.getColor(img);
            let r = color[0], g = color[1], b = color[2];
            let brightness = (r * 299 + g * 587 + b * 114) / 1000;
            if (brightness > 180) { r = 30; g = 41; b = 59; }
            const dynamicRGB = `rgb(${r}, ${g}, ${b})`;
            document.documentElement.style.setProperty('--theme-primary', dynamicRGB);
            localStorage.setItem('evision_wp_final', wallpaperPath);
            localStorage.setItem('evision_color_final', dynamicRGB);
        };
    } 
}

// ==============================================================
// MESIN API: LOAD JADWAL 30 HARI & PAGINATION (ANTI CACHE)
// ==============================================================
let currentHal = new URLSearchParams(window.location.search).get('hal') || 1;

function loadJadwalIntip(hal = 1) {
    let timestamp = new Date().getTime(); // Tambah bumbu nocache
    
    fetch(`api_intip.php?hal=${hal}&nocache=${timestamp}`, { cache: 'no-store' }) // <-- KUNCI SAKTI ANTI CACHE
    .then(r => r.json())
    .then(res => {
        if(res.status !== 'success') return;
        
        // Update teks info tanggal
        document.getElementById('infoTanggal').innerHTML = `Menampilkan jadwal dari <b>${res.info_mulai}</b> sampai <b>${res.info_selesai}</b>.`;

        let htmlTabel = '';
        if (res.data.length === 0) {
            htmlTabel = `
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <div style="margin: 0 auto 15px auto; height: 55px; width: 280px; background-color: #64748b; -webkit-mask: url('logo_evision2.png') no-repeat center; mask: url('logo_evision2.png') no-repeat center; -webkit-mask-size: contain; mask-size: contain; opacity: 0.85;"></div>
                        <span style="font-size: 15px; font-weight: 500;">Belum ada jadwal dalam 30 hari ke depan.</span>
                    </td>
                </tr>`;
        } else {
            res.data.forEach(row => {
                let safeTitle = row.title.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                let safePengusul = row.nama_pengusul.replace(/'/g, "\\'").replace(/"/g, '&quot;');

                // Cek apakah API ngirim `start_time` atau `jam_mulai`.
                let waktuMulai = row.start_time || row.jam_mulai || '00:00';
                let waktuSelesai = row.end_time || row.jam_selesai || '00:00';

                htmlTabel += `
                    <tr>
                        <td class="px-3 fw-bold text-dark">${row.tanggal_format}</td>
                        <td class="px-3 text-success fw-bold" style="color: var(--theme-primary) !important;">
                            <i class="bi bi-play-circle-fill me-1" style="font-size: 0.8rem;"></i>${waktuMulai.substring(0, 5)} 
                            <span class="text-muted mx-1">-</span> 
                            <i class="bi bi-stop-circle-fill me-1" style="font-size: 0.8rem;"></i>${waktuSelesai.substring(0, 5)}
                        </td>
                        <td class="px-3"><span class="badge-ruang">${row.room_name}</span></td>
                        <td class="px-3 fw-semibold text-dark">${safeTitle}</td>
                        <td class="px-3 text-muted"><small class="fw-bold"><i class="bi bi-person-circle me-1" style="color: var(--theme-primary);"></i> ${safePengusul}</small></td>
                    </tr>`;
            });
        }
        document.getElementById('isiTabelIntip').innerHTML = htmlTabel;

        // Render Pagination
        let wadahPage = document.getElementById('wadahPagination');
        if(res.total_halaman > 1) {
            let htmlPage = '';
            for(let i=1; i<=res.total_halaman; i++) {
                let isAct = (i == res.halaman_sekarang) ? 'active' : '';
                htmlPage += `<li class="page-item ${isAct}"><a class="page-link" onclick="loadJadwalIntip(${i})">${i}</a></li>`;
            }
            document.getElementById('isiPagination').innerHTML = htmlPage;
            wadahPage.style.display = 'block';
            currentHal = res.halaman_sekarang;
        } else {
            wadahPage.style.display = 'none';
        }
    })
    .catch(err => {
        document.getElementById('isiTabelIntip').innerHTML = `<tr><td colspan="5" class="text-center text-danger py-4">Gagal memuat jadwal. Cek koneksi Anda.</td></tr>`;
    });
}

// Langsung panggil saat halaman terbuka
loadJadwalIntip(currentHal);
</script>
</body>
</html>
