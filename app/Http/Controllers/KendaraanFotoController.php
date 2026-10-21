<?php

namespace App\Http\Controllers;

use App\Models\Kendaraan;
use App\Models\KendaraanFoto;
use App\Models\User;
use App\Services\ImageStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class KendaraanFotoController extends Controller
{
    private const MAX_FOTO = 5;

    /**
     * Upload satu atau lebih foto baru untuk kendaraan.
     */
    public function store(Request $request, Kendaraan $kendaraan): RedirectResponse
    {
        $this->authorizeKendaraan($request->user(), $kendaraan);

        $request->validate([
            'foto_kendaraan' => ['required', 'array', 'min:1'],
            'foto_kendaraan.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $currentCount = $kendaraan->fotos()->count();
        $uploadedFiles = $request->file('foto_kendaraan');
        $remaining = self::MAX_FOTO - $currentCount;

        if ($remaining <= 0) {
            return back()->withErrors(['foto_kendaraan' => 'Maksimal '.self::MAX_FOTO.' foto per kendaraan sudah tercapai.']);
        }

        $filesToProcess = array_slice($uploadedFiles, 0, $remaining);
        $storedPaths = [];

        try {
            foreach ($filesToProcess as $file) {
                $storedPaths[] = app(ImageStorageService::class)->store($file, 'kendaraan');
            }

            DB::transaction(function () use ($kendaraan, $storedPaths, $currentCount): void {
                $hasUtama = $kendaraan->fotos()->where('is_utama', true)->exists();

                foreach ($storedPaths as $index => $path) {
                    KendaraanFoto::create([
                        'id_kendaraan' => $kendaraan->id,
                        'path' => $path,
                        'is_utama' => (! $hasUtama && $index === 0),
                        'urutan' => $currentCount + $index,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                app(ImageStorageService::class)->deleteSafely($path);
            }

            throw $exception;
        }

        $skipped = count($uploadedFiles) - count($filesToProcess);
        $message = count($filesToProcess).' foto berhasil diunggah.';

        if ($skipped > 0) {
            $message .= " {$skipped} foto dilewati karena batas maksimal ".self::MAX_FOTO.' foto sudah tercapai.';
        }

        return redirect()->route('kendaraan.edit', $kendaraan)->with('success', $message);
    }

    /**
     * Hapus satu foto dari kendaraan.
     */
    public function destroy(Request $request, Kendaraan $kendaraan, KendaraanFoto $kendaraanFoto): RedirectResponse
    {
        $this->authorizeKendaraan($request->user(), $kendaraan);

        abort_unless((int) $kendaraanFoto->id_kendaraan === (int) $kendaraan->id, 404);

        $wasUtama = $kendaraanFoto->is_utama;
        $path = $kendaraanFoto->path;

        DB::transaction(function () use ($kendaraanFoto, $kendaraan, $wasUtama): void {
            $kendaraanFoto->delete();

            // Jika foto yang dihapus adalah foto utama, promosikan foto berikutnya.
            if ($wasUtama) {
                $promoted = $kendaraan->fotos()->orderBy('urutan')->orderBy('id')->first();
                $promoted?->update(['is_utama' => true, 'urutan' => 0]);
            }

            // Normalisasi urutan foto yang tersisa agar tetap rapi 0, 1, 2...
            $remaining = $kendaraan->fotos()->orderByDesc('is_utama')->orderBy('urutan')->orderBy('id')->get();
            foreach ($remaining as $index => $foto) {
                $foto->update(['urutan' => $index]);
            }
        });

        app(ImageStorageService::class)->deleteSafely($path);

        return redirect()->route('kendaraan.edit', $kendaraan)->with('success', 'Foto berhasil dihapus.');
    }

    /**
     * Jadikan foto ini sebagai foto utama (cover).
     */
    public function setUtama(Request $request, Kendaraan $kendaraan, KendaraanFoto $kendaraanFoto): RedirectResponse
    {
        $this->authorizeKendaraan($request->user(), $kendaraan);

        abort_unless((int) $kendaraanFoto->id_kendaraan === (int) $kendaraan->id, 404);

        DB::transaction(function () use ($kendaraan, $kendaraanFoto): void {
            $kendaraan->fotos()->update(['is_utama' => false]);
            $kendaraanFoto->update([
                'is_utama' => true,
                'urutan' => 0,
            ]);

            $otherFotos = $kendaraan->fotos()
                ->where('id', '!=', $kendaraanFoto->id)
                ->orderBy('urutan')
                ->orderBy('id')
                ->get();

            foreach ($otherFotos as $index => $foto) {
                $foto->update(['urutan' => $index + 1]);
            }
        });

        return redirect()->route('kendaraan.edit', $kendaraan)->with('success', 'Foto utama berhasil diubah.');
    }

    /**
     * Pengelola hanya boleh mengelola foto kendaraan yang menjadi tanggung jawabnya.
     */
    private function authorizeKendaraan(User $user, Kendaraan $kendaraan): void
    {
        abort_unless(
            $user->isAdmin() || (int) $kendaraan->id_pengelola === (int) $user->id,
            403,
        );
    }
}
