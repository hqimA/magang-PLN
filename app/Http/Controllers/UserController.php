<?php

namespace App\Http\Controllers;

use App\Enums\UnitPln;
use App\Models\Kendaraan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->addSelect([
                'kendaraan_count' => Kendaraan::query()
                    ->selectRaw('count(*)')
                    ->whereColumn('kendaraan.unit_pln', 'users.unit_pln'),
            ])
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
            'unitPln' => UnitPln::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'peran' => ['required', Rule::in(['ADMIN', 'PENGELOLA'])],
            'unit_pln' => [
                'required_if:peran,PENGELOLA',
                'nullable',
                Rule::in(array_column(UnitPln::cases(), 'value')),
            ],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'peran' => $validated['peran'],
            'unit_pln' => $validated['peran'] === 'PENGELOLA'
                ? $validated['unit_pln']
                : null,
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('user.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user): View
    {
        return view('user.edit', [
            'user' => $user,
            'unitPln' => UnitPln::cases(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'peran' => ['required', Rule::in(['ADMIN', 'PENGELOLA'])],
            'unit_pln' => [
                'required_if:peran,PENGELOLA',
                'nullable',
                Rule::in(array_column(UnitPln::cases(), 'value')),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        // Admin tidak dapat menurunkan peran akunnya sendiri.
        abort_if(
            $user->is($request->user()) && $validated['peran'] !== 'ADMIN',
            403,
            'Peran akun sendiri tidak dapat diubah.',
        );

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'peran' => $validated['peran'],
            'unit_pln' => $validated['peran'] === 'PENGELOLA'
                ? $validated['unit_pln']
                : null,
        ]);

        if (filled($validated['password'] ?? null)) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

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

        if ($user->unit_pln !== null
            && User::query()
                ->where('peran', 'PENGELOLA')
                ->where('unit_pln', $user->unit_pln->value)
                ->where('id', '!=', $user->id)
                ->doesntExist()
            && Kendaraan::query()->where('unit_pln', $user->unit_pln->value)->exists()) {
            return redirect()
                ->route('user.index')
                ->with('error', 'Pengelola terakhir pada Unit PLN ini tidak dapat dihapus selama masih ada kendaraan di unit tersebut.');
        }

        $user->delete();

        return redirect()->route('user.index')->with('success', 'User berhasil dihapus.');
    }
}
