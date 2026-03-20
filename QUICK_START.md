# 🚀 CARA MENJALANKAN DI VSCODE - STEP BY STEP

## ⚡ PALING CEPAT (30 detik)

### Method 1️⃣: Double-click File

```bash
📁 g:\laragon\www\profil_sekolah\
   ├── run-dev-server.bat  ← **DOUBLE-CLICK INI**
   └── start-dev.py         ← atau ini
```

✅ Server akan start otomatis!  
✅ Browser akan terbuka di http://localhost:8000

---

## OR - Method 2️⃣: Terminal Command (Preferred)

### Step 1: Buka VSCode

```
1. Windows Start → type "code"
2. Click Visual Studio Code
3. File → Open Folder → g:\laragon\www\profil_sekolah
```

### Step 2: Buka Terminal

```
Keyboard: Ctrl + ` (backtick)
Menu: Terminal → New Terminal
```

### Step 3: Paste command ini

```bash
cd g:\laragon\www\profil_sekolah && php -S localhost:8000
```

### Step 4: Lihat Output

```
Development Server (http://localhost:8000) started
Listening on http://localhost:8000
Press Ctrl-C to quit
```

### Step 5: Buka Browser

- Automatic: Browser akan terbuka sendiri
- Manual: Buka http://localhost:8000

---

## OR - Method 3️⃣: Via Task (Ctrl+Shift+B)

```
1. Pastikan folder sudah dibuka di VSCode
2. Press: Ctrl + Shift + B
3. Select: "Run PHP Development Server"
4. Server akan start di background
5. Lihat Terminal untuk URL
```

---

## ✅ Yang Harus Terlihat

```
Listening on http://localhost:8000

Press Ctrl-C to quit
```

Kemudian browser akan membuka halaman landing dengan:

- 🎓 School logo (SMK Laboratorium Jakarta)
- 📝 Register button
- 🔓 Login link
- ℹ️ School information

---

## 🎯 Test Features

| Feature   | URL                                       |
| --------- | ----------------------------------------- |
| Home      | http://localhost:8000                     |
| Login     | http://localhost:8000/login.php           |
| Register  | http://localhost:8000/register.php        |
| Admin     | http://localhost:8000/admin_dashboard.php |
| Profile   | http://localhost:8000/profile.php         |
| Dashboard | http://localhost:8000/dashboard.php       |

---

## 📧 Test Email (MailHog)

Setiap email yang terkirim bisa dilihat di:

```
http://localhost:8025
```

---

## 🛑 Stop Server

```
Press Ctrl + C di Terminal
```

---

## ❌ ERROR? CHECK

### Port 8000 already in use?

```bash
php -S localhost:9000
```

### Database error?

1. Buka Laragon
2. Click START ALL
3. Wait for MySQL (Maria DB)

### Blank white page?

```bash
# Run dengan error display:
php -d display_errors=1 -S localhost:8000
```

---

## 📱 Demo Test Flow

1. **Register**

   ```
   Home → "Daftar Sekarang" button
   Fill form with test data
   Verify email (check MailHog)
   ```

2. **Login**

   ```
   Login page with registered email
   Check remember me
   ```

3. **Student Features**

   ```
   Dashboard → View status
   Profile → Edit info
   Jadwal Ujian → View exam schedule
   Kartu → Download student card
   ```

4. **Admin (need to be admin)**
   ```
   Admin Dashboard → Statistics
   Manage Users → See all registrations
   Document Verification → Verify docs
   Logs → View admin actions
   ```

---

## ⚡ TROUBLESHOOTING

### Q: Terminal shows errors?

**A:** Scroll up dalam terminal untuk melihat error message, copy-paste ke ChatGPT

### Q: "Connection refused"

**A:**

1. MySQL belum running → Start Laragon
2. Wrong credentials in .env → Check DB_HOST, DB_USER, DB_PASS

### Q: CSS/JS tidak loading?

**A:**

```bash
# Stop server (Ctrl+C)
# Run lagi:
php -S localhost:8000
# Refresh browser (F5 atau Ctrl+Shift+R)
```

### Q: Can't access admin?

**A:**

1. Create account (register)
2. Change role in database (manual query)
3. OR use test admin account from docs

---

## 🎓 Next - Start Development!

- Edit files di VSCode
- Changes reflect automatically (just refresh browser)
- Check Terminal for errors/logs
- Use MailHog to test emails
- Check debug.log for detailed errors

---

**✨ READY? Let's go!** ✨

Double-click `run-dev-server.bat` or copy terminal command above →  
Server starts → Browser opens → Start coding! 🚀
