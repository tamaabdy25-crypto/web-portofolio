<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'koneksi.php';

// 1. CEK MODE: Apakah ini mode Display (t) atau Dashboard?
$is_display = isset($_GET['t']);
$my_id   = $_SESSION['user_id'] ?? 0;
$my_role = $_SESSION['role'] ?? 'karyawan';

// 2. SET ZONE & SYNC
date_default_timezone_set('Asia/Jakarta');
pg_query($conn, "SET TIME ZONE 'Asia/Jakarta'");

// 3. LOGIKA AUTO-ARCHIVE
pg_query($conn, "UPDATE meetings 
    SET is_finished = 1 
    WHERE is_finished = 0 
    AND TO_TIMESTAMP(meeting_date || ' ' || end_time, 'YYYY-MM-DD HH24:MI:SS') <= NOW()");

$nowWIB = time(); 

// --- LOGIKA PAGINATION (Batas 10 data per halaman) ---
$limit = 10; 
$halaman = isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
$offset = ($halaman - 1) * $limit;

// 4. FILTER QUERY CERDAS
if ($is_display) {
    // Mode DISPLAY: Tampilkan SEMUA jadwal hari ini
    $where_clause = "WHERE meeting_date = CURRENT_DATE AND is_finished = 0";
    $order_clause = "ORDER BY start_time ASC";
} else {
    // Mode DASHBOARD: Cuma munculin jadwal milik sendiri
    if (!$my_id) { exit(); } 
    $where_clause = "WHERE user_id = '$my_id' AND is_finished = 0";
    $order_clause = "ORDER BY meeting_date ASC, start_time ASC";
}

// --- HITUNG TOTAL DATA UNTUK MENCARI JUMLAH HALAMAN ---
$query_count = pg_query($conn, "SELECT COUNT(*) as total FROM meetings $where_clause");
$row_count = pg_fetch_assoc($query_count);
$total_data = $row_count['total'];
$total_halaman = ceil($total_data / $limit);

// --- QUERY UTAMA DENGAN LIMIT & OFFSET ---
$sql = "SELECT * FROM meetings $where_clause $order_clause LIMIT $limit OFFSET $offset";
$res = pg_query($conn, $sql);

echo "<table class='table table-hover align-middle'>
        <thead>
            <tr>
                <th>Agenda & Ruang</th>
                <th>Tanggal & Waktu</th>
                <th style='text-align: center;'>Status</th>";
                if (!$is_display) { echo "<th style='text-align: center;'>Aksi</th>"; }
echo "      </tr>
        </thead>
        <tbody>";

if (pg_num_rows($res) == 0) {
    $colspan = $is_display ? 3 : 4;
    $pesan = $is_display ? "Tidak ada jadwal meeting hari ini." : "Belum ada jadwal meeting yang kamu buat.";
    
    // ============================================================================
    // --- BAGIAN YANG DIUBAH: UKURAN LOGO DIPERBESAR SESUAI MOCKUP ---
    // height diubah jadi 55px, width jadi 280px, dan margin-bottom 15px
    // ============================================================================
    echo "<tr><td colspan='$colspan' style='text-align:center; color:#64748b; padding: 60px;'>
            <div style='margin: 0 auto 15px auto; height: 55px; width: 280px; background-color: #64748b; -webkit-mask: url(\"logo_evision2.png\") no-repeat center; mask: url(\"logo_evision2.png\") no-repeat center; -webkit-mask-size: contain; mask-size: contain; opacity: 0.85;'></div>
            <span style='font-size: 15px; font-weight: 500;'>$pesan</span>
          </td></tr>";
} else {
    // --- ARRAY NAMA BULAN INDONESIA ---
    $bulan_indo = [
        '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
        '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
        '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember'
    ];

    while ($row = pg_fetch_assoc($res)) {
        $startWIB = strtotime($row['meeting_date'] . ' ' . $row['start_time']);
        $endWIB   = strtotime($row['meeting_date'] . ' ' . $row['end_time']);

        $statusBadge = "";
        $rowStyle = "";
        $progressBar = "";

        // Logika Status
        if ($nowWIB < $startWIB) {
            $statusBadge = '<span class="status-badge" style="background:#f1f5f9; color:#64748b; font-size:10px; padding:4px 10px; border-radius:10px; font-weight:700;">MENDATANG</span>';
        } elseif ($nowWIB >= $startWIB && $nowWIB <= $endWIB) {
            $totalDurasi = $endWIB - $startWIB;
            $sudahLewat  = $nowWIB - $startWIB;
            $persen      = ($totalDurasi > 0) ? round(($sudahLewat / $totalDurasi) * 100) : 0;
            
            $statusBadge = '<span class="status-badge" style="background:#dcfce7; color:#16a34a; font-size:10px; padding:4px 10px; border-radius:10px; font-weight:700;">BERLANGSUNG</span>';
            $rowStyle = "style='background: rgba(16, 185, 129, 0.03);'"; 
            $progressBar = '
                <div style="height: 4px; width: 60px; background: #e2e8f0; border-radius: 10px; margin: 5px auto 0; overflow: hidden;">
                    <div style="height: 100%; width: '.$persen.'%; background: #10b981;"></div>
                </div>';
        }

        // --- KONVERSI TANGGAL KE FORMAT INDONESIA ---
        $tgl_db = explode('-', $row['meeting_date']);
        $tanggal_format_baru = $tgl_db[2] . ' ' . $bulan_indo[$tgl_db[1]] . ' ' . $tgl_db[0];

        echo "<tr $rowStyle>
                <td>
                    <span style='display:block; font-weight:700; color: #000000; font-size:18px;'>" . htmlspecialchars($row['title']) . "</span>
                    <span class='badge bg-light text-success border' style='font-size: 10px; padding: 3px 6px;'><i class='bi bi-door-open me-1'></i>" . htmlspecialchars($row['room_name']) . "</span>
                </td>
                <td>
                    <div class='fw-bold' style='font-size:16px; color: #000000; line-height:1.2;'>" . $tanggal_format_baru . "</div>
                    <div class='text-muted' style='font-size: 12px;'><i class='bi bi-clock me-1'></i>" . substr($row['start_time'], 0, 5) . " - " . substr($row['end_time'], 0, 5) . " WIB</div>
                </td>
                <td style='text-align: center; vertical-align: middle;'>
                    $statusBadge
                    $progressBar
                </td>";

                if (!$is_display) {
                    echo "<td style='text-align: center; vertical-align: middle;'>";
                    if ($nowWIB < $startWIB) {
                        echo "<button onclick=\"bukaEdit('{$row['id']}', '{$row['title']}', '{$row['room_name']}', '{$row['meeting_date']}', '{$row['start_time']}', '{$row['end_time']}')\" 
                             style='background:none; border:none; color:#f59e0b; padding:0; margin-right:8px;' title='Edit Jadwal'>
                             <i class='bi bi-pencil-square fs-5'></i></button>";
                        echo "<button onclick=\"konfirmasiHapus('{$row['id']}')\" 
                             style='background:none; border:none; color:#ef4444; padding:0;' title='Hapus Jadwal'>
                             <i class='bi bi-trash fs-5'></i></button>";
                    } else {
                        echo "<button onclick='selesaikanMeeting(" . $row['id'] . ")' 
                             style='background:none; border:none; color:#10b981; padding:0;' title='Tandai Selesai'>
                             <i class='bi bi-check-circle-fill fs-4'></i></button>";
                    }
                    echo "</td>";
                }
        echo "</tr>";
    }
}
echo "</tbody></table>";

// --- MENAMPILKAN TOMBOL PAGINATION DI BAWAH TABEL ---
if ($total_halaman > 1) {
    echo '<nav class="mt-4 pt-3 border-top">';
    echo '<ul class="pagination justify-content-center mb-0">';
    for ($i = 1; $i <= $total_halaman; $i++) {
        $activeClass = ($halaman == $i) ? 'active' : '';
        echo '<li class="page-item ' . $activeClass . '"><a class="page-link shadow-sm" href="?hal=' . $i . '">' . $i . '</a></li>';
    }
    echo '</ul></nav>';
}
?>
