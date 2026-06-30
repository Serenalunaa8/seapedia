<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/register
     * User pilih sendiri role apa saja yang mau didaftarkan (kecuali admin).
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'phone' => $validated['phone'] ?? null,
            ]);

            $roleIds = Role::whereIn('name', $validated['roles'])->pluck('id', 'name');
            $user->roles()->attach($roleIds->values());

            return $user;
        });

        return response()->json([
            'message' => 'Registrasi berhasil. Silakan login.',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'roles' => $user->roles()->pluck('name'),
            ],
        ], 201);
    }

    /**
     * POST /api/login
     * Login pakai username atau email. Token dibuat TANPA active_role dulu.
     */
    public function login(LoginRequest $request)
    {
        $login = $request->validated()['login'];
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $login)->first();

        if (!$user || !Hash::check($request->validated()['password'], $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Username/email atau password salah.'],
            ]);
        }

        $roles = $user->roles()->pluck('name');
        $nonAdminRoles = $roles->reject(fn ($r) => $r === 'admin')->values();

        $token = $user->createToken('seapedia-session');

        // Kalau cuma punya 1 role non-admin (dan bukan admin), langsung set active role otomatis.
        // Kalau admin, juga langsung aktifkan role admin.
        if ($roles->contains('admin')) {
            $token->accessToken->active_role_id = Role::where('name', 'admin')->value('id');
            $token->accessToken->save();
        } elseif ($nonAdminRoles->count() === 1) {
            $token->accessToken->active_role_id = Role::where('name', $nonAdminRoles->first())->value('id');
            $token->accessToken->save();
        }
        // Kalau >1 role non-admin, active_role_id dibiarkan null -> frontend wajib panggil /api/select-role

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'roles' => $roles,
            ],
            'active_role' => $token->accessToken->activeRole?->name,
            'needs_role_selection' => is_null($token->accessToken->active_role_id),
        ]);
    }

    /**
     * POST /api/select-role
     * Dipanggil setelah login kalau user punya >1 role non-admin.
     */
    public function selectRole(Request $request)
    {
        $request->validate([
            'role' => ['required', 'string', 'in:buyer,seller,driver'], // admin tidak lewat sini
        ]);

        $user = $request->user();
        $token = $user->currentAccessToken();

        $hasRole = $user->roles()->where('name', $request->role)->exists();

        if (!$hasRole) {
            return response()->json([
                'message' => 'Kamu tidak memiliki role ini.',
            ], 403);
        }

        $token->active_role_id = Role::where('name', $request->role)->value('id');
        $token->save();

        return response()->json([
            'message' => "Active role diset ke {$request->role}.",
            'active_role' => $request->role,
        ]);
    }

    /**
     * GET /api/me
     * Profil user yang sedang login + semua role miliknya + active role sesi ini.
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => $user->roles()->pluck('name'),
            'active_role' => $token->activeRole?->name,
        ]);
    }

    /**
     * POST /api/logout
     * Hapus token sesi saat ini saja (bukan semua token/device).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }
}