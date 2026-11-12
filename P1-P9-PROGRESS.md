# Progress P1-P9 Subscription Mitra

## Selesai

### P1 — Analisis teknis
- Arsitektur multi-tenant berbasis subdomain vendor dipetakan melalui `ResolveVendor` dan `VendorContext`.
- Guard vendor/client/super-admin, custom user provider, route Volt, model Vendor, service client, serta alur verifikasi Super Admin telah diaudit.
- Landing page tidak diubah.

### P2 — Database dan konfigurasi
- Midtrans SDK `midtrans/midtrans-php` ditambahkan.
- Config `config/midtrans.php` dibuat.
- `.env.example` ditambah konfigurasi Midtrans dan paket subscription.
- Field subscription Vendor ditambahkan/cast:
  - `subscription_started_at`
  - `subscription_expired_at`
  - `current_plan`
  - `last_payment_date`
- Model `VendorPayment` dibuat dengan relasi ke Vendor.
- Migration `vendor_payments` dibuat untuk order, transaction, amount, status, paid time, dan payload Midtrans.
- Enum subscription ditambah status `expired`.

### P3 — Pendaftaran mitra
- Form pendaftaran menjadi 4 tahap.
- Informasi harga, nama paket, durasi, pembayaran manual, dan tanpa auto-renewal ditampilkan.
- Checkbox persetujuan pembayaran dibuat wajib.
- DTO menerima `agreePaymentTerms`.
- Vendor baru tetap dibuat `pending` dan `inactive`.
- Setelah pendaftaran diarahkan ke halaman subscription.

### P4 — Verifikasi Super Admin
- Alur approve vendor yang sudah ada dipertahankan.
- Payment hanya boleh dimulai ketika status vendor `approved`.
- Vendor pending/rejected menerima informasi bahwa pembayaran belum tersedia.

### P5 — Payment subscription
- Halaman Volt `vendor/payment/subscription` dibuat.
- Route:
  - `vendor.payment.subscription`
  - `vendor.payment.callback`
- Midtrans Snap Sandbox/Production URL mengikuti konfigurasi.
- Tombol pembayaran manual dibuat.
- Halaman callback success/pending/failed dibuat.
- Tidak ada auto-renewal.

### P6 — Webhook dan persistence
- `MidtransNotificationController` dibuat.
- Endpoint `POST /api/midtrans/notification` dibuat.
- Signature Midtrans diverifikasi sebelum diproses.
- Status transaksi disimpan ke `vendor_payments`.
- Pembayaran sukses mengaktifkan subscription selama konfigurasi durasi.
- Renewal manual memperpanjang dari tanggal expired yang masih aktif.
- Notifikasi sukses dibuat idempotent berdasarkan `paid_at`.

### P7 — Pembatasan akses vendor
- Middleware `EnsureVendorPaidSubscription` dibuat.
- Route `/admin/*` dibatasi oleh verifikasi dan subscription aktif/belum expired.
- Route payment tidak melewati middleware paid-subscription, tetapi membutuhkan autentikasi vendor.
- Callback tetap dapat diakses pada domain vendor.

### P8 — Filter client
- `list-clinic2` hanya meminta vendor verified dan subscription aktif.
- `VendorService` memfilter `approved`, `active`, dan `subscription_expired_at > now()`.
- Detail vendor dan booking langsung turut memvalidasi status tersebut.

### P9 — Perpanjangan manual
- Payment action selalu membuat order baru.
- Payment sukses saat subscription masih aktif menambah 30 hari dari tanggal expired.
- Tidak ada cron, recurring charge, atau auto-renewal.

## Verifikasi yang berhasil

- `php artisan migrate --force`
- `vendor/bin/pint --dirty --format agent`
- Route payment terdaftar.
- Route webhook terdaftar.
- `tests/Feature/Vendor/Registration/RegisterVendorActionTest.php`
  - 2 test passed
  - 25 assertions passed
- PHP syntax check untuk service, action, dan controller berhasil.

## Perlu dilanjutkan

- Tambahkan test khusus P1-P9: consent form, middleware redirect, filter client, signature webhook, payment success, duplicate webhook, dan renewal.
- Uji transaksi Midtrans Sandbox dengan credential valid.
- Konfirmasi URL webhook publik pada dashboard Midtrans.
- Review migrasi existing pada server lain; database lama memiliki struktur subscription/payment sehingga migration dibuat defensif.
- Jalankan full test suite.
- Jalankan `npm run build` bila memerlukan verifikasi asset production.
- Pastikan `.env` server berisi `MIDTRANS_SERVER_KEY`, `MIDTRANS_CLIENT_KEY`, `MIDTRANS_IS_PRODUCTION=false`, serta variabel subscription.
