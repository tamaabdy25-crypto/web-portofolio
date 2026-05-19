<?php
// MATIKAN ERROR HTML BIAR JSON GAK RUSAK (PENTING DI VERCEL!)
error_reporting(0);
ini_set('display_errors', 0);

// Mulai session buat ngenalin siapa yang lagi login (ngambil cookie PHP)
session_start();

// Izin akses API
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

include 'koneksi.php';

// Kalau belum login, API nolak ngasih data (Keamanan!)
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        "status" => "error", 
        "pesan" => "Akses ditolak. Anda belum login."
    ]);
    exit;
}

$my_id = $_SESSION['user_id'];

// --- LOGIKA HALAMAN (PAGINATION) ---
$limit = 10; 
$halaman = isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
$offset = ($halaman - 1) * $limit;

// FIX POSTGRESQL: Kita bikin 2 skenario, coba pake angka (1), kalau gagal (karena Supabase) pake Boolean (TRUE).
// FIX TANGGAL: Cukup pake (meeting_date + end_time)
$syarat_angka = "FROM meetings WHERE user_id = '$my_id' AND (is_finished = 1 OR (meeting_date + end_time) < CURRENT_TIMESTAMP)";
$syarat_bool = "FROM meetings WHERE user_id = '$my_id' AND (is_finished = TRUE OR (meeting_date + end_time) < CURRENT_TIMESTAMP)";

// Cek dulu database maunya bahasa apa
$sql_cek = @pg_query($conn, "SELECT 1 $syarat_angka LIMIT 1");
$query_syarat = ($sql_cek !== false) ? $syarat_angka : $syarat_bool;

// Cek apakah ini minta data buat nge-print semua?
$is_print_mode = isset($_GET['print']) && $_GET['print'] == 'semua';

if ($is_print_mode) {
    // Kalau print, ambil SEMUA tanpa limit
    $sql = "SELECT * $query_syarat ORDER BY meeting_date DESC, end_time DESC";
    $total_halaman = 1;
} else {
    // Kalau normal, hitung total halaman dulu
    $sql_total = @pg_query($conn, "SELECT COUNT(*) as total $query_syarat");
    
    // Kalau masih error, balikin errornya ke Frontend biar kita tau
    if (!$sql_total) {
        echo json_encode(["status" => "error", "pesan" => "DB Error Total: " . pg_last_error($conn)]);
        exit;
    }
    
    $row_total = pg_fetch_assoc($sql_total);
    $total_data = $row_total['total'];
    $total_halaman = ceil($total_data / $limit);
    if ($total_halaman == 0) $total_halaman = 1; // Minimal 1 halaman

    // Ambil data sesuai halaman saat ini (Limit)
    $sql = "SELECT * $query_syarat ORDER BY meeting_date DESC, end_time DESC LIMIT $limit OFFSET $offset";
}

$result = @pg_query($conn, $sql);

if (!$result) {
    echo json_encode(["status" => "error", "pesan" => "DB Error Fetch: " . pg_last_error($conn)]);
    exit;
}

$data_arsip = [];

while ($row = pg_fetch_assoc($result)) {
    // Format tanggal biar gampang dibaca Javascript nanti
    $row['tanggal_format'] = date('d/m/Y', strtotime($row['meeting_date']));
    $row['jam_format'] = substr($row['start_time'], 0, 5) . ' - ' . substr($row['end_time'], 0, 5);
    
    // Pastikan data yang null jadi string kosong biar gak error di frontend
    $row['notulensi'] = $row['notulensi'] ?? '';
    $row['link_lampiran'] = $row['link_lampiran'] ?? '';
    
    // =================================================================
    // 🧠 LOGIKA PINTAR: OTOMATISASI DAFTAR HADIR DARI PESERTA UNDANGAN
    // =================================================================
    $daftar_hadir_db = $row['daftar_hadir'] ?? '';
    
    // Cek apakah daftar hadir kosong DAN ada data peserta yang diundang?
    if (empty(trim($daftar_hadir_db)) && !empty($row['peserta'])) {
        
        // Coba decode siapa tau formatnya JSON Array dari database
        $peserta_array = json_decode($row['peserta'], true);
        
        // Kalau bukan JSON (gagal decode), kita anggap pisah koma biasa
        if (!is_array($peserta_array)) {
            $peserta_array = explode(',', $row['peserta']);
        }

        $list_otomatis = "";
        $no = 1;
        
        foreach ($peserta_array as $p) {
            // Bersihin teks (Misal: "Budi (budi@gmail.com)" -> ambil "Budi" doang)
            $nama_saja = trim(explode(" (", $p)[0]);
            if (!empty($nama_saja)) {
                $list_otomatis .= $no . ". " . $nama_saja . "\n";
                $no++;
            }
        }
        
        // Masukin hasil generate otomatis ke variabel row
        $row['daftar_hadir'] = trim($list_otomatis);
        
    } else {
        // Kalau database udah ada isinya (udah pernah diketik & disave), pakai data asli
        $row['daftar_hadir'] = $daftar_hadir_db;
    }
    // =================================================================
    
    $data_arsip[] = $row;
}

// BUNGKUS JADI JSON (Nampan Data)
echo json_encode([
    "status" => "success",
    "mode_print" => $is_print_mode,
    "halaman_sekarang" => $halaman,
    "total_halaman" => $total_halaman,
    "data" => $data_arsip
]);
?>
