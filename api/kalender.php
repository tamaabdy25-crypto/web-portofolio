<?php
date_default_timezone_set('Asia/Jakarta'); // 1. TAMBAHAN: Paksa PHP pakai waktu Indonesia
session_start();
include 'koneksi.php';

if (!isset($_SESSION['logged_in'])) {
    header("Location: login.php");
    exit;
}

$my_id = $_SESSION['user_id'] ?? 0;

// --- AMBIL WALLPAPER USER DARI DATABASE ---
$q_theme = pg_query($conn, "SELECT theme_wallpaper FROM users WHERE id = '$my_id'");
$data_theme = pg_fetch_assoc($q_theme);
$user_wallpaper = $data_theme['theme_wallpaper'] ?? "";

// PERHATIKAN: Kode ambil data kalender ($query_kalender) SUDAH DIHAPUS. 
// Kalender sekarang akan mengambil data lewat file API (api_kalender.php)
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>E-VISION - Kalender Agenda</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <style>
        :root { --theme-primary: #10b981; }

        /* 💡 PERBAIKAN: Disamain warna dasarnya sama Dashboard */
        body { 
            background-color: #f8f9fa; 
            font-family: 'Inter', sans-serif; 
            color: #334155; 
            margin: 0; 
        }
        
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100vh;
            height: 100dvh; 
            z-index: -99;
            background-color: #f8f9fa;
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            transition: background 0.5s ease;
        }

        .page-overlay {
            background: rgba(255, 255, 255, 0.2); 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        /* --- LOGO DINAMIS --- */
        .dynamic-logo {
            height: 28px; width: 100px;
            background-color: var(--theme-primary);
            -webkit-mask: url('logo_evision1.png') no-repeat left center;
            -webkit-mask-size: contain;
            mask: url('logo_evision1.png') no-repeat left center;
            mask-size: contain;
            transition: background-color 0.5s ease;
            display: inline-block; vertical-align: middle;
        }

        /* 🔥 FIX 1: HEADER DISAMAKAN CSS-NYA DENGAN DASHBOARD */
        .navbar { background-color: rgba(255, 255, 255, 0.95); backdrop-filter: blur(5px); border-bottom: 2px solid #e2e8f0; padding: 15px 0; }        
        
        /* 🔥 FIX 2: CARD DISAMAKAN CSS-NYA DENGAN DASHBOARD */
        .card { 
            border: none; border-radius: 12px; 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2); 
            background: rgba(255, 255, 255, 0.95) !important; 
        }
        
        .btn-outline-success { color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; }
        .btn-outline-success:hover, .btn-outline-success:focus, .btn-outline-success:active { 
            background-color: var(--theme-primary) !important; color: white !important; border-color: var(--theme-primary) !important; box-shadow: none !important; 
        }

        /* =========================================================
           KUSTOMISASI KALENDER MS TEAMS (BERSIH DARI BIRU WARNET)
        ========================================================= */
        #calendar { font-family: 'Inter', sans-serif; color: #334155; }
        
        .fc .fc-toolbar-title { font-weight: 700; color: #0f172a; font-size: 1.4rem; letter-spacing: -0.5px; }
        
        /* Tombol Navigasi Kalender - Balik Abu-abu Elegan */
        .fc .fc-button-primary { 
            background-color: #f1f5f9 !important; 
            border: 1px solid #cbd5e1 !important; 
            color: #475569 !important;
            font-weight: 600; 
            text-transform: capitalize;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
            padding: 6px 14px;
            transition: all 0.2s ease;
        }
        .fc .fc-button-primary:hover { background-color: #e2e8f0 !important; color: #0f172a !important; }
        
        /* Tombol Aktif Pakai Tema */
        .fc .fc-button-active { 
            background-color: var(--theme-primary) !important; 
            border-color: var(--theme-primary) !important; 
            color: #fff !important; 
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.1) !important;
        }
        
        /* Header Hari (Sen, Sel) */
        .fc-theme-standard th { 
            background-color: #f8fafc; border-color: #e2e8f0; padding: 14px 0; 
            text-transform: uppercase; font-size: 12px; font-weight: 700;
            border-bottom: 2px solid #e2e8f0 !important;
        }
        .fc-theme-standard td, .fc-theme-standard .fc-scrollgrid { border-color: #e2e8f0; border-radius: 12px; overflow: hidden; }
        
        /* FIX: Ilangin link biru warnet */
        .fc-col-header-cell-cushion { 
            color: #64748b !important; text-decoration: none !important; transition: color 0.2s;
        }
        .fc-col-header-cell-cushion:hover { color: var(--theme-primary) !important; }

        /* FIX: Background 'Hari Ini' ngikutin warna tema */
        .fc-col-header-cell.fc-day-today { background-color: color-mix(in srgb, var(--theme-primary) 8%, white) !important; }
        .fc-col-header-cell.fc-day-today .fc-col-header-cell-cushion { color: var(--theme-primary) !important; font-weight: 800; }

        /* Label Jam di kiri (+WIB) */
        .fc-timegrid-slot-label-cushion { font-size: 13px; font-weight: 600; color: #64748b; padding-right: 10px !important; }
        .fc-timegrid-slot-label-cushion::after { content: ' WIB'; font-size: 10px; font-weight: 500; opacity: 0.7; }
        
        /* Garis Merah Real-time */
        .fc-timegrid-now-indicator-line { border-color: #ef4444; border-width: 2px; }
        .fc-timegrid-now-indicator-arrow { border-color: #ef4444; background-color: #ef4444; border-width: 5px; }

        /* Modifikasi Blok Event */
        .fc-timegrid-event {
            border-radius: 6px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05) !important; cursor: pointer; transition: all 0.15s ease;
        }
        .fc-timegrid-event:hover { transform: scale(1.02); box-shadow: 0 4px 8px rgba(0,0,0,0.15); z-index: 5 !important; }
        .fc-custom-event-content { padding: 5px 8px; color: white; display: flex; flex-direction: column; overflow: hidden; }
        .fc-custom-event-title { font-size: 12px; font-weight: 700; line-height: 1.3; margin-bottom: 2px; }
        .fc-custom-event-author { font-size: 11px; opacity: 0.9; font-weight: 500; }

        /* Hilangin area all-day */
        .fc-daygrid-body { display: none !important; }

        @media (max-width: 768px) {
            body { padding-bottom: 0 !important; }
            .page-overlay { padding-bottom: 20px; }
            .dynamic-logo { height: 22px; width: 85px; }
            .header-title-text { font-size: 14px !important; }
            .navbar .btn { font-size: 0.85rem !important; padding: 5px 12px !important; }
            .card { padding: 15px !important; border-radius: 16px !important; }
            .fc-toolbar { flex-direction: column; gap: 10px; }
            .fc-toolbar-title { font-size: 1.1rem !important; }
        }
    </style>
    
    <style>
        <?php if(!empty($user_wallpaper)): ?>
        body::before { background-image: url('<?php echo htmlspecialchars($user_wallpaper); ?>') !important; }
        <?php endif; ?>
    </style>
    
    <script>
        // Terapkan Tema Warna Instan
        const currentWp = "<?php echo htmlspecialchars($user_wallpaper ?? ''); ?>";
        const savedWp = localStorage.getItem('evision_wp_final');
        const savedColor = localStorage.getItem('evision_color_final');
        
        if(currentWp && currentWp === savedWp && savedColor) {
            document.documentElement.style.setProperty('--theme-primary', savedColor);
        }
    </script>    
</head>
<body id="bodyKalender">
<div class="page-overlay"> 

<nav class="navbar sticky-top mb-4 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="navbar-brand d-flex align-items-center text-decoration-none m-0" style="cursor: default;">
            <div class="dynamic-logo" aria-label="E-VISION"></div>
            <span class="text-secondary fw-normal ms-2 border-start ps-2 header-title-text" style="font-size: 18px;">Kalender Agenda</span>
        </div>
        <a href="input.php" class="btn btn-outline-success btn-sm px-3 rounded-pill shadow-sm fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</nav>

<div class="container flex-grow-1"> 
    
    <div class="col-12 d-none d-lg-block mb-4" style="height: 38px;"></div> 

    <div class="col-12">
        <div class="card p-4 mb-4">
            <div id='calendar'></div>
        </div>
    </div>
</div>

</div> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.3.0/color-thief.umd.js"></script>
<script>
// --- LOGIKA TEMA PINTAR (ANTI KEDIP) DARI KODINGAN ASLI LU ---
const wallpaperPath = "<?php echo htmlspecialchars($user_wallpaper ?? ''); ?>";
if (wallpaperPath) {
    const savedWp = localStorage.getItem('evision_wp_final');
    const savedColor = localStorage.getItem('evision_color_final');

    if (wallpaperPath !== savedWp || !savedColor) {
        const img = new Image();
        img.src = wallpaperPath;
        img.crossOrigin = "Anonymous";
        img.onload = function() {
            const colorThief = new ColorThief();
            const color = colorThief.getColor(img);
            
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

// --- INISIALISASI FULLCALENDAR JS ---
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var isMobile = window.innerWidth < 768;

    var calendar = new FullCalendar.Calendar(calendarEl, {
        timeZone: 'Asia/Jakarta', 
        now: '<?php echo date("Y-m-d"); ?>', 
        
        // 💡 UBAH KE MS TEAMS STYLE
        initialView: isMobile ? 'listMonth' : 'timeGridFiveDay', 
        locale: 'id', 
        height: 'auto',
        
        allDaySlot: false, 
        expandRows: true,
        nowIndicator: true, 
        scrollTime: '07:00:00',
        
        slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
        
        views: {
            timeGridFiveDay: {
                type: 'timeGrid',
                duration: { days: 5 }, 
            }
        },

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: isMobile ? '' : 'timeGridFiveDay,listWeek'
        },
        buttonText: {
            today: 'Hari Ini',
            list: 'Daftar Agenda'
        },

        // CUSTOM ISI KOTAK MS TEAMS
        eventContent: function(arg) {
            let title = arg.event.title;
            let pengusul = arg.event.extendedProps.pengusul || 'Sistem';
            let customHtml = `
                <div class="fc-custom-event-content">
                    <div class="fc-custom-event-title">${title}</div>
                    <div class="fc-custom-event-author"><i class="bi bi-person-fill"></i> ${pengusul}</div>
                </div>
            `;
            return { html: customHtml };
        },
        
        // ==============================================================
        // MENGAMBIL DATA DARI API LOKAL (KODINGAN LU TETAP AMAN)
        // ==============================================================
        events: function(info, successCallback, failureCallback) {
            let timestamp = new Date().getTime(); 
            
            fetch(`api_kalender.php?nocache=${timestamp}`, { cache: 'no-store' }) 
                .then(response => response.json())
                .then(hasil => {
                    if (hasil.status === 'success') {
                        let eventList = hasil.data.map(item => {
                            let warna = item.is_done ? '#94a3b8' : 'var(--theme-primary)';
                            
                            return {
                                id: item.id,
                                title: item.title,
                                start: item.start,
                                end: item.end,
                                backgroundColor: warna,
                                borderColor: warna,
                                textColor: '#ffffff',
                                extendedProps: item.extendedProps
                            };
                        });
                        successCallback(eventList);
                    } else {
                        console.error('Gagal ambil data API');
                        failureCallback();
                    }
                })
                .catch(error => {
                    console.error('Error saat fetch API:', error);
                    failureCallback();
                });
        },
        // ==============================================================
        
        // Aksi pas Kotak Jadwal diklik muncul POP-UP
        eventClick: function(info) {
            let prop = info.event.extendedProps;
            Swal.fire({
                title: `<span style="color:var(--theme-primary); font-weight:bold; font-size: 1.25rem;">${info.event.title}</span>`,
                html: `
                    <div style="text-align: left; padding: 15px; background: #f8fafc; border-radius: 12px; margin-top: 5px; color: #334155; font-size: 14px; border: 1px solid #e2e8f0;">
                        <div class="mb-3"><i class="bi bi-calendar-event text-muted me-2"></i> <b>Tanggal:</b> ${info.event.start.toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}</div>
                        <div class="mb-3"><i class="bi bi-clock text-muted me-2"></i> <b>Waktu:</b> ${prop.waktu}</div>
                        <div class="mb-3"><i class="bi bi-door-open text-muted me-2"></i> <b>Ruangan:</b> <span class="badge bg-white text-dark border shadow-sm">${prop.ruang}</span></div>
                        <div class="mb-3"><i class="bi bi-person-badge text-muted me-2"></i> <b>Pengusul:</b> ${prop.pengusul}</div>
                        <div><i class="bi bi-info-circle text-muted me-2"></i> <b>Status:</b> <span class="badge bg-light text-secondary border border-secondary">${prop.status}</span></div>
                    </div>
                `,
                confirmButtonColor: 'var(--theme-primary)',
                confirmButtonText: 'Tutup Detail',
                customClass: {
                    popup: 'rounded-4 shadow-lg'
                }
            });
        }
    });
    calendar.render();
});
</script>
</body>
</html>
