<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $stats = [
            'reports' => $user->reports()->count(),
            'verified' => $user->reports()->whereIn('status', ['verified', 'resolved'])->count(),
            'resolved' => $user->reports()->where('status', 'resolved')->count(),
        ];

        return view('profile.edit', compact('user', 'stats'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user)],
            'institution' => ['nullable', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);
        $user->update($data);
        ActivityLogger::log('user.update', 'Memperbarui profil', $user);

        return back()->with('toast', ['success', 'Profil berhasil diperbarui.']);
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], ['current_password.current_password' => 'Kata sandi saat ini salah.']);

        $request->user()->update(['password' => $data['password']]);
        ActivityLogger::log('user.update', 'Mengganti kata sandi', $request->user());

        return back()->with('toast', ['success', 'Kata sandi berhasil diganti.']);
    }
}
