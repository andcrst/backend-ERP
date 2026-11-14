<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor CSV tanpa library tambahan.
 *
 * Dipakai endpoint `?format=csv` pada modul Finance agar pemilik toko dapat
 * mengolah data di Excel.
 */
final class CsvExporter
{
    /**
     * Bangun respons CSV dari daftar baris asosiatif.
     *
     * @param  list<array<string, mixed>>  $baris
     */
    public static function dariBaris(array $baris, string $namaFile): StreamedResponse
    {
        $kolom = $baris === [] ? [] : array_keys($baris[0]);

        return response()->streamDownload(function () use ($baris, $kolom): void {
            $keluaran = fopen('php://output', 'wb');

            if ($keluaran === false) {
                return;
            }

            // BOM agar Excel membaca UTF-8 dengan benar.
            fwrite($keluaran, "\xEF\xBB\xBF");

            if ($kolom !== []) {
                fputcsv($keluaran, $kolom, ',', '"', '\\');
            }

            foreach ($baris as $satu) {
                fputcsv($keluaran, array_map(
                    fn (string $k): string => self::nilai($satu[$k] ?? null),
                    $kolom,
                ), ',', '"', '\\');
            }

            fclose($keluaran);
        }, $namaFile, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Bangun respons CSV dari satu objek ringkasan (kunci–nilai).
     *
     * Nilai bersarang (array) dilewati karena CSV hanya mendukung tabel datar.
     *
     * @param  array<string, mixed>  $ringkasan
     */
    public static function dariRingkasan(array $ringkasan, string $namaFile): StreamedResponse
    {
        $baris = [];

        foreach ($ringkasan as $kunci => $nilai) {
            if (is_array($nilai)) {
                continue;
            }

            $baris[] = ['metrik' => $kunci, 'nilai' => $nilai];
        }

        return self::dariBaris($baris, $namaFile);
    }

    private static function nilai(mixed $nilai): string
    {
        if ($nilai === null) {
            return '';
        }

        if (is_bool($nilai)) {
            return $nilai ? '1' : '0';
        }

        return (string) $nilai;
    }
}
