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



        /* Header Putih Elegan */

        .sticky-header { position: sticky; top: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(5px); z-index: 100; padding: 20px 0; border-bottom: 2px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }



        /* Card Putih Bersih */

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

           KUSTOMISASI KALENDER BIA KELIHATAN PREMIUM & RAPI

        ========================================================= */

        #calendar { font-family: 'Inter', sans-serif; color: #334155; }

        

        .fc .fc-toolbar-title { font-weight: 700; color: #1e293b; font-size: 1.3rem; }

        

        /* Kustomisasi Tombol Navigasi Kalender (Bulan, Hari, Prev, Next) */

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

        

        /* TOMBOL AKTIF BIAR TEKS TETAP PUTIH */

        .fc .fc-button-active { 

            background-color: var(--theme-primary) !important; 

            border-color: var(--theme-primary) !important; 

            color: #ffffff !important; 

            box-shadow: inset 0 0 0 100px rgba(0, 0, 0, 0.2) !important; 

        }

        

        /* Desain Header Tabel Kalender (Senin, Selasa, dll) */

        .fc-theme-standard th { 

            background-color: #f8fafc; 

            border-color: #e2e8f0; 

            padding: 12px 0; 

            color: #475569; 

            text-transform: uppercase; 

            font-size: 13px; 

            font-weight: 700;

        }

        .fc-theme-standard td, .fc-theme-standard .fc-scrollgrid { border-color: #e2e8f0; border-radius: 8px; overflow: hidden;}

        

        /* Angka Tanggal */

        .fc-daygrid-day-number { color: #334155; font-weight: 600; font-size: 14px; text-decoration: none !important; padding: 8px; }

        .fc-daygrid-day-number:hover { color: var(--theme-primary); }

        

        /* Tanggal Hari Ini */

        .fc-day-today { background-color: rgba(16, 185, 129, 0.05) !important; } 



        /* =========================================================

           FITUR: SCROLL VERTIKAL (BAWAH-ATAS) UNTUK KOTAK HARI

        ========================================================= */

        .fc-daygrid-day-events {

            max-height: 110px !important; 

            overflow-y: auto !important; 

            overflow-x: hidden !important; 

            padding-right: 2px; 

        }

        

        /* Percantik scrollbar vertikal agar tipis dan elegan */

        .fc-daygrid-day-events::-webkit-scrollbar { width: 4px; }

        .fc-daygrid-day-events::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }

        .fc-daygrid-day-events::-webkit-scrollbar-thumb:hover { background: #94a3b8; }



        /* =========================================================

           FITUR: SCROLL HORIZONTAL (KANAN-KIRI) UNTUK NAMA JADWAL

        ========================================================= */

        .fc-event { 

            border: none; 

            padding: 4px 6px; 

            border-radius: 6px; 

            font-size: 11px; 

            font-weight: 700; 

            cursor: pointer; 

            box-shadow: 0 2px 4px rgba(0,0,0,0.1);

            overflow-x: auto !important; 

            display: block; 

            margin-bottom: 3px !important;

        }

        

        .fc-event-main { 

            color: #ffffff !important; 

            white-space: nowrap !important; 

            display: inline-block; 

            min-width: 100%; 

        }



        .fc-event::-webkit-scrollbar { height: 3px; }

        .fc-event::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.5); border-radius: 4px; }

        .fc-event::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.8); }



        /* Mode List untuk HP */

        .fc-list-day-cushion { background-color: #f8fafc !important; font-weight: 700; }

        .fc-list-event-time { font-weight: 600; }

        .fc-list-event-title { font-weight: 600; }

        .fc-list-event:hover td { background-color: #f1f5f9 !important; }

        

        /* MENGGANTI IKON KOSONG MENJADI LOGO EVISION */

        .fc-list-empty {

            padding: 60px 0 !important;

            background-color: #ffffff !important;

            display: flex !important;

            flex-direction: column;

            align-items: center;

        }

        

        .fc-list-empty-cushion {

            display: block !important;

            margin-top: 15px !important;

            font-size: 15px !important;

            font-weight: 500 !important;

            color: #64748b !important;

        }



        .fc-list-empty::before {

            content: "";

            display: block;

            height: 55px;

            width: 280px;

            background-color: #64748b;

            -webkit-mask: url('logo_evision2.png') no-repeat center;

            mask: url('logo_evision2.png') no-repeat center;

            -webkit-mask-size: contain;

            mask-size: contain;

            opacity: 0.85;

        }



        @media (max-width: 768px) {

            body { padding-bottom: 0 !important; }

            .page-overlay { padding-bottom: 20px; }

            .dynamic-logo { height: 22px; width: 85px; }

            .header-title-text { font-size: 14px !important; }

            .sticky-header .btn { font-size: 0.85rem !important; padding: 5px 12px !important; }

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

// --- LOGIKA TEMA PINTAR (ANTI KEDIP) ---

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



// --- INISIALISASI FULLCALENDAR JS (VERSI API FETCH ANTI CACHE) ---

document.addEventListener('DOMContentLoaded', function() {

    var calendarEl = document.getElementById('calendar');

    var isMobile = window.innerWidth < 768;



    var calendar = new FullCalendar.Calendar(calendarEl, {

        timeZone: 'Asia/Jakarta', 

        now: '<?php echo date("Y-m-d"); ?>', 

        initialView: isMobile ? 'listMonth' : 'dayGridMonth', 

        locale: 'id', 

        height: 'auto',

        displayEventTime: false,

        headerToolbar: {

            left: 'prev,next today',

            center: 'title',

            right: isMobile ? '' : 'dayGridMonth,listWeek'

        },

        buttonText: {

            today: 'Hari Ini',

            month: 'Bulan',

            list: 'Daftar Agenda'

        },

        

        // ==============================================================

        // MENGAMBIL DATA DARI API LOKAL (api_kalender.php)

        // ==============================================================

        events: function(info, successCallback, failureCallback) {

            let timestamp = new Date().getTime(); // 💡 Bumbu anti-cache biar fetch gak malas

            

            fetch(`api_kalender.php?nocache=${timestamp}`, { cache: 'no-store' }) // <-- KUNCI SAKTI DI SINI

                .then(response => response.json())

                .then(hasil => {

                    if (hasil.status === 'success') {

                        // Mengolah data JSON sebelum dimasukkan ke kalender

                        let eventList = hasil.data.map(item => {

                            // Mewarnai jadwal berdasarkan is_done (true = abu-abu, false = warna tema)

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

                        

                        // Memasukkan data ke kalender

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
