<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::withCount('reports')
            ->when($request->query('q'), fn ($q, $v) => $q->where(fn ($w) => $w->where('name', 'like', "%$v%")->orWhere('email', 'like', "%$v%")))
            ->when($request->query('role'), fn ($q, $v) => $q->where('role', $v))
            ->orderByRaw("CASE role WHEN 'admin' THEN 0 WHEN 'analyst' THEN 1 ELSE 2 END")
            ->orderBy('name')
            ->paginate(12)->withQueryString();

        $roleCounts = User::selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role');

        return view('users.index', compact('users', 'roleCounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'role' => ['required', Rule::in(array_keys(config('signal.roles')))],
            'institution' => ['nullable', 'string', 'max:120'],
            'password' => ['required', Password::min(8)],
        ]);

        $user = User::create($data);
        ActivityLogger::log('user.create', "Membuat akun {$user->email} ({$user->role})", $user);

        return back()->with('toast', ['success', "Akun {$user->name} dibuat."]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::in(array_keys(config('signal.roles')))],
        ]);

        if ($user->id === $request->user()->id && $data['role'] !== 'admin') {
            return back()->with('toast', ['danger', 'Anda tidak dapat menurunkan peran akun sendiri.']);
        }

        $old = $user->role;
        $user->update($data);
        $user->notify(new SystemNotification('Peran akun diperbarui', 'Peran Anda kini: '.$user->roleLabel(), route('dashboard'), 'shield', 'info'));
        ActivityLogger::log('user.update', "Mengubah peran {$user->email}: {$old} → {$user->role}", $user);

        return back()->with('toast', ['success', "Peran {$user->name} diperbarui menjadi {$user->roleLabel()}."]);
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'Tidak dapat menonaktifkan akun sendiri.');
        $user->update(['is_active' => ! $user->is_active]);
        ActivityLogger::log('user.update', ($user->is_active ? 'Mengaktifkan' : 'Menonaktifkan')." akun {$user->email}", $user);

        return back()->with('toast', ['success', "Akun {$user->name} ".($user->is_active ? 'diaktifkan' : 'dinonaktifkan').'.']);
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->id === $request->user()->id, 422, 'Tidak dapat menghapus akun sendiri.');
        ActivityLogger::log('user.delete', "Menghapus akun {$user->email}", $user);
        $user->delete();

        return back()->with('toast', ['success', 'Akun dihapus.']);
    }
}
