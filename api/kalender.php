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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js'></script>
    
    <style>
        :root { --theme-primary: #10b981; } /* Default warna ijo E-VISION */

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
            background: rgba(255, 255, 255, 0.4); 
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

        .sticky-header { position: sticky; top: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(5px); z-index: 100; padding: 20px 0; border-bottom: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }

        .card { 
            border: 1px solid #e2e8f0; border-radius: 16px; 
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); 
            background: #ffffff !important; 
        }
        
        .btn-outline-success { color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; }
        .btn-outline-success:hover { background-color: var(--theme-primary) !important; color: white !important; }

        /* =========================================================
           KUSTOMISASI UI KALENDER MSTEAMS / OUTLOOK (PREMIUM)
        ========================================================= */
        #calendar { font-family: 'Inter', sans-serif; color: #334155; }
        
        .fc .fc-toolbar-title { font-weight: 700; color: #0f172a; font-size: 1.4rem; letter-spacing: -0.5px; }
        
        /* TOMBOL KALENDER NGIKUTIN TEMA DINAMIS */
        .fc .fc-button-primary { 
            background-color: var(--theme-primary) !important; 
            border-color: var(--theme-primary) !important; 
            color: #ffffff !important;
            font-weight: 600; 
            text-transform: capitalize;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1) !important;
            padding: 6px 14px;
            transition: all 0.2s ease;
        }
        .fc .fc-button-primary:hover { opacity: 0.85; }
        .fc .fc-button-active { 
            box-shadow: inset 0 0 0 100px rgba(0, 0, 0, 0.2) !important; 
            border-color: transparent !important; 
        }
        
        /* Header Hari (Sen, Sel, Rab, dll) */
        .fc-theme-standard th { 
            background-color: #f8fafc; border-color: #e2e8f0; padding: 14px 0; 
            color: #64748b; text-transform: uppercase; font-size: 12px; font-weight: 700;
            border-bottom: 2px solid #e2e8f0 !important;
        }
        .fc-col-header-cell.fc-day-today { background-color: rgba(16, 185, 129, 0.05); color: var(--theme-primary); }
        .fc-col-header-cell.fc-day-today .fc-col-header-cell-cushion { color: var(--theme-primary); font-weight: 800; }

        /* UI JAM DI SEBELAH KIRI (Biar Awam Paham) */
        .fc-timegrid-slot-label-cushion { 
            font-size: 13px; font-weight: 600; color: #64748b; padding-right: 10px !important; 
        }
        .fc-timegrid-slot-label-cushion::after {
            content: ' WIB'; 
            font-size: 10px; font-weight: 500; opacity: 0.7;
        }
        
        /* GARIS WAKTU BERGERAK (REAL-TIME INDICATOR) */
        .fc-timegrid-now-indicator-line { border-color: #ef4444; border-width: 2px; }
        .fc-timegrid-now-indicator-arrow { border-color: #ef4444; background-color: #ef4444; border-width: 5px; }
        
        /* Modifikasi Blok Event */
        .fc-timegrid-event {
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            border: 1px solid rgba(0,0,0,0.05) !important;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .fc-timegrid-event:hover { transform: scale(1.02); box-shadow: 0 4px 8px rgba(0,0,0,0.15); z-index: 5 !important; }
        
        .fc-custom-event-content { padding: 5px 8px; color: white; display: flex; flex-direction: column; overflow: hidden; }
        .fc-custom-event-title { font-size: 12px; font-weight: 700; line-height: 1.3; margin-bottom: 2px; }
        .fc-custom-event-author { font-size: 11px; opacity: 0.9; font-weight: 500; }

        .fc-daygrid-body { display: none !important; }
        .fc-scrollgrid { border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0; }

        @media (max-width: 768px) {
            .page-overlay { padding-bottom: 20px; }
            .header-title-text { font-size: 15px !important; }
            .fc-toolbar { flex-direction: column; gap: 12px; }
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
            <span class="text-secondary fw-medium ms-2 border-start ps-2 header-title-text" style="font-size: 18px;">Kalender Agenda</span>
        </div>
        
        <!-- BAGIAN KANAN: Tombol Kembali & Profil User -->
        <div class="d-flex align-items-center gap-3">
            <a href="input.php" class="btn btn-outline-success btn-sm px-3 rounded-pill shadow-sm fw-bold">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
            
            <?php
                // Ambil data session lu untuk PP dan Nama
                // Ganti $_SESSION['nama'] atau foto sesuai dengan struktur login lu ya!
                $nama_user = $_SESSION['nama'] ?? 'User'; 
                
                // Cek apakah user punya foto di database, kalo kosong kasih foto default
                // Pastikan nama session foto-nya bener!
                if (!empty($_SESSION['foto_profil'])) {
                    $pp_path = $_SESSION['foto_profil'];
                } else {
                    $pp_path = 'https://ui-avatars.com/api/?name=' . urlencode($nama_user) . '&background=random'; // Fallback aman
                }
            ?>
            <div class="dropdown">
                <button class="btn btn-light rounded-pill border shadow-sm d-flex align-items-center px-2 py-1" type="button" data-bs-toggle="dropdown">
                    <img src="<?php echo htmlspecialchars($pp_path); ?>" alt="PP" class="rounded-circle me-2" style="width: 28px; height: 28px; object-fit: cover; border: 1px solid #e2e8f0;">
                    <span class="fw-bold fs-6 me-1 text-dark"><?php echo htmlspecialchars($nama_user); ?></span>
                    <i class="bi bi-caret-down-fill text-muted" style="font-size: 10px;"></i>
                </button>
            </div>
        </div>
        
    </div>
</div>

<div class="container flex-grow-1"> 
    <div class="card p-4 mb-5">
        <div id='calendar'></div>
    </div>
</div>

</div> 
<script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.3.0/color-thief.umd.js"></script>
<script>
// --- LOGIKA TEMA PINTAR (ANTI GAGAL) ---
const wallpaperPath = "<?php echo htmlspecialchars($user_wallpaper ?? ''); ?>";

if (wallpaperPath) {
    const savedWp = localStorage.getItem('evision_wp_final');
    const savedColor = localStorage.getItem('evision_color_final');

    if (savedWp === wallpaperPath && savedColor) {
        document.documentElement.style.setProperty('--theme-primary', savedColor);
    } else {
        const img = new Image();
        img.crossOrigin = "Anonymous";
        
        // Pastikan event onload dideklarasikan SEBELUM src di-set
        img.onload = function() {
            try {
                const colorThief = new ColorThief();
                const color = colorThief.getColor(img);
                let r = color[0], g = color[1], b = color[2];
                let brightness = (r * 299 + g * 587 + b * 114) / 1000;
                
                if (brightness > 180) { r = 30; g = 41; b = 59; }
                const dynamicRGB = `rgb(${r}, ${g}, ${b})`;
                
                document.documentElement.style.setProperty('--theme-primary', dynamicRGB);
                localStorage.setItem('evision_wp_final', wallpaperPath);
                localStorage.setItem('evision_color_final', dynamicRGB);
            } catch (e) {
                console.error("Gagal ekstrak warna dari wallpaper:", e);
            }
        };
        img.src = wallpaperPath; 
    }
}

// --- INISIALISASI FULLCALENDAR ---
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');

    var calendar = new FullCalendar.Calendar(calendarEl, {
        timeZone: 'Asia/Jakarta', 
        initialView: 'timeGridFiveDay', 
        locale: 'id', 
        height: 'auto',
        
        allDaySlot: false, 
        expandRows: true,
        nowIndicator: true, 
        scrollTime: '07:00:00', 
        
        slotLabelFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false 
        },

        views: {
            timeGridFiveDay: {
                type: 'timeGrid',
                duration: { days: 5 }, 
            }
        },

        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '' 
        },
        buttonText: {
            today: 'Hari Ini'
        },
        
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
