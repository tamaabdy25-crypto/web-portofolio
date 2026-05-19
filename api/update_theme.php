<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$my_id = $_SESSION['user_id'];

// =========================================================================
// KUNCI BRANKAS SUPABASE LU (WAJIB DIISI!)
// =========================================================================
$supabase_url = "https://psskhwflnkyzzhdtxdku.supabase.co"; // Ganti pake: https://psskhwflnkyzzhdtxdku.supabase.co
$supabase_key = "sb_publishable_N2b5N1u8PlEc8dDk6yJzOw_DsQIVG9v";     // Ganti pake: sb_publishable_...
$bucket_name  = "evision-storage";
// =========================================================================

// --- LOGIKA RESET TEMA ---
if (isset($_POST['reset_theme'])) {
    // Kita reset di database aja (gak usah repot hapus file di Supabase dulu biar aman)
    pg_query($conn, "UPDATE users SET theme_wallpaper = NULL WHERE id = '$my_id'");
    header("Location: input.php?status=reset_sukses");
    exit();
}

// --- LOGIKA UPLOAD JALUR VIP SUPABASE ---
if (isset($_POST['set_theme'])) {
    $file_name = $_FILES['wallpaper']['name'];
    $file_tmp  = $_FILES['wallpaper']['tmp_name'];
    $file_size = $_FILES['wallpaper']['size'];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // 1. SATPAM FORMAT FILE
    $extensions = array("jpeg", "jpg", "png", "webp");
    if (!in_array($file_ext, $extensions)) {
        echo "<script>alert('Waduh! Format file harus gambar (JPG/PNG/WEBP)!'); window.location.href='input.php';</script>";
        exit();
    }

    // 2. SATPAM UKURAN (MAX 7MB = 7340032 bytes)
    if ($file_size > 7340032) {
        echo "<script>alert('Gagal! Ukuran wallpaper kebesaran. Maksimal 7MB ya!'); window.location.href='input.php';</script>";
        exit();
    }

    // 3. SIAPIN NAMA FILE BIAR GAK BENTROK
    $nama_unik = "theme_" . $my_id . "_" . time() . "." . $file_ext;
    
    // Alamat lengkap kurir buat nganter file
    $upload_url = $supabase_url . "/storage/v1/object/" . $bucket_name . "/" . $nama_unik;
    
    // Baca isi file fisik yang mau dikirim
    $file_content = file_get_contents($file_tmp);
    $mime_type = mime_content_type($file_tmp);

    // 4. PROSES KURIR MENGIRIM FILE (cURL)
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $upload_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST"); // Harus POST karena bikin file baru
    curl_setopt($ch, CURLOPT_POSTFIELDS, $file_content);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Bearer " . $supabase_key,
        "apikey: " . $supabase_key,
        "Content-Type: " . $mime_type
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 5. CEK STATUS KIRIMAN
    if ($http_code == 200) {
        // SUKSES NYAMPE KE SUPABASE! 
        // Sekarang kita ambil link URL Publik-nya biar bisa ditampilin di web
        $public_url = $supabase_url . "/storage/v1/object/public/" . $bucket_name . "/" . $nama_unik;

        // Simpan URL itu ke Database lu
        pg_query($conn, "UPDATE users SET theme_wallpaper = '$public_url' WHERE id = '$my_id'");

        header("Location: input.php?status=tema_sukses");
    } else {
        // GAGAL
        echo "<script>alert('Oops! Kurir gagal nganter foto ke Supabase. Cek URL/API Key lagi!'); window.location.href='input.php';</script>";
    }
    exit();
}
?>
