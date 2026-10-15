<?php

namespace App\Http\Controllers;

use App\Enums\KategoriPenggunaan;
use App\Models\User;
use App\Services\VehicleCategoryAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $peran = (string) $request->query('peran', '');

        $users = User::query()
            ->withCount('kendaraan')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(in_array($peran, ['ADMIN', 'PENGELOLA'], true), fn ($query) => $query->where('peran', $peran))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('user.index', compact('users', 'search', 'peran'));
    }

    public function create(): View
    {
        return view('user.create', [
            'kategoriKendaraan' => KategoriPenggunaan::cases(),
        ]);
    }

    public function store(Request $request, VehicleCategoryAssignmentService $vehicleAssignment): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'peran' => ['required', Rule::in(['ADMIN', 'PENGELOLA'])],
            'kategori_kendaraan' => [
                'required_if:peran,PENGELOLA',
                'nullable',
                Rule::in(array_column(KategoriPenggunaan::cases(), 'value')),
                Rule::unique('users', 'kategori_kendaraan'),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], [
            'kategori_kendaraan.unique' => 'Kategori kendaraan ini sudah ditugaskan ke Pengelola lain.',
        ]);

        DB::transaction(function () use ($validated, $vehicleAssignment): void {
            $manager = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'peran' => $validated['peran'],
                'kategori_kendaraan' => $validated['peran'] === 'PENGELOLA'
                    ? $validated['kategori_kendaraan']
                    : null,
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]);

            $vehicleAssignment->assignUnassignedVehiclesToManager($manager);
        });

        return redirect()->route('user.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('user.edit', [
            'user' => $user,
            'kategoriKendaraan' => KategoriPenggunaan::cases(),
        ]);
    }

    public function update(
        Request $request,
        User $user,
        VehicleCategoryAssignmentService $vehicleAssignment,
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'peran' => ['required', Rule::in(['ADMIN', 'PENGELOLA'])],
            'kategori_kendaraan' => [
                'required_if:peran,PENGELOLA',
                'nullable',
                Rule::in(array_column(KategoriPenggunaan::cases(), 'value')),
                Rule::unique('users', 'kategori_kendaraan')->ignore($user->id),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [
            'kategori_kendaraan.unique' => 'Kategori kendaraan ini sudah ditugaskan ke Pengelola lain.',
        ]);

        // Admin tidak dapat menurunkan peran akunnya sendiri.
        abort_if(
            $user->is($request->user()) && $validated['peran'] !== 'ADMIN',
            403,
            'Peran akun sendiri tidak dapat diubah.',
        );

        DB::transaction(function () use ($user, $validated, $vehicleAssignment): void {
            $user->fill([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'peran' => $validated['peran'],
                'kategori_kendaraan' => $validated['peran'] === 'PENGELOLA'
                    ? $validated['kategori_kendaraan']
                    : null,
            ]);

            if (filled($validated['password'] ?? null)) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
            $vehicleAssignment->assignUnassignedVehiclesToManager($user);
        });

        return redirect()->route('user.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Admin tidak dapat menghapus akunnya sendiri.
        abort_if($user->is($request->user()), 403, 'Akun sendiri tidak dapat dihapus.');

        // Cegah penghapusan bila masih memiliki kendaraan.
        if ($user->kendaraan()->exists()) {
            return redirect()
                ->route('user.index')
                ->with('error', 'User masih memiliki kendaraan dan tidak dapat dihapus.');
        }

        $user->delete();

        return redirect()->route('user.index')->with('success', 'User berhasil dihapus.');
    }
}
