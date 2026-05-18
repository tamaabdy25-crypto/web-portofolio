<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'IT') {
    header("Location: input.php");
    exit;
}
$success = false;
$error_msg = "";
$hari_ini = date('Y-m-d');

if (isset($_POST['register'])) {
    $no_induk = pg_escape_string($conn, $_POST['nomor_induk']);
    $nama     = pg_escape_string($conn, $_POST['nama_lengkap']);
    $pass     = $_POST['password'];
    $role     = $_POST['role']; // Ambil pilihan role dari form

    // 1. Validasi: Password minimal 6 karakter
    if (strlen($pass) < 6) {
        $error_msg = "Password minimal 6 karakter!";
    } else {
        // 2. Cek apakah No Induk sudah terdaftar
        $cek = pg_query($conn, "SELECT id FROM users WHERE no_induk='$no_induk'");
        if (pg_num_rows($cek) > 0) {
            $error_msg = "Nomor Induk ini sudah terdaftar!";
        } else {
            // 3. Hash password sebelum disimpan
            $hashed_pw = password_hash($pass, PASSWORD_DEFAULT);
            
            // 4. INSERT dengan kolom ROLE
            $sql = "INSERT INTO users (no_induk, nama_lengkap, password, role) 
                    VALUES ('$no_induk', '$nama', '$hashed_pw', '$role')";            
            
            if (pg_query($conn, $sql)) {
                $success = true;
            } else {
                $error_msg = "Gagal simpan: " . pg_last_error($conn);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-VISION - Registrasi Akun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body, html { height: 100%; margin: 0; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .register-container { display: flex; height: 100vh; width: 100%; }
        
        .register-side-image {
            flex: 1.2;
            background: linear-gradient(rgba(239, 68, 68, 0.8), rgba(185, 28, 28, 0.9)), 
                        url('https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&q=80');
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 60px;
            color: white;
        }

        .register-side-form {
            flex: 1;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .form-box { width: 100%; max-width: 400px; }
        .form-control, .form-select { padding: 12px; border-radius: 10px; background-color: #f8fafc; border: 1px solid #e2e8f0; }
        .btn-danger { padding: 12px; border-radius: 10px; font-weight: 700; background-color: #ef4444; border: none; }
        .input-group-text { background: #f8fafc; border-radius: 10px 0 0 10px; }
        
        @media (max-width: 992px) {
            .register-container { flex-direction: column; }
            .register-side-image { height: 100px; padding: 20px; }
            .register-side-image h1, .register-side-image p, .register-side-image div { display: none; }
        }
    </style>
</head>
<body>

<div class="register-container">
    <div class="register-side-image">
        <h1 class="fw-bold display-4 mb-3">IT Registration Panel</h1>
        <p class="fs-5 opacity-75">Gunakan halaman ini untuk mendaftarkan akun USER atau IT baru ke dalam sistem.</p>
    </div>

    <div class="register-side-form">
        <div class="form-box">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="fw-bold text-dark mb-1">Daftar Akun</h3>
                    <p class="text-muted small">Input data karyawan baru</p>
                </div>
                <a href="input.php" class="btn btn-sm btn-outline-secondary rounded-pill">Batal</a>
            </div>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nomor Induk Karyawan</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-card-heading text-muted"></i></span>
                        <input type="text" name="nomor_induk" class="form-control" 
                               placeholder="Masukan Nomor Induk" oninput="this.value = this.value.replace(/[^0-9]/g, '');" 
                               required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Lengkap</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-person text-muted"></i></span>
                        <input type="text" name="nama_lengkap" class="form-control" 
                               placeholder="Nama lengkap karyawan" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-bold">Tentukan Role </label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-shield-lock text-muted"></i></span>
                        <select name="role" class="form-select" required>
                            <option value="USER" selected>USER (Karyawan Biasa)</option>
                            <option value="IT">IT (Administrator)</option>
                        </select>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-bold">Password Default</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock text-muted"></i></span>
                        <input type="password" name="password" id="p" class="form-control" 
                               placeholder="••••••" minlength="6" required>
                        <span class="input-group-text bg-white" onclick="v()"><i class="bi bi-eye-slash" id="i"></i></span>
                    </div>
                </div>

                <button type="submit" name="register" class="btn btn-danger w-100 shadow-sm mb-4">DAFTARKAN AKUN</button>
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

    <?php if($success): ?>
    Swal.fire({ 
        icon: 'success', 
        title: 'Berhasil!', 
        text: 'Akun baru telah ditambahkan ke database.', 
        confirmButtonColor: '#ef4444' 
    }).then(() => { window.location.href = 'input.php'; });
    <?php endif; ?>

    <?php if($error_msg): ?>
    Swal.fire({ 
        icon: 'error', 
        title: 'Gagal', 
        text: '<?php echo $error_msg; ?>', 
        confirmButtonColor: '#ef4444' 
    });
    <?php endif; ?>
</script>

</body>
</html>
