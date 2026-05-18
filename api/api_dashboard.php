<?php
session_start();
// Izin akses API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'koneksi.php';

// 1. SET ZONE & LOGIKA AUTO-ARCHIVE (Memaksa selesai jika jam sudah lewat)
date_default_timezone_set('Asia/Jakarta');
pg_query($conn, "SET TIME ZONE 'Asia/Jakarta'");
pg_query($conn, "UPDATE meetings 
    SET is_finished = 1 
    WHERE is_finished = 0 
    AND TO_TIMESTAMP(meeting_date || ' ' || end_time, 'YYYY-MM-DD HH24:MI:SS') <= NOW()");

// Cek Keamanan
$my_id = $_SESSION['user_id'] ?? 0;
if (!$my_id) {
    echo json_encode(["status" => "error", "pesan" => "Akses ditolak. Belum login."]);
    exit;
}

// 2. AMBIL DATA JADWAL LIVE MILIK USER
$sql = "SELECT * FROM meetings WHERE user_id = '$my_id' AND is_finished = 0 ORDER BY meeting_date ASC, start_time ASC";
$res = pg_query($conn, $sql);

$data_live = [];
$nowWIB = time();
$bulan_indo = ['01'=>'Januari','02'=>'Februari','03'=>'Maret','04'=>'April','05'=>'Mei','06'=>'Juni','07'=>'Juli','08'=>'Agustus','09'=>'September','10'=>'Oktober','11'=>'November','12'=>'Desember'];

while ($row = pg_fetch_assoc($res)) {
    $startWIB = strtotime($row['meeting_date'] . ' ' . $row['start_time']);
    $endWIB   = strtotime($row['meeting_date'] . ' ' . $row['end_time']);
    
    // Default Status
    $row['status_berjalan'] = 'mendatang';
    $row['persen_jalan'] = 0;
    
    // Cek apakah sedang berlangsung saat ini detik ini
    if ($nowWIB >= $startWIB && $nowWIB <= $endWIB) {
        $row['status_berjalan'] = 'berlangsung';
        $totalDurasi = $endWIB - $startWIB;
        $sudahLewat  = $nowWIB - $startWIB;
        $row['persen_jalan'] = ($totalDurasi > 0) ? round(($sudahLewat / $totalDurasi) * 100) : 0;
    } elseif ($nowWIB > $endWIB) {
        $row['status_berjalan'] = 'lewat'; // Berjaga-jaga jika terlewat beberapa detik sblm auto-archive
    }

    // Rapikan teks agar Frontend JS tidak perlu pusing mikirin format
    $tgl_db = explode('-', $row['meeting_date']);
    $row['tanggal_indo'] = $tgl_db[2] . ' ' . $bulan_indo[$tgl_db[1]] . ' ' . $tgl_db[0];
    $row['jam_format'] = substr($row['start_time'], 0, 5) . " - " . substr($row['end_time'], 0, 5);
    
    $data_live[] = $row;
}

// 3. BUNGKUS JADI JSON
echo json_encode([
    "status" => "success",
    "data" => $data_live
]);
?>
