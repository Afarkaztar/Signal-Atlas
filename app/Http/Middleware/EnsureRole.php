<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pembatasan akses berbasis peran. Contoh: ->middleware('role:admin,analyst')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->is_active) {
            Auth::logout();

            return redirect()->route('login')->withErrors(['email' => 'Akun Anda dinonaktifkan oleh administrator.']);
        }

        if ($roles && ! in_array($user->role, $roles, true)) {
            abort(403, 'Halaman ini hanya untuk peran: '.implode(', ', array_map(fn ($r) => config("signal.roles.$r", $r), $roles)));
        }

        return $next($request);
    }
}
