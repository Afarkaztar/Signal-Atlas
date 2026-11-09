<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\SystemNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email atau kata sandi tidak cocok.'])->onlyInput('email');
        }

        if (! Auth::user()->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'Akun Anda dinonaktifkan. Hubungi administrator.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        Auth::user()->update(['last_login_at' => now()]);
        ActivityLogger::log('auth.login', 'Masuk ke sistem');

        return redirect()->intended(route('dashboard'))
            ->with('toast', ['success', 'Selamat datang kembali, '.Auth::user()->name.'!']);
    }

    public function showRegister(): View
    {
        return view('auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'institution' => ['nullable', 'string', 'max:120'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Anda harus menyetujui pedoman pelaporan.',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'institution' => $data['institution'] ?? null,
            'password' => $data['password'],
            'role' => 'contributor',
            'last_login_at' => now(),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $user->notify(new SystemNotification(
            'Selamat bergabung di SignalAtlas!',
            'Mulai dengan menjalankan Speed Test atau kirim laporan sinyal pertama Anda.',
            route('speedtest'),
            'sparkles',
            'success',
        ));
        ActivityLogger::log('auth.register', "Pengguna baru terdaftar: {$user->email}", $user);

        return redirect()->route('dashboard')->with('toast', ['success', 'Akun berhasil dibuat. Selamat datang!']);
    }

    public function logout(Request $request): RedirectResponse
    {
        ActivityLogger::log('auth.logout', 'Keluar dari sistem');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('toast', ['info', 'Anda telah keluar.']);
    }
}
