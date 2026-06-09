<?php
// MATIKAN ERROR HTML BIAR JSON GAK RUSAK (PENTING DI VERCEL!)
error_reporting(0);
ini_set('display_errors', 0);

// 1. IZIN AKSES (CORS) - Penting banget buat API!
header("Access-Control-Allow-Origin: *"); 
header("Content-Type: application/json; charset=UTF-8");

// Set Zona Waktu ke WIB biar fungsi time() gak pake jam server luar negeri
date_default_timezone_set('Asia/Jakarta');

// 2. KONEKSI KE DATABASE (Dapur)
include 'koneksi.php';

// 3. AMBIL DATA DARI POSTGRESQL (Supabase)
$query_kalender = @pg_query($conn, "SELECT * FROM meetings ORDER BY meeting_date ASC, start_time ASC");

// Tangkap error kalau database lagi ngambek
if (!$query_kalender) {
    echo json_encode(["status" => "error", "pesan" => "DB Error: " . pg_last_error($conn)]);
    exit;
}

$events = [];
$waktu_sekarang = time(); // Jam saat ini (sudah WIB)

while($row = pg_fetch_assoc($query_kalender)) {
    // Format tanggal khusus buat kalender
    $start_dt = $row['meeting_date'] . 'T' . $row['start_time'];
    $end_dt = $row['meeting_date'] . 'T' . $row['end_time'];
    
    // FIX POSTGRESQL: Cek tipe data boolean, kadang Postgres nge-return 't', true, atau 1
    $is_finished_db = $row['is_finished'];
    $sudah_selesai = ($is_finished_db === 't' || $is_finished_db === true || $is_finished_db == 1);
    
    // Cek apakah waktu sudah lewat
    $is_done = ($sudah_selesai || strtotime($end_dt) < $waktu_sekarang);
    
    // Susun datanya ke dalam array
    $events[] = [
        'id' => $row['id'],
        'title' => strtoupper($row['title']),
        'start' => $start_dt,
        'end' => $end_dt,
        'is_done' => $is_done, // Kirim status selesai atau belum
        'extendedProps' => [
            'ruang' => $row['room_name'],
            'pengusul' => $row['nama_pengusul'],
            'waktu' => substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5) . ' WIB',
            'status' => $is_done ? 'Selesai / Arsip' : 'Mendatang / Live'
        ]
    ];
}

// 4. JADIKAN FORMAT JSON (Bungkus ke dalam nampan)
echo json_encode([
    "status" => "success",
    "pesan" => "Data jadwal berhasil diambil",
    "data" => $events
]);
?>

