<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification - SMK Lab Jakarta</title>
    <link href="assets/css/main.css" rel="stylesheet">
</head>
<body>
<header class="bg-primary text-white py-3">
    <div class="container">
        <h1>SMK Laboratorium Jakarta</h1>
    </div>
</header>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3>Enter Verification Code</h3>
                    </div>
                    <div class="card-body">
                        <form action="verification_process.php" method="post">
                            <div class="mb-3">
                                <label for="code" class="form-label">Verification Code</label>
                                <input type="text" class="form-control" id="code" name="code" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Verify</button>
                            <div class="mt-3 text-center">
                                <button type="button" class="btn btn-link btn-sm" id="resendBtn">Resend Code</button>
                                <div id="resendMessage" class="small mt-1"></div>
                            </div>
                        </form>
                        <div id="message" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            fetch('verification_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('message').innerHTML = '<div class="alert alert-' + (data.success ? 'success' : 'danger') + '">' + data.message + '</div>';
                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                }
            });
        });

        // Resend Code Logic
        const resendBtn = document.getElementById('resendBtn');
        const resendMessage = document.getElementById('resendMessage');
        let countdown = 0;

        resendBtn.addEventListener('click', function() {
            if (countdown > 0) return;

            resendBtn.disabled = true;
            resendMessage.innerHTML = '<span class="text-info">Sending...</span>';

            fetch('resend_code.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resendMessage.innerHTML = '<span class="text-success">' + data.message + '</span>';
                        startCountdown(60, data.remaining); // Disable for 60 seconds and pass remaining quota
                    } else {
                        resendMessage.innerHTML = '<span class="text-danger">' + data.message + '</span>';
                        resendBtn.disabled = false;
                    }
                })
                .catch(err => {
                    resendMessage.innerHTML = '<span class="text-danger">Error sending request.</span>';
                    resendBtn.disabled = false;
                });
        });

        function startCountdown(seconds, remaining) {
            countdown = seconds;
            resendBtn.disabled = true;
            const interval = setInterval(() => {
                resendBtn.textContent = `Resend Code (${countdown}s)`;
                countdown--;
                if (countdown < 0) {
                    clearInterval(interval);
                    if (remaining !== undefined) {
                        resendBtn.textContent = `Resend Code (Sisa ${remaining}x)`;
                    } else {
                        resendBtn.textContent = 'Resend Code';
                    }
                    resendBtn.disabled = false;
                    resendMessage.innerHTML = '';
                }
            }, 1000);
        }
    </script>
</body>
</html>