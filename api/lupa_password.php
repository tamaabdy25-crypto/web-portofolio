<?php
include 'koneksi.php';

$alert_status = "";

if (isset($_POST['reset_pw'])) {
    $no_induk = pg_escape_string($conn, $_POST['nomor_induk']);
    $pw_baru  = $_POST['password'];

    // 1. Cek apakah Nomor Induk terdaftar
    $cek_user = pg_query($conn, "SELECT * FROM users WHERE nomor_induk = '$no_induk'");
    
    if (pg_num_rows($cek_user) > 0) {
        // 2. Hash password baru (6 digit)
        $hashed_pw = password_hash($pw_baru, PASSWORD_DEFAULT);
        
        // 3. Update ke database
        $update = pg_query($conn, "UPDATE users SET password = '$hashed_pw' WHERE nomor_induk = '$no_induk'");
        
        if ($update) {
            $alert_status = "sukses";
        } else {
            $alert_status = "gagal_update";
        }
    } else {
        $alert_status = "user_tidak_ada";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-VISION - Lupa Password</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; margin: 0; }
        .main-container { display: flex; height: 100vh; width: 100%; overflow: hidden; }

        /* Banner Hijau Samping */
        .side-visual {
            flex: 1.2; background: linear-gradient(rgba(16, 185, 129, 0.9), rgba(5, 150, 105, 0.9)), 
            url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80');
            background-size: cover; background-position: center;
            display: flex; flex-direction: column; justify-content: center; padding: 80px; color: white;
        }

        .side-visual h1 { font-size: 3.5rem; font-weight: 800; letter-spacing: -2px; margin-bottom: 20px; }
        .side-visual p { font-size: 1.2rem; opacity: 0.9; max-width: 500px; line-height: 1.6; }

        /* Form Reset */
        .side-form { flex: 1; background: white; display: flex; align-items: center; justify-content: center; padding: 40px; }
        .form-box { width: 100%; max-width: 400px; }
        .form-control { border-radius: 10px; padding: 12px; background-color: #f8fafc; font-size: 14px; }
        .btn-primary { background-color: #10b981; border: none; padding: 12px; border-radius: 10px; font-weight: 700; width: 100%; }
        
        @media (max-width: 992px) {
            .main-container { flex-direction: column; height: auto; min-height: 100vh; background-color: #10b981; }
            .side-visual { display: flex !important; height: 150px; padding: 20px; flex: none; justify-content: center; align-items: center; }
            .side-visual h1 { font-size: 22px; margin: 0; }
            .side-visual p { display: none; }
            .side-form { flex: 1; border-radius: 30px 30px 0 0; margin-top: -30px; position: relative; z-index: 5; }
        }
    </style>
</head>
<body>

<div class="main-container">
    <div class="side-visual">
        <h1>Jadwal<span> Meeting</span></h1>
        <p>Atur ulang kata sandi Anda untuk kembali mengelola jadwal meeting dengan mudah.</p>
    </div>

    <div class="side-form">
        <div class="form-box">
            <div class="mb-4">
                <h3 class="fw-bold text-dark">Lupa Password?</h3>
                <p class="text-muted small">Masukkan Nomor Induk Anda untuk mereset akun.</p>
            </div>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nomor Induk Karyawan</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" name="nomor_induk" class="form-control border-start-0" placeholder="Masukkan Nomor Induk Karyawan Anda" oninput="this.value = this.value.replace(/[^0-9]/g, '');" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Password Baru (Minimal 6 Karakter)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" id="p" class="form-control border-start-0 border-end-0" placeholder="Masukkan Password Baru" minlength="6" required>
                        <span class="input-group-text bg-white" onclick="v()" style="border-radius: 0 10px 10px 0; cursor: pointer;"><i class="bi bi-eye-slash" id="i"></i></span>
                    </div>
                </div>

                <button type="submit" name="reset_pw" class="btn btn-primary shadow-sm mb-3">Atur Ulang Password</button>
                
                <div class="text-center">
                    <a href="login.php" class="text-decoration-none small fw-bold text-muted"><i class="bi bi-arrow-left me-1"></i> Kembali ke Login</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Fungsi Lihat Password
    function v() {
        var x = document.getElementById("p");
        var y = document.getElementById("i");
        if (x.type === "password") {
            x.type = "text";
            y.className = "bi bi-eye";
        } else {
            x.type = "password";
            y.className = "bi bi-eye-slash";
        }
    }

    // Notifikasi SweetAlert
    <?php if($alert_status == "sukses"): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Password Anda telah diperbarui. Silakan login kembali.',
            confirmButtonColor: '#10b981'
        }).then(() => { window.location.href = "login.php"; });
    <?php elseif($alert_status == "user_tidak_ada"): ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: 'Nomor Induk tidak ditemukan di sistem kami.',
            confirmButtonColor: '#ef4444'
        });
    <?php elseif($alert_status == "gagal_update"): ?>
        Swal.fire({
            icon: 'warning',
            title: 'Ups!',
            text: 'Terjadi kesalahan sistem, coba lagi nanti.',
            confirmButtonColor: '#f59e0b'
        });
    <?php endif; ?>
</script>

</body>
</html>
