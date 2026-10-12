<?php

namespace App\Http\Controllers;

use App\Enums\StatusKomponen;
use App\Models\Kendaraan;
use App\Models\KomponenKendaraan;
use App\Models\TemplateJadwalServis;
use App\Services\KomponenServisService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class KendaraanKomponenController extends Controller
{
    /**
     * Terapkan / kloning template servis ke kendaraan tertentu.
     */
    public function applyTemplate(Request $request, Kendaraan $kendaraan, KomponenServisService $service): RedirectResponse
    {
        $validated = $request->validate([
            'id_template' => ['required', 'exists:template_jadwal_servis,id'],
            'mode' => ['nullable', 'in:replace,append'],
        ]);

        $template = TemplateJadwalServis::findOrFail($validated['id_template']);
        $overwrite = ($validated['mode'] ?? 'replace') === 'replace';

        $count = $service->applyTemplateToKendaraan($kendaraan, $template, $overwrite);

        return back()->with('success', "Template '{$template->nama}' berhasil diterapkan! {$count} komponen telah ditambahkan ke {$kendaraan->merk_tipe} ({$kendaraan->plat_nomor}).");
    }

    /**
     * Tambah komponen kustom baru langsung ke kendaraan ini.
     */
    public function store(Request $request, Kendaraan $kendaraan): RedirectResponse
    {
        $validated = $request->validate([
            'nama_komponen' => ['required', 'string', 'max:150'],
            'kategori' => ['required', 'in:KOMPONEN_DASAR_MESIN,SISTEM_PENGAPIAN,BAHAN_BAKAR_EMISI,CHASSIS_BODI'],
            'interval_km' => ['required', 'integer', 'min:500'],
            'interval_bulan' => ['nullable', 'integer', 'min:1'],
            'jenis_aksi_default' => ['required', 'in:P,G'],
        ]);

        $currentKm = $kendaraan->mileageTerbaru?->kilometer_akhir
            ?? $kendaraan->kilometer_terakhir
            ?? 0;

        $intervalKm = (int) $validated['interval_km'];
        $intervalBulan = (int) ($validated['interval_bulan'] ?? 6);
        $nextNomorUrut = ($kendaraan->komponen()->max('nomor_urut') ?? 0) + 1;

        KomponenKendaraan::create([
            'id_kendaraan' => $kendaraan->id,
            'nama_komponen' => $validated['nama_komponen'],
            'kategori' => $validated['kategori'],
            'nomor_urut' => $nextNomorUrut,
            'interval_km' => $intervalKm,
            'interval_bulan' => $intervalBulan,
            'jenis_aksi_default' => $validated['jenis_aksi_default'],
            'km_jatuh_tempo' => $currentKm + $intervalKm,
            'tgl_jatuh_tempo' => now()->addMonths($intervalBulan)->toDateString(),
            'status' => StatusKomponen::AMAN,
            'is_aktif' => true,
        ]);

        return back()->with('success', "Komponen '{$validated['nama_komponen']}' berhasil ditambahkan ke {$kendaraan->plat_nomor}.");
    }

    /**
     * Update komponen milik kendaraan tertentu.
     */
    public function update(Request $request, Kendaraan $kendaraan, KomponenKendaraan $komponen): RedirectResponse
    {
        abort_unless((int) $komponen->id_kendaraan === (int) $kendaraan->id, 404);

        $validated = $request->validate([
            'nama_komponen' => ['required', 'string', 'max:150'],
            'kategori' => ['required', 'in:KOMPONEN_DASAR_MESIN,SISTEM_PENGAPIAN,BAHAN_BAKAR_EMISI,CHASSIS_BODI'],
            'interval_km' => ['required', 'integer', 'min:500'],
            'interval_bulan' => ['nullable', 'integer', 'min:1'],
            'jenis_aksi_default' => ['required', 'in:P,G'],
            'is_aktif' => ['nullable', 'boolean'],
        ]);

        $komponen->update([
            'nama_komponen' => $validated['nama_komponen'],
            'kategori' => $validated['kategori'],
            'interval_km' => (int) $validated['interval_km'],
            'interval_bulan' => (int) ($validated['interval_bulan'] ?? 6),
            'jenis_aksi_default' => $validated['jenis_aksi_default'],
            'is_aktif' => (bool) ($validated['is_aktif'] ?? false),
        ]);

        return back()->with('success', "Komponen '{$komponen->nama_komponen}' berhasil diperbarui.");
    }

    /**
     * Hapus komponen dari kendaraan tertentu.
     */
    public function destroy(Kendaraan $kendaraan, KomponenKendaraan $komponen): RedirectResponse
    {
        abort_unless((int) $komponen->id_kendaraan === (int) $kendaraan->id, 404);

        $nama = $komponen->nama_komponen;

        // Jika sudah ada riwayat servis yang mencatat pengerjaan part ini
        if ($komponen->detailKomponenServis()->exists()) {
            $komponen->update(['is_aktif' => false]);

            return back()->with('info', "Komponen '{$nama}' telah memiliki riwayat servis. Komponen dinonaktifkan daripada dihapus permanen.");
        }

        $komponen->delete();

        return back()->with('success', "Komponen '{$nama}' berhasil dihapus dari {$kendaraan->plat_nomor}.");
    }
}
