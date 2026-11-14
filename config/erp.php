<?php

/**
 * Pengaturan default ERP.
 *
 * Catatan: TIDAK ada feature flag per modul (keputusan D-10=B: big-bang).
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Prefix nomor dokumen
    |--------------------------------------------------------------------------
    | Format: {PREFIX}/{YYYY}/{MM}/{NNNN}
    */
    'dokumen' => [
        'struk' => env('ERP_PREFIX_STRUK', 'INV'),
        'pengadaan' => env('ERP_PREFIX_PENGADAAN', 'PGD'),
        'barang_masuk' => env('ERP_PREFIX_BARANG_MASUK', 'BM'),
        'barang_keluar' => env('ERP_PREFIX_BARANG_KELUAR', 'BK'),
        'opname' => env('ERP_PREFIX_OPNAME', 'OPN'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stok
    |--------------------------------------------------------------------------
    */
    'stok' => [
        'minimum_default' => (int) env('ERP_STOK_MINIMUM', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Harga pokok penjualan (D-05=A)
    |--------------------------------------------------------------------------
    | Metode: rata-rata harga_modal per produk (bukan FIFO).
    */
    'hpp' => [
        'metode' => 'rata_rata',
        'tandai_tanpa_modal' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Biaya angkut (D-31 + D-32=B)
    |--------------------------------------------------------------------------
    | Ditanggung toko: TIDAK masuk total_pembayaran, dicatat sebagai pengeluaran.
    */
    'biaya_angkut' => [
        'ditanggung_toko' => true,
        'default' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate limit
    |--------------------------------------------------------------------------
    | Format Laravel: "{maks_percobaan},{menit}". Default: 5 login/menit.
    | Smoke test dapat menaikkan lewat ERP_THROTTLE_LOGIN (mis. "100,1").
    */
    'throttle' => [
        'login' => env('ERP_THROTTLE_LOGIN', '5,1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Akun awal (dipakai seeder; nilai diambil dari .env)
    |--------------------------------------------------------------------------
    */
    'owner' => [
        'name' => env('ERP_OWNER_NAME', 'Owner Toko'),
        'email' => env('ERP_OWNER_EMAIL', 'owner@erp.local'),
        'password' => env('ERP_OWNER_PASSWORD', 'password'),
    ],

];
