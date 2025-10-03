<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
{
    $user = Auth::user();

    // Cek role utama (admin/guru/orangtua)
    if (!in_array($user->role, $roles)) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    // Jika guru, cek type
    if ($user->role === 'guru') {
        $guru = $user->guru; // relasi User -> Guru

        // Jika route butuh wali kelas
        if (in_array('walikelas', $roles) && $guru->type !== 'walikelas') {
            return response()->json(['message' => 'Hanya wali kelas yang bisa mengakses'], 403);
        }

        // Jika route butuh guru mapel
        if (in_array('gurumapel', $roles) && $guru->type !== 'gurumapel') {
            return response()->json(['message' => 'Hanya guru mapel yang bisa mengakses'], 403);
        }
    }

    return $next($request);
}

}
