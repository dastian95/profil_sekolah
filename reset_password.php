<?php
require_once __DIR__ . '/conn.php';
$token = $_GET['token'] ?? '';
$valid = false;

if ($token) {
    $tokenHash = hash('sha256', $token);
    $stmt = $conn->prepare("SELECT id_pendaftar FROM users WHERE reset_token = ? AND reset_token_expires_at > NOW()");
    $stmt->execute([$tokenHash]);
    if ($stmt->fetch()) {
        $valid = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - SMK Lab Jakarta</title>
    <link href="assets/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body class="login-page">
    <main class="main">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header"><h3>Reset Password</h3></div>
                    <div class="card-body">
                        <?php if ($valid): ?>
                        <form action="reset_password_process.php" method="post">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            <div class="mb-3">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" name="password" id="password" class="form-control" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8">
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </form>
                        <?php else: ?>
                            <div class="alert alert-danger">Invalid or expired token. <a href="forgot_password.php">Try again</a></div>
                        <?php endif; ?>
                        <div id="message" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </main>
    <script>
        document.querySelector('form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('reset_password_process.php', { method: 'POST', body: formData })
            .then(response => {
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Server response:', text);
                        throw new Error('Invalid server response');
                    }
                });
            })
            .then(data => {
                document.getElementById('message').innerHTML = '<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>';
                if(data.success) setTimeout(() => window.location.href = 'login.php', 2000);
            })
            .catch(error => {
                document.getElementById('message').innerHTML = '<div class="alert alert-danger">' + error.message + '</div>';
            });
        });

        // Show/Hide Password Toggle
        function setupPasswordToggle(inputId, toggleId) {
            const passwordInput = document.getElementById(inputId);
            const toggleButton = document.getElementById(toggleId);
            if (!passwordInput || !toggleButton) return;

            toggleButton.addEventListener('click', function() {
                // toggle the type attribute
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // toggle the icon
                const icon = this.querySelector('i');
                if (type === 'password') {
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        }
        setupPasswordToggle('password', 'togglePassword');
        setupPasswordToggle('confirm_password', 'toggleConfirmPassword');
    </script>
</body>
</html>