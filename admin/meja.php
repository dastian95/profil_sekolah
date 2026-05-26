<?php
// Superadmin selalu boleh; admin biasa boleh jika punya tahap kelola_meja
if (empty($_SESSION['is_super'])) {
    $can_meja = false;
    try {
        $ckm = $conn->prepare("SELECT 1 FROM admin_tahapan at JOIN tahapan t ON t.id=at.tahap_id
            WHERE at.admin_id=? AND t.kode='kelola_meja' AND t.is_active=1 LIMIT 1");
        $ckm->execute([$_SESSION['admin_id'] ?? 0]);
        $can_meja = (bool)$ckm->fetchColumn();
    } catch(Throwable) {}
    if (!$can_meja) {
        echo '<div class="alert alert-danger"><i class="bi bi-shield-x me-2"></i>Akses ditolak. Hubungi superadmin untuk mendapatkan izin Kelola Meja.</div>';
        return;
    }
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $nomor = (int)$_POST['nomor_meja'];
            $nama  = trim($_POST['nama'] ?? '') ?: null;
            $fase  = in_array((int)($_POST['fase']??1), [1,2]) ? (int)$_POST['fase'] : 1;
            if ($nomor < 1) throw new Exception('Nomor meja minimal 1.');
            $conn->prepare("INSERT INTO meja (nomor_meja, nama, fase) VALUES (?,?,?)")->execute([$nomor, $nama, $fase]);
            log_admin_action($conn, 'MEJA_ADD', "Tambah Meja $nomor (Fase $fase)");
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Meja <strong>'.$nomor.'</strong> (Fase '.$fase.') ditambahkan.</div>';

        } elseif ($action === 'edit') {
            $id    = (int)$_POST['id'];
            $nomor = (int)$_POST['nomor_meja'];
            $nama  = trim($_POST['nama'] ?? '') ?: null;
            $fase  = in_array((int)($_POST['fase']??1), [1,2]) ? (int)$_POST['fase'] : 1;
            $aktif = isset($_POST['is_active']) ? 1 : 0;
            if ($nomor < 1) throw new Exception('Nomor meja minimal 1.');
            $conn->prepare("UPDATE meja SET nomor_meja=?,nama=?,fase=?,is_active=? WHERE id=?")->execute([$nomor,$nama,$fase,$aktif,$id]);
            log_admin_action($conn, 'MEJA_EDIT', "Edit Meja ID:$id → nomor $nomor, fase $fase");
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Meja diupdate.</div>';

        } elseif ($action === 'delete') {
            $id = (int)$_POST['id'];
            $nm = $conn->prepare("SELECT nomor_meja FROM meja WHERE id=?"); $nm->execute([$id]);
            $nomor = $nm->fetchColumn();
            $conn->prepare("DELETE FROM meja WHERE id=?")->execute([$id]);
            log_admin_action($conn, 'MEJA_DELETE', "Hapus Meja $nomor (ID:$id)");
            $msg = '<div class="alert alert-warning"><i class="bi bi-trash me-2"></i>Meja <strong>'.$nomor.'</strong> dihapus.</div>';

        } elseif ($action === 'reset_antrian') {
            $tanggal = date('Y-m-d');
            $conn->prepare("DELETE FROM antrian WHERE tanggal=?")->execute([$tanggal]);
            log_admin_action($conn, 'ANTRIAN_RESET', "Hapus total antrian tanggal $tanggal");
            $msg = '<div class="alert alert-warning"><i class="bi bi-arrow-clockwise me-2"></i>Antrian hari ini dihapus total. Bisa dibuka ulang.</div>';

        } elseif ($action === 'tambah_nomor') {
            $tanggal = date('Y-m-d');
            $tambah  = (int)($_POST['jumlah_tambah'] ?? 10);
            if ($tambah < 1 || $tambah > 200) throw new Exception('Jumlah tambah 1-200.');
            $maxStmt = $conn->prepare("SELECT COALESCE(MAX(nomor),0) FROM antrian WHERE tanggal=? AND fase=1");
            $maxStmt->execute([$tanggal]);
            $max = (int)$maxStmt->fetchColumn();
            if ($max === 0) throw new Exception('Antrian hari ini belum dibuka. Gunakan Buka Antrian dulu.');
            $mejas = $conn->query("SELECT id, nomor_meja FROM meja WHERE is_active=1 AND fase=1 ORDER BY nomor_meja")->fetchAll();
            if (empty($mejas)) $mejas = $conn->query("SELECT id, nomor_meja FROM meja WHERE is_active=1 ORDER BY nomor_meja")->fetchAll();
            if (empty($mejas)) throw new Exception('Tidak ada meja aktif.');
            $total_meja = count($mejas);
            $stmt = $conn->prepare("INSERT IGNORE INTO antrian (tanggal, nomor, fase, meja_id) VALUES (?,?,1,?)");
            for ($i = 1; $i <= $tambah; $i++) {
                $nomor = $max + $i;
                $meja_idx = ($nomor - 1) % $total_meja;
                $stmt->execute([$tanggal, $nomor, $mejas[$meja_idx]['id']]);
            }
            log_admin_action($conn, 'ANTRIAN_TAMBAH', 'Tambah '.$tambah.' nomor ('.($max+1).'-'.($max+$tambah).') tgl '.$tanggal);
            $msg = '<div class="alert alert-success"><i class="bi bi-plus-circle me-2"></i>Tambah <strong>'.$tambah.'</strong> nomor (nomor '.($max+1).' s/d '.($max+$tambah).').</div>';

        } elseif ($action === 'kurangi_nomor') {
            $tanggal = date('Y-m-d');
            $kurangi = (int)($_POST['jumlah_kurangi'] ?? 10);
            if ($kurangi < 1 || $kurangi > 200) throw new Exception('Jumlah kurangi 1-200.');
            $deleted = $conn->prepare("DELETE FROM antrian WHERE tanggal=? AND fase=1 AND status='menunggu'
                ORDER BY nomor DESC LIMIT $kurangi");
            $deleted->execute([$tanggal]);
            $n = $deleted->rowCount();
            log_admin_action($conn, 'ANTRIAN_KURANGI', "Kurangi $n nomor antrian (menunggu) tanggal $tanggal");
            $msg = '<div class="alert alert-warning"><i class="bi bi-dash-circle me-2"></i>Berhasil hapus <strong>'.$n.'</strong> nomor antrian yang belum dipanggil.</div>';

        } elseif ($action === 'reorder_meja') {
            $ids = json_decode($_POST['ids'] ?? '[]', true);
            if (!is_array($ids)) throw new Exception('Data tidak valid.');
            $conn->beginTransaction();
            try {
                $stmt = $conn->prepare("UPDATE meja SET nomor_meja=? WHERE id=?");
                foreach ($ids as $pos => $mid) $stmt->execute([$pos+1, (int)$mid]);
                $conn->commit();
            } catch (Throwable $e) { $conn->rollBack(); throw $e; }
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;

        } elseif ($action === 'rename_meja') {
            $id   = (int)$_POST['id'];
            $nama = trim($_POST['nama'] ?? '') ?: null;
            $conn->prepare("UPDATE meja SET nama=? WHERE id=?")->execute([$nama, $id]);
            header('Content-Type: application/json');
            echo json_encode(['ok' => true, 'nama' => $nama ?? '']);
            exit;

        } elseif ($action === 'buka_antrian') {
            $tanggal   = date('Y-m-d');
            $jumlah    = (int)($_POST['jumlah'] ?? 50);
            if ($jumlah < 1 || $jumlah > 500) throw new Exception('Jumlah antrian 1-500.');

            // Cek apakah hari ini sudah ada
            $existing = $conn->prepare("SELECT COUNT(*) FROM antrian WHERE tanggal=?"); $existing->execute([$tanggal]);
            if ((int)$existing->fetchColumn() > 0) throw new Exception('Antrian hari ini sudah dibuka. Reset dulu jika ingin mengulang.');

            // Ambil meja aktif urut nomor
            $mejas = $conn->query("SELECT id, nomor_meja FROM meja WHERE is_active=1 ORDER BY nomor_meja")->fetchAll();
            if (empty($mejas)) throw new Exception('Tidak ada meja aktif. Aktifkan minimal 1 meja dulu.');

            $total_meja = count($mejas);
            $stmt = $conn->prepare("INSERT INTO antrian (tanggal, nomor, meja_id) VALUES (?,?,?)");
            for ($i = 1; $i <= $jumlah; $i++) {
                $meja_idx = ($i - 1) % $total_meja;
                $stmt->execute([$tanggal, $i, $mejas[$meja_idx]['id']]);
            }
            log_admin_action($conn, 'ANTRIAN_BUKA', "Buka antrian $tanggal: $jumlah nomor, $total_meja meja");
            $msg = '<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>Antrian dibuka: <strong>'.$jumlah.'</strong> nomor untuk <strong>'.$total_meja.'</strong> meja.</div>';
        }
    } catch (PDOException $e) {
        $msg = $e->getCode() == 23000
            ? '<div class="alert alert-danger">Nomor meja sudah digunakan.</div>'
            : '<div class="alert alert-danger">'.htmlspecialchars($e->getMessage()).'</div>';
    } catch (Exception $e) {
        $msg = '<div class="alert alert-danger">'.htmlspecialchars($e->getMessage()).'</div>';
    }
}

$mejas   = $conn->query("SELECT * FROM meja ORDER BY nomor_meja")->fetchAll();
$tanggal = date('Y-m-d');

// Statistik antrian hari ini
$stat_antrian = ['total'=>0,'menunggu'=>0,'dipanggil'=>0,'selesai'=>0];
try {
    $s = $conn->prepare("SELECT status, COUNT(*) as c FROM antrian WHERE tanggal=? GROUP BY status"); $s->execute([$tanggal]);
    foreach ($s as $row) {
        $stat_antrian['total'] += (int)$row['c'];
        if (isset($stat_antrian[$row['status']])) $stat_antrian[$row['status']] = (int)$row['c'];
    }
} catch(Throwable) {}

// Status meja (nomor yg sedang dilayani)
$meja_status = [];
try {
    $ms = $conn->prepare("SELECT a.meja_id, a.nomor FROM antrian a
        WHERE a.tanggal=? AND a.status='dipanggil' ORDER BY a.dipanggil_at DESC");
    $ms->execute([$tanggal]);
    foreach ($ms as $row) {
        if (!isset($meja_status[$row['meja_id']])) $meja_status[$row['meja_id']] = $row['nomor'];
    }
} catch(Throwable) {}
?>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h5 class="mb-1 fw-semibold">Kelola Meja Antrian</h5>
        <p class="text-muted small mb-0">Konfigurasi meja fisik dan sistem antrian harian.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalBuka">
            <i class="bi bi-play-circle me-1"></i>Buka Antrian Hari Ini
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAdd">
            <i class="bi bi-plus-lg me-1"></i>Tambah Meja
        </button>
    </div>
</div>

<?= $msg ?>

<!-- Stat Antrian Hari Ini -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fs-2 fw-bold text-primary"><?= $stat_antrian['total'] ?></div>
            <div class="small text-muted">Total Antrian</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fs-2 fw-bold text-warning"><?= $stat_antrian['menunggu'] ?></div>
            <div class="small text-muted">Menunggu</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fs-2 fw-bold text-info"><?= $stat_antrian['dipanggil'] ?></div>
            <div class="small text-muted">Sedang Dilayani</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center p-3">
            <div class="fs-2 fw-bold text-success"><?= $stat_antrian['selesai'] ?></div>
            <div class="small text-muted">Selesai</div>
        </div>
    </div>
</div>

<div class="text-muted small mb-2"><i class="bi bi-grip-vertical me-1"></i>Seret kartu untuk mengubah urutan · Klik nama meja untuk mengedit langsung</div>
<?php if (!empty($mejas)): ?>
<!-- Grid Meja -->
<div class="row g-3 mb-4" id="meja-sortable">
    <?php foreach ($mejas as $m): ?>
    <div class="col-md-3 col-sm-4 col-6" data-id="<?= $m['id'] ?>">
        <div class="card text-center p-3 <?= $m['is_active'] ? '' : 'opacity-50' ?>" style="position:relative;">
            <!-- Drag handle -->
            <div class="meja-drag-handle" title="Seret untuk ubah urutan"
                 style="position:absolute;top:6px;left:8px;cursor:grab;color:#ccc;font-size:1rem;line-height:1;">
                <i class="bi bi-grip-vertical"></i>
            </div>
            <div class="mb-2 mt-1">
                <i class="bi bi-person-workspace" style="font-size:2rem;color:<?= $m['is_active']?'#7c3aed':'#aaa' ?>;"></i>
            </div>
            <div class="fw-bold fs-5 meja-nomor-badge">Meja <?= $m['nomor_meja'] ?></div>
            <!-- Nama meja — klik untuk edit -->
            <div class="text-muted small meja-nama-label"
                 data-id="<?= $m['id'] ?>"
                 title="Klik untuk edit nama"
                 style="cursor:pointer;min-height:1.2em;border-bottom:1px dashed transparent;"
                 onmouseover="this.style.borderBottomColor='#7c3aed'"
                 onmouseout="this.style.borderBottomColor='transparent'"
            ><?= htmlspecialchars($m['nama'] ?? '') ?></div>
            <?php $mfase = (int)($m['fase'] ?? 1); ?>
            <div class="mt-1">
                <span class="badge" style="font-size:.68rem;background:<?= $mfase==2?'#ede9fe':'#dbeafe' ?>;color:<?= $mfase==2?'#6d28d9':'#1d4ed8' ?>;">
                    Fase <?= $mfase ?> — <?= $mfase==2?'Input & Surat':'Cek Berkas' ?>
                </span>
            </div>
            <div class="mt-2">
                <?php if (isset($meja_status[$m['id']])): ?>
                    <span class="badge bg-info">Melayani #<?= $meja_status[$m['id']] ?></span>
                <?php elseif ($m['is_active']): ?>
                    <span class="badge bg-success">Siap</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Nonaktif</span>
                <?php endif; ?>
            </div>
            <div class="mt-2 d-flex gap-1 justify-content-center">
                <button class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:.75rem;"
                        data-bs-toggle="modal" data-bs-target="#modalEditMeja<?= $m['id'] ?>">
                    <i class="bi bi-pencil"></i>
                </button>
                <form method="POST" class="d-inline" onsubmit="return confirm('Hapus Meja <?= $m['nomor_meja'] ?>?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $m['id'] ?>">
                    <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-2" style="font-size:.75rem;">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Meja -->
    <div class="modal fade" id="modalEditMeja<?= $m['id'] ?>" tabindex="-1">
      <div class="modal-dialog modal-sm">
        <div class="modal-content">
          <form method="POST">
            <div class="modal-header">
              <h5 class="modal-title">Edit Meja <?= $m['nomor_meja'] ?></h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <input type="hidden" name="action" value="edit">
              <input type="hidden" name="id" value="<?= $m['id'] ?>">
              <div class="mb-3">
                <label class="form-label">Nomor Meja</label>
                <input type="number" name="nomor_meja" class="form-control" value="<?= $m['nomor_meja'] ?>" min="1" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Fase</label>
                <select name="fase" class="form-select">
                    <option value="1" <?= (int)($m['fase']??1)==1?'selected':'' ?>>Fase 1 — Cek Berkas</option>
                    <option value="2" <?= (int)($m['fase']??1)==2?'selected':'' ?>>Fase 2 — Input Data & Surat</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="form-label">Label (opsional)</label>
                <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($m['nama'] ?? '') ?>" placeholder="Contoh: Loket A">
              </div>
              <div class="form-check">
                <input type="checkbox" name="is_active" class="form-check-input" id="aktif_meja<?= $m['id'] ?>" <?= $m['is_active']?'checked':'' ?>>
                <label class="form-check-label" for="aktif_meja<?= $m['id'] ?>">Meja aktif</label>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
              <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="alert alert-info mb-4">
    <i class="bi bi-info-circle me-2"></i>Belum ada meja. Tambah meja dulu untuk menggunakan sistem antrian.
</div>
<?php endif; ?>

<!-- Tambah / Kurangi / Reset Antrian -->
<?php if ($stat_antrian['total'] > 0): ?>
<div class="card mb-0">
    <div class="card-header fw-semibold">
        <i class="bi bi-sliders me-2"></i>Kelola Jumlah Antrian Hari Ini
        <span class="badge bg-secondary ms-2"><?= $stat_antrian['total'] ?> total</span>
        <span class="badge bg-warning text-dark ms-1"><?= $stat_antrian['menunggu'] ?> menunggu</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <!-- Tambah Nomor -->
            <div class="col-md-4">
                <form method="POST">
                    <input type="hidden" name="action" value="tambah_nomor">
                    <label class="form-label fw-semibold small text-success"><i class="bi bi-plus-circle me-1"></i>Tambah Nomor Antrian</label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="jumlah_tambah" class="form-control" value="10" min="1" max="200">
                        <button type="submit" class="btn btn-success"
                                onclick="return confirm('Tambah nomor antrian baru?')">Tambah</button>
                    </div>
                    <div class="form-text">Nomor baru ditambah di urutan paling akhir</div>
                </form>
            </div>
            <!-- Kurangi Nomor -->
            <div class="col-md-4">
                <form method="POST">
                    <input type="hidden" name="action" value="kurangi_nomor">
                    <label class="form-label fw-semibold small text-warning"><i class="bi bi-dash-circle me-1"></i>Kurangi Nomor (Belum Dipanggil)</label>
                    <div class="input-group input-group-sm">
                        <input type="number" name="jumlah_kurangi" class="form-control" value="10" min="1" max="200">
                        <button type="submit" class="btn btn-warning text-dark"
                                onclick="return confirm('Hapus nomor yang belum dipanggil dari akhir?')">Kurangi</button>
                    </div>
                    <div class="form-text">Hanya menghapus nomor berstatus <em>menunggu</em></div>
                </form>
            </div>
            <!-- Reset Total -->
            <div class="col-md-4">
                <form method="POST" onsubmit="return confirm('HAPUS SEMUA antrian hari ini? Termasuk yang sudah selesai.\nAnda bisa buka ulang setelah ini.')">
                    <input type="hidden" name="action" value="reset_antrian">
                    <label class="form-label fw-semibold small text-danger"><i class="bi bi-trash me-1"></i>Reset Total</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="bi bi-arrow-clockwise me-1"></i>Hapus Semua &amp; Mulai dari 0
                        </button>
                    </div>
                    <div class="form-text text-danger">Tidak bisa dibatalkan</div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
// ── Drag & drop reorder meja ──────────────────────────────────────────────────
const mejaGrid = document.getElementById('meja-sortable');
if (mejaGrid) {
    Sortable.create(mejaGrid, {
        handle: '.meja-drag-handle',
        animation: 150,
        ghostClass: 'opacity-50',
        onEnd() {
            const ids = [...mejaGrid.querySelectorAll('[data-id]')].map(el => el.dataset.id);
            // Update badge nomor meja sesuai posisi baru
            mejaGrid.querySelectorAll('.meja-nomor-badge').forEach((b, i) => {
                b.textContent = 'Meja ' + (i + 1);
            });
            fetch('?page=meja', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=reorder_meja&ids=' + encodeURIComponent(JSON.stringify(ids))
            }).then(r => r.json()).catch(() => {});
        }
    });
}

// ── Inline edit nama meja ─────────────────────────────────────────────────────
document.querySelectorAll('.meja-nama-label').forEach(el => {
    el.addEventListener('click', function() {
        if (this.querySelector('input')) return;
        const id   = this.dataset.id;
        const orig = this.textContent.trim();
        const inp  = document.createElement('input');
        inp.type      = 'text';
        inp.value     = orig;
        inp.className = 'form-control form-control-sm text-center';
        inp.style.cssText = 'width:100%;padding:2px 4px;font-size:.82rem;';
        inp.placeholder = 'Nama loket (opsional)';
        this.innerHTML = '';
        this.appendChild(inp);
        inp.focus();
        inp.select();

        const save = () => {
            const val = inp.value.trim();
            fetch('?page=meja', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=rename_meja&id=' + id + '&nama=' + encodeURIComponent(val)
            }).then(r => r.json()).then(() => {
                this.textContent = val;
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

<!-- Modal Tambah Meja -->
<div class="modal fade" id="modalAdd" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-plus-lg me-2"></i>Tambah Meja</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="add">
          <div class="mb-3">
            <label class="form-label">Nomor Meja</label>
            <input type="number" name="nomor_meja" class="form-control" value="<?= count($mejas)+1 ?>" min="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Fase</label>
            <select name="fase" class="form-select">
                <option value="1">Fase 1 — Cek Berkas</option>
                <option value="2">Fase 2 — Input Data & Surat</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Label <small class="text-muted">(opsional)</small></label>
            <input type="text" name="nama" class="form-control" placeholder="Contoh: Loket A">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Tambah</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Buka Antrian -->
<div class="modal fade" id="modalBuka" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header" style="background:linear-gradient(135deg,#7c3aed,#a855f7);color:#fff;">
          <h5 class="modal-title"><i class="bi bi-play-circle me-2"></i>Buka Antrian — <?= date('d M Y') ?></h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="buka_antrian">
          <div class="mb-3">
            <label class="form-label">Jumlah Nomor Antrian</label>
            <input type="number" name="jumlah" class="form-control" value="50" min="1" max="500">
            <small class="text-muted">Nomor akan dibagi merata ke <?= count(array_filter($mejas, fn($m)=>$m['is_active'])) ?> meja aktif secara round-robin.</small>
          </div>
          <div class="alert alert-info small p-2">
            <i class="bi bi-info-circle me-1"></i>
            Meja aktif: <?php
                $aktif_mejas = array_filter($mejas, fn($m) => $m['is_active']);
                echo implode(', ', array_map(fn($m) => 'Meja '.$m['nomor_meja'], $aktif_mejas)) ?: '-';
            ?>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-play-circle me-1"></i>Buka Sekarang</button>
        </div>
      </form>
    </div>
  </div>
</div>
