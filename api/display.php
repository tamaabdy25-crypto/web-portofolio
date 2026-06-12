<?php 
// Melepas session_start & cek login agar bisa diakses umum (Display)
include 'koneksi.php'; 
date_default_timezone_set('Asia/Jakarta');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>E-VISION - Display</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-light: #f1f5f9;
            --card-white: #ffffff;
            --accent-green: #10b981;
            --text-dark: #1e293b;
            --text-muted: #64748b;
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-light); 
            padding: 20px; 
            margin: 0; 
            min-height: 100vh;
            $user_wallpaper
        }

        .display-container { max-width: 1200px; margin: auto; }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: stretch; /* TINGGI KANAN = KIRI */
            margin-bottom: 20px; 
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        /* --- CSS LOGO KIRI (WARNA DIUBAH PAKE CSS) --- */
        .logo-split-container { 
            height: 45px; 
            display: flex;
            align-items: center;
            margin-bottom: 12px;
            filter: drop-shadow(0px 2px 3px rgba(0, 0, 0, 0.15)); 
        }
        
        .logo-split-container img {
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .logo-part-e {
            margin-right: 2px; 
            filter: brightness(0) invert(55%) sepia(61%) saturate(464%) hue-rotate(108deg) brightness(96%) contrast(95%);
        }

        .logo-part-vision {
            filter: brightness(0); 
        }

        /* --- CSS LOGO KANAN BARU --- */
        .logo-asli-container {
            height: 65px; 
            display: flex;
            align-items: center;
            filter: drop-shadow(0px 2px 3px rgba(0, 0, 0, 0.15)); 
        }

        .logo-asli-container img {
            height: 100%;
            object-fit: contain;
            display: block;
        }
        
        #full-date { 
            font-weight: 700; 
            color: var(--accent-green); 
            margin: 2px 0;
            font-size: 1rem;
        }

        .weather-mini {
            display: flex;
            gap: 15px;
            margin-top: 12px;
        }

        .weather-item {
            padding: 10px 18px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: none;
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 6px 6px 12px #d1d5db, -6px -6px 12px #ffffff;
            min-width: 140px;
        }

        .weather-item i { 
            font-size: 1.8rem; 
            filter: drop-shadow(2px 3px 2px rgba(0,0,0,0.15));
            transition: all 0.5s ease;
        }

        .weather-item span { font-size: 14px; font-weight: 800; }
        .weather-item label { font-size: 10px; font-weight: 700; text-transform: uppercase; margin: 0; display: block; }

        .weather-sunny { background: linear-gradient(135deg, #FFF9C4, #FFF176); color: #F57F17; }
        .weather-cloudy { background: linear-gradient(135deg, #E3F2FD, #90CAF9); color: #1976D2; }
        .weather-rainy { background: linear-gradient(135deg, #ECEFF1, #B0BEC5); color: #455A64; }

        .clock-box { 
            background: var(--text-dark); 
            color: #fff; 
            padding: 12px 25px; 
            border-radius: 15px; 
            font-size: 36px; 
            font-weight: 800;
            box-shadow: 0 8px 15px rgba(0,0,0,0.15);
            letter-spacing: 2px;
            font-variant-numeric: tabular-nums; 
            min-width: 200px; 
            text-align: center;
        }

        #data-meeting table { width: 100%; border-collapse: separate; border-spacing: 0 10px; }
        #data-meeting tr { 
            background: var(--card-white); 
            box-shadow: 0 4px 6px rgba(0,0,0,0.02); 
            transition: transform 0.2s;
        }
        #data-meeting td { padding: 18px 25px; border: none; vertical-align: middle; }
        #data-meeting td:first-child { border-radius: 15px 0 0 15px; }
        #data-meeting td:last-child { border-radius: 0 15px 15px 0; }

        .meeting-title { font-weight: 700; color: var(--text-dark); font-size: 1.1rem; }
        .meeting-time { font-weight: 600; color: var(--accent-green); }
        
        /* WADAH KANAN */
        .right-box {
            display: flex;
            flex-direction: column;
            align-items: flex-end; 
            justify-content: space-between; /* JAM KEDORONG MENTOK BAWAH SEJAJAR KIRI */
        }

        /* =================================================================
           🔥 SEKSI TAMBAHAN SAKTI: ATURAN RESPONSIVE ENGINE (ANTI-BERANTAKAN) 🔥
           ================================================================= */
        @media (max-width: 992px) {
            .header-section {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 25px;
            }
            .brand-box {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .logo-split-container {
                justify-content: center;
            }
            .right-box {
                align-items: center;
                gap: 15px;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 12px;
            }
            .weather-mini {
                flex-direction: column;
                width: 100%;
                gap: 10px;
            }
            .weather-item {
                width: 100%;
                justify-content: center;
            }
            .clock-box {
                font-size: 28px;
                padding: 10px 20px;
                min-width: 100%;
            }
            
            /* TRANFORMA TANGGAPAN TABEL MENJADI FORMAT CARD FLUID */
            #data-meeting table, 
            #data-meeting thead, 
            #data-meeting tbody, 
            #data-meeting th, 
            #data-meeting td, 
            #data-meeting tr { 
                display: block !important; 
                width: 100% !important; 
            }
            #data-meeting thead { 
                display: none !important; /* Sembunyikan header tabel di mobile */
            }
            #data-meeting tr { 
                margin-bottom: 15px !important; 
                border-radius: 16px !important;
                background: var(--card-white);
                box-shadow: 0 4px 12px rgba(0,0,0,0.04) !important;
                padding: 12px 0;
            }
            #data-meeting td { 
                text-align: center !important; 
                padding: 8px 20px !important; 
                white-space: normal !important; 
                border-radius: 0 !important;
            }
            /* Styling khusus elemen di dalam baris agar tetap sedap dipandang */
            .meeting-title {
                font-size: 1rem;
            }
            .room-badge {
                display: inline-block;
                margin-top: 4px;
            }
        }
    </style>
</head>
<body>

<div class="display-container">
    <div class="header-section">
        
        <div class="brand-box">
            <div class="logo-split-container">
                <img src="logo_e.png" alt="E-" class="logo-part-e">
                <img src="logo_vision.png" alt="VISION" class="logo-part-vision">
            </div>
            
            <p id="full-date">Memuat Tanggal...</p>
            
            <div class="weather-mini">
                <div id="card-today" class="weather-item weather-cloudy">
                    <i id="icon-today" class="bi bi-cloud-sun-fill"></i>
                    <div>
                        <label>Hari Ini</label>
                        <span id="temp-today">--°C</span>
                    </div>
                </div>
                <div id="card-tomorrow" class="weather-item weather-cloudy">
                    <i id="icon-tomorrow" class="bi bi-clouds-fill"></i>
                    <div>
                        <label>Besok</label>
                        <span id="temp-tomorrow">--°C</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="right-box">
            <div class="logo-asli-container">
                <img src="logo_energia.png" alt="Logo Kanan">
            </div>
            <div class="clock-box" id="digital-clock">00:00:00</div>
        </div>

    </div>
    
    <div id="data-meeting">
        <p style="text-align:center; padding: 40px; color:var(--text-muted);">
            <span class="spinner-border spinner-border-sm me-2"></span>Menghubungkan ke server...
        </p>
    </div>
</div>

<script>
    let serverTime; 
    let lastSync = 0;

    function applyWeatherStyle(elementId, iconId, code) {
        const card = document.getElementById(elementId);
        const icon = document.getElementById(iconId);
        
        if (code === undefined || code === null) return;
        
        card.classList.remove('weather-sunny', 'weather-cloudy', 'weather-rainy');
        
        if (code === 0 || code === 1) { 
            card.classList.add('weather-sunny');
            icon.className = 'bi bi-sun-fill';
        } else if (code >= 2 && code <= 48) { 
            card.classList.add('weather-cloudy');
            icon.className = 'bi bi-cloud-sun-fill';
        } else { 
            card.classList.add('weather-rainy');
            icon.className = 'bi bi-cloud-rain-heavy-fill';
        }
    }

    function updateWeather() {
        fetch('https://api.open-meteo.com/v1/forecast?latitude=-6.2088&longitude=106.8456&current_weather=true&daily=temperature_2m_max,weather_code,weathercode&timezone=Asia%2FJakarta')
            .then(response => response.json())
            .then(data => {
                const current = data.current_weather;
                const codeToday = (current.weather_code !== undefined) ? current.weather_code : current.weathercode;
                document.getElementById('temp-today').innerText = Math.round(current.temperature) + '°C';
                applyWeatherStyle('card-today', 'icon-today', codeToday);
                
                const daily = data.daily;
                const codeTomorrow = (daily.weather_code !== undefined) ? daily.weather_code[1] : daily.weathercode[1];
                document.getElementById('temp-tomorrow').innerText = Math.round(daily.temperature_2m_max[1]) + '°C';
                applyWeatherStyle('card-tomorrow', 'icon-tomorrow', codeTomorrow);
            })
            .catch(err => console.error("Gagal cuaca:", err));
    }

    function loadMeetingData() {
        const startTime = Date.now();
        fetch('fetch_data.php?t=' + new Date().getTime())
            .then(response => {
                const serverDateHeader = response.headers.get('Date');
                if (serverDateHeader) {
                    const latency = (Date.now() - startTime) / 2; 
                    let dateUTC = new Date(serverDateHeader);
                    let jakartaTime = new Date(dateUTC.toLocaleString("en-US", {timeZone: "Asia/Jakarta"}));
                    serverTime = new Date(jakartaTime.getTime() + latency);
                    lastSync = Date.now();
                }
                return response.text();
            })
            .then(data => {
                document.getElementById('data-meeting').innerHTML = data;
            });
    }

    function updateClock() {
        if (!serverTime || lastSync === 0) return;
        const now = new Date(serverTime.getTime() + (Date.now() - lastSync));

        let hh = String(now.getHours()).padStart(2, '0');
        let mm = String(now.getMinutes()).padStart(2, '0');
        let ss = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('digital-clock').innerText = `${hh}:${mm}:${ss}`;

        const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        document.getElementById('full-date').innerText = `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
    }

    updateWeather();
    loadMeetingData();
    
    setInterval(updateWeather, 600000); 
    setInterval(updateClock, 1000);     
    setInterval(loadMeetingData, 5000); 
</script>
</body>
</html>
