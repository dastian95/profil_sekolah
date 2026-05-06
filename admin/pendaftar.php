<?php
$jurusan_list = [
    'Rekayasa Perangkat Lunak (RPL)',
    'Teknik Komputer dan Jaringan (TKJ)',
    'Asisten Keperawatan (AP)',
    'Tata Kecantikan Kulit dan Rambut (TKKR)',
];

$msg = $err = '';

// ── Helper: hitung nilai & usia ──────────────────────────────────────────────
function hitungPendaftar(array $data): array {
    $lahir     = new DateTime($data['tanggal_lahir']);
    $sekarang  = new DateTime();
    $usia      = (int)$lahir->diff($sekarang)->y;
    $nilai_akhir = round(($data['nilai_raport'] * 0.70) + ($data['nilai_tka'] * 0.30), 4);
    $lolos_usia  = ($usia <= 21) ? 1 : 0;
    return array_merge($data, ['usia' => $usia, 'nilai_akhir' => $nilai_akhir, 'lolos_usia' => $lolos_usia]);
}

// ── Nomor pendaftaran otomatis ────────────────────────────────────────────────
function generateNoPendaftaran(PDO $conn, int $gelombang): string {
    $tahun  = date('Y');
    $prefix = "PPDB-{$tahun}-G{$gelombang}-";
    $last   = $conn->prepare("SELECT no_pendaftaran FROM pendaftar WHERE gelombang=? ORDER BY id DESC LIMIT 1");
    $last->execute([$gelombang]);
    $row    = $last->fetchColumn();
    $seq    = $row ? ((int)substr($row, -4) + 1) : 1;
    return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
}

// ── POST: Tambah / Edit / Hapus ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $required = ['nama','nisn','tanggal_lahir','jenis_kelamin','asal_sekolah','jurusan','gelombang','nilai_raport','nilai_tka'];
        $missing  = array_filter($required, fn($f) => empty($_POST[$f]));
        if ($missing) {
            $err = 'Field wajib belum diisi: ' . implode(', ', $missing);
        } else {
            $d = hitungPendaftar([
                'nama'          => trim($_POST['nama']),
                'nisn'          => trim($_POST['nisn']),
                'tanggal_lahir' => $_POST['tanggal_lahir'],
                'jenis_kelamin' => $_POST['jenis_kelamin'],
                'asal_sekolah'  => trim($_POST['asal_sekolah']),
                'no_telp'       => trim($_POST['no_telp'] ?? ''),
                'alamat'        => trim($_POST['alamat'] ?? ''),
                'jurusan'       => $_POST['jurusan'],
                'gelombang'     => (int)$_POST['gelombang'],
                'nilai_raport'  => (float)$_POST['nilai_raport'],
                'nilai_tka'     => (float)$_POST['nilai_tka'],
            ]);

            if ($action === 'add') {
                $no = generateNoPendaftaran($conn, $d['gelombang']);
                $stmt = $conn->prepare("INSERT INTO pendaftar
                    (no_pendaftaran,gelombang,nama,nisn,tanggal_lahir,usia,jenis_kelamin,asal_sekolah,no_telp,alamat,jurusan,nilai_raport,nilai_tka,nilai_akhir,lolos_usia,status)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending')");
                $stmt->execute([$no,$d['gelombang'],$d['nama'],$d['nisn'],$d['tanggal_lahir'],$d['usia'],
                    $d['jenis_kelamin'],$d['asal_sekolah'],$d['no_telp'],$d['alamat'],$d['jurusan'],
                    $d['nilai_raport'],$d['nilai_tka'],$d['nilai_akhir'],$d['lolos_usia']]);

                $logStmt = $conn->prepare("INSERT INTO admin_logs (admin_id,action,details,ip_address) VALUES (?,?,?,?)");
                $logStmt->execute([$_SESSION['admin_id'],'TAMBAH_PENDAFTAR',"Tambah pendaftar: {$d['nama']} ({$no})",$_SERVER['REMOTE_ADDR']]);
                $msg = "Pendaftar <strong>{$d['nama']}</strong> berhasil ditambahkan dengan nomor <strong>{$no}</strong>.";
            } else {
                $id = (int)$_POST['id'];
                $stmt = $conn->prepare("UPDATE pendaftar SET
                    gelombang=?,nama=?,nisn=?,tanggal_lahir=?,usia=?,jenis_kelamin=?,asal_sekolah=?,
                    no_telp=?,alamat=?,jurusan=?,nilai_raport=?,nilai_tka=?,nilai_akhir=?,lolos_usia=?
                    WHERE id=?");
                $stmt->execute([$d['gelombang'],$d['nama'],$d['nisn'],$d['tanggal_lahir'],$d['usia'],
                    $d['jenis_kelamin'],$d['asal_sekolah'],$d['no_telp'],$d['alamat'],$d['jurusan'],
                    $d['nilai_raport'],$d['nilai_tka'],$d['nilai_akhir'],$d['lolos_usia'],$id]);

                $logStmt = $conn->prepare("INSERT INTO admin_logs (admin_id,action,details,ip_address) VALUES (?,?,?,?)");
                $logStmt->execute([$_SESSION['admin_id'],'EDIT_PENDAFTAR',"Edit pendaftar ID:{$id} — {$d['nama']}",$_SERVER['REMOTE_ADDR']]);
                $msg = "Data <strong>{$d['nama']}</strong> berhasil diperbarui.";
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $row = $conn->prepare("SELECT nama, no_pendaftaran FROM pendaftar WHERE id=?");
        $row->execute([$id]);
        $del = $row->fetch();
        $conn->prepare("DELETE FROM pendaftar WHERE id=?")->execute([$id]);
        $logStmt = $conn->prepare("INSERT INTO admin_logs (admin_id,action,details,ip_address) VALUES (?,?,?,?)");
        $logStmt->execute([$_SESSION['admin_id'],'HAPUS_PENDAFTAR',"Hapus: {$del['nama']} ({$del['no_pendaftaran']})",$_SERVER['REMOTE_ADDR']]);
        $msg = "Pendaftar <strong>{$del['nama']}</strong> berhasil dihapus.";
    }
}

// ── Filter & paginasi ─────────────────────────────────────────────────────────
$fJurusan  = $_GET['jurusan']  ?? '';
$fGelombang= $_GET['gelombang']?? '';
$fStatus   = $_GET['status']   ?? '';
$fCari     = trim($_GET['cari']?? '');
$page_num  = max(1, (int)($_GET['p'] ?? 1));
$per_page  = 20;

$where = ['1=1']; $params = [];
if ($fJurusan)   { $where[] = 'jurusan=?';  $params[] = $fJurusan; }
if ($fGelombang) { $where[] = 'gelombang=?';$params[] = $fGelombang; }
if ($fStatus)    { $where[] = 'status=?';   $params[] = $fStatus; }
if ($fCari)      { $where[] = '(nama LIKE ? OR nisn LIKE ? OR no_pendaftaran LIKE ?)'; $params = array_merge($params, ["%$fCari%","%$fCari%","%$fCari%"]); }

$whereStr  = implode(' AND ', $where);
$countStmt = $conn->prepare("SELECT COUNT(*) FROM pendaftar WHERE $whereStr");
$countStmt->execute($params);
$total_rows = $countStmt->fetchColumn();
$total_pages = max(1, ceil($total_rows / $per_page));
$offset = ($page_num - 1) * $per_page;

$dataStmt = $conn->prepare("SELECT * FROM pendaftar WHERE $whereStr ORDER BY nilai_akhir DESC, usia DESC LIMIT $per_page OFFSET $offset");
$dataStmt->execute($params);
$rows = $dataStmt->fetchAll();

$short = ['Rekayasa Perangkat Lunak (RPL)'=>'RPL','Teknik Komputer dan Jaringan (TKJ)'=>'TKJ','Asisten Keperawatan (AP)'=>'AP','Tata Kecantikan Kulit dan Rambut (TKKR)'=>'TKKR'];
?>

<?php if ($msg): ?><div class="alert alert-success alert-dismissible fade show"><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

<!-- Tombol Tambah + Filter -->
<div class="d-flex flex-wrap gap-2 mb-3 align-items-center justify-content-between">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalPendaftar" onclick="resetForm()">
        <i class="bi bi-plus-lg me-1"></i>Tambah Pendaftar
    </button>
    <form class="d-flex flex-wrap gap-2" method="GET">
        <input type="hidden" name="page" value="pendaftar">
        <input type="text" name="cari" class="form-control form-control-sm" placeholder="Cari nama / NISN..." value="<?= htmlspecialchars($fCari) ?>" style="width:180px">
        <select name="jurusan" class="form-select form-select-sm" style="width:auto">
            <option value="">Semua Jurusan</option>
            <?php foreach ($jurusan_list as $j): ?>
            <option value="<?= htmlspecialchars($j) ?>" <?= $fJurusan===$j?'selected':'' ?>><?= $short[$j] ?></option>
            <?php endforeach; ?>
        </select>
        <select name="gelombang" class="form-select form-select-sm" style="width:auto">
            <option value="">Semua Gelombang</option>
            <option value="1" <?= $fGelombang==='1'?'selected':'' ?>>Gelombang 1</option>
            <option value="2" <?= $fGelombang==='2'?'selected':'' ?>>Gelombang 2</option>
        </select>
        <select name="status" class="form-select form-select-sm" style="width:auto">
            <option value="">Semua Status</option>
            <option value="pending"  <?= $fStatus==='pending' ?'selected':'' ?>>Pending</option>
            <option value="diterima" <?= $fStatus==='diterima'?'selected':'' ?>>Diterima</option>
            <option value="ditolak"  <?= $fStatus==='ditolak' ?'selected':'' ?>>Ditolak</option>
        </select>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a href="?page=pendaftar" class="btn btn-outline-secondary btn-sm">Reset</a>
    </form>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center small">
        <span>Total: <strong><?= $total_rows ?></strong> pendaftar</span>
        <span>Halaman <?= $page_num ?> / <?= $total_pages ?></span>
    </div>
    <div class="card-body p-0">
    <div class="table-responsive">
    <table class="table table-hover table-sm mb-0 small">
        <thead class="table-dark">
            <tr>
                <th>No. Daftar</th><th>Nama</th><th>NISN</th><th>L/P</th>
                <th>Jurusan</th><th>Glm</th><th>Raport</th><th>TKA</th>
                <th>Nilai Akhir</th><th>Usia</th><th>Status</th><th>Aksi</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($rows)): ?>
            <tr><td colspan="12" class="text-center py-4 text-muted">Tidak ada data.</td></tr>
        <?php else: foreach ($rows as $r):
            $badge = match($r['status']) { 'diterima'=>'bg-success', 'ditolak'=>'bg-danger', default=>'bg-warning text-dark' };
            $gugur = !$r['lolos_usia'];
        ?>
            <tr class="<?= $gugur ? 'table-secondary text-muted' : '' ?>">
                <td><?= htmlspecialchars($r['no_pendaftaran']) ?></td>
                <td>
                    <?= htmlspecialchars($r['nama']) ?>
                    <?php if ($gugur): ?><i class="bi bi-exclamation-circle text-danger" title="Gugur: usia > 21"></i><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($r['nisn']) ?></td>
                <td><?= $r['jenis_kelamin'] ?></td>
                <td><?= $short[$r['jurusan']] ?? $r['jurusan'] ?></td>
                <td class="text-center"><?= $r['gelombang'] ?></td>
                <td class="text-center"><?= number_format($r['nilai_raport'], 2) ?></td>
                <td class="text-center"><?= number_format($r['nilai_tka'], 2) ?></td>
                <td class="text-center fw-semibold"><?= number_format($r['nilai_akhir'], 2) ?></td>
                <td class="text-center"><?= $r['usia'] ?></td>
                <td><span class="badge <?= $badge ?>"><?= ucfirst($r['status']) ?></span></td>
                <td>
                    <button class="btn btn-xs btn-warning btn-sm py-0 px-1" onclick='editForm(<?= json_encode($r) ?>)' title="Edit"><i class="bi bi-pencil"></i></button>
                    <form method="POST" class="d-inline" onsubmit="return confirm('Hapus pendaftar ini?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $r['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger py-0 px-1" title="Hapus"><i class="bi bi-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
    </table>
    </div>
    </div>
    <!-- Paginasi -->
    <?php if ($total_pages > 1): ?>
    <div class="card-footer d-flex justify-content-center">
        <nav><ul class="pagination pagination-sm mb-0">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <li class="page-item <?= $i===$page_num?'active':'' ?>">
                <a class="page-link" href="?page=pendaftar&p=<?= $i ?>&jurusan=<?= urlencode($fJurusan) ?>&gelombang=<?= $fGelombang ?>&status=<?= $fStatus ?>&cari=<?= urlencode($fCari) ?>"><?= $i ?></a>
            </li>
            <?php endfor; ?>
        </ul></nav>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Tambah/Edit -->
<div class="modal fade" id="modalPendaftar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="modalTitle">Tambah Pendaftar</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="formId" value="">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
              <input type="text" name="nama" id="fNama" class="form-control" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">NISN <span class="text-danger">*</span></label>
              <input type="text" name="nisn" id="fNisn" class="form-control" maxlength="20" required>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">L/P <span class="text-danger">*</span></label>
              <select name="jenis_kelamin" id="fJK" class="form-select" required>
                <option value="L">Laki-laki</option>
                <option value="P">Perempuan</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Tanggal Lahir <span class="text-danger">*</span></label>
              <input type="date" name="tanggal_lahir" id="fTgl" class="form-control" required onchange="hitungUsia()">
            </div>
            <div class="col-md-2">
              <label class="form-label fw-semibold">Usia (otomatis)</label>
              <input type="text" id="previewUsia" class="form-control" readonly placeholder="—">
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Asal Sekolah <span class="text-danger">*</span></label>
              <input type="text" name="asal_sekolah" id="fAsal" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Jurusan Pilihan <span class="text-danger">*</span></label>
              <select name="jurusan" id="fJurusan" class="form-select" required>
                <?php foreach ($jurusan_list as $j): ?>
                <option value="<?= htmlspecialchars($j) ?>"><?= htmlspecialchars($j) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Gelombang <span class="text-danger">*</span></label>
              <select name="gelombang" id="fGelombang" class="form-select" required>
                <option value="1">Gelombang 1</option>
                <option value="2">Gelombang 2</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Nilai Raport (rata²) <span class="text-danger">*</span></label>
              <input type="number" name="nilai_raport" id="fRaport" class="form-control" min="0" max="100" step="0.01" required onchange="hitungPreview()">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Nilai TKA <span class="text-danger">*</span></label>
              <input type="number" name="nilai_tka" id="fTka" class="form-control" min="0" max="100" step="0.01" required onchange="hitungPreview()">
            </div>
            <div class="col-md-3">
              <label class="form-label fw-semibold">Nilai Akhir (preview)</label>
              <input type="text" id="previewNilai" class="form-control fw-bold text-success" readonly placeholder="—">
              <small class="text-muted">(Raport×70%) + (TKA×30%)</small>
            </div>
            <div class="col-md-6">
              <label class="form-label">No. Telepon</label>
              <input type="text" name="no_telp" id="fTelp" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Alamat</label>
              <input type="text" name="alamat" id="fAlamat" class="form-control">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function hitungPreview() {
    const r = parseFloat(document.getElementById('fRaport').value) || 0;
    const t = parseFloat(document.getElementById('fTka').value)    || 0;
    document.getElementById('previewNilai').value = ((r * 0.70) + (t * 0.30)).toFixed(2);
}
function hitungUsia() {
    const tgl = document.getElementById('fTgl').value;
    if (!tgl) return;
    const lahir = new Date(tgl), now = new Date();
    let usia = now.getFullYear() - lahir.getFullYear();
    const m = now.getMonth() - lahir.getMonth();
    if (m < 0 || (m === 0 && now.getDate() < lahir.getDate())) usia--;
    const el = document.getElementById('previewUsia');
    el.value = usia + ' tahun';
    el.className = 'form-control ' + (usia > 21 ? 'text-danger fw-bold' : 'text-success');
}
function resetForm() {
    document.getElementById('modalTitle').textContent = 'Tambah Pendaftar';
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    ['fNama','fNisn','fAsal','fTelp','fAlamat','previewNilai','previewUsia'].forEach(id => {
        const el = document.getElementById(id); if (el) el.value = '';
    });
    document.getElementById('fTgl').value = '';
    document.getElementById('fRaport').value = '';
    document.getElementById('fTka').value = '';
    document.getElementById('fJK').value = 'L';
    document.getElementById('fGelombang').value = '1';
}
function editForm(d) {
    document.getElementById('modalTitle').textContent = 'Edit Pendaftar';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('formId').value   = d.id;
    document.getElementById('fNama').value    = d.nama;
    document.getElementById('fNisn').value    = d.nisn;
    document.getElementById('fJK').value      = d.jenis_kelamin;
    document.getElementById('fTgl').value     = d.tanggal_lahir;
    document.getElementById('fAsal').value    = d.asal_sekolah;
    document.getElementById('fTelp').value    = d.no_telp || '';
    document.getElementById('fAlamat').value  = d.alamat || '';
    document.getElementById('fJurusan').value = d.jurusan;
    document.getElementById('fGelombang').value = d.gelombang;
    document.getElementById('fRaport').value  = d.nilai_raport;
    document.getElementById('fTka').value     = d.nilai_tka;
    hitungPreview(); hitungUsia();
    new bootstrap.Modal(document.getElementById('modalPendaftar')).show();
}
</script>
