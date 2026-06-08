<?php
// MATIKAN ERROR HTML BIAR JSON GAK RUSAK (PENTING DI VERCEL!)
error_reporting(0);
ini_set('display_errors', 0);

// 1. IZIN AKSES (CORS)
header("Access-Control-Allow-Origin: *"); 
header("Content-Type: application/json; charset=UTF-8");

// Set Zona Waktu ke WIB
date_default_timezone_set('Asia/Jakarta');

// --- TAMBAHAN: AMBIL SESSION USER ---
session_start();
$my_id = $_SESSION['user_id'] ?? 0;

// ⚠️ PENTING: Ambil nama user yang login (sesuaikan dengan nama session lu)
// Kalau di session lu nyimpen nama pakai 'nama' atau 'username', ganti di bawah ini:
$my_name = $_SESSION['nama'] ?? ''; 

// 2. KONEKSI KE DATABASE
include 'koneksi.php';

// 3. AMBIL DATA SESUAI STRUKTUR TABEL LU
// Logika: Tampilkan jika dia pembuatnya (user_id) ATAU namanya ada di kolom 'peserta'
$sql = "
    SELECT * FROM meetings 
    WHERE user_id = '$my_id' 
    OR peserta ILIKE '%$my_name%' 
    ORDER BY meeting_date ASC, start_time ASC
";
// Note: ILIKE dipakai di PostgreSQL biar pencariannya gak peduli huruf besar/kecil (Case-Insensitive)

$query_kalender = @pg_query($conn, $sql);

// Tangkap error database
if (!$query_kalender) {
    echo json_encode(["status" => "error", "pesan" => "DB Error: " . pg_last_error($conn)]);
    exit;
}

$events = [];
$waktu_sekarang = time();

while($row = pg_fetch_assoc($query_kalender)) {
    $start_dt = $row['meeting_date'] . 'T' . $row['start_time'];
    $end_dt = $row['meeting_date'] . 'T' . $row['end_time'];
    
    // Cek status selesai dari kolom is_finished
    $is_finished_db = $row['is_finished'];
    $sudah_selesai = ($is_finished_db === 't' || $is_finished_db === true || $is_finished_db == 1);
    
    // Cek apakah waktu sudah lewat
    $is_done = ($sudah_selesai || strtotime($end_dt) < $waktu_sekarang);
    
    // Susun data untuk dikirim ke FullCalendar
    $events[] = [
        'id' => $row['id'],
        'title' => strtoupper($row['title']),
        'start' => $start_dt,
        'end' => $end_dt,
        'is_done' => $is_done,
        'extendedProps' => [
            'ruang' => $row['room_name'],
            'pengusul' => $row['nama_pengusul'], // Menggunakan kolom nama_pengusul sesuai DB lu
            'waktu' => substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5) . ' WIB',
            'status' => $is_done ? 'Selesai / Arsip' : 'Mendatang / Live'
        ]
    ];
}

// 4. KIRIM HASILNYA
echo json_encode([
    "status" => "success",
    "pesan" => "Data jadwal berhasil diambil",
    "data" => $events
]);
?>
