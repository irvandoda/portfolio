# Informasi Aktivasi Plugin

## Yang Akan Terjadi Saat Plugin Diaktifkan

### 1. User Superadmin Otomatis Dibuat
- **Username**: `superadmin`
- **Password**: `superadmin123`
- **Role**: Administrator
- **Email**: `superadmin@[domain-anda]`

### 2. URL Login Darurat
- **URL**: `https://[domain-anda]/pintubelakang`
- **Password Emergency**: `superadmin123` (sama dengan password user)

### 3. Secret Key
- **Secret Key**: `rahasia`
- Akan otomatis ditambahkan ke `wp-config.php`

### 4. Build Admin UI
- Plugin akan otomatis:
  1. Mengecek apakah npm sudah terinstall
  2. Jika belum, akan mencoba install npm secara otomatis
  3. Menjalankan `npm install`
  4. Menjalankan `npm run build`

## Cara Menggunakan Emergency Login

1. Buka URL: `https://[domain-anda]/pintubelakang`
2. Masukkan password: `superadmin123`
3. Anda akan otomatis login sebagai superadmin

## Catatan Keamanan

⚠️ **PENTING**: 
- Ganti password default setelah aktivasi pertama
- Ganti secret key `rahasia` dengan key yang lebih aman
- Simpan informasi login ini di tempat yang aman

## Troubleshooting

### NPM tidak terinstall otomatis
Jika npm tidak bisa diinstall otomatis, install manual:
```bash
# Ubuntu/Debian
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# CentOS/RHEL
curl -fsSL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo yum install -y nodejs
```

Kemudian build manual:
```bash
cd /path/to/plugin/admin
npm install
npm run build
```

### User superadmin sudah ada
Jika user `superadmin` sudah ada, password akan diupdate ke `superadmin123`.

### Secret key tidak terdeteksi
Jika secret key tidak otomatis ditambahkan ke wp-config.php, tambahkan manual:
```php
define('SASEC_SECRET_KEY', 'rahasia');
```

