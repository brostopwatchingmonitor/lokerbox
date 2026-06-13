# 🏧 SewaLokerBox - Smart Locker Management & IoT Payment System

[![Laravel Version](https://img.shields.io/badge/Laravel-v13.7-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![React Version](https://img.shields.io/badge/React-v19.0-61DAFB?style=for-the-badge&logo=react&logoColor=black)](https://react.dev)
[![Inertia.js](https://img.shields.io/badge/Inertia.js-v3.0-9B2C2C?style=for-the-badge&logo=laravel&logoColor=white)](https://inertiajs.com)
[![MongoDB](https://img.shields.io/badge/MongoDB-v8.0-47A248?style=for-the-badge&logo=mongodb&logoColor=white)](https://www.mongodb.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4.0-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)

SewaLokerBox adalah sistem otomasi loker pintar berbasis **IoT (Internet of Things)** dan pembayaran digital terintegrasi. Aplikasi ini dibangun menggunakan arsitektur modern hibrida **Laravel (Inertia.js + React)** dengan basis data dokumenter **MongoDB NoSQL** untuk performa operasional yang tinggi dan kueri real-time yang cepat.

---

## 🚀 Fitur Utama (Features)

*   **📱 Multi-Step React Booking UI**: Antarmuka pemesanan loker yang interaktif dan dinamis, membimbing pengguna mulai dari memilih ukuran loker, menentukan durasi sewa, hingga ringkasan pesanan.
*   **💳 Integrasi Payment Gateway (Midtrans)**: Mendukung pembayaran digital instan (QRIS, E-Wallet, CC) secara aman menggunakan Midtrans Snap SDK pop-up.
*   **📡 Sinkronisasi Webhook Asinkron**: Dilengkapi pengolahan webhook (`/api/webhook`) aman dengan verifikasi signature key untuk otomatis mengaktifkan status loker setelah pengguna membayar.
*   **⚡ Arsitektur MongoDB NoSQL**: Menggunakan relasi dokumen bersarang (*embedded documents*) untuk menjaga performa kueri transaksi dan status loker tanpa operasi JOIN SQL yang lambat.
*   **📍 Indeks Geospatial 2dsphere**: Mendukung pencarian lokasi stasiun loker terdekat secara instan menggunakan kueri radius jarak GPS.
*   **🔑 Secure Auth & Passkey**: Otentikasi bawaan menggunakan Laravel Fortify, mendukung otentikasi biometrik modern (Passkeys/WebAuthn) dan Two-Factor Authentication (2FA).
*   **📲 NFC Card Registration Simulation**: Antarmuka interaktif untuk mendaftarkan kartu RFID/NFC fisik sebagai kunci akses digital pintu loker.

---

## 🛠️ Arsitektur Data NoSQL (MongoDB Schema)

Aplikasi ini mengkonsolidasikan data ke dalam **3 Koleksi Utama** di MongoDB untuk menjamin efisiensi tinggi:

```
  [ Koleksi: users ]
         │
         │ (Referencing via _id)
         ▼
  [ Koleksi: transactions ] ◄─── (Referencing via box_reference.box_id) ───┐
         │                                                                  │
         ├──► (Embedded Array) ──► [ payments ]                             │
         │                                                                  │
         ├──► (Embedded Array) ──► [ authorized_users ]                    │
         │                                                                  │
         └──► (Embedded Array) ──► [ activity_logs ]                        │
                                                                             │
                                                                 [ Koleksi: locker_stations ]
                                                                             │
                                                                     (Embedded Array)
                                                                             │
                                                                             ▼
                                                                      [ locker_boxes ]
```

*   **`users`**: Menyimpan profil akun, saldo dompet, dan token push notification.
*   **`locker_stations`**: Menyimpan lokasi stasiun loker (GeoJSON) dan data box di dalamnya (`boxes` disematkan secara *embedded*).
*   **`transactions`**: Inti operasional. Menyematkan data pembayaran (`payments`), pendelegasian akses (`authorized_users`), dan log sensor hardware (`activity_logs`) dalam satu dokumen.

---

## 💻 Tech Stack & Kebutuhan Sistem

*   **Backend**: Laravel 13.x (PHP 8.3+)
*   **Frontend**: React 19.x & TypeScript (via Inertia.js)
*   **Styling**: Tailwind CSS v4.0 & Shadcn UI
*   **Database**: MongoDB v6.0+ / MongoDB Atlas
*   **Payment**: Midtrans Sandbox SDK

---

## ⚙️ Panduan Instalasi Lokal (Getting Started)

Ikuti langkah berikut untuk menjalankan proyek di komputer lokal Anda:

### 1. Clone Repositori & Masuk ke Direktori
```bash
git clone https://github.com/username/lokerbox.git
cd lokerbox
```

### 2. Instal Dependensi PHP & Javascript
```bash
composer install
npm install
```

### 3. Konfigurasi Variabel Lingkungan (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
cp .env.example .env
```
Buka file `.env` dan sesuaikan pengaturan berikut:
```env
DB_CONNECTION=sqlite # Tetap sqlite untuk session/cache default jika diinginkan

# Koneksi MongoDB Lokal Anda
MONGODB_URI=mongodb://localhost:27017/sewalokerbox
MONGODB_DATABASE=sewalokerbox

# Kunci Akses Midtrans Sandbox Anda (Ambil dari Dashboard Midtrans)
MIDTRANS_SERVER_KEY=SB-Mid-server-YOUR_SECRET_KEY
MIDTRANS_CLIENT_KEY=SB-Mid-client-YOUR_CLIENT_KEY
MIDTRANS_IS_PRODUCTION=false
```

### 4. Generate Application Key & Jalankan Migrasi
```bash
php artisan key:generate
php artisan migrate --database=mongodb
```
*Perintah di atas akan secara otomatis membuat indeks geospatial `2dsphere` dan indeks komposit pencarian di MongoDB lokal Anda.*

### 5. Jalankan Server Pengembangan
Jalankan Laravel backend server:
```bash
php artisan serve
```
Dan jalankan dev compiler Vite (React):
```bash
npm run dev
```
Buka **`http://127.0.0.1:8000`** di browser Anda, masuk ke akun Anda, dan nikmati UI Sewa Loker Pintar!

---

## 📡 Pengujian Webhook Midtrans di Localhost (ngrok)

Untuk menguji fitur otomatisasi status loker ketika pembayaran sukses di localhost:

1.  Jalankan tunnel publik menggunakan `ngrok` (port 8000):
    ```bash
    ngrok http 8000
    ```
2.  Salin URL https publik yang diberikan oleh ngrok (misal: `https://abcd-123.ngrok-free.app`).
3.  Buka dashboard **Midtrans Sandbox > Settings > Configuration** dan ubah **Notification URL** ke:
    ```
    https://abcd-123.ngrok-free.app/api/webhook
    ```
4.  Lakukan transaksi. Setelah Anda membayar di simulator Midtrans, status transaksi di database MongoDB lokal Anda akan otomatis berubah menjadi `ACTIVE` dan kode pengambilan NFC akan muncul di layar dashboard!

---

## 🧪 Menjalankan Pengujian Unit (Testing)
Verifikasi keandalan integrasi model MongoDB menggunakan Pest/PHPUnit:
```bash
./vendor/bin/pest tests/Unit/MongoDBConnectionTest.php
```

---

## 📄 Lisensi (License)
Proyek ini dilisensikan di bawah lisensi MIT - Lihat file [LICENSE](LICENSE) untuk detail lebih lanjut.
