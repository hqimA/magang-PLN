<?php

namespace App\Http\Controllers;

use App\Enums\JenisBbmTemplate;
use App\Enums\TransmisiTemplate;
use App\Models\TemplateJadwalDetail;
use App\Models\TemplateJadwalServis;
use App\Models\TemplateKomponen;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TemplateJadwalServisController extends Controller
{
    public function index(): RedirectResponse
    {
        return redirect()->route('template-komponen.index');
    }

    public function create(): View
    {
        $jenisBbmList = JenisBbmTemplate::cases();
        $transmisiList = TransmisiTemplate::cases();

        return view('template-jadwal-servis.create', compact('jenisBbmList', 'transmisiList'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:template_jadwal_servis,nama'],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'jenis_bbm' => ['required', 'in:BENSIN,SOLAR,DIESEL,LISTRIK,SEMUA'],
            'transmisi' => ['required', 'in:MANUAL,OTOMATIS,SEMUA'],
            'is_aktif' => ['nullable', 'boolean'],
        ]);

        $template = TemplateJadwalServis::create([
            'nama' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'jenis_bbm' => $validated['jenis_bbm'],
            'transmisi' => $validated['transmisi'],
            'is_aktif' => (bool) ($validated['is_aktif'] ?? true),
        ]);

        return redirect()->route('template-komponen.index', ['template_id' => $template->id])
            ->with('success', "Template '{$template->nama}' berhasil dibuat. Silakan tambahkan komponen untuk template ini.");
    }

    public function edit(TemplateJadwalServis $templateJadwalServi): View
    {
        $template = $templateJadwalServi;
        $jenisBbmList = JenisBbmTemplate::cases();
        $transmisiList = TransmisiTemplate::cases();

        return view('template-jadwal-servis.edit', compact('template', 'jenisBbmList', 'transmisiList'));
    }

    public function update(Request $request, TemplateJadwalServis $templateJadwalServi): RedirectResponse
    {
        $template = $templateJadwalServi;

        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:template_jadwal_servis,nama,'.$template->id],
            'deskripsi' => ['nullable', 'string', 'max:255'],
            'jenis_bbm' => ['required', 'in:BENSIN,SOLAR,DIESEL,LISTRIK,SEMUA'],
            'transmisi' => ['required', 'in:MANUAL,OTOMATIS,SEMUA'],
            'is_aktif' => ['nullable', 'boolean'],
        ]);

        $template->update([
            'nama' => $validated['nama'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'jenis_bbm' => $validated['jenis_bbm'],
            'transmisi' => $validated['transmisi'],
            'is_aktif' => (bool) ($validated['is_aktif'] ?? false),
        ]);

        return redirect()->route('template-komponen.index', ['template_id' => $template->id])
            ->with('success', "Template '{$template->nama}' berhasil diperbarui.");
    }

    public function destroy(TemplateJadwalServis $templateJadwalServi): RedirectResponse
    {
        $template = $templateJadwalServi;
        $nama = $template->nama;

        // Cek apakah ada kendaraan yang memakai template ini
        if ($template->kendaraan()->exists()) {
            $count = $template->kendaraan()->count();
            $template->update(['is_aktif' => false]);

            return redirect()->route('template-komponen.index')
                ->with('info', "Template '{$nama}' sedang terhubung dengan {$count} unit kendaraan. Template dinonaktifkan daripada dihapus permanen untuk menjaga relasi data.");
        }

        DB::transaction(function () use ($template) {
            $komponenIds = $template->komponen()->pluck('id');
            TemplateJadwalDetail::whereIn('id_template_komponen', $komponenIds)->delete();
            TemplateKomponen::whereIn('id', $komponenIds)->delete();
            $template->delete();
        });

        return redirect()->route('template-komponen.index')
            ->with('success', "Template '{$nama}' beserta seluruh komponennya berhasil dihapus.");
    }
}
