# PPDB SMK Laboratorium Jakarta

Sistem Penerimaan Peserta Didik Baru (PPDB) berbasis web — **admin-only**.
Pendaftar datang langsung ke sekolah, data diinput oleh admin, hasil seleksi
diumumkan otomatis di halaman publik.

- **Stack:** PHP Native + MySQL/MariaDB + Bootstrap 5.3
- **Sekolah:** SMK Laboratorium Jakarta
- **Sifat:** Internal (tidak ada akun siswa / login publik)

---

## Akses Website

### Halaman Publik
```
http://localhost/profil_sekolah/
```
Berisi profil sekolah, jurusan, jadwal PPDB, dan **tabel pengumuman penerimaan**
(otomatis muncul setelah admin publish hasil seleksi).

### Halaman Admin (Secret Link)
```
http://localhost/profil_sekolah/admin.php?k=smklab2026
```
> URL admin **hanya bisa diakses lewat secret key** (`?k=...`).
> Tanpa parameter `k`, akses akan di-redirect ke halaman publik.
> Secret key (`smklab2026`) bisa diganti di file `.env` (variabel `ADMIN_KEY`).

### Akun Login (pakai **username**, bukan email)

#### 👑 Superadmin — Hardcoded (Rahasia)
> Akun ini **TIDAK ada di database**. Tersimpan di file `admin.php` (konstanta
> `SUPER_ADMIN_USERNAME` & `SUPER_ADMIN_HASH`). Untuk ganti password, edit
> langsung file tersebut.

| Username     | Password               |
|--------------|------------------------|
| `superadmin` | `SuperRahasia2026!`    |

#### 👥 Admin Biasa — Tersimpan di Database (tabel `admins`)

| Username  | Password      | Nama Lengkap   |
|-----------|---------------|----------------|
| `admin`   | `admin123`    | Administrator  |
| `budi`    | `budi2026`    | Budi Santoso   |
| `siti`    | `siti2026`    | Siti Aminah    |
| `rahman`  | `rahman2026`  | Rahman Hakim   |
| `dewi`    | `dewi2026`    | Dewi Lestari   |
| `agus`    | `agus2026`    | Agus Pratama   |

> ⚠️ **Wajib ganti password default** setelah login pertama via menu *Ganti Password*.
> Akun admin biasa bisa ganti password sendiri lewat panel; superadmin harus
> edit file `admin.php` (lihat instruksi di bawah).

### Cara Login

1. Buka URL admin lengkap dengan secret key:
   `http://localhost/profil_sekolah/admin.php?k=smklab2026`
2. Masukkan **username** (mis. `budi`) — bukan email
3. Masukkan password sesuai tabel di atas
4. Klik **Masuk** → diarahkan ke dashboard admin

### Cara Ganti Password Superadmin

Karena password superadmin di-hardcode (rahasia), ganti via PHP CLI:

```bash
# 1. Generate hash baru
php -r "echo password_hash('PasswordBaruAnda', PASSWORD_DEFAULT);"
# Output contoh: $2y$12$xxxxxxxx...

# 2. Edit file admin.php, ganti konstanta SUPER_ADMIN_HASH dengan hash baru
```

---

## Cara Kerja Sistem

### 1. Alur Pendaftar
1. Calon siswa datang langsung ke sekolah membawa berkas:
   - Ijazah / SKHU
   - Kartu Keluarga (KK) DKI Jakarta
   - Raport semester 1–6
   - Fotocopy hasil TKA
2. Admin menginput data pendaftar via menu **Data Pendaftar → Tambah Pendaftar**
3. Sistem otomatis menghitung:
   - **Usia** (dari tanggal lahir)
   - **Nilai Akhir** = (Raport × 70%) + (TKA × 30%)
   - **Lolos Usia** = ya jika ≤ 21 tahun, tidak jika > 21 tahun
4. No. pendaftaran otomatis: `PPDB-{tahun}-G{gelombang}-{0001}`

### 2. Sistem Gelombang
| Gelombang | Periode Pendaftaran | Pengumuman | Porsi Kuota |
|-----------|---------------------|------------|-------------|
| Gelombang 1 | 15 – 29 Juni        | 1 Juli     | 70%         |
| Gelombang 2 | 8 – 9 Juli          | 9 Juli     | 30%         |

Tanggal & porsi bisa diubah lewat menu **Pengaturan Gelombang**.

### 3. Kuota & Jurusan
4 jurusan, masing-masing **36 kursi** (total 144):
- Rekayasa Perangkat Lunak (RPL)
- Teknik Komputer dan Jaringan (TKJ)
- Asisten Keperawatan (AP)
- Tata Kecantikan Kulit dan Rambut (TKKR)

Kuota per gelombang per jurusan:
- Glm 1 → `round(36 × 70%) = 25` per jurusan
- Glm 2 → `round(36 × 30%) = 11` per jurusan

### 4. Proses Seleksi (Ranking)
Buka menu **Ranking & Hasil**, pilih gelombang, klik **Proses Penerimaan**:
1. Pendaftar berusia > 21 tahun otomatis **ditolak** (catatan: "Gugur usia")
2. Sisa pendaftar diranking per jurusan: `ORDER BY nilai_akhir DESC, usia DESC`
   > Bila nilai akhir sama, **usia lebih tua menang** (tiebreaker)
3. Top-N per jurusan → status `diterima`, sisanya `ditolak`

### 5. Publish Pengumuman
1. Buka menu **Pengaturan Gelombang**
2. Klik **Publish Pengumuman Gelombang X**
3. Hasil "diterima" otomatis tampil di halaman publik (`index.php`)
4. Bisa di-unpublish kapan saja jika perlu revisi

### 6. Backup / Export
Menu **Backup / Export** → download CSV (kompatibel Excel & Google Sheets).
Filter tersedia: per gelombang, per jurusan, per status.

---

## Menu Admin Panel

| Menu                  | Fungsi                                                |
|-----------------------|-------------------------------------------------------|
| Dashboard             | Statistik ringkas + chart per jurusan & per status    |
| Data Pendaftar        | CRUD pendaftar (tambah, edit, hapus, filter)          |
| Ranking & Hasil       | Ranking otomatis + proses penerimaan per gelombang    |
| Pengaturan Gelombang  | Set tanggal, kuota, porsi, publish/unpublish          |
| Pengumuman            | Pengumuman umum (info/peringatan) di halaman publik   |
| Backup / Export       | Download data CSV dengan filter                       |
| Ganti Password        | Update password akun admin                            |

---

## Struktur Project

```
profil_sekolah/
├── admin/                    Sub-halaman admin panel
│   ├── home.php              Dashboard (statistik & chart)
│   ├── pendaftar.php         CRUD data pendaftar
│   ├── ranking.php           Ranking & proses seleksi
│   ├── gelombang.php         Setting gelombang & publish
│   ├── announcements.php     Pengumuman umum
│   ├── backup.php            Export CSV
│   └── change_password.php   Ganti password admin
├── assets/                   Gambar, CSS, JS untuk index.php
├── vendor/                   Library Composer (phpdotenv, dll)
├── .env                      Konfigurasi (DB, ADMIN_KEY, dll)
├── .env.example              Template env
├── admin.php                 Login admin (gerbang secret link)
├── admin_dashboard.php       Layout panel admin (sidebar + router)
├── conn.php                  Koneksi PDO MySQL
├── env_loader.php            Loader .env via vlucas/phpdotenv
├── index.php                 Halaman publik (profil + pengumuman)
├── logout.php                Logout admin
├── database/schema.sql        Schema database
└── README.md                 Dokumen ini
```

---

## Database

5 tabel utama (lihat `database/schema.sql`):

| Tabel           | Isi                                                       |
|-----------------|-----------------------------------------------------------|
| `admins`        | Akun admin (email, password bcrypt)                       |
| `gelombang`     | Setting per gelombang (tanggal, kuota, porsi, publish)    |
| `pendaftar`     | Data calon siswa + nilai + status seleksi                 |
| `announcements` | Pengumuman umum di halaman publik                         |
| `admin_logs`    | Audit log aktivitas admin                                 |

### Import Database
```bash
mysql -u root profil_sekolah < database/schema.sql
```
Atau via phpMyAdmin → Import → pilih `database/schema.sql`.

---

## Konfigurasi `.env`

```env
APP_URL="http://localhost/profil_sekolah"
APP_NAME="PPDB SMK Laboratorium Jakarta"
ADMIN_KEY=smklab2026          # ← secret key untuk akses admin.php
DB_HOST=localhost
DB_NAME=profil_sekolah
DB_USER=root
DB_PASS=
APP_TIMEZONE=Asia/Jakarta
```

> ⚠️ **Ganti `ADMIN_KEY`** sebelum deploy ke production.

---

## Setup Cepat (XAMPP)

1. Clone / extract project ke `C:\xampp\htdocs\profil_sekolah`
2. Aktifkan **Apache** dan **MySQL** di XAMPP Control Panel
3. Install dependency Composer:
   ```bash
   composer install
   ```
4. Copy `.env.example` → `.env`, sesuaikan `ADMIN_KEY` dan database
5. Import `database/schema.sql` ke MySQL
6. Buka `http://localhost/profil_sekolah/admin.php?k=smklab2026`
7. Login dengan `admin@smklab.sch.id` / `admin123`
8. Ganti password lewat menu **Ganti Password**

---

## Catatan Keamanan

- ✅ Password admin di-hash dengan `password_hash()` (bcrypt)
- ✅ Semua query pakai prepared statement (PDO)
- ✅ Output di-escape via `htmlspecialchars()`
- ✅ Aksi admin tercatat di `admin_logs` (ip, action, detail)
- ⚠️ Ganti `ADMIN_KEY` & password default sebelum production
- ⚠️ Pastikan `.env` masuk `.gitignore` (sudah default)
