<?php
// Panel pengumuman internal (admin/superadmin) untuk ditampilkan di dashboard home.
// Menampilkan pengumuman yang aktif & sedang dalam rentang tayang.
// Membutuhkan $conn dari dashboard.
$ann_list = [];
try {
    $ann_list = $conn->query("SELECT * FROM announcements
        WHERE is_active=1
          AND (publish_at IS NULL OR publish_at <= NOW())
          AND (expire_at  IS NULL OR expire_at  >= NOW())
        ORDER BY urutan ASC, created_at DESC")->fetchAll();
} catch (PDOException $e) {
    // Kolom jadwal mungkin belum ada (tabel lama) — fallback ke is_active saja
    try {
        $ann_list = $conn->query("SELECT * FROM announcements WHERE is_active=1 ORDER BY created_at DESC")->fetchAll();
    } catch (PDOException $e2) { $ann_list = []; }
}
$ann_colors = ['info'=>'primary','warning'=>'warning','danger'=>'danger','success'=>'success'];
$ann_icons  = ['info'=>'bi-info-circle-fill','warning'=>'bi-exclamation-triangle-fill','danger'=>'bi-megaphone-fill','success'=>'bi-check-circle-fill'];
if (!empty($ann_list)):
?>
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <span class="fw-semibold"><i class="bi bi-megaphone-fill me-2 text-primary"></i>Pengumuman</span>
        <span class="badge bg-primary-subtle text-primary-emphasis"><?= count($ann_list) ?> aktif</span>
    </div>
    <div class="card-body d-flex flex-column gap-2 py-3">
        <?php foreach ($ann_list as $a):
            $c = $ann_colors[$a['type']] ?? 'secondary';
            $ic = $ann_icons[$a['type']] ?? 'bi-info-circle-fill';
        ?>
        <div class="d-flex gap-3 align-items-start border-start border-4 border-<?= $c ?> bg-light rounded-end px-3 py-2">
            <i class="bi <?= $ic ?> fs-5 text-<?= $c ?> mt-1"></i>
            <div class="flex-fill">
                <div class="fw-semibold"><?= htmlspecialchars($a['title']) ?></div>
                <div class="small text-muted" style="white-space:pre-line;"><?= htmlspecialchars($a['message']) ?></div>
                <div class="small text-muted mt-1">
                    <i class="bi bi-clock me-1"></i><?= date('d M Y', strtotime($a['created_at'])) ?>
                    <?php if (!empty($a['target_gelombang'])): ?>
                        · <span class="badge bg-secondary">Gelombang <?= (int)$a['target_gelombang'] ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
