<?php
session_start();
include 'koneksi.php';

// Fallback kalau di file koneksi.php lu pakenya $koneksi
if(isset($koneksi) && !isset($conn)) {
    $conn = $koneksi;
}

// PROTEKSI HALAMAN (Hanya Role IT yang boleh masuk)
if (!isset($_SESSION['logged_in']) || $_SESSION['role'] !== 'IT') {
    echo "<script>alert('Akses Ditolak! Anda bukan admin IT.'); window.location.href='input.php';</script>";
    exit;
}

$status_msg = "";
$search = $_GET['search'] ?? '';

// --- LOGIKA HAPUS AKUN ---
if (isset($_POST['hapus_akun'])) {
    $id_user   = $_POST['id_user_hapus'];

    // Hapus file tema jika ada sebelum menghapus user
    $cek_file = mysqli_query($conn, "SELECT theme_wallpaper FROM users WHERE id = '$id_user'");
    $data_file = mysqli_fetch_assoc($cek_file);
    if (!empty($data_file['theme_wallpaper']) && file_exists($data_file['theme_wallpaper'])) {
        unlink($data_file['theme_wallpaper']); 
    }

    if (mysqli_query($conn, "DELETE FROM users WHERE id = '$id_user'")) {
        $status_msg = "sukses_hapus";
    } else {
        $status_msg = "gagal_sistem";
    }
}

// ==============================================================
// 💡 BLOK BARU: LOGIKA EDIT & RESET AKUN (SISTEM AJAX ANTI-LOADING)
// ==============================================================
if (isset($_POST['edit_akun_ajax'])) {
    header('Content-Type: application/json');
    $id_user   = $_POST['id_user'];
    $nama      = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email     = mysqli_real_escape_string($conn, $_POST['email']);
    $role      = $_POST['role'];
    $pw_custom = trim($_POST['pw_custom']); 

    // Ambil data user target dulu buat ngecek password lamanya
    $q_target = mysqli_query($conn, "SELECT password FROM users WHERE id='$id_user'");
    $target_user = mysqli_fetch_assoc($q_target);

    // Kalau kolom password diisi, berarti sekalian ganti password
    if (!empty($pw_custom)) {
        if (strlen($pw_custom) < 6) {
            echo json_encode(["status" => "error", "pesan" => "Password baru minimal 6 karakter bosku!"]);
            exit;
        }
        // 💡 GEMBOK BARU: Gak boleh sama dengan password target saat ini
        if (password_verify($pw_custom, $target_user['password'])) {
            echo json_encode(["status" => "error", "pesan" => "Kocak! Password reset gak boleh sama persis kayak password dia saat ini!"]);
            exit;
        }
        $hashed_pw = password_hash($pw_custom, PASSWORD_DEFAULT);
        $query_update = "UPDATE users SET nama_lengkap='$nama', email='$email', role='$role', password='$hashed_pw' WHERE id='$id_user'";
    } else {
        // Kalau kolom password kosong, update nama, email & role aja
        $query_update = "UPDATE users SET nama_lengkap='$nama', email='$email', role='$role' WHERE id='$id_user'";
    }

    if(mysqli_query($conn, $query_update)) {
        echo json_encode(["status" => "success", "pesan" => "Data akun berhasil di-update!"]);
    } else {
        echo json_encode(["status" => "error", "pesan" => "Gagal update ke database!"]);
    }
    exit; // Stop HTML render, cukup kirim JSON ke JS
}
// ==============================================================

// --- LOGIKA REGISTER AKUN BARU ---
if (isset($_POST['register_akun'])) {
    $no_induk = mysqli_real_escape_string($conn, $_POST['no_induk']);
    $nama     = mysqli_real_escape_string($conn, $_POST['nama_lengkap']);
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $pass     = $_POST['password'];
    $role     = $_POST['role'];

    if (strlen($pass) < 6) {
        $status_msg = "pw_kurang";
    } else {
        $cek = mysqli_query($conn, "SELECT id FROM users WHERE nomor_induk='$no_induk'");
        if (mysqli_num_rows($cek) > 0) {
            $status_msg = "duplikat";
        } else {
            $hashed_pw = password_hash($pass, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (nomor_induk, nama_lengkap, email, password, role) 
                    VALUES ('$no_induk', '$nama', '$email', '$hashed_pw', '$role')";            
            if (mysqli_query($conn, $sql)) {
                $status_msg = "sukses_register";
            } else {
                $status_msg = "gagal_sistem";
            }
        }
    }
}

// --- LOGIKA PAGINATION & SEARCH (NAMA ATAU NO INDUK) ---
$limit = 10; 
$halaman = isset($_GET['hal']) ? (int)$_GET['hal'] : 1;
$offset = ($halaman - 1) * $limit;

$where_clause = "";
if (!empty($search)) {
    $search_safe = mysqli_real_escape_string($conn, $search);
    $where_clause = " WHERE nomor_induk LIKE '%$search_safe%' OR nama_lengkap LIKE '%$search_safe%' OR email LIKE '%$search_safe%' ";
}

$sql_count = mysqli_query($conn, "SELECT COUNT(*) as total FROM users $where_clause");
$row_count = mysqli_fetch_assoc($sql_count);
$total_data = $row_count['total'];
$total_halaman = ceil($total_data / $limit);

$query_user = mysqli_query($conn, "SELECT id, nama_lengkap, nomor_induk, email, role FROM users 
    $where_clause 
    ORDER BY nama_lengkap ASC 
    LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>IT RESET PANEL - MANAGEMENT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            background-color: #0f172a; 
            font-family: 'Inter', sans-serif; 
            color: #334155;
        }
        
        .card { 
            border-radius: 16px; 
            border: none; 
            overflow: hidden; 
            background: #ffffff; 
        }
        
        .admin-header { 
            background: linear-gradient(135deg, #ef4444, #b91c1c); 
        }
        
        .table thead { background: #f8fafc; color: #475569; }
        .table th { font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
        .table td, .table th { border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        
        .pagination .page-link { border: none; color: #ef4444; background: #f1f5f9; margin: 0 3px; border-radius: 8px !important; font-weight: 600;}
        .pagination .page-item.active .page-link { background: #ef4444; color: white; box-shadow: 0 4px 6px rgba(239, 68, 68, 0.2); }
        
        .badge-role { font-size: 11px; padding: 4px 10px; border-radius: 6px; font-weight: 700; letter-spacing: 0.5px; }
        .bg-it { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .bg-user { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }

        .btn-outline-danger { border-color: #fca5a5; color: #dc2626; }
        .btn-outline-danger:hover { background-color: #ef4444; color: white; border-color: #ef4444;}
        .btn-outline-primary { border-color: #bfdbfe; color: #2563eb; }
        .btn-outline-primary:hover { background-color: #3b82f6; color: white; border-color: #3b82f6;}

        /* --- MEDIA QUERY KHUSUS MOBILE --- */
        @media (max-width: 768px) {
            body { padding-top: 15px !important; padding-bottom: 20px !important; }
            .card-header { padding: 20px 15px !important; }
            
            .header-content { flex-direction: column; align-items: stretch !important; gap: 15px; }
            .header-title { text-align: center; font-size: 1.2rem !important; }
            .header-actions { display: flex; width: 100%; gap: 10px; }
            .header-actions button, .header-actions a { flex: 1; padding: 10px; font-size: 12px; display: flex; justify-content: center; align-items: center; white-space: nowrap; margin: 0 !important;}
            
            .search-group { flex-direction: column; gap: 10px; }
            .search-group .form-control { width: 100%; border-radius: 8px !important; }
            .search-group .btn { width: 100%; border-radius: 8px !important; }

            .table thead { display: none; } 
            .table tbody tr { 
                display: flex; 
                flex-direction: row; 
                justify-content: space-between; 
                align-items: center; 
                padding: 12px 10px; 
                border-bottom: 1px solid #e2e8f0; 
            }
            .table tbody td { display: block; padding: 0; border: none; }
            
            .action-cell { display: flex; flex-direction: row; gap: 6px; margin-top: 0; }
            .action-cell .btn { flex: unset; padding: 8px 12px; font-size: 14px; }
            .action-cell .btn span { display: none !important; }
        }

        /* =======================================================
           💡 CSS BARU: ANIMASI SHAKE BUAT MODAL ERROR
           ======================================================= */
        @keyframes shakeModal {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        .shake-modal {
            animation: shakeModal 0.3s ease-in-out 0s 2;
            border: 2px solid #ef4444 !important;
        }
        .error-text {
            color: #ef4444;
            font-size: 11px;
            font-weight: 700;
            margin-top: 5px;
            display: inline-block;
        }
    </style>
</head>
<body class="py-4">
<div class="container">
    <div class="card shadow-lg">
        
        <div class="card-header admin-header text-white p-4 header-content d-flex justify-content-between align-items-center">
            <h5 class="m-0 fw-bold header-title"><i class="bi bi-shield-lock-fill me-2 text-warning"></i>IT RESET PANEL</h5>
            <div class="header-actions">
                <button type="button" class="btn btn-light fw-bold text-danger me-2 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRegister" style="border-radius: 8px;">
                    <i class="bi bi-person-plus-fill me-1"></i> Tambah Akun
                </button>
                <a href="input.php" class="btn btn-outline-light fw-bold" style="border-radius: 8px;">
                    <i class="bi bi-box-arrow-left me-1"></i> Kembali
                </a>
            </div>
        </div>
        
        <div class="card-body p-3 p-md-4">
            
            <form method="GET" class="mb-4">
                <div class="input-group search-group shadow-sm" style="border-radius: 10px; overflow: hidden;">
                    <span class="input-group-text bg-white border-end-0 d-none d-md-flex"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0 ps-md-0" placeholder="Cari Nama Lengkap, Email, atau No. Induk..." value="<?php echo htmlspecialchars($search); ?>" style="background-color: #f8fafc;">
                    <button class="btn btn-danger px-4 fw-bold" type="submit">CARI</button>
                    <?php if(!empty($search)): ?>
                        <a href="it_reset_rahasia.php" class="btn btn-secondary fw-bold">RESET</a>
                    <?php endif; ?>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="py-3 px-3 rounded-start">Identitas Karyawan</th>
                            <th class="text-center py-3 px-3 rounded-end" style="width: 250px;">Aksi Manajemen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($query_user) > 0): ?>
                            <?php while($u = mysqli_fetch_assoc($query_user)): ?>
                            <tr>
                                <td class="px-3">
                                    <div class="d-flex align-items-center mb-1">
                                        <div class="fw-bold fs-6 me-2 text-dark">
                                            <?php echo !empty($u['nama_lengkap']) ? htmlspecialchars($u['nama_lengkap']) : '<span class="text-danger">[NAMA KOSONG]</span>'; ?>
                                        </div>
                                        <span class="badge-role <?php echo ($u['role'] == 'IT') ? 'bg-it' : 'bg-user'; ?>">
                                            <i class="bi <?php echo ($u['role'] == 'IT') ? 'bi-shield-shaded' : 'bi-person-badge'; ?> me-1"></i><?php echo $u['role']; ?>
                                        </span>
                                    </div>
                                    <div class="small text-muted fw-semibold">
                                        <i class="bi bi-upc-scan me-1"></i> No. Induk: <span class="text-dark me-3"><?php echo !empty($u['nomor_induk']) ? htmlspecialchars($u['nomor_induk']) : '[KOSONG]'; ?></span>
                                        <br class="d-md-none">
                                        <i class="bi bi-envelope me-1"></i> Email: <span class="text-dark"><?php echo !empty($u['email']) ? htmlspecialchars($u['email']) : '[BELUM ADA EMAIL]'; ?></span>
                                    </div>
                                </td>
                                <td class="text-center px-3 action-cell">
                                    <button class="btn btn-sm btn-outline-primary" onclick="bukaEditUser('<?php echo $u['id']; ?>', '<?php echo htmlspecialchars($u['nomor_induk']); ?>', '<?php echo htmlspecialchars($u['nama_lengkap']); ?>', '<?php echo htmlspecialchars($u['email']); ?>', '<?php echo $u['role']; ?>')">
                                        <i class="bi bi-pencil-square"></i> <span>Edit</span>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="bukaHapusUser('<?php echo $u['id']; ?>', '<?php echo htmlspecialchars($u['nama_lengkap']); ?>')">
                                        <i class="bi bi-trash"></i> <span>Hapus</span>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center py-5">
                                    <i class="bi bi-search fs-1 text-muted opacity-50 d-block mb-3"></i>
                                    <span class="text-dark fw-bold">Data Karyawan Tidak Ditemukan.</span>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if($total_halaman > 1): ?>
            <nav class="mt-4 pt-3 border-top">
                <ul class="pagination justify-content-center mb-0">
                    <?php for($i=1; $i<=$total_halaman; $i++): ?>
                        <li class="page-item <?php if($halaman == $i) echo 'active'; ?>">
                            <a class="page-link shadow-sm" href="?hal=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRegister" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header bg-danger text-white border-0" style="border-radius: 16px 16px 0 0;">
        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>Registrasi Akun Baru</h5>
        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <div class="modal-body p-4">
              <div class="mb-3">
                  <label class="form-label small fw-bold text-dark">Nomor Induk</label>
                  <input type="text" name="no_induk" class="form-control bg-light" placeholder="Input Nomor Induk Karyawan" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
              </div>
              <div class="mb-3">
                  <label class="form-label small fw-bold text-dark">Nama Lengkap</label>
                  <input type="text" name="nama_lengkap" class="form-control bg-light" placeholder="Input Nama Lengkap Karyawan" required>
              </div>
              <div class="mb-3">
                  <label class="form-label small fw-bold text-dark">Alamat Email</label>
                  <input type="email" name="email" class="form-control bg-light" placeholder="example@email.com" required>
              </div>
              <div class="row">
                  <div class="col-md-6 mb-3">
                      <label class="form-label small fw-bold text-dark">Role Akun</label>
                      <select name="role" class="form-select bg-light" required>
                          <option value="USER" selected>USER</option>
                          <option value="IT">IT (Admin)</option>
                      </select>
                  </div>
                  <div class="col-md-6 mb-3">
                      <label class="form-label small fw-bold text-dark">Password Awal</label>
                      <input type="text" name="password" class="form-control bg-light" placeholder="Min. 6 Karakter" minlength="6" required>
                  </div>
              </div>
          </div>
          <div class="modal-footer border-0 p-4 pt-0">
            <button type="button" class="btn btn-light fw-bold w-100 mb-2" data-bs-dismiss="modal" style="border-radius: 8px;">Batal</button>
            <button type="submit" name="register_akun" class="btn btn-danger fw-bold w-100 m-0 shadow-sm" style="border-radius: 8px;">Daftarkan Akun</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEditUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header bg-primary text-white border-0" style="border-radius: 16px 16px 0 0;">
        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Data Akun</h5>
        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <form id="formEditUser" onsubmit="editAkunAjax(event)">
          <input type="hidden" name="id_user" id="edit_id_user">
          <div class="modal-body p-4 pb-2">
              <div class="mb-3">
                  <label class="form-label small fw-bold text-muted"><i class="bi bi-lock-fill me-1"></i>Nomor Induk (Permanen)</label>
                  <input type="text" id="edit_no_induk" class="form-control" readonly style="background: #e2e8f0; font-weight: bold;">
              </div>
              <div class="mb-3">
                  <label class="form-label small fw-bold text-dark">Nama Lengkap</label>
                  <input type="text" name="nama_lengkap" id="edit_nama" class="form-control bg-light" required>
              </div>
              <div class="mb-3">
                  <label class="form-label small fw-bold text-dark">Alamat Email</label>
                  <input type="email" name="email" id="edit_email" class="form-control bg-light" required>
              </div>
              <div class="mb-3">
                  <label class="form-label small fw-bold text-dark">Role Akun</label>
                  <select name="role" id="edit_role" class="form-select bg-light" required>
                      <option value="USER">USER</option>
                      <option value="IT">IT (Admin)</option>
                  </select>
              </div>
              <div class="mb-3">
                  <label class="form-label small fw-bold text-dark">Reset Password (Opsional)</label>
                  <input type="text" name="pw_custom" class="form-control border-primary border-opacity-50 bg-primary bg-opacity-10" placeholder="Ketik password baru..." minlength="6">
                  <small class="text-muted d-block mt-1" style="font-size: 11px;">*Kosongkan jika tidak ingin mengubah password user.</small>
              </div>
              
              <div id="error-edit-container" class="text-center mt-3" style="display: none;"></div>
          </div>
          <div class="modal-footer border-0 p-4 pt-0">
            <button type="button" class="btn btn-light fw-bold w-100 mb-2" data-bs-dismiss="modal" style="border-radius: 8px;">Batal</button>
            <button type="submit" id="btn-simpan-edit" class="btn btn-primary fw-bold w-100 m-0 shadow-sm" style="border-radius: 8px;">Simpan Perubahan</button>
          </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="modalHapusUser" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
      <div class="modal-header bg-danger text-white border-0" style="border-radius: 16px 16px 0 0;">
        <h5 class="modal-title fw-bold"><i class="bi bi-exclamation-triangle-fill me-2"></i>Hapus Akun Permanen</h5>
        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
          <input type="hidden" name="id_user_hapus" id="hapus_id_user">
          <div class="modal-body text-center p-4">
              <i class="bi bi-trash3 text-danger opacity-75" style="font-size: 4rem;"></i>
              <p class="mt-3 mb-4 fs-6 text-dark">Anda yakin ingin menghapus seluruh data dan akses <strong class="text-danger fs-5 d-block mt-2" id="hapus_nama_user"></strong>?</p>
          </div>
          <div class="modal-footer border-0 p-4 pt-0 d-flex gap-2">
            <button type="button" class="btn btn-light fw-bold flex-fill m-0" data-bs-dismiss="modal" style="border-radius: 8px;">Batal</button>
            <button type="submit" name="hapus_akun" class="btn btn-danger fw-bold flex-fill m-0 shadow-sm" style="border-radius: 8px;">Hapus</button>
          </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
function bukaEditUser(id, no_induk, nama, email, role) {
    document.getElementById('edit_id_user').value = id;
    document.getElementById('edit_no_induk').value = no_induk;
    document.getElementById('edit_nama').value = nama;
    document.getElementById('edit_email').value = email;
    const roleSelect = document.getElementById('edit_role');
    for(let i=0; i<roleSelect.options.length; i++){
        if(roleSelect.options[i].value === role){
            roleSelect.selectedIndex = i;
            break;
        }
    }
    new bootstrap.Modal(document.getElementById('modalEditUser')).show();
}

function bukaHapusUser(id, nama) {
    document.getElementById('hapus_id_user').value = id;
    document.getElementById('hapus_nama_user').innerText = nama;
    new bootstrap.Modal(document.getElementById('modalHapusUser')).show();
}

// =======================================================
// 💡 JAVASCRIPT BARU: AJAX EDIT & RESET AKUN
// =======================================================
function editAkunAjax(event) {
    event.preventDefault(); 
    
    let form = document.getElementById('formEditUser');
    let formData = new FormData(form);
    formData.append('edit_akun_ajax', 'true'); 

    let btn = document.getElementById('btn-simpan-edit');
    let originalText = btn.innerHTML;
    btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Loading...`;
    btn.disabled = true;

    fetch('it_reset_rahasia.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        
        if (data.status === 'success') {
            bootstrap.Modal.getInstance(document.getElementById('modalEditUser')).hide();
            document.getElementById('error-edit-container').style.display = 'none';
            Swal.fire({ icon: 'success', title: 'Berhasil!', text: data.pesan, confirmButtonColor: '#10b981' })
            .then(() => window.location.reload());
        } else {
            // JURUS SHAKE MODAL
            let modalContent = document.querySelector('#modalEditUser .modal-content');
            modalContent.classList.remove('shake-modal');
            void modalContent.offsetWidth; 
            modalContent.classList.add('shake-modal');
            
            let errContainer = document.getElementById('error-edit-container');
            errContainer.innerHTML = `<span class="error-text bg-white px-2 py-1 rounded border"><i class="bi bi-exclamation-triangle-fill me-1"></i> ${data.pesan}</span>`;
            errContainer.style.display = 'block';
        }
    })
    .catch(err => {
        btn.innerHTML = originalText;
        btn.disabled = false;
        Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal terhubung ke server.', confirmButtonColor: '#ef4444' });
    });
}

// 💡 PEMBERSIH OTOMATIS PAS MODAL DITUTUP (BIAR GAK MERAH TERUS)
document.getElementById('modalEditUser').addEventListener('hidden.bs.modal', function () {
    document.querySelector('#modalEditUser .modal-content').classList.remove('shake-modal');
    document.getElementById('error-edit-container').style.display = 'none';
    document.querySelector('input[name="pw_custom"]').value = ''; // Kosongin password aja, yg lain di-isi ulang sama fungsi bukaEditUser
});
// =======================================================

function cleanURL() { window.history.replaceState({}, document.title, window.location.pathname); }

<?php if($status_msg == "sukses_hapus"): ?>
    Swal.fire({icon: 'success', title: 'Terhapus!', text: 'Akun berhasil dihapus.', confirmButtonColor: '#10b981'}).then(cleanURL);
<?php elseif($status_msg == "sukses_register"): ?>
    Swal.fire({icon: 'success', title: 'Berhasil!', text: 'Akun baru telah aktif.', confirmButtonColor: '#10b981'}).then(cleanURL);
<?php elseif($status_msg == "pw_kurang"): ?>
    Swal.fire({icon: 'warning', title: 'Password Lemah!', text: 'Minimal 6 karakter.', confirmButtonColor: '#f59e0b'}).then(cleanURL);
<?php elseif($status_msg == "duplikat"): ?>
    Swal.fire({icon: 'error', title: 'Gagal', text: 'Nomor Induk tersebut sudah ada.', confirmButtonColor: '#ef4444'}).then(cleanURL);
<?php elseif($status_msg == "gagal_sistem"): ?>
    Swal.fire({icon: 'error', title: 'Error Sistem', text: 'Database gagal memproses.', confirmButtonColor: '#ef4444'}).then(cleanURL);
<?php endif; ?>
</script>
</body>
</html>