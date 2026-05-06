# Gantian

**Gantian** adalah aplikasi capstone untuk **Sistem Peminjaman/Sewa Barang**. Aplikasi ini membantu customer melihat katalog dan mengajukan booking, staff memproses operasional rental, dan admin/owner mengelola katalog serta melihat ringkasan laporan.

## Ringkasan

Gantian dibangun dengan pendekatan **Role-Based Access Control (RBAC)** dan actor generalization. Role `admin` mewarisi kemampuan operasional `staff`, sementara `customer` hanya dapat mengakses workflow peminjaman miliknya sendiri.

Role utama:

- `Customer`: registrasi, login, melihat katalog, mengecek availability, dan submit booking request.
- `Staff`: validasi booking, approve/cancel, check-out, check-in, verifikasi kondisi barang, dan input denda.
- `Admin/Owner`: semua kemampuan Staff, ditambah manajemen item catalog, pricing, stock, status barang, dan laporan revenue.

## Fitur Utama

- Authentication berbasis Laravel Breeze, Livewire, dan Volt.
- Catalog browsing untuk customer dengan status barang, harga, stock, dan validasi tanggal.
- Booking request dengan availability hardening:
  - item `unavailable`, `maintenance`, atau stock `0` ditolak;
  - booking `approved` dan `active` mengonsumsi stock;
  - booking `pending`, `completed`, dan `cancelled` tidak mengonsumsi stock;
  - duplicate request untuk user, item, dan rentang tanggal yang sama diblokir.
- Staff operations untuk approve booking, check-out, check-in, cancel, dan issue denda.
- Atomic booking lifecycle melalui transaction dan conditional status update.
- Admin catalog management untuk create, edit, delete item, pricing, stock, dan status.
- Admin reports untuk ringkasan booking, revenue, dan denda.
- Responsive Blade UI dengan Tailwind CSS, Alpine.js, Livewire, dan aksesibilitas form dasar.

## Tech Stack

- Backend: Laravel 13, PHP 8.3+
- Frontend: Blade, Tailwind CSS, Alpine.js, Livewire 3, Volt
- Auth scaffolding: Laravel Breeze
- Database: SQLite untuk local default, kompatibel dengan MySQL/PostgreSQL melalui konfigurasi `.env`
- ORM: Eloquent Models, Factories, Migrations, Seeders
- Business layer: service classes untuk pricing, availability, booking lifecycle, dan fine assessment
- Testing: PHPUnit, Laravel Feature Tests, Laravel Pint, Vite build

## Setup Lokal

Clone repository, lalu jalankan perintah berikut dari root project.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Default `.env.example` menggunakan SQLite. Untuk setup SQLite lokal:

```bash
touch database/database.sqlite
php artisan migrate:fresh --seed
```

Jika memakai MySQL atau PostgreSQL, ubah konfigurasi database di `.env` sebelum migration:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gantian
DB_USERNAME=root
DB_PASSWORD=
```

Build asset dan jalankan server:

```bash
npm run build
php artisan serve --host=127.0.0.1 --port=8001
```

Untuk development mode dengan Vite:

```bash
npm run dev
```

Alternatif full development runner dari Composer:

```bash
composer run dev
```

## Demo Account

Seeder menyediakan akun reviewer berikut. Semua akun memakai password:

```text
password
```

| Role | Email | Akses utama |
| --- | --- | --- |
| Admin/Owner | `admin@gantian.test` | Admin items, admin reports, dan staff bookings |
| Staff | `staff@gantian.test` | Staff bookings dan operasional rental |
| Customer | `customer@gantian.test` | Catalog, My Bookings, booking request, dan review status booking |
| Customer | `customer2@gantian.test` | Catalog, My Bookings, booking request, dan review status booking |

## Reviewer Workflow

Setelah login, gunakan route berikut untuk mengecek fitur utama:

| Area | Route | Role |
| --- | --- | --- |
| Dashboard | `/dashboard` | Authenticated user |
| Customer Catalog | `/catalog` | Customer |
| Customer Bookings | `/bookings` | Customer |
| Staff Bookings | `/staff/bookings` | Staff, Admin |
| Admin Items | `/admin/items` | Admin |
| Admin Reports | `/admin/reports` | Admin |

Skenario uji manual yang disarankan:

1. Login sebagai `customer@gantian.test`, buka `/catalog`, pilih item available, lalu submit booking request.
2. Buka `/bookings` atau menu My Bookings, lalu verifikasi status dan riwayat booking dari seed data.
3. Coba submit booking duplicate dengan item dan tanggal yang sama, lalu pastikan request ditolak.
4. Login sebagai `staff@gantian.test`, buka `/staff/bookings`, approve pending booking, lanjutkan ke check-out dan check-in.
5. Issue denda pada booking active atau completed.
6. Login sebagai `admin@gantian.test`, kelola item di `/admin/items` dan cek laporan di `/admin/reports`.
7. Pastikan customer tidak bisa membuka route staff/admin.

## Proposal Alignment

Baseline proposal yang dipakai untuk review berjudul **Sistem Manajemen Penyewaan Alat Terjadwal (Gantian)**.

Pemetaan actor proposal ke role aplikasi:

| Proposal | Role aplikasi |
| --- | --- |
| Customer | `customer` |
| Staff/Frontdesk | `staff` |
| Owner/Admin | `admin` |

Scope yang sudah tercakup dalam codebase saat ini meliputi auth/RBAC, catalog, booking request, Customer Bookings, availability validation, staff lifecycle, inspections, fines, admin catalog, admin reports, dan tests. Catatan safety availability tetap mengikuti aturan domain: hanya booking `approved` dan `active` yang reserve stock, sedangkan `pending`, `completed`, dan `cancelled` tidak reserve stock.

Scope berikut masih future scope dan tidak diklaim sebagai fitur final: payment gateway, payment settlement, dan report export.

## Quality Checks

Jalankan checks berikut sebelum push atau demo:

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

Untuk reset database demo:

```bash
php artisan migrate:fresh --seed
```

## Struktur Domain

Entitas utama:

- `users`: menyimpan akun dan role `admin`, `staff`, atau `customer`.
- `items`: katalog barang, harga per hari, stock, dan status availability.
- `bookings`: transaksi rental dengan tanggal mulai, tanggal selesai, total price, dan status lifecycle.
- `fines`: denda untuk booking yang terlambat, rusak, atau punya masalah operasional.

Status booking:

- `pending`: request baru dari customer, belum mengonsumsi stock.
- `approved`: sudah disetujui staff, mengonsumsi stock.
- `active`: barang sedang dipinjam, mengonsumsi stock.
- `completed`: booking selesai, tidak lagi mengonsumsi stock.
- `cancelled`: booking dibatalkan, tidak mengonsumsi stock.

## Catatan Batasan

Payment settlement dan report export belum menjadi fitur final. Saat ini sistem sudah mendukung booking, lifecycle rental, fine assessment, dan reporting dasar. Phase berikutnya yang disarankan adalah demo/reviewer hardening, agar alur demo, data seed, dan dokumentasi reviewer tetap konsisten dengan scope yang sudah ada.
