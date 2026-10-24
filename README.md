# 👟 Sepatu SR — Sistem Rekapitulasi Kebutuhan Ukuran Sepatu (Kemensos / Sekolah Rakyat)

Aplikasi web modern berbasis **Laravel 12**, **Inertia.js**, **Vue 3**, dan **Tailwind CSS v4** untuk manajemen, pemrosesan multi-berkas konfirmasi Excel, dan rekapitulasi kebutuhan ukuran sepatu (siswa, guru, dan tenaga kependidikan) bagi program Sekolah Rakyat (Kemensos).

---

## ✨ Fitur Utama

1. **Multi-File & Multi-Sheet Excel Import**:
   - Mampu mengunggah beberapa file Excel sekaligus (*batch upload*) via *drag & drop*.
   - Deteksi otomatis struktur kolom lembar kerja (*dynamic column mapping*) tanpa terpaku pada format baku.
   - Normalisasi otomatis panjang telapak kaki (konversi millimeter `mm` ke centimeter `cm`, e.g. `235 mm` → `23.5 cm`).
   - Pencegahan duplikasi data sekolah (*atomic re-import refresh*).
   - Filter otomatis untuk mengabaikan sheet rekapitulasi, baris ringkasan, dan formula Excel.

2. **Rekapitulasi Matriks Ukuran Sepatu (Size 36–47)**:
   - Pengelompokan ukuran sepatu per kategori:
     - Siswa Laki-Laki (L) & Perempuan (P)
     - Guru & Tendik Laki-Laki (L) & Perempuan (P)
     - Total Kebutuhan Pria & Total Kebutuhan Wanita
     - Grand Total Pasang Sepatu siap produksi vendor.
   - Visual bar perbandingan proporsi kebutuhan per ukuran sepatu.

3. **Ekspor Data Ultra Cepat (Super Fast Export)**:
   - **Ekspor Excel (.xlsx)**:
     - Sheet 1: Tabel Matriks Rekap Kebutuhan Pengadaan Vendor.
     - Sheet 2: Daftar Lengkap Rincian Penerima (Nama, Gender, Jabatan, Kategori, Panjang Kaki, Ukuran Sepatu).
     - Waktu generasi instan (~0.15 detik) dengan memori rendah dan proteksi *formula injection*.
   - **Cetak Laporan PDF (.pdf)**:
     - Layout dokumen resmi siap cetak dan tanda tangan verifikasi vendor/sekolah.

4. **Keamanan & Tata Kelola Perusahaan (Enterprise Grade)**:
   - Autentikasi sesi aman berbasis **Laravel Sanctum**.
   - Kontrol Akses Berbasis Peran (**Spatie Permission RBAC**).
   - Catatan Aktivitas & Jejak Audit Lengkap (**Spatie Activity Log**).
   - Notifikasi Real-time terintegrasi (**Laravel Reverb WebSocket**).
   - Desain responsif dengan tema 3 mode (Light / Dark / System).

---

## 🛠️ Stack Teknologi

- **Backend Framework**: Laravel 12.x / PHP 8.2+
- **Frontend Framework**: Vue 3 (Composition API)
- **Monolith Bridge**: Inertia.js (`@inertiajs/vue3` + `inertiajs/inertia-laravel`)
- **Styling**: Tailwind CSS v4 + `@tailwindcss/vite`
- **Iconography**: `lucide-vue-next`
- **Excel Engine**: `phpoffice/phpspreadsheet` / `maatwebsite/excel`
- **PDF Engine**: `barryvdh/laravel-dompdf`
- **Role & Audit Trail**: `spatie/laravel-permission` + `spatie/laravel-activitylog`
- **Real-Time Broadcast**: `laravel/reverb` + `pusher-js`

---

## 🚀 Panduan Instalasi Lokal

### 1. Kloning Repositori
```bash
git clone https://github.com/paradigma-monolith/kemensos.git
cd kemensos
```

### 2. Instal Dependensi Backend & Frontend
```bash
composer install
npm install
```

### 3. Konfigurasi Lingkungan (.env)
Salin berkas konfigurasi `.env.example`:
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Migrasi Database & Seeding
```bash
touch database/database.sqlite
php artisan migrate --seed
```

### 5. Jalankan Aplikasi
Jalankan development server Laravel dan Vite:
```bash
# Terminal 1 (Laravel Server)
php artisan serve

# Terminal 2 (Vite Hot-Reload)
npm run dev
```

Buka peramban di: `http://127.0.0.1:8000/`

**Akun Bawaan (Default):**
- **Superadmin**: `admin@hybrid.local` / `password`
- **Staff Operator**: `staff@hybrid.local` / `password`

---

## 🧪 Menjalankan Pengujian (Automated Tests)

```bash
php artisan test --filter=ShoeRecapTest
```

---

## 📄 Lisensi
Hak Cipta © 2026 Kemensos / Sekolah Rakyat. Dikembangkan menggunakan Laravel & Vue.js.
