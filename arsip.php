<?php
include 'koneksi.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$my_id = $_SESSION['user_id'];

// Otomatis tambah kolom jika belum ada
$check_col = mysqli_query($conn, "SHOW COLUMNS FROM meetings LIKE 'notulensi'");
if(mysqli_num_rows($check_col) == 0) {
    mysqli_query($conn, "ALTER TABLE meetings ADD notulensi TEXT NULL AFTER is_finished, ADD daftar_hadir TEXT NULL AFTER notulensi, ADD link_lampiran VARCHAR(255) NULL AFTER daftar_hadir");
}

$q_theme = mysqli_query($conn, "SELECT theme_wallpaper FROM users WHERE id = '$my_id'");
$data_theme = mysqli_fetch_assoc($q_theme);
$user_wallpaper = $data_theme['theme_wallpaper'] ?? "";

// SEMUA LOGIKA SIMPAN & HAPUS SUDAH PINDAH KE api_action.php
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>E-VISION - Arsip</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --theme-primary: #10b981; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; color: #334155; margin: 0; transition: background 0.5s ease; background-attachment: fixed; background-size: cover; background-position: center; }
        .page-overlay { background: rgba(255, 255, 255, 0.2); min-height: 100vh; display: flex; flex-direction: column; width: 100%; }
        .dynamic-logo { height: 28px; width: 100px; background-color: var(--theme-primary); -webkit-mask: url('logo_evision1.png') no-repeat left center; -webkit-mask-size: contain; mask: url('logo_evision1.png') no-repeat left center; mask-size: contain; transition: background-color 0.5s ease; display: inline-block; vertical-align: middle; }
.sticky-header { position: sticky; top: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(5px); z-index: 100; padding: 20px 0; border-bottom: 2px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }        .card { border: none; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2); background: rgba(255, 255, 255, 0.95) !important; }
        .table thead { background-color: var(--theme-primary); color: white; }
        .table td, .table th { color: #000000 !important; white-space: nowrap; vertical-align: middle;} 
        .badge-room { background-color: #e0f2fe; color: #0369a1; font-weight: 700; padding: 5px 10px; border-radius: 8px; font-size: 12px; }
        .btn-outline-success { color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; }
        .btn-outline-success:hover { background-color: var(--theme-primary) !important; color: white !important; border-color: var(--theme-primary) !important; box-shadow: none !important; }
        .btn-print { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 600; font-size: 14px; padding: 6px 16px; border-radius: 8px; transition: all 0.2s ease; }
        .btn-print:hover { background-color: var(--theme-primary); color: white; border-color: var(--theme-primary); }
        .pagination .page-link { border: none; color: #64748b; cursor: pointer; background: #fff; margin: 0 3px; border-radius: 8px !important; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
        .pagination .page-item.active .page-link { background-color: var(--theme-primary); color: white; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .kop-surat-print { display: none; text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .kop-surat-print h2 { margin: 0; font-weight: bold; font-size: 24px; color: #000; }
        .kop-surat-print p { margin: 5px 0 0 0; font-size: 14px; color: #333; }

        /* =======================================================
           PERBAIKAN WARNA POP-UP NOTULENSI (BIAR NGILUT TEMA)
           ======================================================= */
        .modal-content .text-primary,
        .modal-content .modal-title,
        .modal-content .modal-title i {
            color: var(--theme-primary) !important;
        }

        .modal-content .btn-primary {
            background-color: var(--theme-primary) !important;
            border-color: var(--theme-primary) !important;
            color: #ffffff !important;
            transition: all 0.3s ease;
        }

        .modal-content .btn-primary:hover {
            opacity: 0.85;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        /* =======================================================
           PERBAIKAN WARNA TOMBOL POP-UP SWEETALERT (NGIKUT TEMA)
           ======================================================= */
        .swal2-styled.swal2-confirm {
            border-radius: 8px !important;
            font-weight: bold !important;
            transition: all 0.3s ease !important;
        }

        .swal2-styled.swal2-confirm:hover {
            opacity: 0.85 !important;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important;
            background-image: none !important;
        }

        @media (max-width: 768px) {
            body { padding-bottom: 0 !important; } .page-overlay { padding-bottom: 20px; } .dynamic-logo { height: 22px; width: 85px; } .header-title-text { font-size: 14px !important; } .sticky-header .btn { font-size: 0.85rem !important; padding: 5px 12px !important; } .card { padding: 15px !important; border-radius: 16px !important; min-height: calc(100vh - 120px); display: flex; flex-direction: column; } .card-header-flex { flex-direction: column; align-items: flex-start !important; gap: 15px; } .card h5 { font-size: 1.15rem; margin-bottom: 0 !important;} .table-responsive { flex-grow: 1; border-radius: 8px; box-shadow: inset 0 0 5px rgba(0,0,0,0.05); -webkit-overflow-scrolling: touch; } .table td, .table th { font-size: 0.85rem !important; padding: 10px 12px !important; } .badge-room { font-size: 0.7rem; padding: 4px 8px; } .pagination .page-link { padding: 5px 10px; font-size: 0.85rem; }
        }

        @media print {
            @page { size: auto; margin: 0 !important; } body, .page-overlay, .container, .card { background: transparent !important; margin: 0 !important; padding: 0 !important; color: #000 !important; box-shadow: none !important; min-height: auto !important; width: 100% !important; max-width: 100% !important; } body { padding: 1cm !important; } .sticky-header, .btn-print, .pagination, .table-aksi, th.table-aksi, td.table-aksi { display: none !important; } .card { border: none !important; border-radius: 0 !important; padding: 0 !important; } .card-header-flex { display: none !important; } .kop-surat-print { display: block !important; margin-bottom: 20px !important; } .table-responsive { overflow: visible !important; box-shadow: none !important; width: 100% !important; margin: 0 !important; padding: 0 !important; display: block !important; } .table { width: 100% !important; max-width: 100% !important; border-collapse: collapse !important; margin: 0 !important; table-layout: auto !important; } .table, .table tr, .table th, .table td { border: 1pt solid #000 !important; } .table tr { page-break-inside: avoid !important; } .table th, .table td { padding: 10px !important; font-size: 12pt !important; white-space: normal !important; word-wrap: break-word !important; vertical-align: top !important; } .table th { background-color: #f1f5f9 !important; color: #000 !important; print-color-adjust: exact !important; -webkit-print-color-adjust: exact !important; } .badge-room { border: 1pt solid #000 !important; background: transparent !important; color: #000 !important; padding: 4px 8px !important; display: inline-block; margin-top: 5px; }
        }
    </style>
    <style>
        <?php if(!empty($user_wallpaper)): ?>
        @media screen { body { background-image: url('<?php echo htmlspecialchars($user_wallpaper); ?>') !important; } }
        <?php endif; ?>
    </style>
    <script>
        const currentWp = "<?php echo htmlspecialchars($user_wallpaper ?? ''); ?>";
        const savedWp = localStorage.getItem('evision_wp_final');
        const savedColor = localStorage.getItem('evision_color_final');
        if(currentWp && currentWp === savedWp && savedColor) { document.documentElement.style.setProperty('--theme-primary', savedColor); }
    </script>    
</head>
<body id="bodyArsip">
<div class="page-overlay"> 

<div class="sticky-header mb-4">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="dynamic-logo" aria-label="E-VISION"></div>
            <span class="text-secondary fw-normal ms-2 border-start ps-2 header-title-text" style="font-size: 18px;">Arsip</span>
        </div>
        <a href="input.php" class="btn btn-outline-success btn-sm px-3 rounded-pill shadow-sm fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Kembali
        </a>
    </div>
</div>

<div class="container flex-grow-1"> 
    <div class="card p-4">
        
        <div class="kop-surat-print">
            <h2>E-VISION - LAPORAN ARSIP AGENDA MEETING</h2>
            <p>Dicetak pada: <?php echo date('d/m/Y H:i'); ?> WIB</p>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-4 card-header-flex">
            <h5 class="fw-bold m-0 text-dark" id="judulHalaman"><i class="bi bi-clock-history me-2 text-success"></i>Riwayat Agenda</h5>
            <button onclick="loadArsip(1, true)" class="btn btn-print shadow-sm">
                <i class="bi bi-printer-fill me-1"></i> Cetak Laporan (PDF)
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th class="py-3 px-3" style="border-radius: 12px 0 0 0;"><i class="bi bi-journal-text me-1"></i> Agenda & Pengusul</th>
                        <th class="py-3 px-3"><i class="bi bi-door-open me-1"></i> Ruangan</th>
                        <th class="py-3 px-3"><i class="bi bi-calendar-check"></i> Waktu Pelaksanaan</th>
                        <th class="py-3 px-3 text-center table-aksi" style="border-radius: 0 12px 0 0;"><i class="bi bi-gear me-1"></i> Aksi</th>
                    </tr>
                </thead>
                <tbody id="isiTabelArsip">
                    <tr><td colspan="4" class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2 text-success"></span> Mengambil data arsip...</td></tr>
                </tbody>
            </table>
        </div>

        <nav class="mt-4 pt-3 border-top" id="wadahPagination" style="display: none;">
            <ul class="pagination justify-content-center mb-0" id="isiPagination"></ul>
        </nav>
        
    </div>
</div>

<div class="modal fade" id="modalNotulensi" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header bg-light border-bottom-0 rounded-top-4">
        <h5 class="modal-title fw-bold text-primary"><i class="bi bi-journal-text me-2"></i>Detail & Notulensi Rapat</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <form id="formNotulensi" onsubmit="simpanNotulensi(event)">
          <div class="modal-body p-4">
              <input type="hidden" name="id_meeting" id="notul_id">
              <div class="mb-3">
                  <label class="fw-bold mb-2"><i class="bi bi-card-text text-primary me-1"></i> Topik Pembahasan & Hasil Keputusan</label>
                  <textarea name="notulensi" id="notul_teks" class="form-control" rows="5" placeholder="Tulis poin-poin penting, kendala, dan kesimpulan rapat di sini..." style="border-radius: 10px;"></textarea>
              </div>
              <div class="row">
                  <div class="col-md-6 mb-3 mb-md-0">
                      <label class="fw-bold mb-2"><i class="bi bi-people-fill text-success me-1"></i> Daftar Hadir</label>
                      <textarea name="daftar_hadir" id="notul_hadir" class="form-control" rows="3" placeholder="Contoh: 1. Budi, 2. Andi..." style="border-radius: 10px;"></textarea>
                  </div>
                  <div class="col-md-6">
                      <label class="fw-bold mb-2"><i class="bi bi-link-45deg text-danger me-1"></i> Link Lampiran (G-Drive / Docs)</label>
                      <input type="text" name="link_lampiran" id="notul_link" class="form-control" placeholder="Tempel link URL dokumen di sini..." style="border-radius: 10px;">
                  </div>
              </div>
          </div>
          <div class="modal-footer border-top-0 pb-4 px-4 d-flex justify-content-between">
            <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal" style="border-radius: 8px;">Tutup</button>
            <button type="submit" id="btn-notulensi" class="btn btn-primary fw-bold shadow-sm px-4" style="border-radius: 8px;"><i class="bi bi-save me-1"></i> Simpan Catatan</button>
          </div>
      </form>
    </div>
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
            const dynamicRGB = `rgb(${r}, g, b)`;
            document.documentElement.style.setProperty('--theme-primary', dynamicRGB);
            localStorage.setItem('evision_wp_final', wallpaperPath);
            localStorage.setItem('evision_color_final', dynamicRGB);
        };
    }
}

// ==============================================================
// LOAD ARSIP & MEMORI SEMENTARA
// ==============================================================
let currentHal = new URLSearchParams(window.location.search).get('hal') || 1;
let globalDataArsip = []; 

function loadArsip(hal = 1, isPrint = false) {
    let urlAPI = isPrint ? `api_arsip.php?print=semua` : `api_arsip.php?hal=${hal}`;

    fetch(urlAPI).then(r => r.json()).then(res => {
        if(res.status !== 'success') return;
        
        // Simpan data API ke memori global Javascript
        globalDataArsip = res.data; 

        document.getElementById('judulHalaman').innerHTML = `<i class="bi bi-clock-history me-2" style="color: var(--theme-primary);"></i>${isPrint ? 'Riwayat Agenda' : 'Riwayat Agenda'}`;
        let htmlTabel = '';
        
        if (globalDataArsip.length === 0) {
            htmlTabel = `<tr><td colspan="4" class="text-center py-5 text-muted"><div style="margin: 0 auto 15px auto; height: 55px; width: 280px; background-color: #64748b; -webkit-mask: url('logo_evision2.png') no-repeat center; mask: url('logo_evision2.png') no-repeat center; -webkit-mask-size: contain; mask-size: contain; opacity: 0.85;"></div><span style="font-size: 15px; font-weight: 500;">Belum ada riwayat arsip yang tersimpan.</span></td></tr>`;
        } else {
            globalDataArsip.forEach(row => {
                let safeTitle = row.title.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                let safePengusul = row.nama_pengusul.replace(/'/g, "\\'").replace(/"/g, '&quot;');

                htmlTabel += `
                    <tr>
                        <td class="px-3">
                            <div class="fw-bold text-dark fs-6">${safeTitle}</div>
                            <div class="small fw-bold mt-1" style="color: var(--theme-primary);"><i class="bi bi-person-circle me-1"></i> ${safePengusul}</div>
                        </td>
                        <td class="px-3"><span class="badge-room">${row.room_name}</span></td>
                        <td class="px-3">
                            <div class="fw-bold text-dark"><i class="bi bi-calendar-check me-1 text-muted"></i>${row.tanggal_format}</div>
                            <div class="text-muted small mt-1"><i class="bi bi-clock me-1"></i>${row.jam_format} WIB</div>
                        </td>
                        <td class="px-3 text-center table-aksi">
                            <div class="d-flex justify-content-center">
                                <!-- HANYA MENGIRIM ID KE FUNGSI bukaNotulensi -->
                                <button type="button" onclick="bukaNotulensi('${row.id}')" class="btn btn-light p-2 shadow-sm border me-2" style="border-radius: 10px; color: var(--theme-primary);" title="Detail & Notulensi"><i class="bi bi-file-earmark-text-fill"></i></button>
                                <button onclick="konfirmasiHapusArsip(${row.id})" class="btn btn-light text-danger p-2 shadow-sm border" style="border-radius: 10px;" title="Hapus Arsip"><i class="bi bi-trash3-fill"></i></button>
                            </div>
                        </td>
                    </tr>`;
            });
        }
        document.getElementById('isiTabelArsip').innerHTML = htmlTabel;

        let wadahPage = document.getElementById('wadahPagination');
        if(!isPrint && res.total_halaman > 1) {
            let htmlPage = '';
            for(let i=1; i<=res.total_halaman; i++) {
                let isAct = (i == res.halaman_sekarang) ? 'active' : '';
                htmlPage += `<li class="page-item ${isAct}"><a class="page-link" onclick="loadArsip(${i})">${i}</a></li>`;
            }
            document.getElementById('isiPagination').innerHTML = htmlPage;
            wadahPage.style.display = 'block';
            
            currentHal = res.halaman_sekarang;
        } else {
            wadahPage.style.display = 'none';
        }

        if(isPrint) {
            setTimeout(function() { window.print(); loadArsip(currentHal, false); }, 500);
        }
    });
}
loadArsip(currentHal);

// ==============================================================
// MESIN NOTULENSI PINTAR
// ==============================================================

function bukaNotulensi(id) {
    // 1. Cari data di memori global pakai ID
    let dataRapat = globalDataArsip.find(x => x.id == id);
    if (!dataRapat) return; 

    // 2. Cek apakah daftar hadir kosong
    let daftarHadir = dataRapat.daftar_hadir || '';
    
    // 3. Kalau daftar hadir kosong TAPI ada kolom 'peserta' di database
    if (daftarHadir.trim() === '' && dataRapat.peserta) {
        let arrPeserta = [];
        
        // Ekstrak string/JSON peserta jadi Array
        try {
            arrPeserta = JSON.parse(dataRapat.peserta);
            if (!Array.isArray(arrPeserta)) arrPeserta = dataRapat.peserta.split(',');
        } catch(e) {
            arrPeserta = dataRapat.peserta.split(',');
        }

        let hasilList = '';
        let nomor = 1;
        
        arrPeserta.forEach((p) => {
            let pStr = String(p).trim();
            if(pStr) {
               // Ngambil namanya doang, format email dibuang
               let namaBersih = pStr.split('(')[0].trim();
               if (namaBersih !== '') {
                   // Tambahin enter (\n) biar berderet ke bawah
                   hasilList += nomor + '. ' + namaBersih + '\n';
                   nomor++;
               }
            }
        });
        
        daftarHadir = hasilList.trim();
    }

    // 4. Masukin ke kotak Inputan (Textarea)
    document.getElementById('notul_id').value = dataRapat.id;
    document.getElementById('notul_teks').value = dataRapat.notulensi || '';
    document.getElementById('notul_hadir').value = daftarHadir; 
    document.getElementById('notul_link').value = dataRapat.link_lampiran || '';
    
    // 5. Munculin Modal
    var myModal = new bootstrap.Modal(document.getElementById('modalNotulensi'));
    myModal.show();
}

function simpanNotulensi(event) {
    event.preventDefault();
    let form = document.getElementById('formNotulensi');
    let formData = new FormData(form);
    formData.append('action', 'notulensi');

    let btn = document.getElementById('btn-notulensi');
    let oriText = btn.innerHTML;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menyimpan...`;
    btn.disabled = true;

    fetch('api_action.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = oriText;
        btn.disabled = false;
        if (data.status === 'success') {
            bootstrap.Modal.getInstance(document.getElementById('modalNotulensi')).hide();
            // GANTI WARNA TOMBOL OK JADI WARNA TEMA
            Swal.fire({ icon: 'success', title: data.pesan, confirmButtonColor: 'var(--theme-primary)' });
            loadArsip(currentHal); 
        }
    });
}

function konfirmasiHapusArsip(id) {
    Swal.fire({
        title: 'Hapus Arsip?',
        text: "Catatan meeting ini akan dihapus permanen!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444', // Tombol hapus dibiarin MERAH biar user waspada!
        cancelButtonColor: '#cbd5e1',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            let formData = new FormData();
            formData.append('action', 'hapus');
            formData.append('id', id);
            
            fetch('api_action.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: 'Arsip Dihapus', timer: 1500, showConfirmButton: false });
                    loadArsip(currentHal); 
                }
            });
        }
    });
}
</script>
</body>
</html>