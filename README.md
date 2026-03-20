# Profil Sekolah - School Registration System

## 🎓 Project Overview

**Sistem Pendaftaran & Manajemen Sekolah**

- School: SMK Laboratorium Jakarta
- Built with: PHP Native + MySQL + Bootstrap 5
- Purpose: Student registration, document verification, exam scheduling

---

## 🚀 Quick Start (Jalankan Sekarang!)

### **Cara 1: PHP Development Server (✨ Recommended)**

```bash
# 1. Buka Terminal di VSCode (Ctrl + `)
# 2. Paste command ini:

cd g:\laragon\www\profil_sekolah
php -S localhost:8000
```

**Output yang akan terlihat:**

```
Development Server (http://localhost:8000) started...
Listening on http://localhost:8000
```

**3. Buka bagian bawah VSCode untuk melihat URL yang active**

**4. Tekan Ctrl+Click atau pergi ke:**

```
http://localhost:8000
```

---

### **Cara 2: Laragon Built-in Server**

```bash
# Pastikan Laragon sudah HIDUP
# Buka browser ke:
http://localhost/profil_sekolah
```

---

## 📂 Project Structure

```
profil_sekolah/
├── .vscode/
│   ├── tasks.json          ← Run tasks
│   ├── settings.json       ← PHP settings
│   └── launch.json         ← Debug config
│
├── index.php               ← Landing page
├── login.php               ← Login page
├── register.php            ← Registration
├── dashboard.php           ← User dashboard
├── admin_*.php             ← Admin pages
│
├── assets/
│   ├── css/
│   ├── js/
│   └── vendor/             ← Bootstrap, etc
│
├── vendor/                 ← Composer deps
├── uploads/                ← User uploads (photos/docs)
├── .env                    ← Configuration
└── profil_sekolah.sql      ← Database schema
```

---

## 🔐 Test Accounts

### Admin Account

```
Email: admin@test.com
Password: AdminPass123
```

### Student Account

```
Email: student@test.com
Password: Student123
```

_Note: Create your own via registration page_

---

## 🎯 Main Features

### 👨‍🎓 Student

- ✅ Register & Email Verification
- ✅ View Profile & Status
- ✅ Upload Documents
- ✅ Check Exam Schedule
- ✅ Download Student Card (Kartu Peserta)

### 👨‍💼 Admin

- ✅ Dashboard with Statistics
- ✅ Manage Users
- ✅ Verify Documents
- ✅ View Logs
- ✅ Announcements
- ✅ Export Data
- ✅ School Data Comparison

### 🌐 Public

- ✅ Register
- ✅ Login
- ✅ Forgot Password
- ✅ Email Verification

---

## 🔧 Configuration

### Database (.env)

```
DB_HOST=localhost
DB_NAME=profil_sekolah
DB_USER=root
DB_PASS=
```

### Email (MailHog for testing)

```
SMTP_HOST=localhost
SMTP_PORT=1025
MAIL_FROM_ADDRESS=admin@smklab.sch.id
MAIL_FROM_NAME=SMK Lab Jakarta
```

**View test emails:**

```
http://localhost:8025
```

---

## 📱 Page Map

| Page            | URL                         | Role    |
| --------------- | --------------------------- | ------- |
| Landing         | `/`                         | Public  |
| Login           | `/login.php`                | Public  |
| Register        | `/register.php`             | Public  |
| Verify Email    | `/verify.php?token=...`     | Public  |
| Profile         | `/profile.php`              | Student |
| Dashboard       | `/dashboard.php`            | Student |
| Exam Schedule   | `/jadwal_ujian.php`         | Student |
| Student Card    | `/kartu.php`                | Student |
| Admin Dashboard | `/admin_dashboard.php`      | Admin   |
| Manage Users    | `/admin_manage_users.php`   | Admin   |
| Verify Docs     | `/admin_document_users.php` | Admin   |
| Logs            | `/admin_logs.php`           | Admin   |
| Announcements   | `/admin_announcements.php`  | Admin   |

---

## 🐛 Debugging

### In VSCode:

1. **F9** - Toggle Breakpoint
2. **F5** - Start Debugging (requires XDebug setup)
3. **Ctrl + Shift + D** - Debug Panel

### File that needs setup (if using XDebug):

```
G:\laragon\bin\php\php-8.3.30-Win32-vs16-x64\etc\php.ini
```

---

## 💾 Database

### Import Schema

```bash
# In MySQL/Laragon MySQL CLI:
mysql -u root profil_sekolah < profil_sekolah.sql
```

### Check Tables

```sql
USE profil_sekolah;
SHOW TABLES;
```

---

## 📦 Composer Dependencies

Already installed:

- `phpmailer/phpmailer` - Email sending
- `google/apiclient` - Google OAuth
- `vlucas/phpdotenv` - Environment variables

---

## ✅ Checklist sebelum Mulai

- [x] PHP 8.3.30 (Laragon)
- [x] MySQL running
- [x] .env configured
- [x] Composer deps installed
- [x] Database imported
- [ ] Run PHP server (next step!)
- [ ] Open http://localhost:8000

---

## 🆘 Troubleshooting

### Port 8000 already in use?

```bash
php -S localhost:9000
```

### Blank white page?

1. Check Terminal for errors
2. Look at `debug.log`
3. Enable error display:
   ```bash
   php -d display_errors=1 -S localhost:8000
   ```

### Database won't connect?

1. Start MySQL in Laragon
2. Verify `.env`: `DB_HOST`, `DB_NAME`, `DB_USER`
3. Check if database exists: `profil_sekolah`

### Still stuck?

Check detailed guide: `VSCODE_RUN_GUIDE.md`

---

## 🚀 Next Steps

1. **Run the server** (commands above)
2. **Visit localhost:8000**
3. **Register a test account**
4. **Explore the features**
5. **Check admin panel**
6. **Read email in MailHog (localhost:8025)**

**Happy coding! 🎉**
