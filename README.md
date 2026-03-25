# Panduan Instalasi Lokal (XAMPP)

Ikuti langkah-langkah berikut untuk menjalankan proyek Laravel ini di lingkungan lokal menggunakan XAMPP.

---

## Prasyarat

Pastikan tools berikut sudah terinstall:

- XAMPP (PHP 8.x atau versi terbaru)
- Composer (Dependency Manager untuk PHP)
- Node.js & NPM (Frontend asset management)
- Git

---

## Langkah-Langkah Instalasi

### 1. Clone Repository

```bash
git clone https://github.com/haikaltaufiq/inventory.git
cd nama-repo
```

---

### 2. Install Dependency PHP

```bash
composer install
```

---

### 3. Install Dependency Frontend

```bash
npm install
```

---

### 4. Konfigurasi Environment

Copy file `.env.example` menjadi `.env`:

```bash
cp .env.example .env
```

Edit file `.env` dan sesuaikan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_anda
DB_USERNAME=root
DB_PASSWORD=
```

---

### 5. Generate Application Key

```bash
php artisan key:generate
```

---

### 6. Setup Database

1. Jalankan **Apache** dan **MySQL** di XAMPP
2. Buka: http://localhost/phpmyadmin
3. Buat database baru sesuai dengan `DB_DATABASE` di file `.env`

Jalankan migration dan seeder:

```bash
php artisan migrate --seed
```

---

### 7. Storage Link

Agar file upload bisa diakses publik:

```bash
php artisan storage:link
```

---

### 8. Menjalankan Aplikasi

#### 🔹 Opsi A: Artisan Serve (Direkomendasikan)

```bash
php artisan serve
```

Akses di browser:

```
http://127.0.0.1:8000
```

---

#### 🔹 Opsi B: XAMPP (htdocs)

1. Pindahkan project ke:

```
C:\xampp\htdocs\nama-proyek
```

2. Akses melalui browser:

```
http://localhost/nama-proyek/public
```

---

## Menjalankan Frontend (Vite)

jalankan:

```bash
npm run dev
```

> Jalankan di terminal terpisah agar CSS/JS ter-load dengan benar.

---

## Selesai

Aplikasi siap digunakan di lokal.
