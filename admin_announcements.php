<?php
// admin_announcements.php

// Create table if not exists
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'warning', 'danger', 'success') DEFAULT 'info',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_announcement'])) {
        $title = $_POST['title'];
        $message = $_POST['message'];
        $type = $_POST['type'];
        
        $stmt = $conn->prepare("INSERT INTO announcements (title, message, type) VALUES (?, ?, ?)");
        $stmt->execute([$title, $message, $type]);
    } elseif (isset($_POST['delete_id'])) {
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->execute([$_POST['delete_id']]);
    } elseif (isset($_POST['toggle_id'])) {
        $stmt = $conn->prepare("UPDATE announcements SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$_POST['toggle_id']]);
    }
}

// Fetch Announcements
$stmt = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC");
$announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle me-2"></i>Add Announcement</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="info">Info (Blue)</option>
                            <option value="warning">Warning (Yellow)</option>
                            <option value="danger">Danger (Red)</option>
                            <option value="success">Success (Green)</option>
                        </select>
                    </div>
                    <button type="submit" name="add_announcement" class="btn btn-primary w-100">Post Announcement</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-list-ul me-2"></i>Active Announcements</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Title</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th class="text-end pe-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($announcements as $a): ?>
                            <tr>
                                <td class="ps-4">
                                    <strong><?php echo htmlspecialchars($a['title']); ?></strong><br>
                                    <small class="text-muted"><?php echo substr(htmlspecialchars($a['message']), 0, 50) . '...'; ?></small>
                                </td>
                                <td><span class="badge bg-<?php echo $a['type']; ?>"><?php echo ucfirst($a['type']); ?></span></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="toggle_id" value="<?php echo $a['id']; ?>">
                                        <button type="submit" class="btn btn-sm badge <?php echo $a['is_active'] ? 'bg-success' : 'bg-secondary'; ?> border-0">
                                            <?php echo $a['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td><?php echo date('d M Y', strtotime($a['created_at'])); ?></td>
                                <td class="text-end pe-4">
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this announcement?');">
                                        <input type="hidden" name="delete_id" value="<?php echo $a['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if(empty($announcements)): ?>
                                <tr><td colspan="5" class="text-center py-3 text-muted">No announcements found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>