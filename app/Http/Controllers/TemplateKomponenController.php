<?php

namespace App\Http\Controllers;

use App\Enums\JenisAksi;
use App\Enums\KategoriKomponen;
use App\Models\TemplateJadwalDetail;
use App\Models\TemplateJadwalServis;
use App\Models\TemplateKomponen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TemplateKomponenController extends Controller
{
    public function index(Request $request): View
    {
        $templates = TemplateJadwalServis::withCount('komponen')->orderBy('id')->get();
        $selectedId = $request->query('template_id', $templates->first()?->id);
        $selectedTemplate = $templates->firstWhere('id', $selectedId) ?? $templates->first();

        $komponens = collect();
        if ($selectedTemplate) {
            $komponens = TemplateKomponen::where('id_template', $selectedTemplate->id)
                ->with(['jadwalDetail' => fn ($q) => $q->orderBy('interval_km')])
                ->orderBy('nomor_urut')
                ->get();
        }

        return view('template-komponen.index', compact('templates', 'selectedTemplate', 'komponens'));
    }

    public function create(Request $request): View
    {
        $templates = TemplateJadwalServis::where('is_aktif', true)->orderBy('nama')->get();
        $selectedTemplateId = $request->query('template_id', $templates->first()?->id);
        $kategoriList = KategoriKomponen::cases();

        return view('template-komponen.create', compact('templates', 'selectedTemplateId', 'kategoriList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'id_template' => ['required', 'exists:template_jadwal_servis,id'],
            'nama_komponen' => ['required', 'string', 'max:150'],
            'kategori' => ['required', 'in:KOMPONEN_DASAR_MESIN,SISTEM_PENGAPIAN,BAHAN_BAKAR_EMISI,CHASSIS_BODI'],
            'nomor_urut' => ['nullable', 'integer', 'min:1'],
            'is_aktif' => ['nullable', 'boolean'],
            'jadwal' => ['nullable', 'array'],
            'jadwal.*.interval_km' => ['required_with:jadwal', 'integer', 'min:1'],
            'jadwal.*.interval_bulan' => ['nullable', 'integer', 'min:0'],
            'jadwal.*.jenis_aksi' => ['required_with:jadwal', 'in:P,G'],
        ]);

        $idTemplate = (int) $validated['id_template'];
        $nomorUrut = $validated['nomor_urut'] ?? (TemplateKomponen::where('id_template', $idTemplate)->max('nomor_urut') + 1);

        DB::transaction(function () use ($validated, $idTemplate, $nomorUrut) {
            $komponen = TemplateKomponen::create([
                'id_template' => $idTemplate,
                'nomor_urut' => $nomorUrut,
                'nama_komponen' => $validated['nama_komponen'],
                'kategori' => $validated['kategori'],
                'is_aktif' => (bool) ($validated['is_aktif'] ?? true),
            ]);

            if (! empty($validated['jadwal'])) {
                foreach ($validated['jadwal'] as $row) {
                    if (empty($row['interval_km'])) {
                        continue;
                    }
                    TemplateJadwalDetail::create([
                        'id_template_komponen' => $komponen->id,
                        'interval_km' => (int) $row['interval_km'],
                        'interval_bulan' => (int) ($row['interval_bulan'] ?? 0),
                        'jenis_aksi' => $row['jenis_aksi'],
                    ]);
                }
            }
        });

        return redirect()->route('template-komponen.index', ['template_id' => $idTemplate])
            ->with('success', 'Komponen berhasil ditambahkan ke template.');
    }

    public function edit(TemplateKomponen $templateKomponen): View
    {
        $templateKomponen->load([
            'template',
            'jadwalDetail' => fn ($q) => $q->orderBy('interval_km'),
        ]);

        $kategoriList = KategoriKomponen::cases();
        $aksiList = JenisAksi::cases();

        return view('template-komponen.edit', compact('templateKomponen', 'kategoriList', 'aksiList'));
    }

    public function update(Request $request, TemplateKomponen $templateKomponen): RedirectResponse
    {
        $validated = $request->validate([
            'nama_komponen' => ['required', 'string', 'max:150'],
            'kategori' => ['required', 'in:KOMPONEN_DASAR_MESIN,SISTEM_PENGAPIAN,BAHAN_BAKAR_EMISI,CHASSIS_BODI'],
            'nomor_urut' => ['required', 'integer', 'min:1'],
            'is_aktif' => ['nullable', 'boolean'],
            'jadwal' => ['nullable', 'array'],
            'jadwal.*.interval_km' => ['required_with:jadwal', 'integer', 'min:1'],
            'jadwal.*.interval_bulan' => ['nullable', 'integer', 'min:0'],
            'jadwal.*.jenis_aksi' => ['required_with:jadwal', 'in:P,G'],
        ]);

        DB::transaction(function () use ($validated, $templateKomponen) {
            $templateKomponen->update([
                'nama_komponen' => $validated['nama_komponen'],
                'kategori' => $validated['kategori'],
                'nomor_urut' => (int) $validated['nomor_urut'],
                'is_aktif' => (bool) ($validated['is_aktif'] ?? false),
            ]);

            // Bersihkan jadwal lama dan set ulang
            TemplateJadwalDetail::where('id_template_komponen', $templateKomponen->id)->delete();

            if (! empty($validated['jadwal'])) {
                foreach ($validated['jadwal'] as $row) {
                    if (empty($row['interval_km'])) {
                        continue;
                    }
                    TemplateJadwalDetail::create([
                        'id_template_komponen' => $templateKomponen->id,
                        'interval_km' => (int) $row['interval_km'],
                        'interval_bulan' => (int) ($row['interval_bulan'] ?? 0),
                        'jenis_aksi' => $row['jenis_aksi'],
                    ]);
                }
            }
        });

        return redirect()->route('template-komponen.index', ['template_id' => $templateKomponen->id_template])
            ->with('success', "Komponen '{$templateKomponen->nama_komponen}' berhasil diperbarui.");
    }

    public function destroy(TemplateKomponen $templateKomponen): RedirectResponse
    {
        $idTemplate = $templateKomponen->id_template;
        $nama = $templateKomponen->nama_komponen;

        $terpakai = $templateKomponen->pengajuanKomponen()->exists()
            || $templateKomponen->detailKomponenServis()->exists()
            || $templateKomponen->statusKendaraan()->exists();

        if ($terpakai) {
            $templateKomponen->update(['is_aktif' => false]);

            return redirect()->route('template-komponen.index', ['template_id' => $idTemplate])
                ->with('info', "Komponen '{$nama}' telah digunakan dalam data servis. Komponen telah dinonaktifkan daripada dihapus permanen.");
        }

        $templateKomponen->jadwalDetail()->delete();
        $templateKomponen->delete();

        return redirect()->route('template-komponen.index', ['template_id' => $idTemplate])
            ->with('success', "Komponen '{$nama}' berhasil dihapus.");
    }
}
