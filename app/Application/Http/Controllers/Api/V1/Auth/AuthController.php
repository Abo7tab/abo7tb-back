<?php

namespace App\Application\Http\Controllers\Api\V1\Auth;

use App\Application\Http\Controllers\Controller;
use App\Domain\User\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/v1/auth/register
     * تسجيل حساب أب جديد
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users,email',
            'password'              => 'required|string|min:8|confirmed',
            'phone'                 => 'sometimes|string|max:20',
        ]);

        $user = User::create([
            'name'              => $request->name,
            'email'             => $request->email,
            'password_hash'     => Hash::make($request->password),
            'phone'             => $request->phone,
            'subscription_plan' => 'free',
            'is_active'         => true,
        ]);

        $token = $user->createToken('parent-app')->plainTextToken;

        return $this->success([
            'user'  => [
                'id'                => $user->id,
                'uuid'              => $user->uuid,
                'name'              => $user->name,
                'email'             => $user->email,
                'subscription_plan' => $user->subscription_plan,
            ],
            'token' => $token,
        ], 'تم إنشاء الحساب بنجاح.', 201);
    }

    /**
     * POST /api/v1/auth/login
     * تسجيل الدخول
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        // نتحقق من password_hash أولاً، ثم password كـ fallback
        $storedHash = $user->password_hash ?? $user->password ?? '';
        if (!$user || !Hash::check($request->password, $storedHash)) {
            return $this->error('البريد الإلكتروني أو كلمة المرور غير صحيحة.', 401);
        }

        if (!$user->is_active) {
            return $this->error('هذا الحساب موقوف. تواصل مع الدعم.', 403);
        }

        // تحديث آخر تسجيل دخول
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        $token = $user->createToken('parent-app')->plainTextToken;

        return $this->success([
            'user' => [
                'id'                => $user->id,
                'uuid'              => $user->uuid,
                'name'              => $user->name,
                'email'             => $user->email,
                'subscription_plan' => $user->subscription_plan,
                'last_login_at'     => $user->last_login_at?->toIso8601String(),
            ],
            'token' => $token,
        ], 'مرحباً ' . $user->name);
    }

    /**
     * POST /api/v1/auth/logout
     * تسجيل الخروج
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'تم تسجيل الخروج بنجاح.');
    }

    /**
     * GET /api/v1/auth/me
     * بيانات المستخدم الحالي
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success([
            'id'                      => $user->id,
            'uuid'                    => $user->uuid,
            'name'                    => $user->name,
            'email'                   => $user->email,
            'phone'                   => $user->phone,
            'subscription_plan'       => $user->subscription_plan,
            'subscription_expires_at' => $user->subscription_expires_at?->toDateString(),
            'is_active'               => $user->is_active,
            'devices_count'           => $user->devices()->where('is_active', true)->count(),
        ]);
    }

    /**
     * POST /api/v1/auth/refresh
     * تجديد الـ Token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $token = $user->createToken('parent-app')->plainTextToken;

        return $this->success(['token' => $token], 'تم تجديد الرمز.');
    }
}
