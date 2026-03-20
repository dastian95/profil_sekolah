# Profil Sekolah - VSCode Setup & Running Guide

## 📋 Prerequisites

Pastikan sudah terinstall:

- ✅ VSCode
- ✅ PHP 8.3+ (Laragon: `G:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\php.exe`)
- ✅ MySQL (Laragon built-in)
- ✅ Composer dependencies (sudah terinstall)

## 🚀 Cara Menjalankan Project

### **Option 1: PHP Built-in Development Server (Recommended)**

#### Step 1: Buka Terminal di VSCode

Tekan `Ctrl + Backtick` atau View → Terminal

#### Step 2: Jalankan Server

```bash
cd g:\laragon\www\profil_sekolah
php -S localhost:8000
```

Output yang diharapkan:

```
[Wed Mar 20 14:30:00 2026] PHP 8.3.30 Development Server started at http://localhost:8000
[Wed Mar 20 14:30:00 2026] Listening on http://localhost:8000
Press Ctrl-C to quit
```

#### Step 3: Akses Aplikasi

Buka browser dan pergi ke:

```
http://localhost:8000
```

**Kelebihan:**

- ✅ Tidak perlu Apache/Nginx
- ✅ Development cepat
- ✅ Hot reload otomatis

---

### **Option 2: Via Laragon (Apache)**

Pastikan Laragon sudah running (double-click `Laragon.exe`)

#### Access di Browser:

```
http://localhost/profil_sekolah
atau
http://127.0.0.1/profil_sekolah
```

**Kelebihan:**

- ✅ Production-like environment
- ✅ Maria DB sudah running
- ✅ MailHog untuk testing email

---

### **Option 3: Run Task di VSCode**

#### Step 1: Tekan `Ctrl + Shift + B` (Run Build Task)

#### Step 2: Pilih Task:

- `Run PHP Development Server` → Runs on localhost:8000
- `Run via Laragon (Apache)` → Opens http://localhost/profil_sekolah

---

## 🔍 Troubleshooting

### Port 8000 sudah terpakai?

Gunakan port berbeda:

```bash
php -S localhost:9000
```

### Database connection error?

1. Pastikan MySQL running
2. Check `.env` file:
   ```
   DB_HOST=localhost
   DB_NAME=profil_sekolah
   DB_USER=root
   DB_PASS=
   ```

### Blank white page?

1. Check terminal untuk error messages
2. Lihat file `debug.log`
3. Enable `display_errors` di php.ini atau gunakan:
   ```bash
   php -d display_errors=1 -S localhost:8000
   ```

---

## 📱 Pages untuk Testing

### Public Pages

- **Landing/Register:** http://localhost:8000
- **Login:** http://localhost:8000/login.php
- **Forgot Password:** http://localhost:8000/forgot_password.php

### Admin Pages (after login)

- **Admin Dashboard:** http://localhost:8000/admin_dashboard.php
- **Manage Users:** http://localhost:8000/admin_manage_users.php
- **Document Verification:** http://localhost:8000/admin_document_users.php
- **Logs:** http://localhost:8000/admin_logs.php

### Student Pages (after login)

- **Profile:** http://localhost:8000/profile.php
- **Dashboard:** http://localhost:8000/dashboard.php
- **Exam Schedule:** http://localhost:8000/jadwal_ujian.php
- **Student Card:** http://localhost:8000/kartu.php

---

## ⚙️ VSCode Extensions (Recommended)

1. **PHP Intelephense** (bmewburn.vscode-intelephense-client)
   - PHP IntelliSense, code completion

2. **PHP Debug** (felixbecker.php-debug)
   - Debugging dengan XDebug

3. **Thunder Client** atau **REST Client**
   - Testing API endpoints

4. **Database Clients** (cweijan.vscode-mysql-client2)
   - Direct MySQL connection di VSCode

---

## 🐛 Debug Mode

### Enable XDebug (Optional)

Edit `G:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\etc\php.ini`:

```ini
[Xdebug]
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.port=9003
```

Restart PHP server dan setup VSCode launch.json untuk debugging.

---

## 📧 Email Testing

MailHog berjalan di port 1025 (SMTP) dan 8025 (Web UI)

Akses MailHog UI:

```
http://localhost:8025
```

Semua email dari aplikasi akan tertangkap di sini.

---

## 🎯 Quick Start Command

Copy-paste langsung ke terminal VSCode:

```bash
# Navigate ke project
cd g:\laragon\www\profil_sekolah

# Start development server
php -S localhost:8000

# Browser akan terbuka otomatis, jika tidak:
# Buka http://localhost:8000 secara manual
```

**That's it! 🚀 Project sudah running!**
