<?php

namespace App\Http\Controllers;

use App\Exports\KendaraanImportTemplateExport;
use App\Imports\KendaraanImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KendaraanImportController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'extensions:xlsx,xls', 'max:10240'],
        ]);
        $import = new KendaraanImport;
        $import->import($validated['file']);

        return redirect()->route('kendaraan.index')->with(
            'success',
            "Import berhasil: {$import->importedCount()} kendaraan ditambahkan.",
        );
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(
            new KendaraanImportTemplateExport,
            'template-impor-kendaraan.xlsx',
        );
    }
}
