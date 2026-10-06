# Panduan Deployment

## Impor database

1. Buat database MySQL bernama `db_magangpln` dan user database di cPanel atau VPS. Berikan user tersebut hak akses ke database. Di cPanel, nama database dan user biasanya memiliki awalan akun hosting.
2. Impor dump SQL melalui **phpMyAdmin** dengan memilih database lalu membuka tab **Import**. Di VPS, database harus sudah dibuat terlebih dahulu, lalu jalankan:

   ```bash
   mysql -u USER_DATABASE -p db_magangpln < db_magangpln.sql
   ```

3. Salin `.env.example` menjadi `.env`, lalu isi `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD` sesuai detail dari hosting. Pastikan `APP_TIMEZONE=Asia/Jakarta`; untuk server live, gunakan `APP_ENV=production`, `APP_DEBUG=false`, serta `APP_URL` domain aplikasi.

## Cache konfigurasi dan route

Jalankan dari direktori utama aplikasi setelah `.env` siap:

```bash
php artisan config:cache
php artisan route:cache
```
