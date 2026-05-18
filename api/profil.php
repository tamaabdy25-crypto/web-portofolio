<?php
session_start();
include 'koneksi.php';

// Cek session login dengan kunci yang benar
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

$query_user = pg_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
$user = pg_fetch_assoc($query_user);

// ==============================================================
// 💡 BLOK BARU: AKSI UBAH PASSWORD (SISTEM AJAX ANTI-LOADING)
// ==============================================================
if (isset($_POST['update_password_ajax'])) {
    header('Content-Type: application/json');
    $pw_lama = $_POST['pw_lama'];
    $pw_baru = $_POST['pw_baru'];
    $konf_pw = $_POST['konfirmasi_pw'];

    if (!password_verify($pw_lama, $user['password'])) {
        echo json_encode(["status" => "error", "pesan" => "Password lama salah!"]);
    } else if (password_verify($pw_baru, $user['password'])) {
        // 💡 GEMBOK BARU: Gak boleh pake password yang sama persis
        echo json_encode(["status" => "error", "pesan" => "Password baru tidak boleh sama dengan yang lama!"]);
    } else if (strlen($pw_baru) < 6) {
        echo json_encode(["status" => "error", "pesan" => "Password baru minimal 6 karakter!"]);
    } else if ($pw_baru !== $konf_pw) {
        echo json_encode(["status" => "error", "pesan" => "Konfirmasi password tidak cocok!"]);
    } else {
        $hashed_baru = password_hash($pw_baru, PASSWORD_DEFAULT);
        pg_query($conn, "UPDATE users SET password = '$hashed_baru' WHERE id = '$user_id'");
        echo json_encode(["status" => "success", "pesan" => "Password berhasil diperbarui!"]);
    }
    exit; // Stop render HTML, cukup kirim JSON ke Javascript
}
// ==============================================================

// --- AMBIL WALLPAPER UNTUK TEMA ---
$user_wallpaper = $user['theme_wallpaper'] ?? "";

// --- AMBIL FOTO PROFIL ---
$user_foto = $user['foto_profil'] ?? ""; 

// --- FIX: Pengaman Tanggal agar tidak muncul 1970 jika data kosong ---
$tanggal_join = (isset($user['created_at']) && !empty($user['created_at']) && $user['created_at'] != '0000-00-00 00:00:00') 
                ? date('d M Y', strtotime($user['created_at'])) 
                : "Baru Saja";

// ==============================================================
// BLOK AKSI UPLOAD / HAPUS FOTO PROFIL
// ==============================================================
if (isset($_POST['aksi_foto'])) {
    $jenis_aksi = $_POST['aksi_foto'];
    $target_dir = "uploads_profil/";
    
    // Bikin folder kalau belum ada
    if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }

    if ($jenis_aksi === 'upload' && isset($_FILES["file_foto"]) && $_FILES["file_foto"]["error"] == 0) {
        $file_name = $_FILES["file_foto"]["name"];
        $file_tmp  = $_FILES["file_foto"]["tmp_name"];
        $file_size = $_FILES["file_foto"]["size"];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Cek ekstensi (Hanya boleh gambar)
        $allowed_ext = array("jpg", "jpeg", "png", "webp");
        
        if (!in_array($file_ext, $allowed_ext)) {
            $error_msg = "Hanya file JPG, JPEG, PNG & WEBP yang diizinkan!";
        } else if ($file_size > 2000000) { // Batas 2MB biar enteng
            $error_msg = "Ukuran foto maksimal 2MB!";
        } else {
            // Beri nama file unik (ID user + waktu) biar gak bentrok
            $new_file_name = "profil_" . $user_id . "_" . time() . "." . $file_ext;
            $target_file = $target_dir . $new_file_name;
            
            // JURUS UNLINK: Hapus foto lama secara fisik dari server kalau ada
            if (!empty($user_foto) && file_exists($user_foto)) {
                unlink($user_foto);
            }
            
            // Pindahkan file baru
            if (move_uploaded_file($file_tmp, $target_file)) {
                pg_query($conn, "UPDATE users SET foto_profil = '$target_file' WHERE id = '$user_id'");
                $user_foto = $target_file; // Update variabel untuk tampilan
                $success_msg = "Foto profil berhasil diperbarui!";
            } else {
                $error_msg = "Gagal mengunggah foto. Periksa hak akses folder!";
            }
        }
    } 
    else if ($jenis_aksi === 'hapus') {
        // JURUS UNLINK: Hapus foto dari server secara fisik
        if (!empty($user_foto) && file_exists($user_foto)) {
            unlink($user_foto);
        }
        // Bersihkan data di tabel PostgreSQL
        pg_query($conn, "UPDATE users SET foto_profil = NULL WHERE id = '$user_id'");
        $user_foto = "";
        $success_msg = "Foto profil berhasil dihapus!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-VISION - Profil </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --theme-primary: #10b981; }

        body { 
            background-color: #f1f5f9; 
            font-family: 'Inter', sans-serif; 
            background-attachment: fixed;
            background-size: cover;
            background-position: center;
            margin: 0;
            transition: background 0.5s ease;
        }

        /* Overlay full mentok bawah */
        .page-overlay {
            background: rgba(255, 255, 255, 0.2); 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            width: 100%;
        }

        /* --- LOGO DINAMIS (MENGIKUTI TEMA) --- */
        .dynamic-logo {
            height: 28px;
            width: 100px;
            background-color: var(--theme-primary);
            -webkit-mask: url('logo_evision1.png') no-repeat left center;
            -webkit-mask-size: contain;
            mask: url('logo_evision1.png') no-repeat left center;
            mask-size: contain;
            transition: background-color 0.5s ease;
            display: inline-block;
            vertical-align: middle;
        }

        /* Modifikasi Header biar seragam dengan halaman lain */
        .sticky-header { position: sticky; top: 0; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(5px); z-index: 100; padding: 20px 0; border-bottom: 2px solid #e2e8f0; box-shadow: 0 2px 10px rgba(0,0,0,0.05); 
            margin-bottom: 30px;
        }
        
        .profile-card { 
            background: rgba(255, 255, 255, 0.95) !important; 
            border-radius: 12px; 
            border: none; 
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); 
        }
        
        .profile-banner { 
            background: var(--theme-primary); 
            height: 120px; 
            border-radius: 12px 12px 0 0; 
            position: relative; 
            transition: background 0.5s ease;
        }
        
        .avatar-wrapper { position: absolute; bottom: -40px; left: 30px; }
        .avatar-circle { 
            width: 100px; height: 100px; border-radius: 50%; background: #fff; 
            border: 4px solid #fff; display: flex; align-items: center; 
            justify-content: center; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            position: relative; /* Penting untuk tombol kamera */
        }
        .avatar-circle i.icon-default { font-size: 3.5rem; color: #cbd5e1; }
        .avatar-circle img { width: 100%; height: 100%; object-fit: cover; }
        
        /* Tombol Kamera Kecil di Foto Profil */
        .btn-edit-foto {
            position: absolute;
            bottom: -5px;
            right: -5px;
            background: white;
            color: var(--theme-primary);
            border: 2px solid var(--theme-primary);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.2s ease;
            z-index: 10;
        }
        .btn-edit-foto:hover { background: var(--theme-primary); color: white; transform: scale(1.1); }

        .info-label { font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase; }
        .info-value { font-size: 16px; color: #000000 !important; font-weight: 700; margin-bottom: 15px; }
        
        .btn-success, .status-pill.bg-success { 
            background-color: var(--theme-primary) !important; 
            border: none; 
        }
        .text-success, .bi-person-vcard, .bi-shield-lock, .bi-key { 
            color: var(--theme-primary) !important; 
        }

        /* --- FIX TOMBOL KEMBALI BIAR GAK HIJAU PAS DIKLIK --- */
        .btn-outline-success { 
            color: var(--theme-primary) !important; 
            border-color: var(--theme-primary) !important; 
        }
        /* Mengunci semua state: hover, focus, active, dan pas ditekan */
        .btn-outline-success:hover,
        .btn-outline-success:focus,
        .btn-outline-success:active,
        .btn-outline-success.active { 
            background-color: var(--theme-primary) !important; 
            color: white !important; 
            border-color: var(--theme-primary) !important; 
            box-shadow: none !important; /* Hilangin shadow biru/hijau bawaan bootstrap */
        }
        
        .status-pill { font-size: 11px; padding: 4px 12px; border-radius: 20px; font-weight: 700; }
        .form-control { border-radius: 8px; padding: 10px 12px; background: #f8fafc; }

        @media (max-width: 768px) {
            .dynamic-logo { height: 22px; width: 85px; }
            .header-title-text { font-size: 14px !important; }
            .sticky-header .btn { font-size: 0.85rem !important; padding: 5px 12px !important; }
            .page-overlay { padding-bottom: 20px; }
            .avatar-wrapper { left: 50%; transform: translateX(-50%); }
            .profile-banner { display: flex; justify-content: center; }
            .profile-card h4, .profile-card p, .profile-card .status-pill { text-align: center; }
            .d-flex.gap-2.justify-content-center { justify-content: center !important; }
        }

        /* =======================================================
            ANIMASI SHAKE BUAT UPLOAD FOTO & GANTI PASSWORD
            ======================================================= */
        @keyframes shakeError {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-5px); }
            40%, 80% { transform: translateX(5px); }
        }

        @keyframes shakeModal { 
            0%, 100% { transform: translateX(0); } 
            25% { transform: translateX(-10px); } 
            75% { transform: translateX(10px); } 
        }

        .shake-animation {
            animation: shakeError 0.4s ease-in-out;
            border: 2px solid #ef4444 !important; /* Kotak jadi merah */
            background-color: #fef2f2 !important;
        }

        /* Animasi Getar Khusus Modal Password */
        .shake-modal { 
            animation: shakeModal 0.3s ease-in-out 0s 2; 
            border: 2px solid #ef4444 !important; 
        }

        .text-error-shake {
            color: #ef4444;
            font-size: 13px;
            display: none; /* Sembunyiin dulu dari awal */
        }
        
        .error-text { 
            color: #ef4444; 
            font-size: 11px; 
            font-weight: 700; 
            margin-top: 5px; 
            display: inline-block; 
        }
    </style>

    <style>
        /* Paksa background langsung muncul pakai PHP, gak nunggu JS */
        <?php if(!empty($user_wallpaper)): ?>
        body {
            background-image: url('<?php echo htmlspecialchars($user_wallpaper); ?>') !important;
        }
        <?php endif; ?>
    </style>
    <script>
        // Tarik warna dari memori browser sebelum halaman digambar
        const currentWp = "<?php echo htmlspecialchars($user_wallpaper ?? ''); ?>";
        const savedWp = localStorage.getItem('evision_wp_final');
        const savedColor = localStorage.getItem('evision_color_final');
        
        // Kalau wallpaper sama dengan yang ada di memori, tembak warnanya langsung!
        if(currentWp && currentWp === savedWp && savedColor) {
            document.documentElement.style.setProperty('--theme-primary', savedColor);
        }
    </script>
</head>
<body id="bodyProfil">
<div class="page-overlay"> 

    <div class="sticky-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="dynamic-logo" aria-label="E-VISION"></div>
                <span class="text-secondary fw-normal ms-2 border-start ps-2 header-title-text" style="font-size: 18px;">Profil </span>
            </div>
            
            <a href="input.php" class="btn btn-sm btn-outline-success px-3 shadow-sm fw-bold rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Kembali
            </a>
        </div>
    </div>

    <div class="container flex-grow-1">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="profile-card pb-4 text-center text-lg-start">
                    <div class="profile-banner">
                        <div class="avatar-wrapper">
                            <div class="avatar-circle">
                                <?php if (!empty($user_foto)): ?>
                                    <img src="<?php echo htmlspecialchars($user_foto); ?>" alt="Foto Profil">
                                <?php else: ?>
                                    <i class="bi bi-person-fill icon-default"></i>
                                <?php endif; ?>
                            </div>
                            <div class="btn-edit-foto" data-bs-toggle="modal" data-bs-target="#modalFoto" title="Ubah Foto Profil">
                                <i class="bi bi-camera-fill"></i>
                            </div>
                        </div>
                    </div>
                    <div class="px-4" style="margin-top: 50px;">
                        <h4 class="fw-bold mb-1" style="color:#000;"><?php echo htmlspecialchars($user['nama_lengkap']); ?></h4>
                        <p class="text-muted small mb-3">Operator Sistem - <?php echo strtoupper($user['role']); ?></p>
                        <div class="d-flex gap-2 justify-content-center justify-content-lg-start">
                            <span class="status-pill bg-success text-white shadow-sm">VERIFIED</span>
                            <span class="status-pill bg-success text-white shadow-sm">AKTIF</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="profile-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold m-0 text-success">Informasi Identitas</h6>
                        <button class="btn btn-light btn-sm text-success fw-bold border shadow-sm" data-bs-toggle="modal" data-bs-target="#modalPw" style="border-radius: 8px;">
                            <i class="bi bi-key me-1"></i> Ganti Password
                        </button>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <div class="info-label">Nama Panggilan</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['nama_lengkap']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Nomor Induk</div>
                            <div class="info-value"><?php echo htmlspecialchars($user['nomor_induk']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Role Akses</div>
                            <div class="info-value"><?php echo ucwords($user['role']); ?></div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-label">Terdaftar Sejak</div>
                            <div class="info-value"><?php echo $tanggal_join; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPw" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content shadow-lg border-0" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 mt-2 mx-2">
                    <h6 class="fw-bold m-0 text-success"><i class="bi bi-shield-lock-fill me-2"></i>Keamanan Akun</h6>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="formGantiPw" onsubmit="gantiPasswordAjax(event)">
                    <div class="modal-body p-4 pb-2">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password Lama</label>
                            <div class="input-group shadow-sm" style="border-radius: 8px; overflow:hidden;">
                                <input type="password" id="n0" name="pw_lama" class="form-control border-end-0" minlength="6" placeholder="Ketik password saat ini" required>
                                <span class="input-group-text bg-white border-start-0" onclick="v('n0','i0')" style="cursor:pointer"><i class="bi bi-eye-slash text-muted" id="i0"></i></span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Password Baru</label>
                            <div class="input-group shadow-sm" style="border-radius: 8px; overflow:hidden;">
                                <input type="password" id="n1" name="pw_baru" class="form-control border-end-0" minlength="6" placeholder="Minimal 6 karakter" required>
                                <span class="input-group-text bg-white border-start-0" onclick="v('n1','i1')" style="cursor:pointer"><i class="bi bi-eye-slash text-muted" id="i1"></i></span>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold">Konfirmasi Baru</label>
                            <div class="input-group shadow-sm" style="border-radius: 8px; overflow:hidden;">
                                <input type="password" id="n2" name="konfirmasi_pw" class="form-control border-end-0" minlength="6" placeholder="Ulangi Password" required>
                                <span class="input-group-text bg-white border-start-0" onclick="v('n2','i2')" style="cursor:pointer"><i class="bi bi-eye-slash text-muted" id="i2"></i></span>
                            </div>
                        </div>
                        
                        <div id="error-pw-container" class="text-center mt-3" style="display: none;"></div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light fw-bold w-100 mb-2" data-bs-dismiss="modal" style="border-radius: 8px;">Batal</button>
                        <button type="submit" id="btn-simpan-pw" class="btn btn-success w-100 py-2 fw-bold shadow-sm" style="border-radius:8px">Simpan Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalFoto" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content shadow-lg border-0" style="border-radius: 16px;">
                <div class="modal-header border-0 pb-0 mt-2 mx-2">
                    <h6 class="fw-bold m-0 text-success"><i class="bi bi-camera-fill me-2"></i>Ubah Foto Profil</h6>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="formFoto">
                    <div class="modal-body p-4 text-center">
                        <img id="preview-foto" src="<?php echo !empty($user_foto) ? htmlspecialchars($user_foto) : 'data:image/svg+xml;charset=UTF8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23cbd5e1\'%3E%3Cpath d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/%3E%3C/svg%3E'; ?>" 
                             alt="Pratinjau" style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%; border: 3px solid var(--theme-primary); margin-bottom: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                        
                        <p class="small text-muted mb-3">Pilih foto wajah yang jelas. Maksimal ukuran 2MB.</p>
                        
                        <input type="file" name="file_foto" id="input-foto" class="form-control mb-2" accept="image/jpeg, image/png, image/jpg, image/webp" required>
                        <div id="error-foto-size" class="text-error-shake fw-bold text-start mb-2"><i class="bi bi-exclamation-triangle-fill"></i> Gagal: Ukuran foto maksimal 2MB!</div>

                        <input type="hidden" name="aksi_foto" id="input_aksi_foto" value="upload">
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0 d-flex flex-row flex-nowrap gap-2 w-100">
                        <?php if (!empty($user_foto)): ?>
                            <button type="submit" onclick="document.getElementById('input-foto').removeAttribute('required'); document.getElementById('input_aksi_foto').value='hapus';" class="btn btn-outline-danger fw-bold flex-fill m-0" style="border-radius: 8px;"><i class="bi bi-trash"></i> Hapus</button>
                        <?php else: ?>
                            <button type="button" class="btn btn-light fw-bold flex-fill m-0" data-bs-dismiss="modal" style="border-radius: 8px;">Batal</button>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn btn-success fw-bold shadow-sm flex-fill m-0" style="border-radius:8px;">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/color-thief/2.3.0/color-thief.umd.js"></script>
    <script>
        // --- LOGIKA TEMA PINTAR (Logo & Ikon otomatis gelap kalau wallpaper terang) ---
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

        // Lihat / Sembunyi Password
        function v(id, icon) {
            const x = document.getElementById(id);
            const y = document.getElementById(icon);
            x.type = (x.type === "password") ? "text" : "password";
            y.classList.toggle('bi-eye'); y.classList.toggle('bi-eye-slash');
        }

        // =======================================================
        // 💡 JAVASCRIPT BARU: AJAX GANTI PASSWORD (BIAR BISA SHAKE)
        // =======================================================
        function gantiPasswordAjax(event) {
            event.preventDefault(); // Tahan biar halaman gak refresh
            
            let form = document.getElementById('formGantiPw');
            let formData = new FormData(form);
            formData.append('update_password_ajax', 'true'); // Lempar kode unik ke PHP atas

            let btn = document.getElementById('btn-simpan-pw');
            let originalText = btn.innerHTML;
            btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Loading...`;
            btn.disabled = true;

            // Tembak data ke file ini sendiri
            fetch('profil.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                
                if (data.status === 'success') {
                    // Kalo sukses, tutup modal & munculin notif ijo
                    bootstrap.Modal.getInstance(document.getElementById('modalPw')).hide();
                    form.reset();
                    document.getElementById('error-pw-container').style.display = 'none';
                    Swal.fire({ icon: 'success', title: 'Berhasil', text: data.pesan, showConfirmButton: false, timer: 1500 });
                } else {
                    // 💡 KALO GAGAL: JURUS SHAKE MODAL!
                    let modalContent = document.querySelector('#modalPw .modal-content');
                    modalContent.classList.remove('shake-modal'); // Reset animasi kalau di-klik berkali-kali
                    void modalContent.offsetWidth; // Trik trigger ulang animasi
                    modalContent.classList.add('shake-modal');
                    
                    let errContainer = document.getElementById('error-pw-container');
                    errContainer.innerHTML = `<span class="error-text bg-white px-2 py-1 rounded border"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${data.pesan}</span>`;
                    errContainer.style.display = 'block';
                }
            })
            .catch(err => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                Swal.fire({ icon: 'error', title: 'Sistem Sibuk', text: 'Silakan coba lagi.', showConfirmButton: false, timer: 1500 });
            });
        }

        // Preview Foto & Ilangin Error Kalau Ganti File
        document.getElementById('input-foto').addEventListener('change', function(){
            const errorMsg = document.getElementById('error-foto-size');
            errorMsg.style.display = 'none';
            this.classList.remove('shake-animation'); 
            
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e){
                    document.getElementById('preview-foto').src = e.target.result;
                    document.getElementById('input_aksi_foto').value = 'upload'; 
                }
                reader.readAsDataURL(file);
            }
        });

        // VALIDASI EFEK SHAKE PAS TOMBOL SIMPAN FOTO DIKLIK
        document.getElementById('formFoto').addEventListener('submit', function(e) {
            const fileInput = document.getElementById('input-foto');
            const errorMsg = document.getElementById('error-foto-size');
            const aksi = document.getElementById('input_aksi_foto').value;
            const file = fileInput.files[0];

            if (aksi === 'upload' && file) {
                if (file.size > 2097152) { // 2097152 bytes = 2MB
                    e.preventDefault(); 
                    
                    errorMsg.style.display = 'block'; 
                    
                    fileInput.classList.remove('shake-animation');
                    void fileInput.offsetWidth; 
                    fileInput.classList.add('shake-animation');
                }
            }
        });

        <?php if($success_msg): ?> 
            Swal.fire({ icon: 'success', title: 'Berhasil', text: '<?php echo $success_msg; ?>', confirmButtonColor: 'var(--theme-primary)' }); 
        <?php endif; ?>
        <?php if($error_msg): ?> 
            Swal.fire({ icon: 'error', title: 'Gagal', text: '<?php echo $error_msg; ?>', confirmButtonColor: '#ef4444' }); 
        <?php endif; ?>
        // =======================================================
        // 💡 JAVASCRIPT BARU: CLEANER OTOMATIS PAS MODAL DITUTUP
        // =======================================================
        document.getElementById('modalPw').addEventListener('hidden.bs.modal', function () {
            // 1. Cabut efek kotak merah (shake)
            document.querySelector('#modalPw .modal-content').classList.remove('shake-modal');
            
            // 2. Sembunyiin pesan error-nya
            document.getElementById('error-pw-container').style.display = 'none';
            
            // 3. Kosongin isi ketikan form-nya
            document.getElementById('formGantiPw').reset();
            
            // 4. Balikin tipe input ke 'password' dan reset ikon mata ke kondisi awal
            ['n0', 'n1', 'n2'].forEach(id => document.getElementById(id).type = 'password');
            ['i0', 'i1', 'i2'].forEach(id => {
                let icon = document.getElementById(id);
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            });
        });
    </script>
</div> 
</body>
</html>
