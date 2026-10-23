<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\Notifikasi;
use App\Models\PengajuanServis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceApprovalController extends Controller
{
    public function approve(int $id): RedirectResponse
    {
        DB::transaction(function () use ($id): void {
            $pengajuan = PengajuanServis::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $pengajuan->update([
                'status_persetujuan' => 'DISETUJUI',
                'id_disetujui_oleh' => Auth::id(),
            ]);

            Kendaraan::query()
                ->whereKey($pengajuan->id_kendaraan)
                ->update(['status_perawatan' => 'SEDANG_SERVIS']);

            $this->createNotification(
                $pengajuan->id_pengaju,
                'Pengajuan Servis Disetujui',
                sprintf('Pengajuan servis #%d telah disetujui.', $pengajuan->id),
            );
        });

        return back()->with('status', 'pengajuan-servis-approved');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'alasan_penolakan' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($id, $validated): void {
            $pengajuan = PengajuanServis::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $pengajuan->update([
                'status_persetujuan' => 'DITOLAK',
                'alasan_penolakan' => $validated['alasan_penolakan'],
                'id_disetujui_oleh' => Auth::id(),
            ]);

            $this->createNotification(
                $pengajuan->id_pengaju,
                'Pengajuan Servis Ditolak',
                sprintf(
                    'Pengajuan servis #%d ditolak. Alasan: %s',
                    $pengajuan->id,
                    $validated['alasan_penolakan'],
                ),
            );
        });

        return back()->with('status', 'pengajuan-servis-rejected');
    }

    private function createNotification(int $penggunaId, string $judul, string $pesan): void
    {
        Notifikasi::query()->create([
            'id_pengguna' => $penggunaId,
            'judul' => $judul,
            'pesan' => $pesan,
            'sudah_dibaca' => false,
            'tipe_referensi' => 'pengajuan_servis',
            'dibuat_pada' => now(),
        ]);
    }
}
