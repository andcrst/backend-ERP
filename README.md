<div align="center">

# 🏗️ Backend Custom ERP — Toko Bangunan

**REST API backend untuk sistem ERP internal toko bangunan**

Kasir (POS) · Gudang & Persediaan · Pengadaan · Keuangan · Sistem

Dibangun dengan Laravel 13 · PHP 8.3 · MySQL 8 · Sanctum · spatie/permission

[![Code Style](https://img.shields.io/badge/code%20style-Pint-blueviolet)](https://laravel.com/docs/pint)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)](https://www.mysql.com/)

</div>

---

## 📋 Daftar Isi

- [Tentang Proyek](#-tentang-proyek)
- [Modul](#-modul)
- [Tech Stack](#-tech-stack)
- [Instalasi](#-instalasi)
- [Menjalankan Aplikasi](#-menjalankan-aplikasi)
- [Konfigurasi](#-konfigurasi)
- [Peran & Hak Akses](#-peran--hak-akses)
- [Aturan Bisnis](#-aturan-bisnis)
- [Dokumentasi Endpoint](#-dokumentasi-endpoint)
  - [Format Respons](#format-respons) · [Kode Error](#kode-error) · [Query Umum](#query-umum)
  - [1. Autentikasi](#1-autentikasi--utilitas) · [2. Master Produk](#2-master-produk--kategori) · [3. Pelanggan](#3-kasir--pelanggan)
  - [4. Pesanan](#4-kasir--pesanan) · [5. Supplier](#5-gudang--supplier) · [6. Barang Keluar](#6-gudang--barang-keluar)
  - [7. Opname](#7-gudang--opname) · [8. Kartu Stok](#8-gudang--kartu-stok--pemantauan) · [9. Pengadaan](#9-pengadaan--4-tahap)
  - [10. Keuangan](#10-keuangan--baca-saja) · [11. Sistem](#11-sistem)
- [Pengujian & Gaya Kode](#-pengujian--gaya-kode)
- [Struktur Proyek](#-struktur-proyek)

---

## 🎯 Tentang Proyek

Backend API untuk **ERP internal toko bangunan** — bukan sekadar aplikasi kasir. Sistem
menggabungkan penjualan di kasir dengan pengelolaan gudang, pengadaan barang, serta pelaporan
keuangan, dengan **satu sumber kebenaran stok**.

Perubahan stok tidak pernah dilakukan langsung ke kolom `stok.kuantitas`. Seluruh pergerakan
stok dicatat sebagai baris ledger di tabel `mutasi_stok`, sehingga setiap angka stok dapat
ditelusuri asal-usulnya (invarian: **Σ `jumlah_perubahan` = `kuantitas`**).

Aplikasi ini **API-only** — antarmuka pengguna dibuat terpisah.

---

## 📦 Modul

| Modul | Isi | Status |
|---|---|:---:|
| **Autentikasi** | Login/logout token Sanctum, profil, ganti password | ✅ |
| **Master Produk** | CRUD produk & kategori, SKU otomatis, scan barcode, stok minimum | ✅ |
| **Kasir (POS)** | Pesanan multi-item, status per barang, biaya angkut, struk, void | ✅ |
| **Gudang & Persediaan** | Barang keluar beralasan, opname dua tahap, kartu stok, stok menipis | ✅ |
| **Pengadaan** | Alur 4 tahap: ajukan → putuskan → terima → tutup, penerimaan bertahap | ✅ |
| **Keuangan** | Dashboard, 9 jenis rekap, laba kotor/HPP, ekspor CSV (baca-saja) | ✅ |
| **Sistem** | Pengguna, role & permission, audit log, pengaturan toko | ✅ |

---

## 🛠 Tech Stack

| Komponen | Versi / Keterangan |
|---|---|
| **Framework** | Laravel 13.x (API-only) |
| **Bahasa** | PHP 8.3 |
| **Database** | MySQL 8 (InnoDB, `utf8mb4_unicode_ci`) |
| **Autentikasi** | Laravel Sanctum (token) |
| **Otorisasi** | spatie/laravel-permission (RBAC di server) |
| **Code Style** | Laravel Pint |
| **Testing** | PHPUnit 12 |
| **Timezone** | `Asia/Jakarta` |

---

## 🚀 Instalasi

Butuh **PHP 8.3+**, **Composer 2**, dan **MySQL 8**.

```bash
# 1. Dependensi
composer install

# 2. Siapkan environment
#    Salin .env.example menjadi .env, lalu isi kredensial database Anda sendiri.
#    Variabel wajib: DB_*, APP_KEY, dan kredensial akun awal ERP_OWNER_*.

# 3. Generate kunci aplikasi
php artisan key:generate

# 4. Siapkan database kosong lebih dulu (MySQL 8, utf8mb4) — nama bebas,
#    lalu arahkan DB_DATABASE di .env ke nama tersebut.

# 5. Migrasi + data awal (role, akun, kategori, katalog produk)
php artisan migrate --seed

# 6. Jalankan
php artisan serve
```

> ⚠️ **Kredensial tidak disimpan di repo.** Isi database, nama/email/password akun awal, dan
> prefix dokumen melalui `.env` (lihat `.env.example`). Jangan pernah commit `.env`.

---

## ▶️ Menjalankan Aplikasi

```bash
php artisan serve                  # http://127.0.0.1:8000
php artisan route:list             # daftar seluruh endpoint
php artisan migrate:fresh --seed   # reset total (hati-hati: data lama hilang)
```

---

## ⚙️ Konfigurasi

Pengaturan aplikasi ada di `config/erp.php` dan dapat ditimpa lewat `.env`:

| Variabel | Kegunaan | Default |
|---|---|---|
| `ERP_PREFIX_STRUK` | Prefix nomor struk | `INV` |
| `ERP_PREFIX_PENGADAAN` | Prefix nomor pengadaan | `PGD` |
| `ERP_PREFIX_BARANG_MASUK` | Prefix nomor penerimaan | `BM` |
| `ERP_PREFIX_BARANG_KELUAR` | Prefix nomor pengeluaran | `BK` |
| `ERP_PREFIX_OPNAME` | Prefix nomor opname | `OPN` |
| `ERP_STOK_MINIMUM` | Stok minimum default produk baru | `5` |
| `ERP_THROTTLE_LOGIN` | Rate limit login, format `{percobaan},{menit}` | `5,1` |
| `ERP_OWNER_*` | Akun OWNER awal (nama, email, password) | wajib diisi |

Sebagian pengaturan toko (nama toko, alamat, telepon, stok minimum, biaya angkut) juga dapat
diubah saat aplikasi berjalan lewat menu **Pengaturan** — hanya oleh OWNER.

---

## 🔐 Peran & Hak Akses

Empat peran:

| Peran | Ringkas |
|---|---|
| **OWNER** | Semua modul. Satu-satunya yang boleh **membatalkan transaksi (void)** dan mengelola pengguna |
| **FINANCE** | Baca transaksi, rekap penjualan, laba kotor, ekspor. **Baca-saja** — tidak membuat transaksi. Memutuskan & menutup pengadaan, menyetujui opname |
| **GUDANG** | Produk, barang masuk/keluar, mutasi, opname (buat & hitung), pengantaran, ajukan & terima pengadaan. **Tidak** boleh melihat laba/omzet (data keuangan) |
| **KASIR** | Checkout, pelanggan, baca produk |

Otorisasi ditegakkan **di server** melalui middleware `permission:` — bukan sekadar
disembunyikan di frontend.

---

## 📏 Aturan Bisnis

1. **Harga & modal selalu dari database** — payload client tidak pernah menentukan harga.
   Nilainya disalin ke snapshot saat transaksi dibuat, sehingga laporan historis tidak berubah
   ketika harga master diubah.
2. **`stok.kuantitas` hanya berubah lewat satu pintu** (`StokMutasiService`). Tidak ada
   `increment`/`decrement` langsung di mana pun.
3. **`kuantitas` tidak bisa diubah lewat CRUD produk.** Stok awal memakai `kuantitas_awal`
   yang dicatat sebagai mutasi `SALDO_AWAL`.
4. **Status melekat pada barang, bukan transaksi** — hanya 4: `DIAMBIL`,
   `MENUNGGU_DIANTAR`, `SUDAH_DIANTAR`, `GAGAL_DIANTAR`. Status pesanan (`BERJALAN`/`SELESAI`)
   dihitung dari status barang, bukan disimpan.
5. **Stok berkurang** saat barang `DIAMBIL` atau saat gudang mengonfirmasi `SUDAH_DIANTAR`.
   `MENUNGGU_DIANTAR` dan `GAGAL_DIANTAR` **tidak** mengubah stok.
6. **Stok minus ditolak** — `422 STOK_TIDAK_CUKUP` + rollback seluruh transaksi.
7. **`kembalian` hanya untuk TUNAI**; QRIS/TRANSFER selalu `0`. Uang tunai kurang ditolak
   `422 PEMBAYARAN_KURANG` — tidak dianggap uang muka.
8. **`biaya_angkut` di luar `total_pembayaran`** — ditanggung toko, tidak ditagihkan ke pelanggan.
9. **Barang tidak boleh diterima tanpa pengadaan disetujui** —
   `422 PENGAADAAN_BELUM_DISETUJUI`.
10. **Pemisahan tugas** — pengaju pengadaan tidak boleh menyetujui pengadaannya sendiri;
    pembuat opname tidak boleh menyetujui hasil hitungnya sendiri; gudang tidak bisa
    menyetujui opname.
11. **Nomor dokumen tidak pernah dipakai ulang** — pembatalan memakai soft delete.
12. **Laporan mengecualikan transaksi dibatalkan** dan memakai rentang `tanggal_transaksi`.
    Modal yang belum diisi **ditandai** (`hpp_cakupan`, `hpp_lengkap`) — tidak dianggap nol
    diam-diam.
13. **Aksi sensitif diaudit** ke `audit_log`: void, ubah status barang, ubah biaya angkut,
    opname, keputusan pengadaan, perubahan peran pengguna.

---

## 🔗 Dokumentasi Endpoint

Base URL: **`/api/v1`** · Autentikasi: `Authorization: Bearer <token>`

### Format Respons

Sukses:

```json
{ "success": true, "message": "Pesan singkat.", "data": { }, "meta": { } }
```

`meta` hanya ada pada endpoint ber-pagination:

```json
{ "current_page": 1, "per_page": 20, "total": 104, "last_page": 6, "from": 1, "to": 20 }
```

Gagal:

```json
{
  "success": false,
  "message": "Stok Semen tidak mencukupi (tersedia 5, diminta 10).",
  "error_code": "STOK_TIDAK_CUKUP",
  "errors": { "jumlah": ["Stok tidak mencukupi."] }
}
```

### Kode Error

| HTTP | `error_code` | Arti |
|---|---|---|
| 401 | `UNAUTHENTICATED` | Token tidak ada / kedaluwarsa |
| 403 | `FORBIDDEN` | Peran tidak punya permission |
| 403 | `ACCOUNT_INACTIVE` | Akun dinonaktifkan |
| 404 | `NOT_FOUND` | Data tidak ditemukan |
| 409 | `RESOURCE_IN_USE` | Data masih dipakai record lain |
| 422 | `VALIDATION_ERROR` | Validasi field gagal |
| 422 | `INVALID_CREDENTIALS` | Email/password salah |
| 422 | `INVALID_CURRENT_PASSWORD` | Password lama tidak sesuai |
| 422 | `STOK_TIDAK_CUKUP` | Kuantitas stok tidak mencukupi |
| 422 | `PEMBAYARAN_KURANG` | Uang tunai kurang dari total |
| 422 | `ALAMAT_WAJIB` | Barang diantar tanpa pelanggan/alamat |
| 422 | `BIAYA_ANGKUT_WAJIB` | Biaya angkut belum diisi |
| 422 | `BIAYA_ANGKUT_TIDAK_RELEVAN` | Biaya angkut pada transaksi tanpa pengantaran |
| 422 | `PENGAADAAN_BELUM_DISETUJUI` | Terima barang tanpa pengadaan disetujui |
| 422 | `PENGADAAN_BELUM_DITERIMA` | Tutup pengadaan sebelum barang diterima |
| 422 | `OWNER_TERAKHIR` | Operasi akan menghilangkan OWNER aktif terakhir |
| 429 | `TOO_MANY_REQUESTS` | Rate limit terlampaui |
| 500 | `INTERNAL_ERROR` | Kesalahan server |

### Query Umum

| Parameter | Berlaku pada | Keterangan |
|---|---|---|
| `page`, `per_page` | semua daftar | `per_page` maks 100 |
| `search` | stok, kategori, pelanggan, supplier, pengguna | pencarian teks |
| `dari`, `sampai` | pesanan, pengeluaran, opname, pengadaan, penerimaan, rekap | rentang tanggal (`YYYY-MM-DD`) |
| `status` | opname, pengadaan, pengeluaran, penerimaan | filter status dokumen |
| `format=csv` | ekspor rekap | satu-satunya format yang didukung |

---

### 1. Autentikasi & Utilitas

| Method | Endpoint | Akses | Fungsi |
|---|---|---|---|
| GET | `/health` | publik | Status aplikasi & koneksi DB |
| POST | `/auth/login` | publik | Login → token (rate limit 5/menit) |
| POST | `/auth/logout` | auth | Cabut token yang dipakai |
| GET | `/auth/me` | auth | Profil + peran + daftar permission |
| PATCH | `/auth/password` | auth | Ganti password (token lain dicabut) |

**`POST /auth/login`**

```json
{ "email": "user@contoh.id", "password": "********" }
```

```json
{
  "success": true,
  "data": { "token": "1|abc...", "token_type": "Bearer", "user": { "id": 4, "role": "KASIR" } }
}
```

---

### 2. Master Produk & Kategori

| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/stok` | `stok.view` | Daftar produk (`search`, `id_kategori`, `tersedia`, `menipis`) |
| POST | `/stok` | `stok.create` | Tambah produk (+`kuantitas_awal`, `satuan`, `harga_modal`) |
| GET | `/stok/scan` | `stok.view` | Cari produk via `?kode_sku=` |
| GET | `/stok/{id_produk}` | `stok.view` | Detail produk |
| PUT | `/stok/{id_produk}` | `stok.update` | Ubah produk (**tanpa** `kuantitas`) |
| DELETE | `/stok/{id_produk}` | `stok.delete` | Hapus produk (`409` bila dipakai transaksi) |
| GET | `/kategori` | `kategori.view` | Daftar kategori + jumlah produk |
| POST | `/kategori` | `kategori.create` | Tambah kategori |
| GET | `/kategori/{id_kategori}` | `kategori.view` | Detail kategori |
| PUT | `/kategori/{id_kategori}` | `kategori.update` | Ubah kategori |
| DELETE | `/kategori/{id_kategori}` | `kategori.delete` | Hapus kategori (`409` bila dipakai) |

**`POST /stok`** — `kuantitas` **ditolak** `422`; gunakan `kuantitas_awal` (dicatat sebagai
mutasi `SALDO_AWAL`).

```json
{
  "nama_produk": "Besi 10 mm",
  "harga": 73000,
  "satuan": "batang",
  "harga_modal": 62000,
  "stok_minimum": 5,
  "kuantitas_awal": 25
}
```

---

### 3. Kasir — Pelanggan

| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/kasir/pelanggan` | `pelanggan.view` | Daftar (`search`, `aktif_saja`) |
| POST | `/kasir/pelanggan` | `pelanggan.create` | Tambah pelanggan |
| GET | `/kasir/pelanggan/{id_pelanggan}` | `pelanggan.view` | Detail |
| PUT | `/kasir/pelanggan/{id_pelanggan}` | `pelanggan.update` | Ubah |
| DELETE | `/kasir/pelanggan/{id_pelanggan}` | `pelanggan.delete` | Hapus (soft delete) |
| GET | `/kasir/pelanggan/{id_pelanggan}/riwayat` | `pelanggan.view` | Riwayat transaksi pelanggan |

| Field | Tipe | Wajib | Aturan |
|---|---|---|---|
| `nama_pelanggan` | string | ya (buat) | maks 100 |
| `telepon` | string | tidak | maks 20 |
| `alamat` | string | tidak | maks 1000 · **wajib terisi** bila dipakai untuk barang `MENUNGGU_DIANTAR` |
| `catatan` | string | tidak | maks 1000 |
| `is_active` | boolean | tidak | default `true` |

> Pelanggan **hanya diperlukan bila ada barang yang diantar**. Penjualan yang seluruh barangnya
> `DIAMBIL` tidak memerlukan data pelanggan.

---

### 4. Kasir — Pesanan

| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/kasir/pesanan` | `transaksi.view` | Daftar (`dari`, `sampai`, `status_pembayaran`, `berjalan=1`) |
| POST | `/kasir/pesanan` | `transaksi.create` | Buat pesanan |
| GET | `/kasir/pesanan/{id_transaksi}` | `transaksi.view` | Detail + status per barang |
| GET | `/kasir/pesanan/{id_transaksi}/struk` | `transaksi.view` | Data struk |
| DELETE | `/kasir/pesanan/{id_transaksi}` | `transaksi.void` | Batalkan (**OWNER saja**) |
| GET | `/kasir/tugas-antar` | `stok.mutasi` | Tugas pengantaran (**GUDANG**) |
| PATCH | `/kasir/pesanan/{id_transaksi}/barang/{id_detail}` | `stok.keluar.posting` | Ubah status barang (**GUDANG**) |
| PATCH | `/kasir/pesanan/{id_transaksi}/biaya-angkut` | `stok.keluar.posting` | Isi biaya angkut (**GUDANG**) |

**Status per BARANG** — hanya 4 nilai:

| Status | Diubah oleh | Efek stok |
|---|---|---|
| `DIAMBIL` | Kasir | **− saat simpan** |
| `MENUNGGU_DIANTAR` | Kasir | belum |
| `SUDAH_DIANTAR` | Gudang | **− saat konfirmasi** |
| `GAGAL_DIANTAR` | Gudang | tidak berubah |

`status_pesanan` **dihitung**: `BERJALAN` bila masih ada barang `MENUNGGU_DIANTAR`/`GAGAL_DIANTAR`,
selain itu `SELESAI`.

**`POST /kasir/pesanan`**

```json
{
  "detail": [
    { "id_produk": 1, "jumlah": 1, "status_barang": "DIAMBIL" },
    { "id_produk": 2, "jumlah": 5, "status_barang": "MENUNGGU_DIANTAR" }
  ],
  "jenis_pembayaran": "TUNAI",
  "jumlah_bayar": 500000,
  "id_pelanggan": 1
}
```

| Field | Tipe | Wajib | Keterangan |
|---|---|---|---|
| `detail[]` | array | ya | min 1 baris |
| `detail[].id_produk` | int | ya | harus ada, tidak boleh duplikat |
| `detail[].jumlah` | int | ya | ≥ 1 |
| `detail[].status_barang` | enum | tidak | `DIAMBIL` (default) \| `MENUNGGU_DIANTAR` |
| `jenis_pembayaran` | enum | ya | `TUNAI` \| `QRIS` \| `TRANSFER` |
| `jumlah_bayar` | number | tidak | TUNAI; `< total` → `422 PEMBAYARAN_KURANG` |
| `referensi_pembayaran` | string | tidak | opsional untuk QRIS/TRANSFER |
| `id_pelanggan` | int | **wajib bila ada pengantaran** | pelanggan harus punya alamat |
| `catatan` | string | tidak | maks 1000 |

**Transisi status barang yang diizinkan:**

```
MENUNGGU_DIANTAR → SUDAH_DIANTAR   (stok −)
MENUNGGU_DIANTAR → GAGAL_DIANTAR   (stok tetap)
GAGAL_DIANTAR    → SUDAH_DIANTAR   (kirim ulang, stok −)
```

**`PATCH /kasir/pesanan/{id}/biaya-angkut`** — `{"biaya_angkut": 50000}`. Biaya angkut
**ditanggung toko**, tidak menambah `total_pembayaran`.

---

### 5. Gudang — Supplier

| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/gudang/supplier` | `supplier.view` | Daftar (`search`, `aktif_saja`) |
| POST | `/gudang/supplier` | `supplier.create` | Tambah supplier |
| GET | `/gudang/supplier/{id_supplier}` | `supplier.view` | Detail |
| PUT | `/gudang/supplier/{id_supplier}` | `supplier.update` | Ubah |
| DELETE | `/gudang/supplier/{id_supplier}` | `supplier.delete` | Hapus (soft delete) |

`nama_supplier` **unik**, maks 100 karakter.

---

### 6. Gudang — Barang Keluar

| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/gudang/pengeluaran/opsi-alasan` | `stok.keluar.view` | Opsi alasan |
| GET | `/gudang/pengeluaran` | `stok.keluar.view` | Daftar (`dari`, `sampai`, `alasan`, `status`) |
| POST | `/gudang/pengeluaran` | `stok.keluar.create` | Buat draf |
| GET | `/gudang/pengeluaran/{id_pengeluaran}` | `stok.keluar.view` | Detail |
| POST | `/gudang/pengeluaran/{id_pengeluaran}/posting` | `stok.keluar.posting` | **Posting → stok berkurang** |

`alasan` ∈ `RUSAK`, `HILANG`, `PAKAI_INTERNAL`, `RETUR_SUPPLIER`, `LAINNYA`.
`LAINNYA` **wajib** disertai `catatan`. Draf **tidak** menyentuh stok.

```json
{ "alasan": "RUSAK", "item": [{ "id_produk": 1, "jumlah": 2, "catatan": "Kemasan sobek" }] }
```

| Error | Kondisi |
|---|---|
| `422 STOK_TIDAK_CUKUP` | Saat posting stok kurang → rollback, status tetap `DRAFT` |
| `422 VALIDATION_ERROR` | Sudah diposting, atau `LAINNYA` tanpa catatan |

---

### 7. Gudang — Opname

| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/gudang/opname/opsi-status` | `stok.view` | Opsi status |
| GET | `/gudang/opname` | `stok.view` | Daftar (`dari`, `sampai`, `status`) |
| POST | `/gudang/opname` | `stok.opname.create` | Buat sesi + snapshot `kuantitas_sistem` |
| GET | `/gudang/opname/{id_opname}` | `stok.view` | Detail + selisih |
| PATCH | `/gudang/opname/{id_opname}/item/{id_item}` | `stok.opname.create` | Isi hasil hitung fisik |
| POST | `/gudang/opname/{id_opname}/setujui` | `stok.opname.setujui` | **Setujui → posting selisih** |
| POST | `/gudang/opname/{id_opname}/batalkan` | `stok.opname.setujui` | Batalkan (wajib `alasan`) |

```
DRAFT ──(semua item terisi)──► DIHITUNG ──(OWNER/FINANCE setujui)──► DISETUJUI
  └────────(OWNER batalkan)──────────────────────────────────────► DIBATALKAN
```

| Aturan | Kode |
|---|---|
| GUDANG tidak boleh menyetujui | `403` |
| Pembuat tidak boleh menyetujui sesinya sendiri | `403` |
| Setujui sebelum `DIHITUNG` / sesi sudah final | `422` |

`kuantitas_sistem` adalah **snapshot** saat sesi dibuat; `selisih` dihitung server
(`kuantitas_fisik` − `kuantitas_sistem`) dan **tidak boleh** dikirim client.

---

### 8. Gudang — Kartu Stok & Pemantauan

| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/gudang/kartu-stok/{id_produk}` | `stok.mutasi` | Riwayat mutasi (`dari`, `sampai`, `jenis_mutasi`) |
| GET | `/gudang/stok-menipis` | `stok.view` | `kuantitas <= stok_minimum` |
| GET | `/gudang/ringkasan-persediaan` | `stok.view` | Total produk, menipis, kosong, nilai persediaan |
| GET | `/gudang/jenis-mutasi` | `stok.view` | Label jenis mutasi |

Baris kartu stok memuat `arah` (`MASUK`/`KELUAR`), `kuantitas_sebelum`, `kuantitas_sesudah`,
`referensi_tipe`, dan `referensi_id` sehingga setiap perubahan stok dapat ditelusuri.

---

### 9. Pengadaan — 4 Tahap

| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/gudang/pengadaan/opsi-status` | `pengadaan.view` | Opsi status |
| GET | `/gudang/pengadaan` | `pengadaan.view` | Daftar (`dari`, `sampai`, `status`, `id_supplier`) |
| POST | `/gudang/pengadaan` | `pengadaan.create` | **1) Ajukan** (GUDANG) |
| GET | `/gudang/pengadaan/{id_pengadaan}` | `pengadaan.view` | Detail + daftar penerimaan |
| GET | `/gudang/pengadaan/{id_pengadaan}/sisa` | `pengadaan.view` | Sisa belum diterima per produk |
| PUT | `/gudang/pengadaan/{id_pengadaan}/ajukan-ulang` | `pengadaan.create` | Ajukan ulang (setelah `REVISI`/`DITOLAK`) |
| POST | `/gudang/pengadaan/{id_pengadaan}/penerimaan` | `stok.masuk.create` | **3) Terima barang** (GUDANG) |
| GET | `/gudang/penerimaan` | `stok.masuk.view` | Daftar penerimaan |
| GET | `/gudang/penerimaan/{id_penerimaan}` | `stok.masuk.view` | Detail penerimaan |
| POST | `/gudang/penerimaan/{id_penerimaan}/posting` | `stok.masuk.posting` | **Posting → stok + `harga_modal`** |
| GET | `/finance/pengadaan/opsi-keputusan` | `pengadaan.view` | Opsi keputusan |
| GET | `/finance/pengadaan` | `pengadaan.view` | Daftar (default `DIAJUKAN`) |
| GET | `/finance/pengadaan/{id_pengadaan}` | `pengadaan.view` | Detail |
| PATCH | `/finance/pengadaan/{id_pengadaan}/putuskan` | `pengadaan.setujui` | **2) Setujui / Tolak / Revisi** |
| PATCH | `/finance/pengadaan/{id_pengadaan}/tutup` | `pengadaan.tutup` | **4) Tutup** |

```
[GUDANG]            [FINANCE/OWNER]      [GUDANG]            [FINANCE/OWNER]
Ajukan    ───────►  Putuskan   ───────►  Terima barang ────► Tutup
DIAJUKAN            DISETUJUI            DITERIMA            DITUTUP
                    DITOLAK / REVISI     (stok +, modal ↑)
```

**Ajukan** — `status`, `nomor_pengadaan`, `jumlah_diterima` **dilarang** dikirim.

```json
{
  "id_supplier": 1,
  "alasan": "Stok menipis",
  "tanggal_kebutuhan": "2026-12-01",
  "item": [{ "id_produk": 1, "jumlah_diminta": 10, "perkiraan_harga": 60000 }]
}
```

**Putuskan** — `{"keputusan": "SETUJUI"}`. Untuk `TOLAK`/`REVISI`, `catatan` **wajib**.

**Terima barang** — hanya untuk pengadaan `DISETUJUI`.

```json
{
  "nomor_surat_jalan": "SJ-001",
  "catatan": "Datang 7 dari 10",
  "item": [{ "id_produk": 1, "jumlah": 7, "harga_modal": 61000 }]
}
```

| Aturan | Kode |
|---|---|
| Terima barang tanpa pengadaan disetujui | `422 PENGAADAAN_BELUM_DISETUJUI` |
| Tutup sebelum `DITERIMA` | `422 PENGADAAN_BELUM_DITERIMA` |
| GUDANG memutuskan / menutup | `403` |
| Pengaju menyetujui pengadaannya sendiri | `403` |
| Jumlah diterima ≠ pengajuan tanpa `catatan` | `422` |
| Barang di luar pengajuan | `422` |

Posting penerimaan **menaikkan stok**, **memperbarui `stok.harga_modal`**, menambah
`jumlah_diterima`, dan mengubah pengadaan → `DITERIMA`.

---

### 10. Keuangan — Baca-Saja

| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/finance/dashboard` | `finance.dashboard` | Hari ini, bulan berjalan, tren 7 hari, piutang |
| GET | `/finance/dashboard/opsi-pembayaran` | `finance.dashboard` | Opsi jenis pembayaran |
| GET | `/finance/jenis-rekap` | `finance.rekap` | Daftar jenis rekap |
| GET | `/finance/rekap/penjualan/harian` | `finance.rekap` | Omzet per hari |
| GET | `/finance/rekap/penjualan/bulanan` | `finance.rekap` | Omzet per bulan |
| GET | `/finance/rekap/pendapatan` | `finance.rekap` | Tunai vs non-tunai, tunai bersih |
| GET | `/finance/rekap/pembayaran` | `finance.rekap` | Per metode + persentase |
| GET | `/finance/rekap/kasir` | `finance.rekap` | Per kasir |
| GET | `/finance/rekap/produk` | `finance.rekap` | Produk terlaris + HPP |
| GET | `/finance/rekap/laba-kotor` | `finance.rekap` | HPP, laba & margin kotor |
| GET | `/finance/rekap/transaksi` | `finance.rekap` | Daftar transaksi non-void |
| GET | `/finance/rekap/stok` | `finance.rekap` | Nilai persediaan |
| GET | `/finance/rekap/{jenis}/ekspor` | `finance.ekspor` | Ekspor CSV |

Semua rekap menerima `?dari=YYYY-MM-DD&sampai=YYYY-MM-DD` (default bulan berjalan).

**`laba-kotor`** — modal yang belum diisi **ditandai**, bukan dianggap nol:

```json
{
  "pendapatan": 130000,
  "hpp": 50000,
  "laba_kotor": 15000,
  "margin_kotor": 23.08,
  "baris_tanpa_modal": 1,
  "hpp_cakupan": 50,
  "hpp_lengkap": false,
  "catatan": "1 dari 2 baris belum punya harga modal; laba kotor hanya dihitung dari baris yang bermodal."
}
```

`{jenis}` ekspor ∈ `penjualan-harian`, `penjualan-bulanan`, `pendapatan`, `pembayaran`,
`kasir`, `produk`, `laba-kotor`, `transaksi`, `stok`. Respons `text/csv` dengan BOM.
Format selain `csv` → `422`; jenis tak dikenal → `404`.

> **GUDANG tidak punya `finance.rekap`** — laba, HPP, dan omzet tergolong data keuangan
> yang tidak boleh dilihat gudang. **KASIR** tidak punya akses ke modul ini sama sekali.

---

### 11. Sistem

| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/sistem/user/opsi-role` | `user.view` | Opsi 4 peran |
| GET | `/sistem/user` | `user.view` | Daftar (`search`, `role`, `is_active`) |
| POST | `/sistem/user` | `user.create` | Tambah pengguna |
| GET | `/sistem/user/{id}` | `user.view` | Detail |
| PUT | `/sistem/user/{id}` | `user.update` | Ubah data dasar |
| DELETE | `/sistem/user/{id}` | `user.delete` | Hapus (soft delete) |
| PATCH | `/sistem/user/{id}/role` | `user.ubah_role` | **Ubah peran** (token dicabut) |
| PATCH | `/sistem/user/{id}/status` | `user.update` | **Aktif/nonaktif** (token dicabut) |
| POST | `/sistem/user/{id}/reset-password` | `user.reset_password` | **Reset password** (token dicabut) |
| GET | `/sistem/role` | `role.view` | Daftar role + permission |
| GET | `/sistem/permission` | `role.view` | Permission per modul |
| PUT | `/sistem/role/{role}/permission` | `role.update` | Ubah permission role |
| GET | `/sistem/audit-log` | `audit.view` | Jejak audit (`aksi`, `entitas_tipe`, `id_pengguna`, `dari`, `sampai`) |
| GET | `/sistem/pengaturan` | `pengaturan.view` | Pengaturan toko |
| PUT | `/sistem/pengaturan` | `pengaturan.update` | Simpan pengaturan |

**`POST /sistem/user`**

```json
{ "name": "Budi", "email": "budi@toko.id", "password": "********", "role": "KASIR" }
```

`role` ∈ `OWNER` \| `FINANCE` \| `GUDANG` \| `KASIR`. Password minimal 8 karakter.
Role Spatie disinkronkan otomatis. **Nama boleh kembar — yang unik hanya `email`.**

**Perlindungan anti-lockout:**

| Aturan | Kode |
|---|---|
| Ubah peran / nonaktifkan / hapus **diri sendiri** | `422 VALIDATION_ERROR` |
| Menghilangkan **OWNER aktif terakhir** | `422 OWNER_TERAKHIR` |

**Pengaturan toko** — kunci yang dikenali: `nama_toko`, `alamat_toko`, `telepon_toko`,
`prefix_struk`, `stok_minimum_default`, `biaya_angkut_default`. Kunci lain diabaikan.

---

### Ringkasan Jumlah Endpoint

| Modul | Jumlah |
|---|---:|
| Autentikasi & utilitas | 5 |
| Master produk & kategori | 11 |
| Kasir (pelanggan + pesanan) | 14 |
| Gudang (supplier, keluar, opname, kartu stok) | 20 |
| Pengadaan (ajukan, terima, putuskan, tutup) | 15 |
| Keuangan | 13 |
| Sistem | 15 |
| Alias lama `/transaksi` (deprecated) | 5 |
| **Total** | **99** |

> Endpoint `/transaksi` adalah **alias lama** yang dipertahankan untuk kompatibilitas;
> gunakan `/kasir/pesanan`.

---

### 12. Alias Lama `/transaksi` (Deprecated)

| Method | Endpoint | Permission | Fungsi |
|---|---|---|---|
| GET | `/transaksi` | `transaksi.view` | Alias daftar pesanan |
| POST | `/transaksi` | `transaksi.create` | Alias buat pesanan |
| GET | `/transaksi/{id_transaksi}` | `transaksi.view` | Alias detail pesanan |
| GET | `/transaksi/{id_transaksi}/struk` | `transaksi.view` | Alias data struk |
| DELETE | `/transaksi/{id_transaksi}` | `transaksi.void` | Alias void (**OWNER**) |

Perbedaan penting: alias lama **selalu** menganggap semua barang `DIAMBIL`, sehingga tidak
mendukung alur pengantaran. Untuk transaksi yang perlu diantar, gunakan `/kasir/pesanan`.

---

## 🧪 Pengujian & Gaya Kode

```bash
php artisan test              # PHPUnit
vendor/bin/pint --test        # cek gaya kode (wajib PASS sebelum commit)
vendor/bin/pint               # perbaiki otomatis
```

---

## 📁 Struktur Proyek

```
├── app/
│   ├── Enums/                  # Peran, status barang/dokumen, jenis mutasi, alasan
│   ├── Exceptions/             # ApiException (error code + status HTTP)
│   ├── Http/
│   │   ├── Controllers/Api/V1/ # Auth, Kasir, Gudang, Finance, Sistem, Master
│   │   ├── Middleware/         # ForceJsonResponse, EnsureAccountIsActive
│   │   ├── Requests/           # Validasi form
│   │   └── Resources/          # Bentuk respons JSON
│   ├── Models/                 # Eloquent
│   ├── Services/               # Seluruh aturan bisnis & agregasi laporan
│   └── Support/                # ApiResponse, CsvExporter
├── bootstrap/
├── config/                     # app, auth, database, erp, sanctum, permission, ...
├── database/
│   ├── data/                   # Katalog produk awal (CSV)
│   ├── factories/
│   ├── migrations/
│   └── seeders/                # Role, akun awal, kategori, katalog
├── public/
├── routes/
│   ├── api.php
│   └── api/v1/                 # auth, master, kasir, gudang, pengadaan, finance, sistem
├── storage/
└── composer.json
```

---

<div align="center">

**Backend Custom ERP — Toko Bangunan** · Laravel 13 · PHP 8.3 · MySQL 8

</div>
