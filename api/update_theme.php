<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$my_id = $_SESSION['user_id'];

// =========================================================================
// KUNCI BRANKAS SUPABASE LU (WAJIB DIISI!)
// =========================================================================
$supabase_url = "https://psskhwflnkyzzhdtxdku.supabase.co"; // Ganti pake URL lu
$supabase_key = "sb_secret_" . "9aoyVHQqFyk4sUVf2gRyIQ_bAhRCHsB"; // Trik ninja GitHub
$bucket_name  = "evision-storage";
// =========================================================================

// --- FUNGSI PENGHANCUR FILE DI SUPABASE ---
function hapusFileSupabase($url_file, $supabase_url, $supabase_key, $bucket_name) {
    if (!empty($url_file)) {
        // Ambil nama file dari ujung URL (contoh: theme_1_123.jpg)
        $file_name = basename($url_file); 
        $delete_url = $supabase_url . "/storage/v1/object/" . $bucket_name . "/" . $file_name;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $delete_url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $supabase_key,
            "apikey: " . $supabase_key
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}
// ------------------------------------------

// --- LOGIKA RESET TEMA ---
if (isset($_POST['reset_theme'])) {
    $cek = pg_query($conn, "SELECT theme_wallpaper FROM users WHERE id = '$my_id'");
    $d = pg_fetch_assoc($cek);
    
    // 1. Hapus file fisik di Supabase dulu
    hapusFileSupabase($d['theme_wallpaper'], $supabase_url, $supabase_key, $bucket_name);
    
    // 2. Baru kosongin databasenya
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

    // --- 💡 HAPUS TEMA LAMA SEBELUM UPLOAD YANG BARU ---
    $cek_lama = pg_query($conn, "SELECT theme_wallpaper FROM users WHERE id = '$my_id'");
    $data_lama = pg_fetch_assoc($cek_lama);
    hapusFileSupabase($data_lama['theme_wallpaper'], $supabase_url, $supabase_key, $bucket_name);
    // ------------------------------------------------

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
    if ($http_code == 200 || $http_code == 201) { // 💡 Tambahin 201 buat jaga-jaga status Created
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
