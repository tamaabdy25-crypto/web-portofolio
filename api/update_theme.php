<?php
session_start();
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$my_id = $_SESSION['user_id'];

// --- LOGIKA RESET ---
if (isset($_POST['reset_theme'])) {
    $cek = mysqli_query($conn, "SELECT theme_wallpaper FROM users WHERE id = '$my_id'");
    $d = mysqli_fetch_assoc($cek);
    if (!empty($d['theme_wallpaper']) && file_exists($d['theme_wallpaper'])) { unlink($d['theme_wallpaper']); }
    mysqli_query($conn, "UPDATE users SET theme_wallpaper = NULL WHERE id = '$my_id'");
    header("Location: input.php?status=reset_sukses");
    exit();
}

// --- LOGIKA UPLOAD (DIPERBAIKI: HAPUS FILE LAMA OTOMATIS) ---
if (isset($_POST['set_theme'])) {
    $file_name = $_FILES['wallpaper']['name'];
    $file_tmp  = $_FILES['wallpaper']['tmp_name'];
    $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Hanya cek format, bukan ukuran
    $extensions = array("jpeg", "jpg", "png", "webp");
    
    if (in_array($file_ext, $extensions)) {
        if (!is_dir('uploads')) { mkdir('uploads', 0777, true); }

        // --- TAMBAHAN: HAPUS FILE LAMA DARI FOLDER SEBELUM SIMPAN YANG BARU ---
        $cek_lama = mysqli_query($conn, "SELECT theme_wallpaper FROM users WHERE id = '$my_id'");
        $data_lama = mysqli_fetch_assoc($cek_lama);
        if (!empty($data_lama['theme_wallpaper']) && file_exists($data_lama['theme_wallpaper'])) {
            unlink($data_lama['theme_wallpaper']); 
        }
        // ---------------------------------------------------------------------------

        $nama_unik = "theme_" . $my_id . "_" . time() . "." . $file_ext;
        $target_dir = "uploads/" . $nama_unik;

        if (move_uploaded_file($file_tmp, $target_dir)) {
            mysqli_query($conn, "UPDATE users SET theme_wallpaper = '$target_dir' WHERE id = '$my_id'");
            header("Location: input.php?status=tema_sukses");
        } else {
            echo "Gagal! XAMPP kamu masih mencekal file besar. Cek php.ini (upload_max_filesize).";
        }
    } else {
        echo "Format file harus gambar (JPG/PNG/WEBP)!";
    }
    exit();
}
?>