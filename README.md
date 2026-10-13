# 🚀 Arsitektur Hybrid Laravel (Traditional SPA via REST API & Inertia.js Bridge)

Aplikasi ini mengimplementasikan **Arsitektur Hybrid Modern** yang menggabungkan dua paradigma frontend Vue 3 dalam satu backend Laravel terpadu dengan basis data **MySQL**, diamankan menggunakan **Laravel Sanctum (Stateful Cookie-Based Session)**:

1. **Autentikasi Terpadu (Laravel Sanctum)**:
   - Akses Login: `/login` (Tersedia tombol autofill akun demo 1-klik)
   - Akun Demo: `admin@hybrid.local` / `password`
   - Sesi berbasis HTTP-Only Cookie yang otomatis berlaku serentak untuk halaman Inertia.js dan Traditional SPA (Single Sign-On).

2. **Inertia.js Bridge (Monolith Experience - Terproteksi)**:
   - Akses via: `/` (Architecture Hub & Telemetry) dan `/inertia/*` (Dashboard & Products CRUD)
   - Berkomunikasi langsung dari controller Laravel via `Inertia::render()` dengan props Eloquent tanpa perlu membuat endpoint API manual.
   - Dilengkapi navbar profil pengguna & tombol Logout.

3. **Traditional SPA (Decoupled Client-Side Routing via REST API - Terproteksi)**:
   - Akses via: `/spa/*`
   - Berjalan mandiri di browser menggunakan **Vue Router (History Mode)** dan **Pinia Store**.
   - Berkomunikasi asinkron via **Axios** ke endpoint `/api/v1/*`.


---

## 🛠️ Stack Teknologi

- **Backend Framework**: Laravel 12.x / PHP 8.2 (Ampps Environment)
- **Database**: MySQL 8.0 (Database: `hybrid_laravel`)
- **Frontend Framework**: Vue 3 (Composition API)
- **Bundler & Tooling**: Vite 7 + `@vitejs/plugin-vue` + Tailwind CSS v4
- **State Management & Routing (SPA)**: Pinia + Vue Router
- **Bridge Monolith**: `@inertiajs/vue3` + `inertiajs/inertia-laravel`
- **HTTP Client**: Axios
- **Role & Permission (RBAC)**: `spatie/laravel-permission` v6
- **Audit Trail & Activity Log**: `spatie/laravel-activitylog` v4
- **Interactive OpenAPI Documentation**: `dedoc/scramble` (`/docs/api`)
- **Document & Spreadsheet Export**: `maatwebsite/excel` (Excel/CSV) + `barryvdh/laravel-dompdf` (PDF)
- **In-Memory Data Store & Cache**: Redis (`predis/predis` v3.7 + `Illuminate\Support\Facades\Redis`)
- **High-Performance Application Engine**: Laravel Octane + RoadRunner (`laravel/octane` + `rr.exe`)
- **Real-Time WebSockets**: Laravel Reverb + Laravel Echo (`laravel/reverb` + `laravel-echo` + `pusher-js`)
- **Application Health & Metrics**: Laravel Pulse (`laravel/pulse`)
- **Error Tracking & Telemetry**: Sentry Full-Stack (`sentry/sentry-laravel` + `@sentry/vue`)

---

## 📁 Struktur Direktori Arsitektur

```text
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   ├── ProductApiController.php    # REST API CRUD untuk Traditional SPA
│   │   │   └── StatsApiController.php      # REST API Telemetri & Status Arsitektur
│   │   └── Inertia/
│   │       ├── DashboardController.php     # Controller Hub & Dashboard Inertia
│   │       └── ProductInertiaController.php# Controller CRUD Produk Inertia
│   └── Middleware/
│       └── HandleInertiaRequests.php       # Middleware Shared Props & Flash Messages
routes/
├── web.php                                 # Rute Hub, Inertia (/inertia/*), & SPA Catch-All (/spa/{any})
├── api.php                                 # Rute RESTful JSON v1 (/api/v1/*)
└── channels.php                            # Definisi Autorisasi Channel WebSocket (Reverb)
resources/
├── views/
│   ├── app.blade.php                       # Template Host untuk Inertia.js Bridge
│   └── spa.blade.php                       # Template Host untuk Traditional SPA (#spa-app)
└── js/
    ├── inertia.js                          # Entry Point Vite untuk Inertia.js
    ├── spa.js                              # Entry Point Vite untuk Traditional SPA
    ├── echo.js                             # Inisialisasi Laravel Echo Client (WebSocket Reverb)
    ├── sentry.js                           # Sentry Client (Vue 3, Browser Tracing, Session Replay)
    ├── Components/
    │   └── HybridNavbar.vue                # Navbar Adaptif (Inertia & SPA Mode)
    ├── Pages/                              # Halaman Inertia.js
    │   ├── HomeHub.vue                     # Interactive Architecture Hub Landing
    │   └── Inertia/
    │       ├── Dashboard.vue               # Inertia Monolith Dashboard
    │       └── Products.vue                # Inertia Direct Eloquent CRUD
    └── spa/                                # Modul Traditional SPA
        ├── App.vue                         # Root SPA Layout dengan RouterView
        ├── router/index.js                 # Vue Router (base: /spa)
        ├── stores/productStore.js          # Pinia Reactive Store & Latency Tracker
        └── views/
            ├── HomeView.vue                # SPA Overview & Real-Time API Ping
            ├── ProductsView.vue            # Reactive CRUD via Axios REST API
            └── ApiExplorerView.vue         # Interaktif REST API Inspector
```

---

## 🌐 Daftar URL & Endpoint Tersedia

### 1. Web & Frontend Interface
| URL | Deskripsi | Paradigma |
|---|---|---|
| `http://localhost/hybridlaravel/public/` | Architecture Hub & Interactive Switcher | Inertia.js |
| `http://localhost/hybridlaravel/public/inertia/dashboard` | Dedicated Dashboard | Inertia.js |
| `http://localhost/hybridlaravel/public/inertia/products` | Manajemen Produk (Props Direct) | Inertia.js |
| `http://localhost/hybridlaravel/public/spa` | Home SPA & Real-time API Latency | Traditional SPA |
| `http://localhost/hybridlaravel/public/spa/products` | Reactive Products CRUD (Axios) | Traditional SPA |
| `http://localhost/hybridlaravel/public/spa/api-explorer` | Interactive REST API Tester | Traditional SPA |
| `http://localhost/hybridlaravel/public/pulse` | Real-Time Health & Server Metrics Dashboard | Laravel Pulse |

### 2. REST API Endpoints (`routes/api.php`)
| Method | Endpoint | Deskripsi |
|---|---|---|
| `GET` | `/api/v1/stats` | Telemetri sistem, database status, dan ringkasan |
| `GET` | `/api/v1/products` | Koleksi data produk (JSON) |
| `POST` | `/api/v1/products` | Tambah produk baru via payload JSON |
| `GET` | `/api/v1/products/{id}` | Detail produk |
| `PUT` | `/api/v1/products/{id}` | Perbarui produk |
| `DELETE` | `/api/v1/products/{id}` | Hapus produk |

---

## ⚙️ Konfigurasi Database (MySQL)

File `.env` telah dikonfigurasi:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hybrid_laravel
DB_USERNAME=root
DB_PASSWORD=mysql
```

---

## 🔨 Perintah Pengembangan (Development Commands)

Untuk kompilasi aset saat mengubah kode Vue atau CSS:
```bash
# Menjalankan Vite Development Server dengan Hot Module Replacement (HMR)
npm run dev

# Membangun bundle produksi
npm run build
```

Untuk menjalankan Laravel Server:
```bash
# Opsi 1: Server standar Laravel
php artisan serve

# Opsi 2: High-Performance Engine (Laravel Octane + RoadRunner)
php artisan octane:start --watch
# atau
composer run octane

# Opsi 3: WebSocket Server (Laravel Reverb)
php artisan reverb:start --debug
# atau
composer run reverb
```

---

## 🛡️ Error Tracking & Monitoring (Sentry Full-Stack)

Aplikasi telah dilengkapi integrasi **Sentry Full-Stack** untuk memantau error, performa transaksi, dan rekaman sesi pengguna baik pada sisi Backend (Laravel 12) maupun Frontend (Vue 3 / Inertia / SPA).

### 1. Konfigurasi Environment (`.env`)
Tambahkan DSN proyek Sentry Anda pada file `.env`:
```env
# Backend Sentry (Laravel 12)
SENTRY_LARAVEL_DSN=https://xxxxxxxx@oXXXX.ingest.sentry.io/XXXX
SENTRY_TRACES_SAMPLE_RATE=1.0
SENTRY_PROFILES_SAMPLE_RATE=1.0
SENTRY_SEND_DEFAULT_PII=true

# Frontend Sentry (Vue 3 / Vite)
VITE_SENTRY_DSN_PUBLIC="${SENTRY_LARAVEL_DSN}"
VITE_SENTRY_TRACES_SAMPLE_RATE=1.0
```
> *Catatan: Jika DSN dibiarkan kosong, Sentry SDK akan berada dalam mode nonaktif (dormant) secara aman tanpa menyebabkan error pada aplikasi.*

### 2. Fitur & Integrasi yang Tersedia
- **Backend (Laravel 12)**:
  - Exception handler terintegrasi via `\Sentry\Laravel\Integration::handles($exceptions)` di `bootstrap/app.php`.
  - Log channel khusus `'sentry'` di `config/logging.php` (dapat ditambahkan ke `LOG_STACK=single,sentry`).
  - Tracing otomatis untuk HTTP Requests, database queries Eloquent, cache, dan queue jobs.
- **Frontend (Vue 3)**:
  - Menggunakan modul terpusat di `resources/js/sentry.js`.
  - Terhubung otomatis ke **Inertia.js** (`resources/js/inertia.js`) dan **Traditional SPA + Vue Router** (`resources/js/spa.js`).
  - **Browser Tracing** untuk navigasi halaman & latensi rendering.
  - **Session Replay** untuk merekonstruksi interaksi pengguna saat terjadi error.
  - Pelacakan komponen Vue (`trackComponents: true`).

### 3. Cara Pengujian (Testing)
- **Uji Backend**:
  ```bash
  php artisan sentry:test
  ```
- **Uji Unit & Fitur Integrasi**:
  ```bash
  php artisan test --filter=SentryIntegrationTest
  ```
- **Uji Frontend (Console Browser)**:
  ```javascript
  import('@/sentry').then(({ Sentry }) => Sentry.captureException(new Error("Test Frontend Error")));
  ```

---

## ⚡ Laravel Octane (Supercharged High-Performance Server)

Aplikasi telah dilengkapi **Laravel Octane** terintegrasi dengan engine **RoadRunner** (`rr.exe` binary untuk Windows) untuk melayani ribuan request per detik dengan menjaga framework tetap hangat di memori RAM (*in-memory state execution*).

### 1. Keunggulan & Dampak Arsitektur
* **Eliminasi Bootstrap Overhead**: Laravel hanya di-boot satu kali saat server dinyalakan. Setiap request baru yang masuk langsung diproses tanpa memuat ulang framework, service provider, dan router dari awal.
* **Latensi Sub-Millisecond**: Response time REST API (`/api/v1/*`) dan render Inertia.js (`/inertia/*`) berkurang drastis dibanding model request-response PHP-FPM konvensional.
* **RoadRunner Native Engine**: Menggunakan binary Go berkemampuan tinggi (`rr.exe`) yang kompatibel langsung dengan Windows.

### 2. Konfigurasi Environment (`.env`)
Pengaturan engine Octane pada `.env`:
```env
# Laravel Octane Server Engine
OCTANE_SERVER=roadrunner
OCTANE_HTTPS=false
```

File konfigurasi utama:
- `config/octane.php`: Pengaturan listener lifecycle, flush instances, garbage collection, dan interval worker.
- `.rr.yaml`: Pengaturan port RPC, worker pool, static files handling, dan memory limit.

### 3. Perintah Menjalankan Server
```bash
# 1. Menjalankan server Octane standar (default port 8000)
php artisan octane:start

# 2. Menjalankan via shortcut Composer script
composer run octane

# 3. Menjalankan dengan Watch Mode (auto-reload saat file PHP berubah)
php artisan octane:start --watch

# 4. Menjalankan di host, port, dan jumlah worker spesifik
php artisan octane:start --host=127.0.0.1 --port=8080 --workers=4 --max-requests=500
```

### 4. Perintah Manajemen & Pemeliharaan
| Perintah | Deskripsi |
|---|---|
| `php artisan octane:status` | Memeriksa apakah server Octane sedang berjalan dan menampilkan status worker |
| `php artisan octane:reload` | Mereload worker tanpa downtime (cocok saat update kode di production) |
| `php artisan octane:stop` | Menghentikan server Octane dan semua worker process |
| `php artisan test --filter=OctaneIntegrationTest` | Menjalankan suite pengujian integrasi Octane dan binary RoadRunner |

### 5. Praktik Terbaik State Management di Octane
Karena worker Octane berjalan persisten di memori:
- Hindari menyimpan data request di *static properties* atau service container *singleton* yang tidak di-reset per request.
- Gunakan event listener Octane (`FlushTemporaryContainerInstances`, `CollectGarbage`) yang telah otomatis dikonfigurasi di `config/octane.php`.
- Parameter `--max-requests=500` (default) memastikan worker di-recycle secara berkala untuk mencegah kebocoran memori (*memory leak*).

---

## 🔴 Redis (In-Memory Data Store, Fast Cache & Queues)

Aplikasi telah dilengkapi dukungan penuh **Redis In-Memory Data Store** menggunakan client **Predis (v3.7)** yang kompatibel murni dengan PHP di semua sistem operasi (termasuk Windows) tanpa mewajibkan ekstensi C PHP lokal:

### 1. Peran & Kemampuan Redis dalam Arsitektur
* **Blazing-Fast Cache**: Akses data ter-cache di RAM dengan latensi mikrodetik via `Cache::store('redis')`.
* **Distributed Session Store**: Sinkronisasi sesi login Sanctum antar instance server (`SESSION_DRIVER=redis`).
* **High-Throughput Queue Backend**: Pemrosesan background job dan notifikasi secara cepat (`QUEUE_CONNECTION=redis`).
* **Pub/Sub Real-Time Integration**: Mendukung transmisi pesan ke WebSocket broadcaster dan worker.

### 2. Konfigurasi Environment (`.env`)
```env
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 3. Penggunaan Langsung (Redis Facade)
```php
use Illuminate\Support\Facades\Redis;

// Simpan key dengan durasi kedaluwarsa 60 detik
Redis::setex('hybrid:active_users', 60, json_encode($users));

// Ambil data key
$activeUsers = json_decode(Redis::get('hybrid:active_users'), true);

// Increment counter real-time
Redis::incr('hybrid:page_hits');
```

### 4. Telemetri & Layanan Pemantauan
Status koneksi dan telemetri Redis dipantau secara otomatis melalui service `App\Services\RedisService` dan ditampilkan di:
* **Architecture Hub (`/`)**: Kartu telemetri Redis menampilkan driver client, status port, dan scope.
* **REST API Endpoint (`/api/v1/stats`)**: Data `infrastructure.redis` terhidrasi secara real-time.
* **Pengujian Otomatis**: Jalankan `php artisan test --filter=RedisIntegrationTest`.

---

## 📡 Real-Time WebSockets (Laravel Reverb & Echo)

Aplikasi telah dilengkapi **Laravel Reverb**, server WebSocket resmi first-party dari Laravel yang dirancang untuk kecepatan tinggi, konsumsi memori rendah, dan skalabilitas masif tanpa ketergantungan pada layanan pihak ketiga berbayar (seperti Pusher Cloud).

### 1. Konfigurasi Environment (`.env`)
```env
# Broadcaster Driver
BROADCAST_CONNECTION=reverb

# Reverb Server Credentials
REVERB_APP_ID=918184
REVERB_APP_KEY=csnsqubjynnlmr44mkkb
REVERB_APP_SECRET=3y5f2qab1t4qqflwnroy
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Vite Client Credentials (Frontend Echo)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 2. Menjalankan Reverb Server
```bash
# Menjalankan server WebSocket Reverb (default port 8080)
php artisan reverb:start

# Menjalankan dengan log debug pesan masuk/keluar
php artisan reverb:start --debug

# Atau via shortcut Composer:
composer run reverb
```

### 3. Integrasi Frontend (Laravel Echo & Pusher JS)
Klien WebSocket terpusat di `resources/js/echo.js` dan otomatis tersedia global di browser (`window.Echo`).

**Penggunaan pada Komponen Vue (Inertia maupun Traditional SPA):**
```javascript
import { echo } from '@/echo';

// Mendengarkan Public Channel
echo.channel('products')
    .listen('ProductCreated', (e) => {
        console.log('Produk baru ditambahkan real-time:', e.product);
    });

// Mendengarkan Private Channel terotentikasi
echo.private(`App.Models.User.${userId}`)
    .listen('OrderUpdated', (e) => {
        console.log('Update pesanan:', e.order);
    });
```

### 4. Pengujian Fitur WebSocket
Jalankan pengujian integrasi Reverb:
```bash
php artisan test --filter=ReverbIntegrationTest
```

---

## 💓 Laravel Pulse (Real-Time Performance & Health Monitoring)

Aplikasi telah dilengkapi **Laravel Pulse**, alat telemetri dan pemantauan performa aplikasi real-time bawaan Laravel untuk mengamati beban server, penggunaan antrean (queue), dan slow queries.

### 1. Mengakses Dashboard Pulse
Buka browser pada URL:
```text
http://localhost/hybridlaravel/public/pulse
```

### 2. Metrik yang Dipantau Secara Otomatis
- **Server Health**: Utilisasi CPU, penggunaan RAM, dan kapasitas penyimpanan disk.
- **Slow Requests**: Permintaan HTTP yang melampaui ambang batas waktu respons.
- **Slow Queries**: Kueri database MySQL yang membutuhkan waktu eksekusi tinggi.
- **Queue & Jobs**: Monitoring antrean job, waktu tunggu pemrosesan, dan job yang gagal.
- **User Concurrency**: Aktivitas pengguna yang sedang berinteraksi dengan aplikasi.
- **Cache Hits & Misses**: Rasio efektivitas caching database.

### 3. Perintah Background Recorder (Worker)
Untuk mencatat beban sistem secara kontinu saat menjalankan aplikasi di background:
```bash
# Menjalankan perekam metrik Pulse
php artisan pulse:check
# atau
php artisan pulse:work
```

---

## 🏢 Modul Aplikasi Enterprise (Production-Ready Suite)

Starter kit ini telah dilengkapi modul fungsional kelas bisnis lengkap untuk membangun sistem skala produksi:

### 1. Role & Permission Management (Spatie RBAC v6)
- **Roles Bawaan**: `Super Admin`, `Admin`, `Staff`.
- **Permissions Bawaan**: `view products`, `manage products`, `export data`, `manage users`, `manage roles`, `view audit logs`.
- **Otorisasi Terpusat**: Terintegrasi otomatis ke middleware, Blade directive `@can`, dan shared props Inertia/SPA (`$page.props.auth.user.roles` & `permissions`).
- **Akun Demo**:
  - `admin@hybrid.local` / `password` (Role: **Super Admin**)
  - `staff@hybrid.local` / `password` (Role: **Staff**)

### 2. Audit Trail & Activity Log (Spatie Activitylog v4)
- **Pelacakan Otomatis Model**: Setiap penambahan, pengubahan, dan penghapusan model (seperti `Product`) otomatis tersimpan ke tabel `activity_log`.
- **Inertia Viewer**: Kunjungi `/audit-logs` untuk melihat visualisasi tabel riwayat perubahan, pelaku perubahan, timestamp, dan diff JSON properties.
- **REST API Endpoint**: `/api/v1/audit-logs`.

### 3. Pengaturan Profil, Kata Sandi & Upload Avatar
- **Halaman Profil Inertia**: Kunjungi `/profile` (atau klik foto profil di navbar).
- **Upload Avatar**: Mendukung upload gambar (JPG/PNG/WebP maks 2MB) ke storage disk (`storage/app/public/avatars`) dengan fallback otomatis ke UI-Avatars.
- **Ganti Kata Sandi**: Dilengkapi validasi kata sandi saat ini (`current_password`) dan konfirmasi kata sandi baru.
- **Pemulihan Lupa Sandi**: Alur Forgot Password di `/forgot-password` dan Reset Password di `/reset-password/{token}`.

### 4. Ekspor & Laporan Data (Excel, CSV, PDF)
- **Spreadsheet Excel (.xlsx)**: `/exports/products/excel` (atau via tombol di katalog produk).
- **Raw CSV (.csv)**: `/exports/products/csv`.
- **Cetak Laporan PDF (.pdf)**: `/exports/products/pdf` (di-generate rapi dengan DomPDF).

### 5. Lonceng Notifikasi Real-Time (WebSockets Reverb)
- **Navbar Bell Icon**: Terpasang di [HybridNavbar.vue](resources/js/Components/HybridNavbar.vue) lengkap dengan unread badge counter.
- **Penyimpanan Ganda**: Notifikasi disimpan ke database MySQL sekaligus disiarkan instan ke private channel pengguna (`App.Models.User.{id}`) via Laravel Reverb.
- **Tombol Uji 1-Klik**: Di dropdown notifikasi, klik **"Kirim Notifikasi Demo (WebSocket)"** untuk langsung melihat animasi lonceng real-time.

### 6. Dokumentasi API Otomatis (Dedoc Scramble OpenAPI)
- **Dokumentasi Interaktif**: Buka browser pada `/docs/api` (atau klik link **API Docs ↗** di navbar).
- **Zero-Annotation**: Mengurai seluruh rute dan skema JSON controller `/api/v1/*` secara otomatis tanpa menulis tag komentar manual.

### 7. Collapsible Enterprise Sidebar Navigation ([AdminSidebar.vue](resources/js/Components/AdminSidebar.vue))
- **Akses & Pintasan**: Buka menu via tombol hamburger di navbar atau pintasan keyboard `Ctrl + B` (atau `Cmd + B`).
- **Mode Fleksibel**:
  - **Slide-Over Drawer**: Menyelip mulus dari sisi kiri dengan backdrop blur tanpa mengganggu tata letak konten halaman.
  - **Pinned Docked Mode**: Pengguna dapat mengklik ikon Pin untuk mengunci sidebar di sisi kiri secara permanen (tersimpan di `localStorage`).
  - **Collapsible Rail**: Dapat diciutkan menjadi icon-bar ringkas (`w-20`) lengkap dengan hover tooltip, atau diperluas (`w-72`).
- **Kategori Menu**: Pusat Kontrol Hub, Inertia SSR Dashboard & Produk, Vue 3 SPA Mode, Audit Trail Spatie, Profil Akun, Scramble OpenAPI Docs, serta Laravel Pulse.

### 8. Real-Time Floating Chat di Kanan Bawah ([FloatingChat.vue](resources/js/Components/FloatingChat.vue))
- **Posisi Khusus Kanan Bawah**: Melayang secara presisi di sudut kanan bawah (`fixed bottom-5 right-5 z-50`).
- **Ditenagai Laravel Reverb**: Berjalan real-time sub-10ms melalui public channel `chat-channel` dan event `ChatMessageSent` (`ShouldBroadcastNow`).
- **Penyimpanan Database MySQL**: Pesan tersimpan persisten di tabel `chat_messages` dengan riwayat otomatis dimuat saat dibuka.
- **Fitur Canggih & Siap Pakai**:
  - **Pulsating Status Beacon**: Indikator hijau untuk WebSocket Reverb aktif, dan fallback smart polling saat offline.
  - **Uji Pesan Otomatis (1-Klik)**: Tombol *"⚡ Tes Balas Otomatis"* untuk langsung memicu balasan simulasi bot real-time tanpa perlu membuka 2 akun terpisah.
  - **Nada Notifikasi Audio (Web Audio API)**: Suara chime sintetis lembut saat pesan baru tiba, dilengkapi tombol mute/unmute.
  - **Unread Counter Badge**: Animasi bounce jumlah pesan yang belum terbaca saat jendela tertutup.
### 9. 3-Way Theme Switcher: Dark, Light, & Mengikuti Tema Sistem ([ThemeSwitcher.vue](resources/js/Components/ThemeSwitcher.vue))
- **3 Pilihan Tema Fleksibel**:
  - ☀️ **Terang (Light Mode)**: Desain antarmuka cerah, bersih, dan kontras tajam.
  - 🌙 **Gelap (Dark Mode)**: Palet gelap modern berbasis Slate-950 dengan aksen warna premium.
  - 💻 **Sistem (System / Auto OS)**: Mendeteksi preferensi OS (Windows/macOS/Linux/Android/iOS) via event listener `window.matchMedia('(prefers-color-scheme: dark)')` secara real-time.
- **Bebas Kedip (Anti-FOUC)**: Dilengkapi script inisialisasi instan di `<head>` Blade (`app.blade.php` & `spa.blade.php`) sebelum DOM dirender, mencegah efek flicker putih/hitam saat halaman dimuat ulang.
- **Persistensi Lokal**: Pilihan tema tersimpan otomatis di browser menggunakan `localStorage.setItem('hybrid_theme', ...)`.
- **Penempatan Ergonomis**: Komponen terpasang di sudut kanan atas [HybridNavbar.vue](resources/js/Components/HybridNavbar.vue) berdampingan dengan lonceng notifikasi, aktif serentak di mode Inertia.js dan Traditional SPA.
