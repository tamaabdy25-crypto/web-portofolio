<?php
// MATIKAN ERROR HTML BIAR JSON GAK RUSAK (PENTING DI VERCEL!)
error_reporting(0);
ini_set('display_errors', 0);

session_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
include 'koneksi.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "pesan" => "Akses ditolak. Belum login."]);
    exit;
}

$my_id = $_SESSION['user_id'];
$my_no_induk = $_SESSION['no_induk'] ?? "";
$nama_lengkap = $_SESSION['nama_lengkap'] ?? "Tamu";

date_default_timezone_set('Asia/Jakarta');
$hari_ini = date('Y-m-d');
$jam_sekarang = date('H:i');

// ==============================================================
// 0. AUTO-CREATE TABEL KOTAK POS (ANTREAN EMAIL) BIAR KILAT ⚡
// ==============================================================
$cek_tabel = @pg_query($conn, "SELECT tablename FROM pg_tables WHERE tablename = 'antrean_email'");
if(pg_num_rows($cek_tabel) == 0) {
    @pg_query($conn, "CREATE TABLE antrean_email (
        id SERIAL PRIMARY KEY,
        email_tujuan VARCHAR(100),
        nama_tujuan VARCHAR(100),
        subjek VARCHAR(255),
        pesan_html TEXT,
        status INT DEFAULT 0
    )");
}

// ==============================================================
// 1. OTOMATIS TAMBAH KOLOM PESERTA BIAR GA ERROR
// ==============================================================
$check_col = @pg_query($conn, "SELECT column_name FROM information_schema.columns WHERE table_name='meetings' AND column_name='peserta'");
if(pg_num_rows($check_col) == 0) {
    @pg_query($conn, "ALTER TABLE meetings ADD peserta TEXT NULL");
}

// ==============================================================
// 2. OBAT SAKTI: PATCH OTOMATIS BIAR JADWAL LAMA KEBACA PESERTANYA
// ==============================================================
$q_patch = @pg_query($conn, "SELECT id FROM meetings WHERE peserta IS NULL OR peserta = ''");
if ($q_patch) {
    while($r_patch = pg_fetch_assoc($q_patch)) {
        $id_patch = $r_patch['id'];
        $sync_p = @pg_query($conn, "SELECT u.nama_lengkap FROM agenda_peserta ap JOIN users u ON ap.id_user = u.id WHERE ap.id_agenda = '$id_patch'");
        if ($sync_p) {
            $arr_p = [];
            while($r_p = pg_fetch_assoc($sync_p)){ $arr_p[] = $r_p['nama_lengkap']; }
            if(count($arr_p) > 0) {
                $str_p = pg_escape_string($conn, implode(', ', $arr_p));
                @pg_query($conn, "UPDATE meetings SET peserta = '$str_p' WHERE id = '$id_patch'");
            }
        }
    }
}

$action = $_POST['action'] ?? '';

// --- 1. AKSI TAMBAH JADWAL ---
if ($action == 'tambah') {
    $date  = $_POST['meeting_date'];
    $title = strtoupper(pg_escape_string($conn, $_POST['title'])); 
    $room  = strtoupper(pg_escape_string($conn, $_POST['room_name'])); 
    $start = $_POST['start_time'];
    $end   = $_POST['end_time'];
    
    if ($date < $hari_ini) {
        echo json_encode(["status" => "error", "pesan" => "Tanggal sudah terlewat!"]); exit;
    } else if ($date == $hari_ini && $end <= $jam_sekarang) {
        echo json_encode(["status" => "error", "pesan" => "Waktu sudah lewat dari jam saat ini!"]); exit;
    } else if ($end <= $start) {
        echo json_encode(["status" => "error", "pesan" => "Waktu selesai tidak logis!"]); exit;
    } else {
        // FIX BOOLEAN: is_finished = FALSE
        $check_bentrok = @pg_query($conn, "SELECT * FROM meetings WHERE room_name = '$room' AND meeting_date = '$date' AND is_finished = FALSE AND (('$start' >= start_time AND '$start' < end_time) OR ('$end' > start_time AND '$end' <= end_time) OR (start_time >= '$start' AND start_time < '$end'))");
        
        if ($check_bentrok && pg_num_rows($check_bentrok) > 0) {
            echo json_encode(["status" => "error", "pesan" => "Ruangan dipakai di jam tersebut!"]); exit;
        } else {
            // FIX BOOLEAN: FALSE, bukan 0
            $simpan = @pg_query($conn, "INSERT INTO meetings (title, room_name, meeting_date, start_time, end_time, user_id, is_finished, nomor_induk, nama_pengusul) VALUES ('$title', '$room', '$date', '$start', '$end', '$my_id', FALSE, '$my_no_induk', '$nama_lengkap') RETURNING id");
            
            if ($simpan) {
                $id_meeting_baru = pg_fetch_result($simpan, 0, 'id');

                $peserta_clean = [];
                if (isset($_POST['peserta']) && is_array($_POST['peserta'])) {
                    foreach ($_POST['peserta'] as $p) {
                        $p = pg_escape_string($conn, $p);
                        if (is_numeric($p)) {
                            $peserta_clean[] = $p;
                        } else {
                            $q_cari = @pg_query($conn, "SELECT id FROM users WHERE nama_lengkap = '$p' LIMIT 1");
                            if($r_cari = pg_fetch_assoc($q_cari)){ $peserta_clean[] = $r_cari['id']; }
                        }
                    }
                }
                $peserta_clean = array_unique($peserta_clean);

                foreach ($peserta_clean as $id_karyawan) {
                    // FIX BOOLEAN: FALSE, FALSE, bukan 0, 0
                    @pg_query($conn, "INSERT INTO agenda_peserta (id_agenda, id_user, email_terkirim_undangan, email_terkirim_pengingat) VALUES ('$id_meeting_baru', '$id_karyawan', FALSE, FALSE)");
                }

                $sync_peserta = @pg_query($conn, "SELECT u.nama_lengkap FROM agenda_peserta ap JOIN users u ON ap.id_user = u.id WHERE ap.id_agenda = '$id_meeting_baru'");
                $arr_nama = [];
                while($r = pg_fetch_assoc($sync_peserta)){
                    $arr_nama[] = $r['nama_lengkap'];
                }
                $str_nama = pg_escape_string($conn, implode(', ', $arr_nama));
                @pg_query($conn, "UPDATE meetings SET peserta = '$str_nama' WHERE id = '$id_meeting_baru'");

                echo json_encode(["status" => "success", "pesan" => "Jadwal Baru Disimpan"]); exit;
            } else {
                echo json_encode(["status" => "error", "pesan" => "Gagal Simpan: " . pg_last_error($conn)]); exit;
            }
        }
    }
}

// --- 2. AKSI EDIT JADWAL ---
if ($action == 'edit') {
    $id_edit = $_POST['id_edit'];
    $date  = $_POST['meeting_date'];
    $title = strtoupper(pg_escape_string($conn, $_POST['title'])); 
    $room  = strtoupper(pg_escape_string($conn, $_POST['room_name'])); 
    $start = $_POST['start_time'];
    $end   = $_POST['end_time'];
    
    if ($date < $hari_ini) {
        echo json_encode(["status" => "error", "pesan" => "Tanggal sudah terlewat!"]); exit;
    } else if ($date == $hari_ini && $end <= $jam_sekarang) {
        echo json_encode(["status" => "error", "pesan" => "Waktu sudah lewat dari jam saat ini!"]); exit;
    } else if ($end <= $start) {
        echo json_encode(["status" => "error", "pesan" => "Waktu selesai tidak logis!"]); exit;
    } else {
        // FIX BOOLEAN: is_finished = FALSE
        $check_bentrok = @pg_query($conn, "SELECT * FROM meetings WHERE room_name = '$room' AND meeting_date = '$date' AND is_finished = FALSE AND id != '$id_edit' AND (('$start' >= start_time AND '$start' < end_time) OR ('$end' > start_time AND '$end' <= end_time) OR (start_time >= '$start' AND start_time < '$end'))");
        
        if ($check_bentrok && pg_num_rows($check_bentrok) > 0) {
            echo json_encode(["status" => "error", "pesan" => "Ruangan dipakai di jam tersebut!"]); exit;
        } else {
            $update = @pg_query($conn, "UPDATE meetings SET title='$title', room_name='$room', meeting_date='$date', start_time='$start', end_time='$end' WHERE id='$id_edit'");
            
            if ($update) {
                $peserta_baru = [];
                if (isset($_POST['peserta']) && is_array($_POST['peserta'])) {
                    foreach ($_POST['peserta'] as $p) {
                        $p = pg_escape_string($conn, $p);
                        if (is_numeric($p)) {
                            $peserta_baru[] = $p; 
                        } else {
                            $q_cari = @pg_query($conn, "SELECT id FROM users WHERE nama_lengkap = '$p' LIMIT 1");
                            if ($r_cari = pg_fetch_assoc($q_cari)) {
                                $peserta_baru[] = $r_cari['id'];
                            }
                        }
                    }
                }
                $peserta_baru = array_unique($peserta_baru);
                
                $q_lama = @pg_query($conn, "SELECT id_user FROM agenda_peserta WHERE id_agenda = '$id_edit'");
                $peserta_lama = [];
                while($r_lama = pg_fetch_assoc($q_lama)){
                    $peserta_lama[] = $r_lama['id_user'];
                }

                $peserta_dihapus = array_diff($peserta_lama, $peserta_baru);

                // ==============================================================
                // HAPUS KORBAN & LEMPAR KE ANTREAN EMAIL
                // ==============================================================
                if (!empty($peserta_dihapus)) {
                    foreach ($peserta_dihapus as $id_kick) {
                        $id_kick = pg_escape_string($conn, $id_kick);
                        
                        $q_korban = @pg_query($conn, "SELECT nama_lengkap, email FROM users WHERE id = '$id_kick'");
                        if($r_korban = pg_fetch_assoc($q_korban)) {
                            $nama_korban = $r_korban['nama_lengkap'];
                            $email_korban = $r_korban['email'];
                            
                            if (!empty($email_korban)) {
                                $subject = "Undangan Dibatalkan: " . $title;
                                $message = "
                                <div style='font-family: Arial, sans-serif; color: #333; padding: 20px; border: 1px solid #ddd; border-radius: 10px; max-width: 500px;'>
                                    <h3 style='color: #ef4444; border-bottom: 2px solid #ef4444; padding-bottom: 10px;'>UNDANGAN DIBATALKAN </h3>
                                    <p>Halo, <b>$nama_korban</b>!</p>
                                    <p>Mohon maaf, Anda telah <b>dihapus dari daftar peserta</b> untuk agenda meeting berikut:</p>
                                    <table border='0' cellpadding='6' style='background: #fef2f2; width: 100%; border-radius: 8px;'>
                                        <tr><td width='30%'><b>Agenda</b></td><td>: $title</td></tr>
                                        <tr><td><b>Tanggal</b></td><td>: $date</td></tr>
                                        <tr><td><b>Waktu</b></td><td>: " . substr($start, 0, 5) . " - " . substr($end, 0, 5) . " WIB</td></tr>
                                        <tr><td><b>Ruangan</b></td><td>: $room</td></tr>
                                    </table>
                                    <p style='margin-top: 20px;'>Silakan abaikan undangan sebelumnya. Mohon maklum atas perubahan jadwal ini.</p>
                                </div>
                                ";

                                $em = pg_escape_string($conn, $email_korban);
                                $nm = pg_escape_string($conn, $nama_korban);
                                $sb = pg_escape_string($conn, $subject);
                                $ps = pg_escape_string($conn, $message);
                                @pg_query($conn, "INSERT INTO antrean_email (email_tujuan, nama_tujuan, subjek, pesan_html) VALUES ('$em', '$nm', '$sb', '$ps')");
                            }
                        }

                        @pg_query($conn, "DELETE FROM agenda_peserta WHERE id_agenda = '$id_edit' AND id_user = '$id_kick'");
                    }
                }

                foreach ($peserta_baru as $id_karyawan) {
                    $id_karyawan = pg_escape_string($conn, $id_karyawan);
                    $cek_peserta = @pg_query($conn, "SELECT id FROM agenda_peserta WHERE id_agenda='$id_edit' AND id_user='$id_karyawan'");
                    
                    if (pg_num_rows($cek_peserta) == 0) {
                        // FIX BOOLEAN: FALSE, FALSE
                        @pg_query($conn, "INSERT INTO agenda_peserta (id_agenda, id_user, email_terkirim_undangan, email_terkirim_pengingat) VALUES ('$id_edit', '$id_karyawan', FALSE, FALSE)");
                    }
                }
                
                $sync_peserta = @pg_query($conn, "SELECT u.nama_lengkap FROM agenda_peserta ap JOIN users u ON ap.id_user = u.id WHERE ap.id_agenda = '$id_edit'");
                $arr_nama = [];
                while($r = pg_fetch_assoc($sync_peserta)){
                    $arr_nama[] = $r['nama_lengkap'];
                }
                $str_nama = pg_escape_string($conn, implode(', ', $arr_nama));
                @pg_query($conn, "UPDATE meetings SET peserta = '$str_nama' WHERE id = '$id_edit'");
                
                echo json_encode(["status" => "success", "pesan" => "Agenda Diperbarui"]); exit;
            } else {
                echo json_encode(["status" => "error", "pesan" => "Gagal mengupdate agenda!"]); exit;
            }
        }
    }
}

// --- 3. AKSI HAPUS JADWAL ---
if ($action == 'hapus') {
    $id = pg_escape_string($conn, $_POST['id']);

    $q_meet = @pg_query($conn, "SELECT title, meeting_date, room_name, start_time, end_time FROM meetings WHERE id='$id'");
    if ($r_meet = pg_fetch_assoc($q_meet)) {
        $title = $r_meet['title'];
        $date  = $r_meet['meeting_date'];
        $room  = $r_meet['room_name'];
        $start = substr($r_meet['start_time'], 0, 5);
        $end   = substr($r_meet['end_time'], 0, 5);

        $q_peserta = @pg_query($conn, "SELECT u.nama_lengkap, u.email FROM agenda_peserta ap JOIN users u ON ap.id_user = u.id WHERE ap.id_agenda='$id'");
        
        while ($r_peserta = pg_fetch_assoc($q_peserta)) {
            $nama_peserta = $r_peserta['nama_lengkap'];
            $email_peserta = $r_peserta['email'];

            if (!empty($email_peserta)) {
                $subject = "MEETING DIBATALKAN: " . $title;
                $message = "
                <div style='font-family: Arial, sans-serif; color: #333; padding: 20px; border: 1px solid #ddd; border-radius: 10px; max-width: 500px;'>
                    <h3 style='color: #ef4444; border-bottom: 2px solid #ef4444; padding-bottom: 10px;'>MEETING DIBATALKAN </h3>
                    <p>Halo, <b>$nama_peserta</b>!</p>
                    <p>Mohon maaf, agenda meeting berikut ini telah <b>dibatalkan sepenuhnya</b> oleh penyelenggara:</p>
                    <table border='0' cellpadding='6' style='background: #fef2f2; width: 100%; border-radius: 8px;'>
                        <tr><td width='30%'><b>Agenda</b></td><td>: $title</td></tr>
                        <tr><td><b>Tanggal</b></td><td>: $date</td></tr>
                        <tr><td><b>Waktu</b></td><td>: $start - $end WIB</td></tr>
                        <tr><td><b>Ruangan</b></td><td>: $room</td></tr>
                    </table>
                    <p style='margin-top: 20px;'>Mohon sesuaikan kembali jadwal Anda. Terima kasih atas pengertiannya.</p>
                </div>
                ";

                $em = pg_escape_string($conn, $email_peserta);
                $nm = pg_escape_string($conn, $nama_peserta);
                $sb = pg_escape_string($conn, $subject);
                $ps = pg_escape_string($conn, $message);
                @pg_query($conn, "INSERT INTO antrean_email (email_tujuan, nama_tujuan, subjek, pesan_html) VALUES ('$em', '$nm', '$sb', '$ps')");
            }
        }
    }

    @pg_query($conn, "DELETE FROM agenda_peserta WHERE id_agenda='$id'");
    @pg_query($conn, "DELETE FROM meetings WHERE id='$id'");
    
    echo json_encode(["status" => "success", "pesan" => "Agenda Dihapus"]); exit;
}

// --- 4. AKSI SELESAIKAN JADWAL ---
if ($action == 'selesai') {
    $id = pg_escape_string($conn, $_POST['id']);
    // FIX BOOLEAN: Pake TRUE, bukan 1
    @pg_query($conn, "UPDATE meetings SET is_finished=TRUE WHERE id='$id'");
    echo json_encode(["status" => "success", "pesan" => "Agenda Selesai"]); exit;
}

// --- 5. AKSI SIMPAN NOTULENSI ---
if ($action == 'notulensi') {
    $id_rapat = pg_escape_string($conn, $_POST['id_meeting']);
    $notulensi = pg_escape_string($conn, $_POST['notulensi']);
    $daftar_hadir = pg_escape_string($conn, $_POST['daftar_hadir']);
    $link = pg_escape_string($conn, $_POST['link_lampiran']);

    @pg_query($conn, "UPDATE meetings SET notulensi='$notulensi', daftar_hadir='$daftar_hadir', link_lampiran='$link' WHERE id='$id_rapat' AND user_id='$my_id'");
    echo json_encode(["status" => "success", "pesan" => "Notulensi Disimpan"]); exit;
}
?>
