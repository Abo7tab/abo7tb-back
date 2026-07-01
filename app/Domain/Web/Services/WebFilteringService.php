<?php

namespace App\Domain\Web\Services;

use App\Domain\Web\DTOs\BatchBrowsingDTO;
use App\Domain\Web\DTOs\BlockWebsiteDTO;
use App\Domain\Web\DTOs\RecordBrowsingDTO;
use App\Domain\Web\Models\BlockedWebsite;
use App\Domain\Web\Models\BrowsingHistory;
use App\Domain\Web\Models\WebsiteCategory;

class WebFilteringService
{
    /**
     * تسجيل زيارة موقع واحدة
     * إذا كان نفس الرابط زُور اليوم، يزيد العداد
     */
    public function recordVisit(RecordBrowsingDTO $dto): BrowsingHistory
    {
        $existing = BrowsingHistory::where('device_id', $dto->deviceId)
            ->where('url', $dto->url)
            ->whereDate('visited_at', today())
            ->first();

        if ($existing) {
            $existing->update([
                'visit_count' => $existing->visit_count + 1,
                'visited_at'  => now(),
            ]);
            return $existing->fresh();
        }

        return BrowsingHistory::create([
            'device_id'    => $dto->deviceId,
            'url'          => $dto->url,
            'title'        => $dto->title,
            'browser_name' => $dto->browserName,
            'visit_count'  => 1,
            'visited_at'   => now(),
        ]);
    }

    /**
     * تسجيل مجموعة زيارات دفعة واحدة (batch sync من الجهاز)
     *
     * @return array{recorded: int, skipped: int, total: int}
     */
    public function recordBatch(BatchBrowsingDTO $dto): array
    {
        $recorded = 0;
        $skipped  = 0;

        foreach ($dto->history as $item) {
            try {
                $url = $item['url'] ?? null;
                if (!$url) {
                    $skipped++;
                    continue;
                }

                BrowsingHistory::updateOrCreate(
                    [
                        'device_id'  => $dto->deviceId,
                        'url'        => $url,
                        'visited_at' => $item['visited_at'] ?? now()->toDateTimeString(),
                    ],
                    [
                        'title'        => $item['title']        ?? null,
                        'browser_name' => $item['browser_name'] ?? null,
                        'visit_count'  => (int) ($item['visit_count'] ?? 1),
                    ]
                );
                $recorded++;
            } catch (\Exception) {
                $skipped++;
            }
        }

        return [
            'recorded' => $recorded,
            'skipped'  => $skipped,
            'total'    => count($dto->history),
        ];
    }

    /**
     * حظر موقع
     */
    public function blockWebsite(BlockWebsiteDTO $dto): BlockedWebsite
    {
        return BlockedWebsite::updateOrCreate(
            [
                'device_id' => $dto->deviceId,
                'domain'    => $dto->domain,
            ],
            [
                'category'   => $dto->category,
                'block_type' => $dto->blockType,
                'is_active'  => true,
                'blocked_at' => now(),
            ]
        );
    }

    /**
     * إلغاء حظر موقع
     */
    public function unblockWebsite(int $deviceId, string $domain): bool
    {
        $result = BlockedWebsite::where('device_id', $deviceId)
            ->where('domain', $domain)
            ->update(['is_active' => false]);

        return $result > 0;
    }

    /**
     * حظر فئة كاملة من المواقع بكل نطاقاتها
     *
     * @throws \Exception
     */
    public function blockCategory(int $deviceId, string $categoryName): int
    {
        $category = WebsiteCategory::where('category_name', $categoryName)->first();

        if (!$category) {
            throw new \Exception("الفئة غير موجودة: {$categoryName}");
        }

        $count   = 0;
        $domains = $category->domains ?? [];

        foreach ($domains as $domain) {
            BlockedWebsite::updateOrCreate(
                ['device_id' => $deviceId, 'domain' => $domain],
                [
                    'category'   => $categoryName,
                    'block_type' => 'category',
                    'is_active'  => true,
                    'blocked_at' => now(),
                ]
            );
            $count++;
        }

        // سجل category placeholder لفحص الكيوردز لاحقاً
        BlockedWebsite::updateOrCreate(
            ['device_id' => $deviceId, 'domain' => "category:{$categoryName}"],
            [
                'category'   => $categoryName,
                'block_type' => 'category',
                'is_active'  => true,
                'blocked_at' => now(),
            ]
        );

        return $count;
    }

    /**
     * إلغاء حظر فئة كاملة
     */
    public function unblockCategory(int $deviceId, string $categoryName): int
    {
        return BlockedWebsite::where('device_id', $deviceId)
            ->where('category', $categoryName)
            ->update(['is_active' => false]);
    }

    /**
     * فحص إذا كان الموقع محظوراً (للجهاز يفحص قبل الفتح)
     *
     * @return array{blocked: bool, domain: string, reason?: string, category?: string}
     */
    public function isWebsiteBlocked(int $deviceId, string $url): array
    {
        $domain = $this->extractDomain($url);

        // 1. فحص الحظر المباشر بالدومين
        $directBlock = BlockedWebsite::where('device_id', $deviceId)
            ->where('is_active', true)
            ->where(function ($q) use ($domain) {
                $q->where('domain', $domain)
                  ->orWhere('domain', "www.{$domain}");
            })
            ->first();

        if ($directBlock) {
            return [
                'blocked'  => true,
                'reason'   => $directBlock->block_type,
                'category' => $directBlock->category,
                'domain'   => $domain,
            ];
        }

        // 2. فحص الفئات المحظورة بالكيوردز
        $categoryNames = BlockedWebsite::where('device_id', $deviceId)
            ->where('is_active',  true)
            ->where('block_type', 'category')
            ->pluck('category')
            ->unique()
            ->toArray();

        foreach ($categoryNames as $categoryName) {
            $category = WebsiteCategory::where('category_name', $categoryName)->first();

            if ($category) {
                if ($category->containsDomain($domain) || $category->containsKeyword($url)) {
                    return [
                        'blocked'  => true,
                        'reason'   => 'category',
                        'category' => $categoryName,
                        'domain'   => $domain,
                    ];
                }
            }
        }

        return [
            'blocked' => false,
            'domain'  => $domain,
        ];
    }

    /**
     * إحصائيات التصفح (today / week / month)
     *
     * @return array{period: string, total_visits: int, unique_sites: int, top_domains: array, browsers: array}
     */
    public function getBrowsingStats(int $deviceId, string $period = 'today'): array
    {
        $query = BrowsingHistory::where('device_id', $deviceId);

        match ($period) {
            'week'  => $query->whereBetween('visited_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('visited_at', [now()->startOfMonth(), now()->endOfMonth()]),
            default => $query->whereDate('visited_at', today()),
        };

        $history = $query->orderByDesc('visited_at')->get();

        // أكثر المواقع زيارةً (top 10)
        $topDomains = $history
            ->groupBy(fn (BrowsingHistory $h) => $h->domain)
            ->map(fn ($group) => [
                'domain'      => $group->first()->domain,
                'visit_count' => (int) $group->sum('visit_count'),
                'last_visit'  => $group->max('visited_at'),
            ])
            ->sortByDesc('visit_count')
            ->values()
            ->take(10)
            ->toArray();

        // أكثر المتصفحات استخداماً
        $browsers = $history
            ->groupBy('browser_name')
            ->map(fn ($group, $browser) => [
                'browser' => $browser ?? 'Unknown',
                'count'   => $group->count(),
            ])
            ->values()
            ->toArray();

        return [
            'period'       => $period,
            'total_visits' => (int) $history->sum('visit_count'),
            'unique_sites' => $history->pluck('domain')->unique()->count(),
            'top_domains'  => $topDomains,
            'browsers'     => $browsers,
        ];
    }

    // ==================== Private Helpers ====================

    private function extractDomain(string $url): string
    {
        if (str_contains($url, '://')) {
            $parsed = parse_url($url);
            $host   = $parsed['host'] ?? $url;
        } else {
            $host = $url;
        }

        return str_starts_with($host, 'www.')
            ? substr($host, 4)
            : $host;
    }
}
