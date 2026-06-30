<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware ini SELALU mengecek active_role pada token sesi saat ini,
 * bukan daftar seluruh role yang dimiliki user.
 *
 * Pemakaian di routes/api.php:
 *   Route::middleware(['auth:sanctum', 'active.role:seller'])->group(...)
 */
class EnsureActiveRole
{
    public function handle(Request $request, Closure $next, string $requiredRole): Response
    {
        $token = $request->user()?->currentAccessToken();

        if (!$token || !$token->active_role_id) {
            return response()->json([
                'message' => 'Belum memilih active role untuk sesi ini.',
            ], 409); // 409 = perlu pilih role dulu via /api/select-role
        }

        $activeRoleName = $token->activeRole?->name;

        if ($activeRoleName !== $requiredRole) {
            return response()->json([
                'message' => "Aksi ini hanya untuk role aktif: {$requiredRole}.",
            ], 403);
        }

        return $next($request);
    }
}