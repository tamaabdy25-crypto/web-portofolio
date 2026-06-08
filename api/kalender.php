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
            height: 100dvh; 
            z-index: -99;
            background-color: #f1f5f9;
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

        .sticky-header { position: sticky; top: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(5px); z-index: 100; padding: 20px 0; border-bottom: 2px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }

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
           KUSTOMISASI KALENDER (TEMA MSTEAMS TIMEGRID)
        ========================================================= */
        #calendar { font-family: 'Inter', sans-serif; color: #334155; }
        
        .fc .fc-toolbar-title { font-weight: 700; color: #1e293b; font-size: 1.3rem; }
        
        .fc .fc-button-primary { 
            background-color: var(--theme-primary) !important; 
            border-color: var(--theme-primary) !important; 
            color: #ffffff !important;
            font-weight: 600; 
            text-transform: capitalize;
            border-radius: 8px;
            box-shadow: none !important;
            padding: 6px 12px;
        }
        .fc .fc-button-primary:hover { opacity: 0.85; }
        .fc .fc-button-active { 
            box-shadow: inset 0 0 0 100px rgba(0, 0, 0, 0.2) !important; 
        }
        
        .fc-theme-standard th { 
            background-color: #f8fafc; border-color: #e2e8f0; padding: 12px 0; 
            color: #475569; text-transform: uppercase; font-size: 13px; font-weight: 700;
        }
        
        /* Modifikasi Blok Event (Biar muat Teks Berlapis) */
        .fc-timegrid-event {
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
            border: none !important;
            cursor: pointer;
            transition: transform 0.1s ease;
        }
        .fc-timegrid-event:hover {
            transform: scale(1.02);
            z-index: 5 !important;
        }
        
        .fc-custom-event-content {
            padding: 4px;
            color: white;
            height: 100%;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .fc-custom-event-title {
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
            margin-bottom: 3px;
        }
        .fc-custom-event-author {
            font-size: 11px;
            opacity: 0.9;
            font-weight: 400;
        }

        /* Hilangkan kolom All-Day jika tidak dipakai */
        .fc-daygrid-body { display: none !important; }

        @media (max-width: 768px) {
            body { padding-bottom: 0 !important; }
            .page-overlay { padding-bottom: 20px; }
            .dynamic-logo { height: 22px; width: 85px; }
            .header-title-text { font-size: 14px !important; }
            .fc-toolbar { flex-direction: column; gap: 10px; }
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
        if(currentWp && currentWp === savedWp && savedColor) {
            document.documentElement.style.setProperty('--theme-primary', savedColor);
        }
    </script>    
</head>
<body id="bodyKalender">
<div class="page-overlay"> 

<div class="sticky-header mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="dynamic-logo" aria-label="E-VISION"></div>
            <span class="text-secondary fw-normal ms-2 border-start ps-2 header-title-text" style="font-size: 18px;">Kalender Agenda</span>
        </div>
        <a href="input.php" class="btn btn-outline-success btn-sm px-3 rounded-pill shadow-sm fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="container flex-grow-1"> 
    <div class="card p-4 mb-4">
        <div id='calendar'></div>
    </div>
</div>

</div> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.3.0/color-thief.umd.js"></script>
<script>
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

// --- INISIALISASI FULLCALENDAR ---
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var isMobile = window.innerWidth < 768;

    var calendar = new FullCalendar.Calendar(calendarEl, {
        timeZone: 'Asia/Jakarta', 
        now: '<?php echo date("Y-m-d"); ?>', 
        
        // 1. MENGUBAH DEFAULT VIEW KE CUSTOM VIEW 5 HARI (Atau List kalau di HP)
        initialView: isMobile ? 'listWeek' : 'timeGridFiveDay', 
        locale: 'id', 
        height: 'auto',
        
        // 2. SETTING WAKTU KANTOR (Biar nggak kepanjangan sampai tengah malam)
        slotMinTime: '07:00:00', // Jam paling atas
        slotMaxTime: '18:00:00', // Jam paling bawah
        allDaySlot: false, // Hilangkan slot Seharian
        expandRows: true,

        // 3. BIKIN CUSTOM VIEW "5 HARI DINAMIS"
        views: {
            timeGridFiveDay: {
                type: 'timeGrid',
                duration: { days: 5 }, // Nampilin 5 hari doang
                buttonText: '5 Hari'
            }
        },

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: isMobile ? '' : 'timeGridFiveDay,listWeek' // Tombol navigasinya
        },
        buttonText: {
            today: 'Hari Ini',
            list: 'Daftar Agenda'
        },
        
        // 4. KUSTOMISASI ISI KOTAK JADWAL (Nampilin Judul + Nama Pengusul)
        eventContent: function(arg) {
            let title = arg.event.title;
            let pengusul = arg.event.extendedProps.pengusul || 'Sistem';

            // HTML ini yang bakal masuk ke dalem blok warnanya
            let customHtml = `
                <div class="fc-custom-event-content">
                    <div class="fc-custom-event-title">${title}</div>
                    <div class="fc-custom-event-author"><i class="bi bi-person-fill"></i> ${pengusul}</div>
                </div>
            `;
            return { html: customHtml };
        },

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
                                extendedProps: item.extendedProps
                            };
                        });
                        successCallback(eventList);
                    } else {
                        failureCallback();
                    }
                })
                .catch(error => {
                    console.error('Error saat fetch API:', error);
                    failureCallback();
                });
        },
        
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
