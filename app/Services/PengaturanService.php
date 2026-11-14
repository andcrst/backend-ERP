<?php

namespace App\Services;

use App\Models\Pengaturan;
use Illuminate\Support\Collection;

/**
 * Baca/tulis pengaturan toko (CP-5).
 *
 * Nilai disimpan di tabel `pengaturan`; fallback ke `Pengaturan::bawaan()`
 * bila baris belum ada, jadi sistem tetap jalan tanpa seeding.
 */
class PengaturanService
{
    /**
     * Seluruh pengaturan dikenali + nilainya (default bila belum diset).
     *
     * @return array<string, string|null>
     */
    public function semua(): array
    {
        /** @var Collection<string, Pengaturan> $tersimpan */
        $tersimpan = Pengaturan::query()->get()->keyBy('kunci');

        $hasil = [];

        foreach (Pengaturan::bawaan() as $kunci => $meta) {
            $baris = $tersimpan->get($kunci);

            $hasil[$kunci] = $baris?->nilai ?? $meta['default'];
        }

        return $hasil;
    }

    /**
     * Ambil satu nilai.
     */
    public function ambil(string $kunci): ?string
    {
        $baris = Pengaturan::query()->where('kunci', $kunci)->first();

        return $baris?->nilai ?? Pengaturan::bawaan()[$kunci]['default'] ?? null;
    }

    /**
     * Simpan sebagian pengaturan (hanya kunci yang dikenali).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, string|null>
     */
    public function simpan(array $data, ?int $idPengguna = null): array
    {
        $bawaan = Pengaturan::bawaan();

        foreach ($data as $kunci => $nilai) {
            if (! array_key_exists($kunci, $bawaan)) {
                continue;
            }

            Pengaturan::query()->updateOrCreate(
                ['kunci' => $kunci],
                [
                    'nilai' => $nilai === null ? null : (string) $nilai,
                    'grup' => $bawaan[$kunci]['grup'],
                    'keterangan' => $bawaan[$kunci]['keterangan'],
                ],
            );
        }

        return $this->semua();
    }

    /**
     * Metadata kunci (grup & keterangan) untuk UI.
     *
     * @return array<string, array{default:string|null, grup:string, keterangan:string}>
     */
    public function meta(): array
    {
        return Pengaturan::bawaan();
    }
}
