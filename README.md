# Panduan Menjalankan Aplikasi Wedding Organizer

## Persyaratan
1. **XAMPP** (atau WAMP/MAMP) - untuk web server dan MySQL
   - Download dari: https://www.apachefriends.org/
   - Atau gunakan PHP built-in server (jika sudah install PHP)

2. **Web Browser** (Chrome, Firefox, Edge, dll)

## Langkah-langkah Setup

### Metode 1: Menggunakan XAMPP (Disarankan)

#### 1. Install XAMPP
- Download dan install XAMPP dari https://www.apachefriends.org/
- Pastikan Apache dan MySQL terinstall

#### 2. Copy Folder Project
- Copy folder project ke dalam folder `htdocs` di XAMPP
  - Lokasi biasanya: `C:\xampp\htdocs\`
  - Jadi path lengkapnya: `C:\xampp\htdocs\WeddingOrganizer\`

#### 3. Setup Database
1. Buka XAMPP Control Panel
2. Start **Apache** dan **MySQL**
3. Buka browser, akses: `http://localhost/phpmyadmin`
4. Klik tab **Import**
5. Pilih file `Database Wedding Organizer.sql`
6. Klik **Go** untuk import database
7. Database `WeddingOrganizer` akan dibuat otomatis **dengan data admin default**

**Catatan:** Database sudah termasuk data admin default:
- **ID Admin:** `1`
- **Nama:** `Admin`
- **Username:** `admin`
- **Password:** `admin123` (tidak digunakan untuk login)

#### 4. Konfigurasi Database (Jika Perlu)
Edit file `login.php` jika kredensial database berbeda:
- Baris 6-9: Sesuaikan `$host`, `$user`, `$password`, `$dbname` jika perlu

#### 5. Menjalankan Aplikasi
1. Pastikan Apache dan MySQL sudah running di XAMPP
2. Buka browser
3. Akses: `http://localhost/WeddingOrganizer/index.php`

#### 6. Login ke Aplikasi
**Admin (Default):**
- **Username:** `admin`
- **Password:** `admin123`

**Customer:**
- Register lewat `register.php`, lalu login lewat `customer_login.php`.

Setelah login berhasil, Anda akan diarahkan ke dashboard masing-masing.

---

### Metode 2: Menggunakan PHP Built-in Server

Jika sudah install PHP secara terpisah (tanpa XAMPP):

#### 1. Setup Database MySQL
- Install MySQL secara terpisah atau gunakan XAMPP MySQL saja
- Import database seperti langkah 3 di Metode 1

#### 2. Menjalankan Server
1. Buka Command Prompt atau PowerShell
2. Masuk ke folder project:
   ```powershell
   cd "C:\xampp\htdocs\WeddingOrganizer"
   ```
3. Jalankan PHP server:
   ```powershell
   php -S localhost:8000
   ```

#### 3. Akses Aplikasi
- Buka browser: `http://localhost:8000/login.php`

---

## Struktur File

```
WeddingOrganizer/
├── index.php                      # Landing page
├── login.php                      # Login admin
├── dashboard.php                  # Dashboard admin
├── admin_pesanan.php              # Kelola pesanan (admin)
├── admin_pelanggan.php            # Kelola pelanggan (admin)
├── admin_profile.php              # Profil admin (ganti password)
├── customer_login.php             # Login customer
├── customer_dashboard.php         # Dashboard customer
├── customer_profile.php           # Profil customer (ganti password)
├── sewa_wedding.php               # Form sewa wedding (customer)
├── my_sewa.php                    # Riwayat sewa (customer)
├── logout.php                     # Logout admin (redirect ke index)
├── customer_logout.php            # Logout customer (redirect ke index)
├── assets/theme.css               # Tema global (wedding theme)
├── Database Wedding Organizer.sql # File database (sudah termasuk data admin)
└── README.md                      # File dokumentasi ini
```

---

## Kredensial Login

**Admin Default:**
- **Username:** `admin`
- **Password:** `admin123`

**Catatan:** 
- Login admin menggunakan `Username` + `Password`
- Login customer menggunakan `Email` + `Password`
- Jika password di database masih plain text (misal diedit manual), sistem akan otomatis meng-hash setelah login berhasil

---

## Fitur Aplikasi

1. **Login System**
   - Admin: login Username + Password
   - Customer: login Email + Password
   - Session management
   - Redirect otomatis ke dashboard masing-masing

2. **Dashboard**
   - Menampilkan statistik data
   - Insight admin: pesanan belum lunas, agenda 7 hari, agenda terdekat
   - Filter dan pencarian pesanan
   - Logout redirect ke `index.php`

3. **Database**
   - Tabel Admin (untuk login)
   - Tabel Pelanggan
   - Tabel Sewa_Wedding
   - Tabel Registrasi

4. **Kelola Pesanan (Admin)**
   - Filter status pembayaran dan pencarian
   - Update pembayaran, tanggal acara, keterangan
   - Hapus pesanan

5. **Kelola Pelanggan (Admin)**
   - Cari dan edit data pelanggan
   - Reset password pelanggan
   - Hapus pelanggan (pesanan ikut terhapus karena relasi)

6. **Profil**
   - Admin: ganti password di `admin_profile.php`
   - Customer: ganti password di `customer_profile.php`

7. **Tema Wedding**
   - Tema global dipusatkan di `assets/theme.css` untuk konsistensi tampilan

---

## Troubleshooting

### Error: "Koneksi gagal"
- Pastikan MySQL sudah running di XAMPP Control Panel
- Cek kredensial database di `config.php`
- Pastikan database `WeddingOrganizer` sudah diimport
- Default kredensial: host=`localhost`, user=`root`, password=` ` (kosong), dbname=`WeddingOrganizer`

### Error: "Username atau password salah"
- Pastikan database sudah diimport dengan benar
- Pastikan data admin sudah ada di tabel `Admin`
- Login admin menggunakan: Username = `admin`, Password = `admin123`
- Pastikan tidak ada spasi extra saat mengetik

### Error: "Bootstrap CSS/JS tidak ditemukan"
- Aplikasi menggunakan CDN Bootstrap, jadi tidak perlu setup folder assets
- Pastikan koneksi internet aktif untuk load CDN
- Jika tidak ada internet, download Bootstrap dan buat folder `assets/bootstrap/`

### Error 404: "File dashboard.php tidak ditemukan"
- Pastikan file `dashboard.php` sudah ada di folder project
- Jika belum ada, file sudah tersedia di repository

### Halaman Blank/Kosong
- Cek error di XAMPP logs: `C:\xampp\apache\logs\error.log`
- Aktifkan error reporting di PHP untuk debugging
- Pastikan semua file PHP tidak corrupt

### Error setelah login (redirect ke dashboard)
- Pastikan file `dashboard.php` ada
- Pastikan session sudah dimulai dengan benar
- Cek koneksi database di dashboard.php

---

## Catatan Penting

1. **Keamanan Database:**
   - Untuk production, **jangan gunakan password kosong** untuk database!
   - Ganti kredensial default dengan yang lebih aman
   - Gunakan password yang kuat untuk user database

2. **Port:**
   - Pastikan port 80 (Apache) dan 3306 (MySQL) tidak digunakan aplikasi lain
   - Jika port 80 terpakai, ubah port Apache di XAMPP atau gunakan PHP built-in server dengan port lain

3. **Bootstrap:**
   - Aplikasi menggunakan Bootstrap 5.3.0 via CDN
   - Tidak perlu download atau setup folder assets
   - Memerlukan koneksi internet untuk load CDN

4. **Data Admin:**
   - Data admin default sudah tersedia di file SQL
   - Tidak perlu menambahkan data admin manual jika mengimport file SQL yang sudah diupdate
   - Untuk menambah admin baru, gunakan phpMyAdmin atau buat form tambah admin

---

## Update Terbaru

- ✅ Bootstrap sudah menggunakan CDN (tidak perlu setup folder assets)
- ✅ Database sudah termasuk data admin default
- ✅ Sudah ada halaman dashboard.php
- ✅ Logout admin/customer redirect ke `index.php`
- ✅ Tema wedding global (`assets/theme.css`) dipakai di halaman utama
- ✅ Admin: insight pesanan dan agenda + filter pesanan di dashboard
- ✅ Admin: halaman kelola pesanan & pelanggan
- ✅ Profil: ganti password untuk admin dan customer

---

## Kontak & Support

Jika mengalami masalah yang tidak teratasi:
1. Cek file error log di `C:\xampp\apache\logs\error.log`
2. Pastikan semua file sudah terinstall dengan benar
3. Pastikan versi PHP dan MySQL kompatibel (PHP 7.4+ dan MySQL 5.7+)
