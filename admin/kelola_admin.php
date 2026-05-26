<?php
// Akses hanya untuk superadmin
if (empty($_SESSION['is_super'])) {
    echo '<div class="alert alert-danger"><i class="bi bi-shield-x me-2"></i>Akses ditolak. Halaman ini hanya untuk Super Admin.</div>';
    return;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'add') {
            $name     = trim($_POST['name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (!$name || !$username || !$email || strlen($password) < 6) {
                throw new Exception('Semua field wajib diisi & password minimal 6 karakter.');
            }
            if (!preg_match('/^[a-z0-9_]{3,30}$/i', $username)) {
                throw new Exception('Username hanya boleh huruf/angka/underscore, 3-30 karakter.');
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO admins (name, username, email, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $username, $email, $hash]);

            $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (NULL, 'ADMIN_CREATE', ?, ?)");
            $log->execute(["Superadmin membuat admin baru: $username ($name)", $_SERVER['REMOTE_ADDR']]);

            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Admin <strong>'.htmlspecialchars($username).'</strong> berhasil ditambahkan.</div>';

        } elseif ($action === 'edit') {
            $id       = (int)$_POST['id'];
            $name     = trim($_POST['name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email    = trim($_POST['email'] ?? '');

            if (!$name || !$username || !$email) throw new Exception('Semua field wajib diisi.');

            $stmt = $conn->prepare("UPDATE admins SET name=?, username=?, email=? WHERE id=?");
            $stmt->execute([$name, $username, $email, $id]);

            // Update tahapan assignment
            $conn->prepare("DELETE FROM admin_tahapan WHERE admin_id=?")->execute([$id]);
            $tahapan_ids = $_POST['tahapan'] ?? [];
            foreach ($tahapan_ids as $tid) {
                $conn->prepare("INSERT IGNORE INTO admin_tahapan (admin_id, tahap_id) VALUES (?,?)")->execute([$id, (int)$tid]);
            }

            $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (NULL, 'ADMIN_EDIT', ?, ?)");
            $log->execute(["Superadmin edit admin ID:$id → $username, ".count($tahapan_ids)." tahapan", $_SERVER['REMOTE_ADDR']]);

            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Admin berhasil diupdate.</div>';

        } elseif ($action === 'reset_password') {
            $id      = (int)$_POST['id'];
            $newPwd  = $_POST['new_password'] ?? '';
            if (strlen($newPwd) < 6) throw new Exception('Password minimal 6 karakter.');

            $hash = password_hash($newPwd, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE admins SET password=? WHERE id=?");
            $stmt->execute([$hash, $id]);

            $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (NULL, 'ADMIN_RESET_PWD', ?, ?)");
            $log->execute(["Superadmin reset password admin ID:$id", $_SERVER['REMOTE_ADDR']]);

            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Password berhasil direset.</div>';

        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];

            $stmt = $conn->prepare("SELECT username FROM admins WHERE id=?");
            $stmt->execute([$id]);
            $u = $stmt->fetchColumn();

            $conn->prepare("DELETE FROM admins WHERE id=?")->execute([$id]);

            $log = $conn->prepare("INSERT INTO admin_logs (admin_id, action, details, ip_address) VALUES (NULL, 'ADMIN_DELETE', ?, ?)");
            $log->execute(["Superadmin hapus admin: $u (ID:$id)", $_SERVER['REMOTE_ADDR']]);

            $msg = '<div class="alert alert-warning"><i class="bi bi-trash me-2"></i>Admin <strong>'.htmlspecialchars($u).'</strong> dihapus.</div>';
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $msg = '<div class="alert alert-danger">Username atau email sudah dipakai.</div>';
        } else {
            $msg = '<div class="alert alert-danger">'.htmlspecialchars($e->getMessage()).'</div>';
        }
    } catch (Exception $e) {
        $msg = '<div class="alert alert-danger">'.htmlspecialchars($e->getMessage()).'</div>';
    }
}

// Statistik & data
$total_admin = $conn->query("SELECT COUNT(*) FROM admins")->fetchColumn();
$admins = $conn->query("SELECT a.*,
    (SELECT MAX(created_at) FROM admin_logs WHERE admin_id=a.id AND action='LOGIN') AS last_login,
    (SELECT COUNT(*) FROM admin_logs WHERE admin_id=a.id) AS total_actions
    FROM admins a ORDER BY a.id")->fetchAll();

// Mapping kode → halaman (harus sinkron dengan admin_dashboard.php)
$tahap_pages_map = [
    'input_data'      => ['pendaftar', 'antrian'],
    'proses_berkas'   => ['antrian', 'pendaftar'],
    'ranking'         => ['ranking', 'pendaftar'],
    'pengumuman'      => ['announcements', 'gelombang'],
    'kelola_meja'     => ['meja', 'antrian', 'antrian_display'],
    'kelola_gelombang'=> ['gelombang'],
];

// Load semua tahapan + assigned tahapan per admin
$all_tahapan = [];
try { $all_tahapan = $conn->query("SELECT * FROM tahapan WHERE is_active=1 ORDER BY urutan")->fetchAll(); } catch(Throwable) {}

$admin_tahapan_map = []; // [admin_id => [tahap_id, ...]]
if ($all_tahapan) {
    $rows = $conn->query("SELECT admin_id, tahap_id FROM admin_tahapan")->fetchAll();
    foreach ($rows as $r) $admin_tahapan_map[$r['admin_id']][] = (int)$r['tahap_id'];
}
?>

<div class="alert alert-warning small mb-3">
    <i class="bi bi-shield-fill-check me-1"></i>
    <strong>Mode Super Admin</strong> — Halaman ini hanya bisa diakses oleh akun superadmin (hardcoded di <code>admin.php</code>).
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-people-fill text-primary fs-1"></i>
                <div>
                    <div class="fs-3 fw-bold"><?= $total_admin ?></div>
                    <small class="text-muted">Admin di Database</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-shield-fill-check text-warning fs-1"></i>
                <div>
                    <div class="fs-3 fw-bold">1</div>
                    <small class="text-muted">Superadmin (Hardcoded)</small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <button type="button" class="btn btn-light w-100 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAdd">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Admin Baru
                </button>
            </div>
        </div>
    </div>
</div>

<?= $msg ?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2"></i>Daftar Admin</span>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Username</th>
                <th>Nama</th>
                <th>Email</th>
                <th>Tahapan</th>
                <th>Login Terakhir</th>
                <th class="text-center">Aktivitas</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($admins as $a):
            $assigned_ids = $admin_tahapan_map[$a['id']] ?? [];
        ?>
            <tr>
                <td><?= $a['id'] ?></td>
                <td><span class="badge bg-light text-dark border"><i class="bi bi-person me-1"></i><?= htmlspecialchars($a['username']) ?></span></td>
                <td class="fw-semibold"><?= htmlspecialchars($a['name']) ?></td>
                <td class="text-muted small"><?= htmlspecialchars($a['email']) ?></td>
                <td>
                    <?php if (!empty($assigned_ids)):
                        foreach ($all_tahapan as $t):
                            if (in_array($t['id'], $assigned_ids)): ?>
                                <span class="badge me-1 mb-1" style="background:#ede9fe;color:#7c3aed;font-size:.72rem;">
                                    <i class="bi <?= htmlspecialchars($t['icon']) ?> me-1"></i><?= htmlspecialchars($t['nama']) ?>
                                </span>
                            <?php endif;
                        endforeach;
                    else: ?>
                        <span class="text-muted small fst-italic">Belum di-assign</span>
                    <?php endif; ?>
                </td>
                <td class="small text-muted">
                    <?= $a['last_login'] ? date('d M Y, H:i', strtotime($a['last_login'])) : '<em>Belum pernah</em>' ?>
                </td>
                <td class="text-center"><span class="badge bg-info-subtle text-info border"><?= $a['total_actions'] ?> aksi</span></td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $a['id'] ?>" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalReset<?= $a['id'] ?>" title="Reset Password">
                        <i class="bi bi-key"></i>
                    </button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Yakin hapus admin <?= htmlspecialchars($a['username']) ?>? Semua log aksinya akan ikut terhapus.')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Hapus">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>

            <!-- Modal Edit -->
            <div class="modal fade" id="modalEdit<?= $a['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <div class="modal-content">
                  <form method="POST">
                    <div class="modal-header">
                      <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Admin — <?= htmlspecialchars($a['username']) ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="action" value="edit">
                      <input type="hidden" name="id" value="<?= $a['id'] ?>">
                      <div class="row g-3 mb-3">
                        <div class="col-md-4">
                          <label class="form-label">Nama Lengkap</label>
                          <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($a['name']) ?>" required>
                        </div>
                        <div class="col-md-4">
                          <label class="form-label">Username</label>
                          <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($a['username']) ?>" pattern="[a-zA-Z0-9_]{3,30}" required>
                        </div>
                        <div class="col-md-4">
                          <label class="form-label">Email</label>
                          <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($a['email']) ?>" required>
                        </div>
                      </div>
                      <?php if (!empty($all_tahapan)): ?>
                      <hr>
                      <label class="form-label fw-semibold">
                          <i class="bi bi-diagram-3-fill me-1" style="color:#7c3aed;"></i>Assign Tahapan
                          <small class="text-muted fw-normal ms-1">— centang tahapan yang bisa diakses admin ini</small>
                      </label>
                      <div class="row g-2">
                        <?php foreach ($all_tahapan as $t): ?>
                        <div class="col-md-6">
                          <label class="d-flex align-items-start gap-2 p-3 rounded-3 border cursor-pointer <?= in_array($t['id'], $assigned_ids) ? 'border-primary-subtle bg-primary-subtle' : 'bg-light' ?>"
                                 style="cursor:pointer;"
                                 onclick="this.classList.toggle('border-primary-subtle'); this.classList.toggle('bg-primary-subtle'); this.classList.toggle('bg-light');">
                            <input type="checkbox" name="tahapan[]" value="<?= $t['id'] ?>"
                                   class="form-check-input mt-0 flex-shrink-0"
                                   <?= in_array($t['id'], $assigned_ids) ? 'checked' : '' ?>>
                            <div>
                              <div class="fw-semibold small">
                                <i class="bi <?= htmlspecialchars($t['icon']) ?> me-1" style="color:#7c3aed;"></i><?= htmlspecialchars($t['nama']) ?>
                              </div>
                              <?php if ($t['deskripsi']): ?>
                                <div class="text-muted" style="font-size:.75rem;"><?= htmlspecialchars($t['deskripsi']) ?></div>
                              <?php endif; ?>
                              <div class="mt-1" style="font-size:.72rem;">
                                <?php
                                $halaman_list = $tahap_pages_map[$t['kode']] ?? [$t['halaman_key']];
                                foreach ($halaman_list as $hl):
                                ?>
                                <span class="badge me-1" style="background:#ede9fe;color:#7c3aed;"><?= htmlspecialchars($hl) ?></span>
                                <?php endforeach; ?>
                              </div>
                            </div>
                          </label>
                        </div>
                        <?php endforeach; ?>
                      </div>
                      <?php else: ?>
                      <div class="alert alert-info small mt-2">
                          <i class="bi bi-info-circle me-1"></i>Belum ada tahapan. Buat dulu di menu <strong>Alur Pendaftaran</strong>.
                      </div>
                      <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                      <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <!-- Modal Reset Password -->
            <div class="modal fade" id="modalReset<?= $a['id'] ?>" tabindex="-1">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="POST">
                    <div class="modal-header">
                      <h5 class="modal-title"><i class="bi bi-key me-2"></i>Reset Password — <?= htmlspecialchars($a['username']) ?></h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <input type="hidden" name="action" value="reset_password">
                      <input type="hidden" name="id" value="<?= $a['id'] ?>">
                      <div class="alert alert-info small">
                        <i class="bi bi-info-circle me-1"></i>Sebagai superadmin, Anda bisa reset password admin lain tanpa perlu tahu password lama.
                      </div>
                      <div class="mb-3">
                        <label class="form-label">Password Baru</label>
                        <input type="text" name="new_password" class="form-control" minlength="6" placeholder="Minimal 6 karakter" required>
                        <small class="text-muted">Pastikan password aman & sampaikan ke admin yang bersangkutan.</small>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                      <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-clockwise me-1"></i>Reset Password</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    </div>
</div>

<!-- Modal Add Admin -->
<div class="modal fade" id="modalAdd" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title"><i class="bi bi-plus-lg me-2"></i>Tambah Admin Baru</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="form-label">Nama Lengkap</label>
            <input type="text" name="name" class="form-control" placeholder="Contoh: Andi Wijaya" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Username <small class="text-muted">(huruf/angka/underscore, 3-30 karakter)</small></label>
            <input type="text" name="username" class="form-control" placeholder="Contoh: andi" pattern="[a-zA-Z0-9_]{3,30}" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="andi@smklab.sch.id" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password <small class="text-muted">(minimal 6 karakter)</small></label>
            <input type="text" name="password" class="form-control" minlength="6" placeholder="Password awal admin" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>Tambah Admin</button>
        </div>
      </form>
    </div>
  </div>
</div>
