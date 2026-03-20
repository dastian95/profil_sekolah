# 📚 Data SMK Laboratorium Jakarta - Dokumentasi

## 📋 Ringkasan

Data lengkap tentang SMK Laboratorium Jakarta telah diintegrasikan ke dalam project Profil Sekolah. Data mencakup:

### ✅ Informasi yang Tersedia

- **Identitas Sekolah**: Nama, NPSN, Tahun berdiri, Status Akreditasi
- **Kontak & Lokasi**: Alamat lengkap, Telepon, Email, Koordinat GPS
- **Program Studi (4)**: RPL, TKJ, AP, TKKR
- **Jalur Penerimaan (4)**: Prestasi, Zonasi, Afirmasi, Perpindahan Orang Tua
- **Statistik**: Jumlah siswa, guru, fasilitas
- **Prestasi & Penghargaan**: 5+ prestasi nasional/internasional
- **Kemitraan**: 5 partnership strategis dengan industri
- **Kalender Akademik**: Jadwal pendaftaran hingga akhir tahun ajaran

---

## 📁 Struktur File

```
profil_sekolah/
├── data/
│   └── school_info.json          ← Data utama SMK Lab Jakarta (JSON)
│
├── about_school.php              ← Halaman "Tentang Kami" (menampilkan semua data)
└── index.php                     ← Updated dengan link ke about_school.php
```

---

## 🔍 Detail Data - Per Kategori

### 1️⃣ **Informasi Dasar Sekolah**

```json
{
  "name": "SMK Laboratorium Jakarta",
  "npsn": "20107068",
  "accreditation": "A",
  "founded": 1980,
  "addressFull": "Jl. H. Bape No. 1, Kampung Melayu, Kec. Jatinegara, Kota Jakarta Timur, DKI Jakarta 13410"
}
```

**File**: `data/school_info.json` → `school.name`, `school.npsn`, dll

---

### 2️⃣ **Program Studi (4 Jurusan)**

#### A. Rekayasa Perangkat Lunak (RPL)

- **Capacity**: 120 siswa/tahun
- **Skills**: Web Development, Mobile App, Database, UI/UX, Cloud Computing

#### B. Teknik Komputer dan Jaringan (TKJ)

- **Capacity**: 120 siswa/tahun
- **Skills**: Network Admin, Server Mgmt, Cybersecurity, IT Support

#### C. Asisten Keperawatan (AP)

- **Capacity**: 80 siswa/tahun
- **Skills**: Patient Care, Health Safety, Medical Equipment, First Aid

#### D. Tata Kecantikan Kulit & Rambut (TKKR)

- **Capacity**: 80 siswa/tahun
- **Skills**: Skin Care, Hair Treatment, Makeup, Customer Service

**Total Kapasitas**: 400 siswa/tahun

**File**: `data/school_info.json` → `school.majors[]`

---

### 3️⃣ **Jalur Penerimaan (4 Routes)**

| Jalur              | Kuota | Nilai Min | Syarat Utama             |
| ------------------ | ----- | --------- | ------------------------ |
| **Prestasi**       | 30    | 7.5       | Sertifikat prestasi      |
| **Zonasi**         | 50    | 6.5       | Domisili sesuai zona     |
| **Afirmasi**       | 15    | 6.0       | SKTM (Surat Tidak Mampu) |
| **Perpindahan OT** | 5     | 6.5       | Surat mutasi orang tua   |

**File**: `data/school_info.json` → `school.admissionRoutes[]`

---

### 4️⃣ **Fasilitas**

14 fasilitas utama:

- Laboratorium Komputer
- Laboratorium Jaringan
- Workshop Teknik
- Perpustakaan Digital
- Ruang Multimedia
- Ruang Praktik Kecantikan
- Dan lainnya...

**File**: `data/school_info.json` → `school.facilities[]`

---

### 5️⃣ **Prestasi & Penghargaan**

```
2023: Juara 1 Kompetisi Robotika Nasional
2023: Sertifikasi ISO 9001:2015
2022: Juara 2 LKS Bidang TKJ (Propinsi)
2022: School of Excellent (Penghargaan Sekolah Unggulan)
2021: Program Green School (Sekolah Hijau)
```

**File**: `data/school_info.json` → `school.achievements[]`

---

### 6️⃣ **Kemitraan Strategis**

5 Partnership dengan:

- PT. Telkom Indonesia (Jaringan & TI)
- Google Developer Community (Software Development)
- IBM Innovation Lab (Cloud Computing & AI)
- Rumah Sakit Besar Jakarta (Keperawatan)
- Beauty Academy International (Tata Kecantikan)

**File**: `data/school_info.json` → `school.partnerships[]`

---

### 7️⃣ **Kalender Akademik 2026**

```
Pendaftaran  : 1 Juni - 15 Juli 2026
Seleksi      : 20 Juli - 10 Agustus 2026
Verifikasi   : 15 Agustus 2026
Tahun Ajaran : 1 September 2026 - 30 Juni 2027
Semester 1   : 1 Sept - 20 Des 2026
Semester 2   : 10 Jan - 30 Juni 2027
```

**File**: `data/school_info.json` → `school.academicCalendar`

---

## 🌐 Cara Mengakses Data

### Opsi 1: PHP JSON Loading (Backend)

```php
<?php
// Load data dari JSON
$school_file = __DIR__ . '/data/school_info.json';
$school = json_decode(file_get_contents($school_file), true)['school'];

// Akses data
echo $school['name'];                    // "SMK Laboratorium Jakarta"
echo $school['statistics']['totalStudents'];  // 1200
echo $school['majors'][0]['name'];       // "Rekayasa Perangkat Lunak (RPL)"
?>
```

### Opsi 2: JavaScript (Frontend)

```javascript
// Load data
fetch("/data/school_info.json")
  .then((r) => r.json())
  .then((data) => {
    const school = data.school;
    console.log(school.name);
    console.log(school.majors);
  });
```

### Opsi 3: API Endpoint

Untuk membuat API endpoint yang mengembalikan data sekolah:

```php
<?php
// api/school_info.php
header('Content-Type: application/json');
$school = json_decode(file_get_contents(__DIR__ . '/../data/school_info.json'), true)['school'];
echo json_encode($school);
?>
```

---

## 🖼️ Halaman Menampilkan Data

### ✅ Halaman "Tentang Kami" (`about_school.php`)

Halaman ini menampilkan SEMUA data sekolah dengan layout yang menarik:

- **Header Hero** - Intro sekolah
- **Statistik** - Total siswa, guru, program, tahun berdiri
- **Program Studi** - Card untuk setiap jurusan
- **Jalur Penerimaan** - Info detail per jalur dengan syarat
- **Prestasi** - Timeline penghargaan
- **Kemitraan** - Grid partnership
- **Kontak** - Info lengkap + embedded Google Maps
- **CTA (Call-to-Action)** - Tombol "Mulai Pendaftaran"

**URL**: `http://localhost:8000/about_school.php`

---

## 📝 Modifikasi & Pengembangan

Jika ingin menambah/mengubah data:

### 1. Edit JSON

```bash
# Edit file: profil_sekolah/data/school_info.json
# Tambahkan data baru atau ubah yang sudah ada
```

### 2. Perbarui Halaman

Data akan otomatis tampil di `about_school.php` karena menggunakan dynamic PHP loops

### 3. Database (Optional)

Untuk production, import data ke database:

```sql
-- Buat table school_info
CREATE TABLE school_info (
  id INT PRIMARY KEY,
  name VARCHAR(255),
  npsn VARCHAR(20),
  address TEXT,
  ...
);

-- Insert dari JSON
```

---

## 🔗 Integrasi dengan Sistem

### Di Landing Page (`index.php`)

```
Navigasi:
- Home
- Tentang Kami ← Link ke about_school.php ✅
- About
- Jurusan
- Buat Akun
- Login
```

### Di Admin Dashboard

Bisa menambahkan widget:

- Jumlah program studi
- Kapasitas penerimaan
- Status kemitraan

### Di Form Pendaftaran

Dropdown untuk memilih jurusan menggunakan data dari JSON

---

## 📊 Data Quality Checklist

✅ Informasi dasar sekolah - Lengkap  
✅ Program studi (4) - Lengkap dengan kompetensi  
✅ Jalur penerimaan (4) - Lengkap dengan syarat  
✅ Fasilitas - Lengkap (14 item)  
✅ Prestasi - Lengkap (5 item)  
✅ Kemitraan - Lengkap (5 item)  
✅ Kontak - Lengkap dengan koordinat GPS  
✅ Kalender akademik - Lengkap untuk tahun 2026

---

## 🚀 Next Steps

1. ✅ **Halaman About** - Sudah dibuat (`about_school.php`)
2. ⭕ **API Endpoint** - Bisa dibuat untuk mobile apps
3. ⭕ **Database Integration** - Optional untuk production
4. ⭕ **Admin Panel** - Bisa edit data sekolah dari dashboard
5. ⭕ **Multilingual** - Tambah support for English

---

## 📞 Support

Untuk info lebih lanjut tentang SMK Laboratorium Jakarta:

- 📧 Email: info@smklabjakarta.sch.id
- ☎️ Telepon: +62 21 4712922
- 🌐 Website: https://www.smklabjakarta.sch.id

---

**Generated**: March 20, 2026  
**Last Updated**: March 20, 2026
