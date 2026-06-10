<?php
// 🔥 FIX 1: NYALAKAN ERROR DULU BIAR KELIATAN PENYAKIT ASLINYA APA
error_reporting(E_ALL);
ini_set('display_errors', 1);

// BERSIHKAN OUTPUT BUFFER BIAR GAK ADA SPASI NYELIP DARI KONEKSI.PHP
if (ob_get_length()) ob_clean();

// 1. IZIN AKSES (CORS)
header("Access-Control-Allow-Origin: *"); 
header("Content-Type: application/json; charset=UTF-8");

// Set Zona Waktu ke WIB
date_default_timezone_set('Asia/Jakarta');

// 2. KONEKSI KE DATABASE
include 'koneksi.php';

// 🔥 FIX 2: CEK KONEKSI DULU! Kalau putus, kasih tau JSON error, jangan langsung di-query
if (!$conn) {
    echo json_encode(["status" => "error", "pesan" => "Koneksi database Supabase terputus!"]);
    exit;
}

// ==============================================================
// 3. 🔥 FIX TOTAL: TANGKAP NAMA USER & SARING DATABASE SUPABASE 🔥
// ==============================================================
$user_login = $_GET['user'] ?? '';
$user_login = pg_escape_string($conn, $user_login); // Pengaman anti SQL Injection

if (!empty($user_login)) {
    // KUNCI SAKTI: Hanya ambil jadwal jika nama lu adalah Pengusul ATAU nama lu ada di kolom peserta!
    $query_kalender = pg_query($conn, "SELECT * FROM meetings WHERE nama_pengusul = '$user_login' OR peserta LIKE '%$user_login%' ORDER BY meeting_date ASC, start_time ASC");
} else {
    // Proteksi Keamanan: Kalau parameter nama kosong/hancur, kunci data biar gak bocor!
    $query_kalender = pg_query($conn, "SELECT * FROM meetings WHERE 1=0");
}

// 🔥 FIX 3: Tangkap error tanpa bikin PHP crash
if (!$query_kalender) {
    echo json_encode(["status" => "error", "pesan" => "Tabel meetings gagal dibaca atau gak ketemu!"]);
    exit;
}

$events = [];
$waktu_sekarang = time(); // Jam saat ini (WIB)

while($row = pg_fetch_assoc($query_kalender)) {
    // Format tanggal khusus buat kalender
    $start_dt = $row['meeting_date'] . 'T' . $row['start_time'];
    $end_dt = $row['meeting_date'] . 'T' . $row['end_time'];
    
    // FIX POSTGRESQL: Cek tipe data boolean
    $is_finished_db = $row['is_finished'] ?? 'f';
    $sudah_selesai = ($is_finished_db === 't' || $is_finished_db === true || $is_finished_db == 1);
    
    // Cek apakah waktu sudah lewat
    $is_done = ($sudah_selesai || strtotime($end_dt) < $waktu_sekarang);
    
    // Susun datanya ke dalam array
    $events[] = [
        'id' => $row['id'],
        'title' => strtoupper($row['title']),
        'start' => $start_dt,
        'end' => $end_dt,
        'is_done' => $is_done,
        'extendedProps' => [
            'ruang' => $row['room_name'],
            'pengusul' => $row['nama_pengusul'] ?? 'Sistem',
            'waktu' => substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5) . ' WIB',
            'status' => $is_done ? 'Selesai / Arsip' : 'Mendatang / Live'
        ]
    ];
}

// 4. JADIKAN FORMAT JSON
echo json_encode([
    "status" => "success",
    "pesan" => "Data jadwal berhasil diambil",
    "data" => $events
]);
exit;
?>
