# 🔧 SpareStock — Sistem Manajemen Inventaris Sparepart

**SpareStock** adalah aplikasi berbasis web untuk membantu pengelolaan inventaris sparepart, mulai dari pencatatan barang, pemantauan jumlah stok, hingga informasi harga beli dan harga jual. Aplikasi ini dikembangkan menggunakan PHP dan MySQL serta dapat dijalankan secara lokal menggunakan XAMPP.

---

## 📌 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Fitur Utama](#-fitur-utama)
- [Teknologi yang Digunakan](#-teknologi-yang-digunakan)
- [Struktur Proyek](#-struktur-proyek)
- [Persyaratan Sistem](#-persyaratan-sistem)
- [Instalasi](#-instalasi)
- [Konfigurasi Database](#-konfigurasi-database)
- [Cara Menggunakan](#-cara-menggunakan)
- [Hak Akses Pengguna](#-hak-akses-pengguna)
- [Keamanan](#-keamanan)
- [Pengembangan Selanjutnya](#-pengembangan-selanjutnya)
- [Kontribusi](#-kontribusi)
- [Lisensi](#-lisensi)

---

## 📖 Tentang Proyek

SpareStock dibuat untuk mempermudah pengelolaan data sparepart dalam satu aplikasi yang terpusat. Dengan aplikasi ini, pengguna dapat melihat daftar sparepart, memeriksa kondisi stok, dan menemukan barang berdasarkan kode, nama, atau kategori.

Aplikasi ini dirancang agar pengelolaan inventaris menjadi lebih terstruktur dan memudahkan pengguna mengetahui barang yang perlu segera ditambah stoknya.

### 🎯 Tujuan

- Mempermudah pencatatan dan pengelolaan data sparepart.
- Membantu memantau jumlah stok barang.
- Memberikan peringatan ketika stok mencapai atau berada di bawah batas minimum.
- Mempermudah pencarian barang dalam daftar inventaris.
- Menyediakan informasi harga beli dan harga jual dalam satu halaman.

---

## ✨ Fitur Utama

### 📦 1. Manajemen Data Sparepart

- Menampilkan daftar inventaris sparepart.
- Menampilkan kode dan nama barang.
- Menampilkan kategori sparepart.
- Menampilkan jumlah stok.
- Menampilkan harga beli dan harga jual.
- Menyediakan fitur tambah, edit, dan hapus barang untuk pengguna dengan hak akses admin.

### 🔎 2. Pencarian Sparepart

Pencarian membantu pengguna menemukan barang tanpa harus memeriksa seluruh tabel secara manual.

Pencarian dapat dilakukan berdasarkan:
- Kode sparepart.
- Nama sparepart.
- Kategori sparepart.

Hasil pencarian ditampilkan langsung pada tabel inventaris.

### ⚠️ 3. Pemantauan Stok Menipis

SpareStock memeriksa stok barang berdasarkan batas stok minimum yang tersimpan pada data sparepart.

- Barang dengan stok kurang dari atau sama dengan stok minimum ditandai sebagai **Stok Menipis**.
- Barang dengan stok di atas batas minimum ditandai sebagai **Aman**.
- Daftar barang dengan stok menipis ditampilkan pada bagian peringatan.
- Barang dengan stok menipis diprioritaskan pada urutan tabel.

### 📊 4. Ringkasan Inventaris

Halaman Data Sparepart menampilkan:
- Total jenis sparepart.
- Jumlah sparepart dengan stok menipis.
- Informasi kondisi stok inventaris.

### 👤 5. Hak Akses Pengguna

Aplikasi membedakan hak akses admin dan pengguna biasa berdasarkan peran yang tersimpan dalam sesi pengguna.

- **Admin:** dapat mengakses tombol tambah, edit, dan hapus pada halaman inventaris.
- **Pengguna biasa:** dapat melihat data sparepart tanpa tombol pengelolaan barang.

> Hak akses yang sebenarnya tetap bergantung pada pemeriksaan otorisasi di setiap halaman dan proses backend.

---

## 🛠️ Teknologi yang Digunakan

| Teknologi | Kegunaan |
|---|---|
| PHP | Logika backend aplikasi |
| MySQL | Penyimpanan data inventaris |
| PDO | Koneksi dan akses database dari PHP |
| HTML5 | Struktur halaman web |
| CSS3 | Tampilan dan tata letak aplikasi |
| XAMPP | Lingkungan pengembangan lokal |
| Git | Pengelolaan versi kode |
| GitHub | Penyimpanan repository proyek |

---

## 📁 Struktur Proyek

Struktur berikut merupakan gambaran direktori utama aplikasi. Nama dan jumlah file dapat berbeda sesuai versi proyek yang digunakan.

```text
sparestock/
├── assets/
│   └── css/
│       └── style.css
├── config/
│   └── database.php
├── includes/
│   ├── auth.php
│   └── sidebar.php
├── login.php
├── products.php
├── product_add.php
├── product_edit.php
├── product_delete.php
├── .gitignore
└── README.md
```

Keterangan:

- `assets/css/` — file stylesheet untuk tampilan aplikasi.
- `config/database.php` — konfigurasi koneksi database.
- `includes/auth.php` — kode pendukung autentikasi dan fungsi terkait.
- `includes/sidebar.php` — navigasi sidebar aplikasi.
- `login.php` — halaman login.
- `products.php` — halaman daftar, pencarian, dan pemantauan stok sparepart.
- `product_add.php` — halaman penambahan sparepart.
- `product_edit.php` — halaman pengubahan data sparepart.
- `product_delete.php` — proses penghapusan sparepart.
- `.gitignore` — aturan file yang tidak perlu dimasukkan ke Git.
- `README.md` — dokumentasi proyek.

---

## 💻 Persyaratan Sistem

Sebelum menjalankan SpareStock, siapkan:

- Windows, Linux, atau sistem operasi yang mendukung lingkungan pengembangan PHP.
- XAMPP atau lingkungan server lokal sejenis.
- PHP yang kompatibel dengan kode aplikasi.
- MySQL atau MariaDB.
- Web browser modern.
- Git, jika ingin mengelola repository menggunakan Git.

---

## 🚀 Instalasi

### 1. Clone repository

Buka terminal dan jalankan:

```bash
git clone https://github.com/USERNAME/sparestock.git
```

Ganti `USERNAME` dengan username GitHub pemilik repository.

Masuk ke direktori proyek:

```bash
cd sparestock
```

### 2. Pindahkan proyek ke direktori server lokal

Untuk pengguna XAMPP di Windows, letakkan folder proyek pada:

```text
C:\xampp\htdocs\sparestock
```

### 3. Jalankan XAMPP

Buka XAMPP Control Panel, lalu jalankan:

- **Apache**
- **MySQL**

Pastikan kedua layanan berjalan dengan baik.

### 4. Siapkan database

Buka phpMyAdmin melalui:

```text
http://localhost/phpmyadmin
```

Buat database baru bernama:

```text
sparestock
```

Impor struktur database yang sesuai dengan versi proyek jika file SQL tersedia.

Jika belum memiliki file SQL, struktur tabel dan data awal perlu disiapkan sesuai kebutuhan aplikasi sebelum dijalankan.

### 5. Konfigurasikan koneksi database

Buat atau sesuaikan file `config/database.php` berdasarkan lingkungan lokal. Sesuaikan host, nama database, username, password, dan pengaturan koneksi PDO dengan konfigurasi MySQL milikmu.

**Jangan menggunakan kredensial database milik orang lain atau mengunggah password ke repository publik.**

### 6. Jalankan aplikasi

Buka browser dan kunjungi:

```text
http://localhost/sparestock/
```

Jika aplikasi belum memiliki halaman utama, akses halaman login secara langsung:

```text
http://localhost/sparestock/login.php
```

Gunakan akun yang sudah terdaftar pada database.

---

## 🧭 Cara Menggunakan

### Login

Masuk menggunakan akun yang sudah terdaftar. Menu dan tindakan yang tersedia bergantung pada peran pengguna.

### Melihat inventaris

Buka halaman **Data Sparepart** untuk melihat daftar barang beserta kategori, jumlah stok, harga beli, dan harga jual.

### Mencari sparepart

Masukkan kode, nama, atau kategori pada kolom pencarian, kemudian klik **Cari**. Klik **Reset** untuk menampilkan seluruh daftar barang kembali.

### Menambahkan sparepart

Pengguna admin dapat memilih tombol **Tambah Sparepart** untuk membuka formulir penambahan barang.

### Mengedit atau menghapus sparepart

Pada kolom Aksi, admin dapat memilih **Edit** untuk mengubah data atau **Hapus** untuk menghapus barang setelah mengonfirmasi tindakan tersebut.

### Memantau stok

Periksa bagian peringatan stok untuk mengetahui sparepart yang telah mencapai atau berada di bawah batas stok minimum.

---

## 🔐 Keamanan

Beberapa hal yang perlu diperhatikan saat mengembangkan dan menjalankan SpareStock:

- Jangan mengunggah password database, token, atau kredensial rahasia ke GitHub.
- Simpan konfigurasi lokal yang sensitif di file yang dikecualikan melalui `.gitignore`.
- Gunakan prepared statements untuk query database yang menerima input pengguna.
- Lakukan validasi input dan escaping output untuk membantu mencegah serangan injeksi dan XSS.
- Pastikan setiap proses tambah, edit, dan hapus melakukan pemeriksaan otorisasi di sisi server.
- Pertimbangkan perlindungan CSRF untuk formulir yang mengubah data.
- Gunakan HTTPS dan konfigurasi server yang sesuai saat aplikasi dipasang di lingkungan produksi.
- Jangan menggunakan akun demo atau password bawaan yang mudah ditebak pada server publik.

---

## 🗺️ Pengembangan Selanjutnya

Beberapa ide fitur yang dapat dikembangkan pada versi berikutnya:

- [ ] Pagination untuk daftar sparepart dalam jumlah besar.
- [ ] Filter berdasarkan kategori.
- [ ] Pengelolaan stok masuk dan stok keluar.
- [ ] Riwayat perubahan stok.
- [ ] Laporan inventaris.
- [ ] Ekspor data ke CSV atau Excel.
- [ ] Dashboard dengan visualisasi data inventaris.
- [ ] Pengelolaan profil dan akun pengguna.
- [ ] Peningkatan validasi dan keamanan aplikasi.

---

## 🤝 Kontribusi

Kontribusi dan saran pengembangan sangat dihargai.

1. Fork repository ini.
2. Buat branch baru untuk perubahan yang ingin dilakukan.
3. Lakukan perubahan dan uji fitur terkait.
4. Commit perubahan dengan pesan yang jelas.
5. Ajukan Pull Request untuk ditinjau.

Untuk melaporkan bug atau menyampaikan ide fitur, silakan gunakan bagian Issues pada repository GitHub jika fitur tersebut diaktifkan.

---

## 📄 Lisensi

Lisensi proyek belum ditentukan.

Jika ingin mengizinkan orang lain menggunakan, memodifikasi, dan mendistribusikan kode secara jelas, tentukan lisensi yang sesuai dan tambahkan file `LICENSE` ke repository.

---

<p align="center">
  Dibuat dengan ❤️ untuk membantu pengelolaan inventaris sparepart.
</p>
