<?php

namespace App\Domain\Web\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteCategory extends Model
{
    protected $table = 'website_categories';

    public $timestamps = false;

    protected $fillable = [
        'category_name',
        'description',
        'domains',
        'keywords',
        'is_default',
    ];

    protected $casts = [
        'domains'    => 'array',
        'keywords'   => 'array',
        'is_default' => 'boolean',
    ];

    // ==================== Scopes ====================

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ==================== Helpers ====================

    public function containsDomain(string $domain): bool
    {
        $domains = $this->domains ?? [];
        foreach ($domains as $blocked) {
            if (str_contains($domain, $blocked) ||
                str_contains($blocked, $domain)) {
                return true;
            }
        }
        return false;
    }

    public function containsKeyword(string $url): bool
    {
        $keywords = $this->keywords ?? [];
        $urlLower = strtolower($url);
        foreach ($keywords as $keyword) {
            if (str_contains($urlLower, strtolower($keyword))) {
                return true;
            }
        }
        return false;
    }
}
