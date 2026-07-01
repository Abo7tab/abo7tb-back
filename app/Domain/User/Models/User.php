<?php

namespace App\Domain\User\Models;

use App\Domain\Shared\Traits\Auditable;
use App\Domain\Shared\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuid, Auditable, Notifiable, SoftDeletes;

    protected $table = 'users';

    /**
     * الحقول القابلة للإضافة — مطابقة لأعمدة الـ DB الفعلية
     * DB columns: id, uuid, name, email, password_hash, phone,
     *             profile_image, subscription_plan, subscription_expires_at,
     *             email_verified_at, last_login_at, last_login_ip,
     *             is_active, created_at, updated_at
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password_hash',   // الحقل الفعلي في DB
        'phone',
        'profile_image',   // مش avatar
        'subscription_plan',
        'subscription_expires_at',
        'is_active',
        'email_verified_at',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * الحقول المخفية في الاستجابة
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * تعريف Laravel بأن حقل المصادقة هو password_hash
     */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash ?? '';
    }

    /**
     * Casts
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'last_login_at'           => 'datetime',
            'subscription_expires_at' => 'date',
            'is_active'               => 'boolean',
            // لا نضع 'password' => 'hashed' لأن الحقل اسمه password_hash
        ];
    }

    // ==================== Relations ====================

    public function devices()
    {
        return $this->hasMany(\App\Domain\Device\Models\Device::class);
    }
}
