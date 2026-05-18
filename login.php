<?php
session_start();
include 'koneksi.php';

// 1. CEK COOKIE UNTUK AUTO LOGIN
if (!isset($_SESSION['logged_in']) && isset($_COOKIE['ingat_nomor_induk']) && isset($_COOKIE['token_aman'])) {
    $no_induk_cookie = $_COOKIE['ingat_nomor_induk'];
    $token_cookie = $_COOKIE['token_aman'];

    $query_cek = mysqli_query($conn, "SELECT * FROM users WHERE nomor_induk='$no_induk_cookie'");
    if (mysqli_num_rows($query_cek) === 1) {
        $data_cookie = mysqli_fetch_assoc($query_cek);
        
        if ($token_cookie === hash('sha256', $data_cookie['nomor_induk'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id']   = $data_cookie['id'];
            $_SESSION['no_induk']  = $data_cookie['nomor_induk'];
            $_SESSION['nama_lengkap'] = $data_cookie['nama_lengkap'];
            $_SESSION['role'] = $data_cookie['role'];

            header("Location: input.php");
            exit;
        }
    }
}

// 2. CEK SESSION
if (isset($_SESSION['logged_in'])) {
    header("Location: input.php");
    exit;
}

// --- LOGIKA AMBIL DATA DARI COOKIE ---
$remembered_no_induk = $_COOKIE['ingat_nomor_induk'] ?? '';

$error = "";
if (isset($_POST['login'])) {
    $no_induk = mysqli_real_escape_string($conn, $_POST['nomor_induk']);
    $pass = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE nomor_induk='$no_induk'");
    if (mysqli_num_rows($query) === 1) {
        $data = mysqli_fetch_assoc($query);
        
        if (password_verify($pass, $data['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id']   = $data['id'];
            $_SESSION['no_induk']  = $data['nomor_induk'];
            $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
            $_SESSION['role'] = $data['role'];

            if (isset($_POST['remember_me'])) {
                $durasi = time() + (86400 * 365 * 50); 
                setcookie('ingat_nomor_induk', $no_induk, $durasi, "/");
                setcookie('token_aman', hash('sha256', $no_induk), $durasi, "/");
            } else {
                setcookie('ingat_nomor_induk', '', time() - 3600, "/");
                setcookie('token_aman', '', time() - 3600, "/");
            }
            
            header("Location: input.php");
            exit;
        } else { 
            $error = "Password salah!"; 
        }
    } else { 
        $error = "Nomor Induk tidak ditemukan!"; 
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-VISION - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body, html { height: 100%; margin: 0; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        
        .login-container { 
            display: flex; 
            min-height: 100vh; 
            width: 100%; 
        }
        
        /* DESKTOP BACKGROUND */
        .login-side-image {
            flex: 1.2;
            background: linear-gradient(rgba(16, 185, 129, 0.85), rgba(5, 150, 105, 0.95)), 
                        url('https://xwork.co/blog/wp-content/uploads/2016/06/business-meeting.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            color: white;
            position: relative; 
        }

        .login-side-form {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .form-box { width: 100%; max-width: 400px; }
        .form-control { padding: 12px; border-radius: 10px; background-color: #f8fafc; border: 1px solid #e2e8f0; }
        .btn-success { padding: 12px; border-radius: 10px; font-weight: 700; background-color: #10b981; border: none; }
        .input-group-text { cursor: pointer; background: #f8fafc; border-radius: 0 10px 10px 0; }
        .form-check-input:checked { background-color: #10b981; border-color: #10b981; }
        
        /* --- STYLING LOGO POJOK ATAS (DESKTOP) --- */
        .desktop-logo-main {
            position: absolute; 
            top: 30px; /* Didorong lebih nempel ke atap layar */
            left: 35px; /* Didorong lebih nempel ke pojok kiri */
            max-width: 280px; /* Ukuran diperbesar drastis biar teks bawahnya kebaca */
            filter: drop-shadow(0px 2px 4px rgba(0,0,0,0.2));
        }

        /* --- STYLING TEXT WELCOME BESAR --- */
        .welcome-text {
            font-size: 4rem; 
            font-weight: 800;
            letter-spacing: -1px;
            margin-bottom: 10px;
        }

        /* Trik ubah Logo Putih jadi Gelap khusus untuk di form mobile */
        .mobile-logo {
            filter: brightness(0) opacity(0.8); 
        }

        /* --- MEDIA QUERY KHUSUS MOBILE --- */
        @media (max-width: 992px) {
            .login-container {
                flex-direction: column;
                justify-content: center; 
                align-items: center;
                padding: 20px; 
                background: linear-gradient(rgba(16, 185, 129, 0.8), rgba(5, 150, 105, 0.9)), 
                            url('https://xwork.co/blog/wp-content/uploads/2016/06/business-meeting.jpg');
                background-size: cover;
                background-position: center;
                background-attachment: fixed;
            }
            
            .login-side-image {
                display: none !important; 
            }

            .login-side-form {
                flex: none;
                width: 100%;
                max-width: 450px;
                background-color: #ffffff;
                border-radius: 24px; 
                padding: 40px 25px 30px 25px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.2); 
            }
            
            .form-box { max-width: 100%; margin: 0 auto; }
            
            .mobile-label { text-transform: uppercase; letter-spacing: 0.5px; font-size: 0.75rem !important;}
        }
    </style>
</head>
<body>

<div class="login-container">
    
    <div class="login-side-image d-none d-lg-flex">
        
        <img src="logo_evision1.png" alt="Logo E-VISION" class="desktop-logo-main">
        
        <h1 class="welcome-text">Selamat Datang</h1>
        
        <p class="fs-5 opacity-75 mt-2" style="max-width: 450px; line-height: 1.6;">
            Sistem Manajemen Jadwal Meeting yang Cepat, Aman, dan Terorganisir.
        </p>
        
        <div class="mt-4">
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-check-circle-fill me-3 fs-4 text-warning"></i>
                <span class="fs-5">Tempat dan Waktu Real Time Disajikan</span>
            </div>
            <div class="d-flex align-items-center mb-3">
                <i class="bi bi-check-circle-fill me-3 fs-4 text-warning"></i>
                <span class="fs-5">Tidak Bentrok dengan Jadwal Lain</span>
            </div>
        </div>
    </div>

    <div class="login-side-form">
        <div class="form-box">
            
            <div class="text-center mb-4 d-block d-lg-none">
                <img src="logo_evision1.png" alt="Logo E-VISION" class="mobile-logo mb-2" style="max-width: 180px;">
            </div>

            <div class="mb-4 text-start">
                <h3 class="fw-bold text-dark mb-1" style="font-size: 1.5rem;">Login Akun</h3>
                <p class="text-muted small">Silakan masuk untuk melanjutkan.</p>
            </div>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold mobile-label">Nomor Induk</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" name="nomor_induk" class="form-control border-start-0" 
                               placeholder="Contoh: 2024001" oninput="this.value = this.value.replace(/[^0-9]/g, '');" 
                               value="<?php echo htmlspecialchars($remembered_no_induk); ?>"
                               inputmode="numeric" required style="border-radius: 0 10px 10px 0;">
                    </div>
                </div>
                
                <div class="mb-3">
                   <label class="form-label small fw-bold mobile-label">Password</label>
                    <div class="input-group">
                   <span class="input-group-text bg-white border-end-0" style="border-radius: 10px 0 0 10px;"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" id="p" class="form-control border-start-0 border-end-0" 
                         placeholder="••••••••" required>
                        <span class="input-group-text" onclick="v()" id="t"><i class="bi bi-eye-slash" id="i"></i></span>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember_me" id="rememberMe" <?php echo $remembered_no_induk ? 'checked' : ''; ?>>
                        <label class="form-check-label small" for="rememberMe" style="font-size: 13px; cursor: pointer;">
                            Ingatkan Akun Saya
                        </label>
                    </div>
                    <a href="javascript:void(0)" onclick="pesanLupaPw()" class="text-success text-decoration-none small fw-bold" style="font-size: 12px;">Lupa Password?</a>
                </div>

                <button type="submit" name="login" class="btn btn-success w-100 shadow-sm mb-2" style="padding: 14px;">MASUK SEKARANG</button>
            </form>
        </div>
    </div>
</div>

<script>
    function v() {
        const p = document.getElementById('p'); const i = document.getElementById('i');
        p.type = p.type === "password" ? "text" : "password";
        i.classList.toggle('bi-eye'); i.classList.toggle('bi-eye-slash');
    }
    <?php if($error): ?>
    Swal.fire({ icon: 'error', title: 'Login Gagal', text: '<?php echo $error; ?>', confirmButtonColor: '#10b981' });
    <?php endif; ?>

    function pesanLupaPw() {
        Swal.fire({
            title: 'Lupa Password?',
            html: 'Silakan hubungi <b>Tim IT</b> atau datang ke ruang admin untuk melakukan reset password akun Anda.',
            icon: 'info',
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Siap, Mengerti'
        });
    }
</script>

</body>
</html>