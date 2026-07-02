# Arsip: Sistem Jalur Gelombang 2 (multi-jalur) — DINONAKTIFKAN

**Tanggal diarsipkan:** 2026-07-01
**Alasan:** Atas keputusan pemilik, G2 memakai **sistem yang sama dengan G1** (kompetisi
Top-25 murni berdasarkan **nilai akhir**). Sistem jalur multi (Reguler/Yatim-Piatu/
Anak Guru/ABK dengan kuota per-jalur, zonasi, nilai khusus) **tidak dipakai**.

> Folder `_archive/` **DIKECUALIKAN dari deploy** (lihat `.github/workflows/deploy.yml`
> `--exclude-glob _archive`) — jadi kode ini **tidak ikut ke server live**. Ini hanya
> cadangan/referensi bila suatu saat mau dihidupkan kembali.

## Yang dihapus dari kode aktif

1. **`admin/pendaftar.php`** — tab "Jalur Seleksi (Gelombang 2)" beserta isinya:
   pilihan jalur (radio), Kelurahan/Jarak (zonasi), Status Ortu (Yatim/Piatu),
   dan Nilai Khusus (ABK). Lihat `form_tab_jalur.html` di folder ini.
   Juga variabel `$g2_aktif` dan `<li id="tabJalurNav">` yang rusak/tersembunyi.

2. **`admin/ranking.php`** — cabang `rank_sort()` untuk `g2_jarak`, `g2_abk`,
   `g2_reguler` (dead code, hanya `g1` yang dipakai). Lihat `ranking_rank_sort_g2.php`.

## Yang SENGAJA dibiarkan (inert, aman)

- Kolom DB `jalur`, `nilai_khusus`, `jarak_km`, `status_ortu`, `kelurahan` **tidak
  didrop** — sekadar kolom mati. Data lama tetap utuh.
- Fungsi JS `onJalurChange()`, `setG2Section()`, `updateJarakZonasi()` **dibiarkan**
  karena semuanya sudah *guard* (`if (el) …`) → jadi **no-op** setelah HTML dihapus,
  dan `editForm/resetForm` tidak patah.
- Logika simpan `$jalur` (default `'reguler'`), `$nilai_khusus` (null bila bukan abk)
  dibiarkan — aman karena field-nya tak lagi dikirim → nilai default.

## Cara menghidupkan kembali (bila perlu)

Kembalikan blok HTML dari `form_tab_jalur.html` ke `admin/pendaftar.php` (sebelum
`<!-- ── Tab Data Diri`), kembalikan `<li>` nav tab Jalur, kembalikan cabang
`rank_sort` dari `ranking_rank_sort_g2.php`, dan implementasikan kuota per-jalur di
`conn.php` `auto_rank_jurusan` (BELUM pernah dibuat — dulu ranking mengabaikan jalur).
