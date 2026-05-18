<?php
// 1. IZIN AKSES (CORS) - Penting banget buat API!
// Ini ngasih tau kalau file API ini boleh dipanggil oleh aplikasi siapapun (Web, Android, dll)
header("Access-Control-Allow-Origin: *"); 
header("Content-Type: application/json; charset=UTF-8"); // Ngasih tau format outputnya adalah JSON

// 2. KONEKSI KE DATABASE (Dapur)
include 'koneksi.php';

// 3. AMBIL DATA DARI MYSQL
$query_kalender = mysqli_query($conn, "SELECT * FROM meetings ORDER BY meeting_date ASC, start_time ASC");
$events = [];

while($row = mysqli_fetch_assoc($query_kalender)) {
    // Format tanggal khusus buat kalender
    $start_dt = $row['meeting_date'] . 'T' . $row['start_time'];
    $end_dt = $row['meeting_date'] . 'T' . $row['end_time'];
    
    $is_done = ($row['is_finished'] == 1 || strtotime($end_dt) < time());
    
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
// echo json_encode itu perintah sakti buat ngubah array PHP jadi teks JSON
echo json_encode([
    "status" => "success",
    "pesan" => "Data jadwal berhasil diambil",
    "data" => $events
]);
?>