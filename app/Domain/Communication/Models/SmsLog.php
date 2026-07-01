<?php

namespace App\Domain\Communication\Models;

use App\Domain\Device\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsLog extends Model
{
    protected $table = 'sms_logs';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'phone_number',
        'phone_hash',
        'contact_name',
        'message_type',
        'message_body',
        'direction',
        'is_read',
        'is_unknown',
        'parent_read',
        'sent_at',
    ];

    protected $casts = [
        'is_unknown'   => 'boolean',
        'parent_read'  => 'boolean',
        'sent_at'      => 'datetime',
        'phone_number' => 'encrypted',
        'contact_name' => 'encrypted',
        'message_body' => 'encrypted',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Helpers ====================

    public function getPreviewAttribute(): string
    {
        return mb_substr($this->message_body ?? '', 0, 100)
            . (mb_strlen($this->message_body ?? '') > 100 ? '...' : '');
    }

    public function isIncoming(): bool
    {
        return $this->message_type === 'received';
    }

    public function isOutgoing(): bool
    {
        return $this->message_type === 'sent';
    }

    public function getDirectionAttribute(): string
    {
        return $this->message_type === 'sent' ? 'outgoing' : 'incoming';
    }

    public function setDirectionAttribute(string $value): void
    {
        $this->attributes['message_type'] = $value === 'outgoing' ? 'sent' : 'received';
    }

    public function getIsReadAttribute(): bool
    {
        return (bool) $this->parent_read;
    }

    public function setIsReadAttribute(bool $value): void
    {
        $this->attributes['parent_read'] = $value;
    }

    // ==================== Scopes ====================

    public function scopeIncoming($query)
    {
        return $query->where('message_type', 'received');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('message_type', 'sent');
    }

    public function scopeUnread($query)
    {
        return $query->where('parent_read', false);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('sent_at', today());
    }

    public function scopeByNumber($query, string $number)
    {
        return $query->where('phone_hash', self::hashPhone($number));
    }

    /**
     * NOTE: phone_number, contact_name, message_body are encrypted.
     * SQL LIKE search is NOT possible on encrypted columns.
     * Filter on direction only at DB level; keyword filtering must happen
     * in PHP after decryption, or use a dedicated search index.
     */
    public function scopeSearch($query, string $keyword)
    {
        // Encrypted fields cannot be searched with LIKE.
        // Return all and let the caller filter in PHP, or remove this scope.
        return $query;
    }

    public static function hashPhone(string $number): string
    {
        return hash('sha256', preg_replace('/\s+/', '', $number));
    }
}
