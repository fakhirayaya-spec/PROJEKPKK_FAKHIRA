<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Contoh pemakaian di route: ->middleware('role:admin') atau ->middleware('role:admin,kasir')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // Akun yang dinonaktifkan admin langsung dikeluarkan, termasuk yang sedang login
        $pengguna = $request->user();
        if ($pengguna && ($pengguna->status ?? 'aktif') !== 'aktif') {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun kamu sedang dinonaktifkan oleh admin. Hubungi admin untuk mengaktifkan kembali.',
            ]);
        }

        if (!$request->user() || !in_array($request->user()->role, $roles)) {
            abort(403, 'Kamu tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
