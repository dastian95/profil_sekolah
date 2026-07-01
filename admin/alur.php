<?php
if (empty($_SESSION['is_super'])) {
    echo '<div class="alert alert-danger"><i class="bi bi-shield-x me-2"></i>Akses ditolak.</div>';
    return;
}

// Halaman yang bisa diakses admin biasa (key => label)
// 'none' = tahap offline/tatap muka, tidak terkait halaman admin manapun.
const HALAMAN_TERSEDIA = [
    'pendaftar'    => 'Data Pendaftar',
    'antrian'      => 'Meja Antrian',
    'ranking'      => 'Ranking & Hasil',
    'daftar_ulang' => 'Sesi Daftar Ulang',
    'announcements'=> 'Pengumuman',
    'backup'       => 'Backup / Export',
    'none'         => 'Tanpa Halaman (Offline / Tatap Muka)',
];

$msg = '';
if (!empty($_SESSION['flash_alur'])) {
    $msg = $_SESSION['flash_alur'];
    unset($_SESSION['flash_alur']);
}

// Auto-migrate: durasi_estimasi
try { $conn->exec("ALTER TABLE tahapan ADD COLUMN durasi_estimasi VARCHAR(50) NULL AFTER deskripsi"); } catch(PDOException) {}

// ── POST Handler ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $nama    = trim($_POST['nama'] ?? '');
            $kode    = trim($_POST['kode'] ?? '');
            $urutan  = (int)($_POST['urutan'] ?? 1);
            $icon    = trim($_POST['icon'] ?? 'bi-circle');
            $hal     = $_POST['halaman_key'] ?? 'pendaftar';
            $desk    = trim($_POST['deskripsi'] ?? '');

            if (!$nama || !$kode) throw new Exception('Nama dan Kode wajib diisi.');
            if (!preg_match('/^[a-z0-9_]{2,50}$/', $kode)) throw new Exception('Kode hanya huruf kecil, angka, underscore (2-50 karakter).');
            if (!array_key_exists($hal, HALAMAN_TERSEDIA)) throw new Exception('Halaman tidak valid.');

            $durasi = trim($_POST['durasi_estimasi'] ?? '') ?: null;
            $conn->prepare("INSERT INTO tahapan (nama, kode, urutan, icon, deskripsi, halaman_key, durasi_estimasi) VALUES (?,?,?,?,?,?,?)")
                 ->execute([$nama, $kode, $urutan, $icon, $desk ?: null, $hal, $durasi]);
            log_admin_action($conn, 'TAHAPAN_ADD', "Tambah tahapan: $kode ($nama)");
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Tahapan <strong>'.htmlspecialchars($nama).'</strong> ditambahkan.</div>';

        } elseif ($action === 'edit') {
            $id     = (int)$_POST['id'];
            $nama   = trim($_POST['nama'] ?? '');
            $kode   = trim($_POST['kode'] ?? '');
            $urutan = (int)($_POST['urutan'] ?? 1);
            $icon   = trim($_POST['icon'] ?? 'bi-circle');
            $hal    = $_POST['halaman_key'] ?? 'pendaftar';
            $desk   = trim($_POST['deskripsi'] ?? '');
            $aktif  = isset($_POST['is_active']) ? 1 : 0;

            if (!$nama || !$kode) throw new Exception('Nama dan Kode wajib diisi.');
            if (!preg_match('/^[a-z0-9_]{2,50}$/', $kode)) throw new Exception('Kode hanya huruf kecil, angka, underscore.');
            if (!array_key_exists($hal, HALAMAN_TERSEDIA)) throw new Exception('Halaman tidak valid.');

            $durasi = trim($_POST['durasi_estimasi'] ?? '') ?: null;
            $conn->prepare("UPDATE tahapan SET nama=?,kode=?,urutan=?,icon=?,deskripsi=?,halaman_key=?,is_active=?,durasi_estimasi=? WHERE id=?")
                 ->execute([$nama, $kode, $urutan, $icon, $desk ?: null, $hal, $aktif, $durasi, $id]);
            log_admin_action($conn, 'TAHAPAN_EDIT', "Edit tahapan ID:$id → $kode");
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Tahapan diupdate.</div>';

        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $nm = $conn->prepare("SELECT nama FROM tahapan WHERE id=?"); $nm->execute([$id]);
            $nama = $nm->fetchColumn();
            $conn->prepare("DELETE FROM tahapan WHERE id=?")->execute([$id]);
            log_admin_action($conn, 'TAHAPAN_DELETE', "Hapus tahapan: $nama (ID:$id)");
            $msg = '<div class="alert alert-warning"><i class="bi bi-trash me-2"></i>Tahapan <strong>'.htmlspecialchars($nama).'</strong> dihapus.</div>';

        } elseif ($action === 'move') {
            $id  = (int)$_POST['id'];
            $dir = $_POST['dir'] ?? '';
            $all = $conn->query("SELECT id FROM tahapan ORDER BY urutan, id")->fetchAll(PDO::FETCH_COLUMN);
            $all = array_map('intval', $all);
            $idx = array_search($id, $all);
            if ($idx !== false) {
                if ($dir === 'up' && $idx > 0) [$all[$idx-1],$all[$idx]] = [$all[$idx],$all[$idx-1]];
                elseif ($dir === 'down' && $idx < count($all)-1) [$all[$idx],$all[$idx+1]] = [$all[$idx+1],$all[$idx]];
                $conn->beginTransaction();
                try {
                    $stmt = $conn->prepare("UPDATE tahapan SET urutan=? WHERE id=?");
                    foreach ($all as $pos => $tid) $stmt->execute([$pos+1,$tid]);
                    $conn->commit();
                } catch (Throwable $e) { $conn->rollBack(); }
            }
        } elseif ($action === 'reorder') {
            // AJAX drag & drop reorder — terima JSON array of IDs
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            if (!is_array($ids)) throw new Exception('Data tidak valid.');
            $ids = array_map('intval', $ids);
            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare("UPDATE tahapan SET urutan=? WHERE id=?");
                foreach ($ids as $pos => $tid) $stmt->execute([$pos+1, $tid]);
                $conn->commit();
            } catch (Throwable $e) { $conn->rollBack(); throw $e; }
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;

        } elseif ($action === 'rename') {
            // AJAX inline rename
            $id   = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '');
            if (strlen($nama) < 2) throw new Exception('Nama minimal 2 karakter.');
            $conn->prepare("UPDATE tahapan SET nama=? WHERE id=?")->execute([$nama, $id]);
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'nama' => $nama]);
            exit;
        }
    } catch (PDOException $e) {
        $msg = $e->getCode() == 23000
            ? '<div class="alert alert-danger">Kode tahapan sudah digunakan.</div>'
            : '<div class="alert alert-danger">'.htmlspecialchars($e->getMessage()).'</div>';
    } catch (Exception $e) {
        $msg = '<div class="alert alert-danger">'.htmlspecialchars($e->getMessage()).'</div>';
    }

    // PRG: redirect setelah POST non-AJAX agar refresh tidak mengulang aksi
    if (in_array($action, ['add', 'edit', 'delete', 'move'], true)) {
        $_SESSION['flash_alur'] = $msg;
        while (ob_get_level() > 0) ob_end_clean();
        header('Location: ' . (!empty($_SESSION['is_super']) ? 'superadmin_dashboard.php' : 'admin_dashboard.php') . '?page=alur');
        exit;
    }
}

// ── Load data ─────────────────────────────────────────────────────────────────
$tahapan_list = $conn->query("SELECT t.*,
    (SELECT COUNT(*) FROM admin_tahapan WHERE tahap_id=t.id) AS jumlah_admin
    FROM tahapan t ORDER BY t.urutan")->fetchAll();
$max_urutan = count($tahapan_list);
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h5 class="mb-1 fw-semibold">Alur Pendaftaran</h5>
        <p class="text-muted small mb-0">
            Urutan tahapan yang dilalui pendaftar. Cukup isi <strong>nama</strong> &amp; pilih <strong>halaman</strong> —
            kode &amp; urutan dibuat otomatis. Seret <i class="bi bi-grip-vertical"></i> untuk mengubah urutan, klik nama untuk ganti nama.
        </p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
        <i class="bi bi-plus-lg me-1"></i>Tambah Tahapan
    </button>
</div>

<?= $msg ?>

<?php if (empty($tahapan_list)): ?>
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>Belum ada tahapan. Klik <strong>Tambah Tahapan</strong> untuk membuat alur pendaftaran.
</div>
<?php else: ?>

<!-- Alur visual -->
<div class="d-flex align-items-center gap-2 flex-wrap mb-4 p-3 bg-white rounded-3 border">
    <?php foreach ($tahapan_list as $i => $t): ?>
        <div class="d-flex align-items-center gap-2">
            <div class="text-center px-3 py-2 rounded-3 border <?= $t['is_active'] ? 'border-primary-subtle bg-primary-subtle' : 'border-secondary-subtle bg-light opacity-50' ?>" style="min-width:110px;">
                <i class="bi <?= htmlspecialchars($t['icon']) ?> d-block mb-1" style="font-size:1.3rem;<?= $t['is_active'] ? 'color:#7c3aed' : 'color:#aaa' ?>"></i>
                <div class="fw-semibold small"><?= htmlspecialchars($t['nama']) ?></div>
                <div class="text-muted" style="font-size:.68rem;"><?= HALAMAN_TERSEDIA[$t['halaman_key']] ?? $t['halaman_key'] ?></div>
                <?php if (!empty($t['durasi_estimasi'])): ?><div style="font-size:.62rem;color:#9ca3af;"><i class="bi bi-clock"></i> <?= htmlspecialchars($t['durasi_estimasi']) ?></div><?php endif; ?>
            </div>
            <?php if ($i < count($tahapan_list) - 1): ?>
                <i class="bi bi-arrow-right text-muted"></i>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<!-- Tabel -->
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span>Daftar Tahapan</span>
        <small class="text-muted"><i class="bi bi-grip-vertical me-1"></i>Seret baris untuk mengubah urutan · Klik nama untuk mengedit</small>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th style="width:44px"></th>
                <th style="width:44px" class="text-center">#</th>
                <th>Nama Tahapan</th>
                <th>Kode</th>
                <th>Halaman Admin</th>
                <th class="text-center">Admin</th>
                <th class="text-center">Status</th>
                <th class="text-end">Aksi</th>
            </tr>
        </thead>
        <tbody id="alur-sortable">
        <?php foreach ($tahapan_list as $t_idx => $t): ?>
        <tr data-id="<?= $t['id'] ?>">
            <td class="text-center drag-handle" style="cursor:grab;color:#aaa;font-size:1.2rem;" title="Seret untuk ubah urutan">
                <i class="bi bi-grip-vertical"></i>
            </td>
            <td class="text-center">
                <span class="badge bg-secondary order-badge"><?= $t_idx + 1 ?></span>
            </td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <i class="bi <?= htmlspecialchars($t['icon']) ?>" style="color:#7c3aed;font-size:1.1rem;"></i>
                    <div>
                        <div class="fw-semibold tahapan-nama"
                             data-id="<?= $t['id'] ?>"
                             title="Klik untuk edit nama"
                             style="cursor:pointer;border-bottom:1px dashed transparent;"
                             onmouseover="this.style.borderBottomColor='#7c3aed'"
                             onmouseout="this.style.borderBottomColor='transparent'"
                        ><?= htmlspecialchars($t['nama']) ?></div>
                        <?php if ($t['deskripsi']): ?>
                            <div class="text-muted small"><?= htmlspecialchars(mb_strimwidth($t['deskripsi'], 0, 60, '…')) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($t['durasi_estimasi'])): ?>
                            <div class="text-muted" style="font-size:.72rem;"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($t['durasi_estimasi']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            <td><code class="small"><?= htmlspecialchars($t['kode']) ?></code></td>
            <td>
                <span class="badge" style="background:#ede9fe;color:#7c3aed;">
                    <?= htmlspecialchars(HALAMAN_TERSEDIA[$t['halaman_key']] ?? $t['halaman_key']) ?>
                </span>
            </td>
            <td class="text-center">
                <?php if ($t['jumlah_admin'] > 0): ?>
                    <span class="badge bg-primary"><?= $t['jumlah_admin'] ?> admin</span>
                <?php else: ?>
                    <span class="text-muted small">—</span>
                <?php endif; ?>
            </td>
            <td class="text-center">
                <?= $t['is_active']
                    ? '<span class="badge bg-success">Aktif</span>'
                    : '<span class="badge bg-secondary">Nonaktif</span>' ?>
            </td>
            <td class="text-end">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $t['id'] ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus tahapan <?= htmlspecialchars(addslashes($t['nama'])) ?>? Semua assignment admin ke tahapan ini juga akan dihapus.')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $t['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" <?= $t['jumlah_admin']>0?'title="Ada admin di-assign ke tahapan ini"':'' ?>>
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </td>
        </tr>

        <!-- Modal Edit -->
        <div class="modal fade" id="modalEdit<?= $t['id'] ?>" tabindex="-1">
          <div class="modal-dialog">
            <div class="modal-content">
              <form method="POST">
                <div class="modal-header">
                  <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Edit Tahapan</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="action" value="edit">
                  <input type="hidden" name="id" value="<?= $t['id'] ?>">
                  <div class="mb-3">
                    <label class="form-label">Nama Tahapan</label>
                    <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($t['nama']) ?>" required>
                  </div>
                  <input type="hidden" name="urutan" value="<?= $t['urutan'] ?>">
                  <div class="row g-2 mb-3">
                    <div class="col-md-8">
                      <label class="form-label">Kode <small class="text-muted">(huruf kecil/angka/_)</small></label>
                      <input type="text" name="kode" class="form-control font-monospace" value="<?= htmlspecialchars($t['kode']) ?>" pattern="[a-z0-9_]{2,50}" required>
                    </div>
                    <div class="col-md-4">
                      <label class="form-label">Icon</label>
                      <input type="text" name="icon" class="form-control" value="<?= htmlspecialchars($t['icon']) ?>" placeholder="bi-circle">
                    </div>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Halaman yang Dibuka</label>
                    <select name="halaman_key" class="form-select">
                      <?php foreach (HALAMAN_TERSEDIA as $k => $l): ?>
                        <option value="<?= $k ?>" <?= $t['halaman_key']===$k?'selected':'' ?>><?= $l ?></option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Deskripsi <small class="text-muted">(opsional)</small></label>
                    <textarea name="deskripsi" class="form-control" rows="2"><?= htmlspecialchars($t['deskripsi'] ?? '') ?></textarea>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Estimasi Durasi <small class="text-muted">(opsional, contoh: 5-10 menit)</small></label>
                    <input type="text" name="durasi_estimasi" class="form-control" value="<?= htmlspecialchars($t['durasi_estimasi'] ?? '') ?>" placeholder="5-10 menit">
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="aktif<?= $t['id'] ?>" <?= $t['is_active']?'checked':'' ?>>
                    <label class="form-check-label" for="aktif<?= $t['id'] ?>">Tahapan aktif</label>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                  <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
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
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
// ── Drag & drop reorder ────────────────────────────────────────────────────────
const tbody = document.getElementById('alur-sortable');
if (tbody) {
    Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'table-info',
        onEnd() {
            const ids = [...tbody.querySelectorAll('tr[data-id]')].map(r => r.dataset.id);
            // Update badge nomor urut
            tbody.querySelectorAll('.order-badge').forEach((b, i) => b.textContent = i + 1);
            fetch('?page=alur', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=reorder&ids=' + encodeURIComponent(JSON.stringify(ids))
            }).then(r => r.json()).catch(() => {});
        }
    });
}

// ── Modal Tambah: kode otomatis dari nama + icon picker ───────────────────────
function autoKode(nama) {
    const slug = nama.toLowerCase()
        .replace(/[^a-z0-9\s_]/g, '')
        .trim()
        .replace(/\s+/g, '_')
        .substring(0, 50);
    const kode = slug.length >= 2 ? slug : '';
    document.getElementById('addKode').value = kode;
    document.getElementById('kodePreview').textContent = kode || '—';
}
function pickIcon(btn) {
    document.querySelectorAll('#iconPicker .icon-pick').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('addIcon').value = btn.dataset.icon;
}

// ── Inline edit nama ───────────────────────────────────────────────────────────
document.querySelectorAll('.tahapan-nama').forEach(el => {
    el.addEventListener('click', function() {
        if (this.querySelector('input')) return;
        const id   = this.dataset.id;
        const orig = this.textContent.trim();
        this.style.borderBottomColor = '#7c3aed';
        const inp = document.createElement('input');
        inp.type  = 'text';
        inp.value = orig;
        inp.className = 'form-control form-control-sm d-inline';
        inp.style.cssText = 'width:200px;padding:2px 6px;';
        this.innerHTML = '';
        this.appendChild(inp);
        inp.focus();
        inp.select();

        const save = () => {
            const val = inp.value.trim();
            if (!val || val === orig) { this.textContent = orig; return; }
            fetch('?page=alur', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=rename&id=' + id + '&nama=' + encodeURIComponent(val)
            }).then(r => r.json()).then(d => {
                this.textContent = d.nama || val;
            }).catch(() => { this.textContent = orig; });
        };

        inp.addEventListener('blur', save);
        inp.addEventListener('keydown', e => {
            if (e.key === 'Enter') { e.preventDefault(); inp.blur(); }
            if (e.key === 'Escape') { this.textContent = orig; }
        });
    });
});
</script>

<!-- Modal Tambah -->
<div class="modal fade" id="modalAdd" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header" style="background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;">
          <h5 class="modal-title"><i class="bi bi-plus-lg me-2"></i>Tambah Tahapan Baru</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add">
          <!-- Kode & urutan diisi otomatis — user cukup isi nama, pilih halaman, klik ikon -->
          <input type="hidden" name="kode" id="addKode" value="">
          <input type="hidden" name="urutan" value="<?= $max_urutan + 1 ?>">

          <div class="mb-3">
            <label class="form-label fw-semibold">1. Nama Tahapan <span class="text-danger">*</span></label>
            <input type="text" name="nama" id="addNama" class="form-control form-control-lg"
                   placeholder="contoh: Cek Berkas Pendaftar" required oninput="autoKode(this.value)">
            <small class="text-muted">Kode otomatis: <code id="kodePreview">—</code> · Urutan otomatis di posisi terakhir (bisa diseret nanti)</small>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">2. Halaman yang Dibuka untuk Admin <span class="text-danger">*</span></label>
            <select name="halaman_key" class="form-select">
              <?php foreach (HALAMAN_TERSEDIA as $k => $l): ?>
                <option value="<?= $k ?>"><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <small class="text-muted">Admin yang ditugaskan ke tahapan ini hanya bisa membuka halaman tersebut. Pilih <strong>Tanpa Halaman</strong> untuk tahap yang dilakukan langsung/tatap muka (tidak lewat website).</small>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">3. Pilih Ikon</label>
            <input type="hidden" name="icon" id="addIcon" value="bi-circle">
            <div class="d-flex flex-wrap gap-2" id="iconPicker">
              <?php foreach (['bi-folder-check','bi-person-lines-fill','bi-pencil-square','bi-card-checklist',
                              'bi-printer','bi-clipboard-check','bi-people-fill','bi-table',
                              'bi-trophy','bi-megaphone','bi-file-earmark-text','bi-check-circle',
                              'bi-grid-3x2-gap-fill','bi-display','bi-shield-check','bi-circle'] as $ic): ?>
              <button type="button" class="btn btn-outline-secondary icon-pick <?= $ic === 'bi-circle' ? 'active' : '' ?>"
                      data-icon="<?= $ic ?>" onclick="pickIcon(this)"
                      style="width:44px;height:44px;font-size:1.2rem;">
                <i class="bi <?= $ic ?>"></i>
              </button>
              <?php endforeach; ?>
            </div>
          </div>

          <a class="small text-decoration-none" data-bs-toggle="collapse" href="#addOpsional">
            <i class="bi bi-chevron-down me-1"></i>Opsional: deskripsi &amp; estimasi durasi
          </a>
          <div class="collapse mt-2" id="addOpsional">
            <div class="mb-2">
              <textarea name="deskripsi" class="form-control" rows="2" placeholder="Deskripsi singkat tugas admin di tahapan ini"></textarea>
            </div>
            <input type="text" name="durasi_estimasi" class="form-control" placeholder="Estimasi durasi, contoh: 5-10 menit">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>Tambah Tahapan</button>
        </div>
      </form>
    </div>
  </div>
</div>
