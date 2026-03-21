<?php
// protected by password (for development only)
session_start();

// Handle logout
if (isset($_GET['logout'])) {
    unset($_SESSION['email_logs_auth']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$authenticated = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === 'admin123') { // Simple auth
        $_SESSION['email_logs_auth'] = true;
    } else {
        $error = 'Invalid password';
    }
}

$authenticated = $_SESSION['email_logs_auth'] ?? false;

if (!$authenticated) {
?>
    <!DOCTYPE html>
    <html>

    <head>
        <title>Email Logs - Auth Required</title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body {
                padding: 20px;
            }
        </style>
    </head>

    <body>
        <div class="container" style="max-width: 400px; margin-top: 50px;">
            <div class="card">
                <div class="card-header">
                    <h5>Email Logs - Development Only</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary">View Logs</button>
                    </form>
                    <?php if (isset($error)) echo '<div class="alert alert-danger mt-2">' . $error . '</div>'; ?>
                </div>
            </div>
        </div>
    </body>

    </html>
<?php
    exit;
}

// If authenticated, show logs
$logDir = __DIR__; // Current directory contains the JSON files
$logs = [];

if (is_dir($logDir)) {
    $files = array_reverse(glob($logDir . '/*.json'));
    foreach ($files as $file) {
        $logs[] = [
            'filename' => basename($file),
            'data' => json_decode(file_get_contents($file), true),
            'time' => filemtime($file)
        ];
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Email Logs - Development</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f5f5f5;
        }

        .log-item {
            background: white;
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid #0d6efd;
        }

        .reset-link {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 3px;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
        }

        .btn-copy {
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2><i class="bi bi-envelope"></i> Email Logs (Development)</h2>
            <a href="?logout=1" class="btn btn-sm btn-secondary">Logout</a>
        </div>

        <?php if (empty($logs)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No emails sent yet. Try using "Forgot Password" feature.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($logs as $log):
                    $resetLink = $log['data']['reset_link'] ?? '';
                    $escapedLink = htmlspecialchars($resetLink);
                    $jsonLink = json_encode($resetLink);
                ?>
                    <div class="col-md-6">
                        <div class="log-item">
                            <h5><i class="bi bi-envelope-at"></i> <?php echo htmlspecialchars($log['data']['to'] ?? ''); ?></h5>
                            <p><strong>To:</strong> <?php echo htmlspecialchars($log['data']['to_name'] ?? ''); ?></p>
                            <p><strong>Sent:</strong> <?php echo htmlspecialchars($log['data']['timestamp'] ?? ''); ?></p>
                            <p><strong>Subject:</strong> <?php echo htmlspecialchars($log['data']['subject'] ?? ''); ?></p>
                            <div class="mt-3">
                                <label><strong>Reset Link:</strong></label>
                                <div class="reset-link">
                                    <?php echo $escapedLink ?: '<em class="text-muted">No link available</em>'; ?>
                                </div>
                                <?php if ($resetLink): ?>
                                    <button class="btn btn-sm btn-primary btn-copy mt-2" data-url="<?php echo $escapedLink; ?>">
                                        <i class="bi bi-clipboard"></i> Copy Link
                                    </button>
                                    <a href="<?php echo $escapedLink; ?>" target="_blank" class="btn btn-sm btn-success mt-2">
                                        <i class="bi bi-arrow-up-right"></i> Open Link
                                    </a>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted"><i class="bi bi-clock"></i> File: <?php echo htmlspecialchars($log['filename']); ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Link copied to clipboard!');
            }).catch(err => {
                console.error('Failed to copy:', err);
                alert('Could not copy link');
            });
        }

        // Setup copy button event listeners
        document.querySelectorAll('.btn-copy').forEach(button => {
            button.addEventListener('click', function() {
                const url = this.getAttribute('data-url');
                copyToClipboard(url);
            });
        });
    </script>
</body>

</html>