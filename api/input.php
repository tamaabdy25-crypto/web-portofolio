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

// --- LOGIKA BACA COOKIE KTP ---
$akses_diberikan = false;

if (isset($_COOKIE['ingat_nomor_induk']) && isset($_COOKIE['token_aman'])) {
    $cookie_no_induk = pg_escape_string($conn, $_COOKIE['ingat_nomor_induk']);
    $cookie_token    = $_COOKIE['token_aman'];
    
    if ($cookie_token === hash('sha256', $cookie_no_induk)) {
        $q_cek = pg_query($conn, "SELECT * FROM users WHERE nomor_induk = '$cookie_no_induk'");
        if (pg_num_rows($q_cek) > 0) {
            $data = pg_fetch_assoc($q_cek);
            
            // Set ulang session untuk container ini
            $_SESSION['user_id']      = $data['id'];
            $_SESSION['no_induk']     = $data['nomor_induk'];
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
            $_SESSION['role']         = $data['role'];
            $akses_diberikan          = true;
        }
    }
}

// --- PENGAMANAN HALAMAN ---
if (!$akses_diberikan) {
    header("Location: login.php"); 
    exit;
}

date_default_timezone_set('Asia/Jakarta');
$hari_ini = date('Y-m-d');
$my_id        = $_SESSION['user_id'] ?? 0;
$my_no_induk  = $_SESSION['no_induk'] ?? "";
$nama_lengkap = $_SESSION['nama_lengkap'] ?? "Tamu";
$my_role      = $_SESSION['role'] ?? "USER"; 

$q_user = pg_query($conn, "SELECT theme_wallpaper, foto_profil FROM users WHERE id = '$my_id'");
$data_user = pg_fetch_assoc($q_user);
$user_wallpaper = $data_user['theme_wallpaper'] ?? "";
$user_foto = $data_user['foto_profil'] ?? "";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scalable=no, user-scalable=no">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>E-VISION - Dashboard</title>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --theme-primary: #10b981; }
        
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; color: #334155; margin: 0; }
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

        .page-overlay { background: rgba(255, 255, 255, 0.2); min-height: 100vh; display: flex; flex-direction: column; width: 100%; }
        .navbar { background-color: rgba(255, 255, 255, 0.95); backdrop-filter: blur(5px); border-bottom: 2px solid #e2e8f0; padding: 15px 0; }        
        .dynamic-logo { height: 28px; width: 100px; background-color: var(--theme-primary); -webkit-mask: url('logo_evision1.png') no-repeat left center; -webkit-mask-size: contain; mask: url('logo_evision1.png') no-repeat left center; mask-size: contain; transition: background-color 0.5s ease; }
        .card { border: none; border-radius: 12px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2); background: rgba(255, 255, 255, 0.95) !important; }
        .table td, .table th { color: #000000 !important; white-space: nowrap; }
        .btn-primary, .btn-info, .status-badge.bg-berlangsung { background-color: var(--theme-primary) !important; border: none !important; color: white !important; }
        .text-success { color: var(--theme-primary) !important; }
        .form-control, .form-select { font-size: 14px; border-radius: 10px; background-color: #f8fafc; padding: 10px; }
        @media (max-width: 992px) {
            .btn-lihat-undangan-responsive {
                display: inline-block !important;
                margin-top: 5px;
            }
        }
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-10px); } 75% { transform: translateX(10px); } }
        .shake-modal { animation: shake 0.3s ease-in-out 0s 2; border: 2px solid #ef4444 !important; }
        .error-text { color: #ef4444; font-size: 11px; font-weight: 700; margin-top: 5px; display: block; }
        #preview-theme { width: 100%; max-height: 150px; object-fit: cover; border-radius: 10px; display: none; margin-bottom: 15px; border: 2px solid var(--theme-primary); }
        .btn-arsip-kustom { background-color: #ffffff !important; color: var(--theme-primary) !important; border: 2px solid var(--theme-primary) !important; font-weight: 700; border-radius: 10px; transition: all 0.2s; }
        .btn-arsip-kustom:hover { background-color: var(--theme-primary) !important; color: white !important; }
        .btn-arsip-kustom:hover i { color: white !important; }
        
        .select2-container--default .select2-selection--multiple { background-color: #f8fafc; border: 1px solid #dee2e6; border-radius: 10px; min-height: 44px; padding: 4px 8px; }
        .select2-container--default.select2-container--focus .select2-selection--multiple { border-color: var(--theme-primary); box-shadow: none !important; }
        .select2-container--default .select2-selection--multiple .select2-search.select2-search--inline .select2-search__field { height: 30px !important; line-height: 28px !important; margin-top: 2px !important; font-family: 'Inter', sans-serif; padding-bottom: 0 !important; }
        .select2-dropdown { border: 1px solid #dee2e6; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden; z-index: 9999; }
        .select2-results__option { padding: 8px 12px; border-bottom: 1px solid #f8f9fa; }
        .select2-results__option--highlighted[aria-selected] { background-color: var(--theme-primary) !important; color: white !important; }
        .select2-results__option--highlighted[aria-selected] .text-dark-custom { color: #ffffff !important; }
        .select2-results__option--highlighted[aria-selected] .text-muted-custom { color: rgba(255,255,255,0.8) !important; }
        .select2-results__option--highlighted[aria-selected] .icon-custom-bg { background: rgba(255,255,255,0.2) !important; color: #ffffff !important; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice { background-color: rgba(16, 185, 129, 0.1); color: var(--theme-primary); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 6px; padding: 4px 8px; font-weight: 700; font-size: 12px; margin-top: 6px; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove { color: var(--theme-primary); border-right: none; margin-right: 6px; font-weight: bold; }
        .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover { background: transparent; color: #ef4444; }

        .modal-content .btn-success { background-color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; color: #ffffff !important; transition: all 0.3s ease !important; }
        .modal-content .btn-success:hover { opacity: 0.85 !important; transform: translateY(-1px) !important; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important; }
        .swal2-styled.swal2-confirm { background-color: var(--theme-primary) !important; border-color: var(--theme-primary) !important; color: #ffffff !important; border-radius: 8px !important; font-weight: bold !important; transition: all 0.3s ease !important; }
        .swal2-styled.swal2-confirm:hover { opacity: 0.85 !important; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1) !important; background-image: none !important; }

        @media (max-width: 992px) { body { padding-bottom: 0 !important; } .page-overlay { padding-bottom: 20px; } .navbar .container { flex-wrap: nowrap; } .dynamic-logo { height: 22px; width: 85px; } .dropdown-menu-end { position: absolute !important; right: 0; left: auto; } .action-buttons-container { display: grid !important; grid-template-columns: 1fr 1fr; gap: 10px !important; margin-bottom: 20px !important; } .action-buttons-container .btn { width: 100%; font-size: 13px !important; padding: 10px !important; display: flex; align-items: center; justify-content: center; border-radius: 12px; font-weight: bold; margin: 0 !important; } .action-buttons-container .btn i { margin-right: 6px !important; font-size: 15px; } .card { padding: 20px 15px !important; border-radius: 16px !important; min-height: calc(100vh - 260px); display: flex; flex-direction: column; margin-bottom: 10px !important; } .card h5 { font-size: 1.15rem; margin-bottom: 20px !important; } #area-tabel-otomatis { flex-grow: 1; overflow-x: auto; -webkit-overflow-scrolling: touch; } .modal-body label { font-size: 12px; } .modal-title { font-size: 1.1rem; } }
        
        .dropdown-menu .dropdown-item:active, .dropdown-menu .dropdown-item:focus { background-color: #f8f9fa !important; color: inherit !important; }
        .dropdown-menu .dropdown-item:hover { background-color: #f1f5f9 !important; color: inherit !important; }
        .dropdown-menu .dropdown-item.text-danger:hover, .dropdown-menu .dropdown-item.text-danger:focus, .dropdown-menu .dropdown-item.text-danger:active { color: #ef4444 !important; background-color: #fee2e2 !important; }
        @media (max-width: 768px) { .header-title-text { font-size: 14px !important; } }

        @keyframes shakeError {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }
        .shake-animation {
            animation: shakeError 0.4s ease-in-out;
            border: 2px solid #ef4444 !important; 
            background-color: #fef2f2 !important;
        }
        .text-error-shake {
            display: none; 
        }
    </style>
    
    <style><?php if(!empty($user_wallpaper)): ?>body::before { background-image: url('<?php echo htmlspecialchars($user_wallpaper); ?>') !important; }<?php endif; ?></style>
    
    <script>
        const currentWp = "<?php echo htmlspecialchars($user_wallpaper ?? ''); ?>";
        const savedWp = localStorage.getItem('evision_wp_final');
        const savedColor = localStorage.getItem('evision_color_final');
        if(currentWp && currentWp === savedWp && savedColor) { document.documentElement.style.setProperty('--theme-primary', savedColor); }
    </script>
</head>
<body id="bodyMain">
<div class="page-overlay"> 

<nav class="navbar sticky-top mb-4 shadow-sm">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand d-flex align-items-center text-decoration-none" href="index.php">
            <div class="dynamic-logo" aria-label="E-VISION"></div>
            <span class="text-secondary fw-normal ms-2 border-start ps-2 header-title-text" style="font-size: 18px;">Dashboard</span>
        </a>
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle border-0 shadow-sm px-2 px-md-3 d-flex align-items-center" type="button" data-bs-toggle="dropdown" style="border-radius: 20px;">
            <?php if (!empty($user_foto)): ?>
                <img src="<?php echo htmlspecialchars($user_foto); ?>" alt="Profil" class="me-2 shadow-sm" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 1px solid var(--theme-primary);">
            <?php else: ?>
                <i class="bi bi-person-circle text-success fs-5 me-2"></i> 
            <?php endif; ?>
            <span class="fw-bold d-inline-block" style="font-size: 14px;"><?php echo htmlspecialchars($nama_lengkap); ?></span>
        </button>
            <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2" style="border-radius: 12px;">
                <li><a class="dropdown-item py-2 fw-semibold" href="profil.php"><i class="bi bi-person me-2 text-muted"></i> Profil Saya</a></li>
                <li><a class="dropdown-item py-2 fw-semibold" href="#" data-bs-toggle="modal" data-bs-target="#modalTema"><i class="bi bi-palette me-2 text-muted"></i> Ganti Tema</a></li>
                <li><a class="dropdown-item py-2 fw-semibold" href="display.php"><i class="bi bi-tv me-2 text-muted"></i> Halaman Display</a></li>
                <?php if ($my_role === 'IT'): ?>
                <li><a class="dropdown-item py-2 fw-semibold" href="it_reset_rahasia.php"><i class="bi bi-shield-lock-fill me-2 text-muted"></i> Panel IT</a></li>                <?php endif; ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item py-2 fw-bold text-danger" href="javascript:void(0)" onclick="konfirmasiLogout()"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container flex-grow-1"> 
    <div class="col-12 action-buttons-container d-flex justify-content-end mb-4 gap-2">
        <a href="cek_jadwal.php" class="btn btn-arsip-kustom shadow-sm"><i class="bi bi-eye text-success"></i> <span>Cek Jadwal</span></a>
        <a href="kalender.php" class="btn btn-arsip-kustom shadow-sm"><i class="bi bi-calendar3 text-success"></i> <span>Kalender Agenda</span></a>
        <a href="arsip.php" class="btn btn-arsip-kustom shadow-sm"><i class="bi bi-archive text-success"></i> <span>Arsip</span></a>
        <button type="button" class="btn btn-arsip-kustom shadow-sm" onclick="bukaModalTambah()"><i class="bi bi-plus-lg text-success fw-bold"></i> <span>Tambah Agenda</span></button>
    </div>

    <div class="col-12">
        <div class="card p-4 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h5 class="fw-bold m-0"><i class="bi bi-calendar-check me-2 text-success"></i>Daftar Agenda Meeting</h5>
            </div>
            <div id="area-tabel-otomatis" class="table-responsive">
                <p class="text-center py-4 text-muted small fw-semibold"><span class="spinner-border spinner-border-sm me-2 text-success" role="status" aria-hidden="true"></span> Menyinkronkan data...</p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTema" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header border-bottom-0 pb-0">
        <h5 class="modal-title fw-bold"><i class="bi bi-palette text-success me-2"></i>Pengaturan Tema</h5>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <form id="formTema" onsubmit="simpanTema(event)">
          <div class="modal-body pt-3">
              <img id="preview-theme" src="#" alt="Pratinjau Gambar">
             <p class="small text-muted mb-3">
    <i class="bi bi-info-circle me-1"></i>Pilih foto HD untuk wallpaper. Jika terang, logo & ikon otomatis menjadi gelap.
    <strong class="text-danger d-block mt-1"><i class="bi bi-exclamation-circle-fill me-1"></i>Maksimal ukuran file: 4.2MB</strong>
</p>
              <input type="file" name="wallpaper" id="input-wallpaper" class="form-control mb-2" accept="image/*">
          </div>
          <div class="modal-footer border-top-0 pt-0 d-flex justify-content-between">
            <button type="button" onclick="resetTema()" class="btn btn-light fw-semibold btn-sm px-3" style="border-radius: 8px;">Reset Default</button>
            <button type="submit" id="btn-tema" class="btn btn-success fw-bold btn-sm px-4 shadow-sm" style="border-radius: 8px;">Terapkan Tema</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalTambah" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 550px;">
    <div class="modal-content border-0 shadow-lg" id="modalContentTambah" style="border-radius: 16px;">
      <div class="modal-header bg-light border-bottom-0 rounded-top-4 py-3">
        <h6 class="modal-title fw-bold text-success mb-0"><i class="bi bi-calendar-plus me-2"></i>Buat Jadwal Baru</h6>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <form id="formTambah" onsubmit="simpanData(event, 'tambah')">
          <div class="modal-body p-3">
              <div class="row mb-3">
                  <div class="col-md-6 mb-2 mb-md-0">
                      <label class="fw-semibold text-muted small mb-1">Nomor Induk</label>
                      <div class="input-group">
                          <span class="input-group-text bg-light border-end-0"><i class="bi bi-person-badge text-muted"></i></span>
                          <input type="text" class="form-control border-start-0 ps-0" value="<?php echo htmlspecialchars($my_no_induk); ?>" readonly style="background-color: #f8f9fa; font-weight: 700;">
                      </div>
                  </div>
                  <div class="col-md-6">
                      <label class="fw-semibold text-muted small mb-1">Nama Pengusul</label>
                      <input type="text" class="form-control" value="<?php echo htmlspecialchars($nama_lengkap); ?>" readonly style="background-color: #f8f9fa; font-weight: 700;">
                  </div>
              </div>
              <hr class="text-muted opacity-25 mb-3 mt-0">
              
              <div class="mb-3">
                <label class="fw-bold mb-1 small">Judul Agenda</label>
                <input type="text" name="title" id="tambah_title" class="form-control" placeholder="Contoh: Rapat Evaluasi" oninput="this.value = this.value.toUpperCase()" required>
              </div>

              <div class="mb-3">
                  <label class="fw-bold mb-1 small">Undang Peserta Meeting</label>
                  <select name="peserta[]" id="pilih_peserta" class="form-control" multiple="multiple" style="width: 100%;">
                      </select>
              </div>
              
              <div class="row">
                  <div class="col-md-6 mb-3">
                      <label class="fw-bold mb-1 small">Ruangan</label>
                      <div class="input-group">
                          <span class="input-group-text bg-white border-end-0"><i class="bi bi-door-open text-muted"></i></span>
                          <select name="room_name" id="tambah_room" class="form-select border-start-0 ps-0 text-truncate" style="padding-right: 30px; text-overflow: ellipsis;" required>
                              <option value="" disabled selected>-- Pilih Ruangan --</option>
                              <option value="RUANG MEETING SYNERGY 7">RUANG MEETING SYNERGY 7</option>
                              <option value="RUANG MEETING EXCELENT">RUANG MEETING EXCELENT</option>
                              <option value="RUANG MEETING DEPAN HCGS">RUANG MEETING DEPAN HCGS</option>
                          </select>
                      </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="fw-bold mb-1 small">Tanggal Pelaksanaan</label>
                    <input type="date" name="meeting_date" id="tambah_date" class="form-control" min="<?php echo $hari_ini; ?>" required>
                  </div>
              </div>
              
              <div class="row bg-light p-2 rounded-3 mx-0 border">
                  <div class="col-6">
                      <label class="fw-bold small mb-1" style="font-size: 11px;"><i class="bi bi-play-circle text-success me-1"></i>Jam Mulai</label>
                      <input type="time" name="start_time" id="tambah_start" class="form-control text-center fw-bold" required>
                  </div>
                  <div class="col-6">
                      <label class="fw-bold small mb-1" style="font-size: 11px;"><i class="bi bi-stop-circle text-danger me-1"></i>Jam Selesai</label>
                      <input type="time" name="end_time" id="tambah_end" class="form-control text-center fw-bold" required>
                  </div>
                  <div class="col-12 text-center mt-2" id="error-tambah" style="display:none;"></div>
              </div>
          </div>
          <div class="modal-footer border-top-0 pb-3 px-3 pt-1">
            <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal" style="border-radius: 8px;">Batal</button>
            <button type="submit" id="btn-tambah" class="btn btn-success fw-bold shadow-sm px-4" style="border-radius: 8px;">Simpan Jadwal</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEdit" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 550px;">
    <div class="modal-content border-0 shadow-lg" id="modalContentEdit" style="border-radius: 16px;">
      <div class="modal-header bg-light border-bottom-0 rounded-top-4 py-3">
        <h6 class="modal-title fw-bold text-success mb-0"><i class="bi bi-pencil-square me-2"></i>Edit Jadwal Agenda</h6>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEdit" onsubmit="simpanData(event, 'edit')">
          <input type="hidden" name="id_edit" id="edit_id">
          <div class="modal-body p-3">
              <div class="mb-3">
                <label class="fw-bold mb-1 small">Judul Agenda</label>
                <input type="text" name="title" id="edit_title" class="form-control" oninput="this.value = this.value.toUpperCase()" required>
              </div>

              <div class="mb-3">
                  <label class="fw-bold mb-1 small">Edit/Tambah Peserta Meeting (Opsional)</label>
                  <select name="peserta[]" id="edit_peserta" class="form-control" multiple="multiple" style="width: 100%;">
                  </select>
              </div>

              <div class="row">
                  <div class="col-md-6 mb-3">
                      <label class="fw-bold mb-1 small">Ruangan</label>
                      <div class="input-group">
                          <span class="input-group-text bg-white border-end-0"><i class="bi bi-door-open text-muted"></i></span>
                          <select name="room_name" id="edit_room" class="form-select border-start-0 ps-0 text-truncate" style="padding-right: 30px; text-overflow: ellipsis;" required>
                              <option value="RUANG MEETING SYNERGY 7">RUANG MEETING SYNERGY 7</option>
                              <option value="RUANG MEETING EXCELENT">RUANG MEETING EXCELENT</option>
                              <option value="RUANG MEETING DEPAN HCGS">RUANG MEETING DEPAN HCGS</option>
                          </select>
                      </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label class="fw-bold mb-1 small">Tanggal Pelaksanaan</label>
                    <input type="date" name="meeting_date" id="edit_date" class="form-control" min="<?php echo $hari_ini; ?>" required>
                  </div>
              </div>
              <div class="row bg-light p-2 rounded-3 mx-0 border">
                  <div class="col-6">
                      <label class="fw-bold small mb-1" style="font-size: 11px;"><i class="bi bi-play-circle text-success me-1"></i>Jam Mulai</label>
                      <input type="time" name="start_time" id="edit_start" class="form-control text-center fw-bold" required>
                  </div>
                  <div class="col-6">
                      <label class="fw-bold small mb-1" style="font-size: 11px;"><i class="bi bi-stop-circle text-danger me-1"></i>Jam Selesai</label>
                      <input type="time" name="end_time" id="edit_end" class="form-control text-center fw-bold" required>
                  </div>
                  <div class="col-12 text-center mt-2" id="error-edit" style="display:none;"></div>
              </div>
          </div>
          <div class="modal-footer border-top-0 pb-3 px-3 pt-1">
            <button type="button" class="btn btn-light fw-semibold" data-bs-dismiss="modal" style="border-radius: 8px;">Batal</button>
            <button type="submit" id="btn-edit" class="btn btn-success fw-bold shadow-sm px-4" style="border-radius: 8px;">Update Jadwal</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalUndangan" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header bg-light border-bottom-0 py-3">
        <h6 class="modal-title fw-bold text-success mb-0" id="modalUndanganTitle"><i class="bi bi-people-fill me-2"></i>Daftar Undangan</h6>
        <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-3">
        <ul class="list-group list-group-flush" id="listUndanganPeserta" style="max-height: 300px; overflow-y: auto;">
            </ul>
      </div>
      <div class="modal-footer border-top-0 pt-1">
        <button type="button" class="btn btn-success fw-bold btn-sm px-4 shadow-sm" data-bs-dismiss="modal" style="border-radius: 8px;">Tutup</button>
      </div>
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
            const dynamicRGB = `rgb(${r}, ${g}, ${b})`;
            document.documentElement.style.setProperty('--theme-primary', dynamicRGB);
            localStorage.setItem('evision_wp_final', wallpaperPath);
            localStorage.setItem('evision_color_final', dynamicRGB);
        };
    }
}

document.getElementById('input-wallpaper').addEventListener('change', function(){
    const file = this.files[0];
    
    // Hapus getaran dan tulisan error tiap ganti file baru
    const errorMsg = document.getElementById('error-tema-size');
    if (errorMsg) errorMsg.style.display = 'none';
    this.classList.remove('shake-animation'); 

    if (file) {
        const reader = new FileReader();
        reader.onload = function(e){
            const preview = document.getElementById('preview-theme');
            preview.src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    }
});

function simpanTema(event) {
    event.preventDefault();
    
    let inputWpCheck = document.getElementById('input-wallpaper');
    if (inputWpCheck && inputWpCheck.files[0] && inputWpCheck.files[0].size > 4404019) {
        return false; 
    }

    let form = document.getElementById('formTema');
    let formData = new FormData(form);
    formData.append('set_theme', 'true');

    let btn = document.getElementById('btn-tema');
    let originalText = btn.innerHTML;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Menerapkan...`;
    btn.disabled = true;

    fetch('update_theme.php', { method: 'POST', body: formData })
    .then(res => res.text()) 
    .then(() => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        bootstrap.Modal.getInstance(document.getElementById('modalTema')).hide();
        localStorage.removeItem('evision_wp_final');
        localStorage.removeItem('evision_color_final');
        Swal.fire({ icon: 'success', title: 'Wallpaper Berhasil Diterapkan', timer: 1500, showConfirmButton: false })
        .then(() => window.location.reload()); 
    });
}

function resetTema() {
    let formData = new FormData();
    formData.append('reset_theme', 'true');

    fetch('update_theme.php', { method: 'POST', body: formData })
    .then(res => res.text())
    .then(() => {
        bootstrap.Modal.getInstance(document.getElementById('modalTema')).hide();
        localStorage.removeItem('evision_wp_final');
        localStorage.removeItem('evision_color_final');
        Swal.fire({ icon: 'success', title: 'Tema Default Kembali', timer: 1500, showConfirmButton: false })
        .then(() => window.location.reload());
    });
}

// ==========================================
// KODE SAKTI ANTI-CACHE WAKTU FETCH TABEL
// ==========================================
function loadTabelUser() {
    fetch('api_dashboard.php?nocache=' + new Date().getTime(), { cache: 'no-store' })
    .then(response => response.json())
    .then(res => {
        if(res.status !== 'success') return;
        
        // 🔥 UPDATE 1: Tambah kolom <th>Daftar Undangan</th> di header tabel agar berjejer rapi
        let htmlTabel = `
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Agenda & Ruang</th>
                    <th>Tanggal & Waktu</th>
                    <th>Daftar Undangan</th>
                    <th style="text-align: center;">Status</th>
                    <th style="text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>`;

        if(res.data.length === 0) {
            // 🔥 UPDATE 2: Ubah colspan jadi 5 agar pas di tengah jika tabel kosong
            htmlTabel += `<tr>
                <td colspan="5" class="text-center py-5 text-muted">
                    <div style="margin: 0 auto 15px auto; height: 55px; width: 280px; background-color: #64748b; -webkit-mask: url('logo_evision2.png') no-repeat center; mask: url('logo_evision2.png') no-repeat center; -webkit-mask-size: contain; mask-size: contain; opacity: 0.85;"></div>
                    <span style="font-weight: 500; font-size: 15px;">Belum ada jadwal meeting yang kamu buat.</span>
                </td>
            </tr>`;
        } else {
            res.data.forEach(row => {
                let rowStyle = '', statusBadge = '', progressBar = '', btnAksi = '';
                let safeTitle = row.title.replace(/'/g, "\\'").replace(/"/g, '&quot;');
                
                if (row.status_berjalan === 'mendatang') {
                    statusBadge = `<span class="status-badge" style="background:#f1f5f9; color:#64748b; font-size:10px; padding:4px 10px; border-radius:10px; font-weight:700;">MENDATANG</span>`;
                    btnAksi = `
                        <button type="button" onclick="event.preventDefault(); bukaEdit('${row.id}', '${safeTitle}', '${row.room_name}', '${row.meeting_date}', '${row.start_time}', '${row.end_time}', '${encodeURIComponent(row.peserta || '')}')" style="background:none; border:none; color:#f59e0b; padding:0; margin-right:8px;" title="Edit Jadwal"><i class="bi bi-pencil-square fs-5"></i></button>
                        <button type="button" onclick="event.preventDefault(); aksiEksekusi('hapus', '${row.id}', 'Hapus Agenda?', 'Data yang dihapus tidak bisa dikembalikan!', '#ef4444', 'Ya, Hapus')" style="background:none; border:none; color:#ef4444; padding:0;" title="Hapus Jadwal"><i class="bi bi-trash fs-5"></i></button>
                    `;
                } else {
                    rowStyle = `style="background: rgba(16, 185, 129, 0.03);"`;
                    statusBadge = `<span class="status-badge" style="background:#dcfce7; color:#16a34a; font-size:10px; padding:4px 10px; border-radius:10px; font-weight:700;">BERLANGSUNG</span>`;
                    progressBar = `<div style="height: 4px; width: 60px; background: #e2e8f0; border-radius: 10px; margin: 5px auto 0; overflow: hidden;"><div style="height: 100%; width: ${row.persen_jalan}%; background: #10b981;"></div></div>`;
                    btnAksi = `<button type="button" onclick="event.preventDefault(); aksiEksekusi('selesai', '${row.id}', 'Selesaikan Agenda?', 'Agenda ini akan ditandai as selesai.', '#10b981', 'Selesai')" style="background:none; border:none; color:#10b981; padding:0;" title="Tandai Selesai"><i class="bi bi-check-circle-fill fs-4"></i></button>`;
                }

                // 🔥 UPDATE 3: Pisahkan tombol ke dalam <td> baru tersendiri agar lurus sejajar sesuai instruksi foto
                htmlTabel += `
                    <tr ${rowStyle}>
                        <td>
                            <span style="display:block; font-weight:700; color: #000000; font-size:18px;">${row.title}</span>
                            <span class="badge bg-light text-success border" style="font-size: 10px; padding: 3px 6px;"><i class="bi bi-door-open me-1"></i>${row.room_name}</span>
                        </td>
                        <td>
                            <div class="fw-bold" style="font-size:16px; color: #000000; line-height:1.2;">${row.tanggal_indo}</div>
                            <div class="text-muted mb-1" style="font-size: 12px;"><i class="bi bi-clock me-1"></i>${row.jam_format} WIB</div>
                        </td>
                        <td>
                            <div style="max-width: 165px;">
                                <button type="button" class="btn btn-sm btn-outline-success py-1 px-2 btn-lihat-undangan-responsive w-100 text-start d-flex align-items-center justify-content-between" style="font-size: 11px; border-radius: 8px; font-weight: 600; border-color: rgba(16, 185, 129, 0.25); background: rgba(16, 185, 129, 0.02);" onclick="event.preventDefault(); tampilkanUndangan('${safeTitle}', '${encodeURIComponent(row.peserta || '')}')">
                                    <span><i class="bi bi-people-fill me-1"></i> Lihat Daftar Undangan</span>
                                    <i class="bi bi-chevron-right small opacity-50"></i>
                                </button>
                            </div>
                        </td>
                        <td style="text-align: center; vertical-align: middle;">${statusBadge}${progressBar}</td>
                        <td style="text-align: center; vertical-align: middle;">${btnAksi}</td>
                    </tr>
                `;
            });
        }
        htmlTabel += `</tbody></table>`;
        document.getElementById('area-tabel-otomatis').innerHTML = htmlTabel;
    })
    .catch(err => console.error("Gagal load API Dashboard:", err));
}

setInterval(loadTabelUser, 5000); 
loadTabelUser(); 

function tampilkanUndangan(title, dataPesertaEncoded) {
    document.getElementById('modalUndanganTitle').innerHTML = `<i class="bi bi-people-fill me-2"></i>Daftar Undangan <span class="badge bg-light text-secondary border ms-2 fw-normal" style="font-size: 12px; letter-spacing: 0.02em;">${title}</span>`;
    let listContainer = document.getElementById('listUndanganPeserta');
    listContainer.innerHTML = '';
    
    if (dataPesertaEncoded) {
        try {
            let decodedData = decodeURIComponent(dataPesertaEncoded);
            let arrPeserta = JSON.parse(decodedData);
            if (!Array.isArray(arrPeserta)) { arrPeserta = decodedData.split(','); }
            
            if(arrPeserta.length === 0 || (arrPeserta.length === 1 && arrPeserta[0].trim() === '')) {
                listContainer.innerHTML = '<li class="list-group-item text-center text-muted small py-3">Tidak ada peserta khusus yang diundang.</li>';
            } else {
                arrPeserta.forEach(function(item) {
                    let namaBersih = String(item).trim();
                    if(namaBersih) {
                        listContainer.innerHTML += `<li class="list-group-item d-flex align-items-center py-2 fw-semibold" style="font-size: 13px; color: #334155;"><i class="bi bi-person-check-fill text-success me-2 fs-5"></i> ${namaBersih}</li>`;
                    }
                });
            }
        } catch (e) {
            let arrPeserta = decodeURIComponent(dataPesertaEncoded).split(',');
            if(arrPeserta.length === 0 || (arrPeserta.length === 1 && arrPeserta[0].trim() === '')) {
                listContainer.innerHTML = '<li class="list-group-item text-center text-muted small py-3">Tidak ada peserta khusus yang diundang.</li>';
            } else {
                arrPeserta.forEach(function(item) {
                    let namaBersih = String(item).trim();
                    if(namaBersih) {
                        listContainer.innerHTML += `<li class="list-group-item d-flex align-items-center py-2 fw-semibold" style="font-size: 13px; color: #334155;"><i class="bi bi-person-check-fill text-success me-2 fs-5"></i> ${namaBersih}</li>`;
                    }
                });
            }
        }
    } else {
        listContainer.innerHTML = '<li class="list-group-item text-center text-muted small py-3">Tidak ada peserta khusus yang diundang.</li>';
    }
    
    var myModalUndangan = new bootstrap.Modal(document.getElementById('modalUndangan'));
    myModalUndangan.show();
}

function bukaModalTambah() {
    document.getElementById('error-tambah').style.display = 'none';
    setTimeout(() => { $('#tambah_title').focus(); }, 300);
    var myModal = new bootstrap.Modal(document.getElementById('modalTambah'));
    myModal.show();
}

function bukaEdit(id, title, room, date, start, end, dataPesertaEncoded = '') {
    document.getElementById('error-edit').style.display = 'none';
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_title').value = title;
    document.getElementById('edit_room').value = room;
    document.getElementById('edit_date').value = date;
    document.getElementById('edit_start').value = start.substr(0, 5);
    document.getElementById('edit_end').value = end.substr(0, 5);
    
    let selectEdit = $('#edit_peserta');
    selectEdit.empty(); 
    
    if (dataPesertaEncoded) {
        try {
            let decodedData = decodeURIComponent(dataPesertaEncoded);
            let arrPeserta = JSON.parse(decodedData);
            if (!Array.isArray(arrPeserta)) { arrPeserta = decodedData.split(','); }

            arrPeserta.forEach(function(item) {
                let namaBersih = String(item).trim();
                if(namaBersih) {
                    var newOption = new Option(namaBersih, namaBersih, true, true);
                    selectEdit.append(newOption);
                }
            });
            selectEdit.trigger('change');
        } catch (e) {
            let arrPeserta = decodeURIComponent(dataPesertaEncoded).split(',');
            arrPeserta.forEach(function(item) {
                let namaBersih = String(item).trim();
                if(namaBersih) {
                    var newOption = new Option(namaBersih, namaBersih, true, true);
                    selectEdit.append(newOption);
                }
            });
            selectEdit.trigger('change');
        }
    } else {
        selectEdit.val(null).trigger('change');
    }
    
    setTimeout(() => { $('#edit_title').focus(); }, 300);
    var myModal = new bootstrap.Modal(document.getElementById('modalEdit'));
    myModal.show();
}

function simpanData(event, tipeAksi) {
    event.preventDefault(); 
    
    let idForm = tipeAksi === 'tambah' ? 'formTambah' : 'formEdit';
    let idModal = tipeAksi === 'tambah' ? 'modalTambah' : 'modalEdit';
    let idContent = tipeAksi === 'tambah' ? 'modalContentTambah' : 'modalContentEdit';
    let idError = tipeAksi === 'tambah' ? 'error-tambah' : 'error-edit';
    let idBtn = tipeAksi === 'tambah' ? 'btn-tambah' : 'btn-edit';
    
    let form = document.getElementById(idForm);
    let formData = new FormData(form);
    formData.append('action', tipeAksi);

    let btn = document.getElementById(idBtn);
    let originalText = btn.innerHTML;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Loading...`;
    btn.disabled = true;

    fetch('api_action.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        if (data.status === 'success') {
            bootstrap.Modal.getInstance(document.getElementById(idModal)).hide();
            form.reset();
            if(tipeAksi === 'tambah') $('#pilih_peserta').val(null).trigger('change'); 
            if(tipeAksi === 'edit') $('#edit_peserta').val(null).trigger('change'); 
            Swal.fire({ icon: 'success', title: data.pesan, showConfirmButton: false, timer: 1500 });
            loadTabelUser(); 
        } else {
            let contentDiv = document.getElementById(idContent);
            contentDiv.classList.add('shake-modal');
            setTimeout(() => contentDiv.classList.remove('shake-modal'), 1000);
            
            let errDiv = document.getElementById(idError);
            errDiv.innerHTML = `<span class="error-text bg-white px-2 py-1 rounded border"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${data.pesan}</span>`;
            errDiv.style.display = 'block';
        }
    });
}

function aksiEksekusi(tipeAksi, idJadwal, judul, pesan, warnaTombol, teksTombol) {
    Swal.fire({
        title: judul,
        text: pesan,
        icon: tipeAksi === 'hapus' ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: warnaTombol,
        cancelButtonText: 'Batal',
        confirmButtonText: teksTombol
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Memproses...',
                allowOutsideClick: false,
                showConfirmButton: false,
                didOpen: () => { Swal.showLoading(); }
            });

            let formData = new FormData();
            formData.append('action', tipeAksi);
            formData.append('id', idJadwal);
            
            fetch('api_action.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({ icon: 'success', title: data.pesan, showConfirmButton: false, timer: 1500 });
                    loadTabelUser();
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: data.pesan });
                }
            })
            .catch(err => {
                Swal.fire({ icon: 'error', title: 'Sistem Sibuk', text: 'Silakan coba lagi.', showConfirmButton: false, timer: 1500 });
            });
        }
    });
}

function konfirmasiLogout() {
    Swal.fire({
        title: 'Keluar Sistem?',
        text: "Anda harus login kembali untuk akses dashboard.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#cbd5e1',
        confirmButtonText: 'Ya, Keluar!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = "logout.php";
    });
}

// ==========================================
// INISIALISASI MESIN PENCARI SELECT2 
// ==========================================
$(document).ready(function() {
    
    function formatPeserta(repo) {
        if (repo.loading) return "Mencari data...";
        
        let parts = repo.text.split(' (');
        let nama = parts[0];
        let email = parts[1] ? parts[1].replace(')', '') : 'Tidak ada email';

        return $(`
            <div class='d-flex align-items-center'>
                <div class='icon-custom-bg me-3 rounded-circle d-flex align-items-center justify-content-center' style='width:32px; height:32px; background: rgba(16,185,129,0.1); color:var(--theme-primary);'>
                    <i class='bi bi-person-fill fs-5'></i>
                </div>
                <div>
                    <div class='fw-bold text-dark-custom' style='font-size:13px; color:#334155;'>${nama}</div>
                    <div class='small text-muted-custom' style='font-size:11px; color:#6c757d;'><i class='bi bi-envelope me-1'></i>${email}</div>
                </div>
            </div>
        `);
    }

    function formatPesertaSelection(repo) {
        if (!repo.id) return repo.text;
        return repo.text.split(' (')[0]; 
    }

    $('#pilih_peserta').select2({
        dropdownParent: $('#modalTambah .modal-content'), 
        placeholder: 'Ketik nama atau email',
        minimumInputLength: 2,
        ajax: {
            url: 'api_cari_peserta.php',
            dataType: 'json',
            delay: 250,
            processResults: function (data) { return { results: data }; },
            cache: true
        },
        templateResult: formatPeserta,
        templateSelection: formatPesertaSelection,
        escapeMarkup: function(m) { return m; } 
    });

    $('#edit_peserta').select2({
        dropdownParent: $('#modalEdit .modal-content'), 
        placeholder: 'Ketik nama atau email peserta tambahan',
        minimumInputLength: 2,
        ajax: {
            url: 'api_cari_peserta.php',
            dataType: 'json',
            delay: 250,
            processResults: function (data) { return { results: data }; },
            cache: true
        },
        templateResult: formatPeserta,
        templateSelection: formatPesertaSelection,
        escapeMarkup: function(m) { return m; } 
    });

    // ==========================================
    // FITUR: ENTER UNTUK PINDAH KOLOM (KAYA TAB)
    // ==========================================
    function aktifkanEnterPindah(formId, urutanId) {
        $('#' + formId).on('keydown', 'input:not(.select2-search__field), select', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault(); 
                
                let idSekarang = $(this).attr('id');
                let indexSekarang = urutanId.indexOf(idSekarang);
                
                if (indexSekarang > -1 && indexSekarang < urutanId.length - 1) {
                    let idBerikutnya = urutanId[indexSekarang + 1];
                    let elemenBerikutnya = $('#' + idBerikutnya);
                    
                    if (elemenBerikutnya.hasClass('select2-hidden-accessible')) {
                        elemenBerikutnya.select2('open'); 
                    } else {
                        elemenBerikutnya.focus(); 
                    }
                } 
                else if (indexSekarang === urutanId.length - 1) {
                    $('#' + formId + ' button[type="submit"]').click();
                }
            }
        });
    }

    let urutanTambah = ['tambah_title', 'pilih_peserta', 'tambah_room', 'tambah_date', 'tambah_start', 'tambah_end'];
    aktifkanEnterPindah('formTambah', urutanTambah);

    let urutanEdit = ['edit_title', 'edit_peserta', 'edit_room', 'edit_date', 'edit_start', 'edit_end'];
    aktifkanEnterPindah('formEdit', urutanEdit);

    $('#pilih_peserta').on('select2:close', function (e) {
        setTimeout(function() { $('#tambah_room').focus(); }, 50);
    });
    $('#edit_peserta').on('select2:close', function (e) {
        setTimeout(function() { $('#edit_room').focus(); }, 50);
    });
});

// --- SATPAM FRONTEND INTERAKTIF (4.2MB SAKLEK) ---
const formTema = document.getElementById('formTema'); 
const inputWallpaper = document.getElementById('input-wallpaper'); 

if (formTema && inputWallpaper) {
    const btnSubmitTema = formTema.querySelector('button[type="submit"]');
    const teksAsliBtn = btnSubmitTema ? btnSubmitTema.innerHTML : 'Terapkan';

    formTema.addEventListener('submit', function(e) {
        const file = inputWallpaper.files[0];

        if (file) {
            if (file.size > 4718592) { 
                e.preventDefault(); 
                e.stopImmediatePropagation(); 
                
                if (btnSubmitTema) {
                    btnSubmitTema.innerHTML = teksAsliBtn;
                    btnSubmitTema.disabled = false;
                    btnSubmitTema.classList.remove('disabled');
                }

                let errorMsg = document.getElementById('error-tema-size');
                if (!errorMsg) {
                    errorMsg = document.createElement('div');
                    errorMsg.id = 'error-tema-size';
                    errorMsg.className = 'text-error-shake fw-bold text-start mb-2';
                    errorMsg.style.color = '#ef4444';
                    errorMsg.style.fontSize = '13px';
                    errorMsg.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Gagal: Ukuran wallpaper maksimal 4.2MB!';
                    inputWallpaper.parentNode.insertBefore(errorMsg, inputWallpaper.nextSibling);
                }
                
                errorMsg.style.display = 'block'; 
                
                inputWallpaper.classList.remove('shake-animation');
                void inputWallpaper.offsetWidth; 
                inputWallpaper.classList.add('shake-animation');
            }
        }
    });

    inputWallpaper.addEventListener('change', function() {
        const errorMsg = document.getElementById('error-tema-size');
        if (errorMsg) errorMsg.style.display = 'none';
        this.classList.remove('shake-animation'); 
        
        if (btnSubmitTema) {
            btnSubmitTema.innerHTML = teksAsliBtn;
            btnSubmitTema.disabled = false;
        }
    });
}
</script>
</body>
</html>
