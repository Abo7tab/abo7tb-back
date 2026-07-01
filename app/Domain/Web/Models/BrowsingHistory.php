<?php

namespace App\Domain\Web\Models;

use App\Domain\Device\Models\Device;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrowsingHistory extends Model
{
    protected $table = 'browsing_history';

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'url',
        'title',
        'browser_name',
        'visit_count',
        'visited_at',
    ];

    protected $casts = [
        'visit_count' => 'integer',
        'visited_at'  => 'datetime',
        // Encrypt PII at-rest (automatically decrypted when read from DB)
        'url'         => 'encrypted',
        'title'       => 'encrypted',
    ];

    // ==================== Relations ====================

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    // ==================== Helpers ====================

    public function getDomainAttribute(): string
    {
        $parsed = parse_url($this->url);
        $host   = $parsed['host'] ?? $this->url;
        return str_starts_with($host, 'www.')
            ? substr($host, 4)
            : $host;
    }

    public function isHttps(): bool
    {
        return str_starts_with($this->url, 'https://');
    }

    // ==================== Scopes ====================

    public function scopeToday($query)
    {
        return $query->whereDate('visited_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('visited_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeByBrowser($query, string $browser)
    {
        return $query->where('browser_name', $browser);
    }

    /**
     * NOTE: url and title are encrypted — SQL LIKE won't work.
     * Filter in PHP after decryption.
     */
    public function scopeSearch($query, string $keyword)
    {
        // Encrypted fields cannot be searched with LIKE.
        // Return all and let the caller filter in PHP.
        return $query;
    }
}
