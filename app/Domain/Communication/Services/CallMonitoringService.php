<?php

namespace App\Domain\Communication\Services;

use App\Domain\Communication\DTOs\SyncCallLogsDTO;
use App\Domain\Communication\Models\CallLog;

class CallMonitoringService
{
    /**
     * مزامنة سجلات المكالمات
     *
     * @return array{synced: int, skipped: int, total: int}
     */
    public function syncCallLogs(SyncCallLogsDTO $dto): array
    {
        $synced  = 0;
        $skipped = 0;

        foreach ($dto->calls as $call) {
            try {
                $number   = $call['phone_number'] ?? null;
                $calledAt = $call['called_at']    ?? null;

                if (!$number || !$calledAt) {
                    $skipped++;
                    continue;
                }

                CallLog::updateOrCreate(
                    [
                        'device_id'    => $dto->deviceId,
                        'phone_hash'   => CallLog::hashPhone($number),
                        'called_at'    => $calledAt,
                    ],
                    [
                        'phone_number'      => $number,
                        'contact_name'      => $call['contact_name']      ?? null,
                        'call_type'         => $call['call_type']         ?? 'unknown',
                        'duration_sec'      => (int) ($call['duration_sec'] ?? 0),
                        'is_unknown'        => empty($call['contact_name']),
                        'parent_read'       => false,
                        'is_blocked_number' => (bool) ($call['is_blocked_number'] ?? false),
                    ]
                );
                $synced++;
            } catch (\Exception) {
                $skipped++;
            }
        }

        return [
            'synced'  => $synced,
            'skipped' => $skipped,
            'total'   => count($dto->calls),
        ];
    }

    /**
     * إحصائيات المكالمات
     *
     * @return array{period: string, total: int, incoming: int, outgoing: int, missed: int, top_numbers: array}
     */
    public function getCallStats(int $deviceId, string $period = 'today'): array
    {
        $query = CallLog::where('device_id', $deviceId);

        match ($period) {
            'week'  => $query->whereBetween('called_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('called_at', [now()->startOfMonth(), now()->endOfMonth()]),
            default => $query->whereDate('called_at', today()),
        };

        $calls = $query->get();

        $topNumbers = $calls
            ->groupBy('phone_number')
            ->map(fn ($group) => [
                'phone_number' => $group->first()->phone_number,
                'contact_name' => $group->first()->contact_name,
                'call_count'   => $group->count(),
                'total_sec'    => (int) $group->sum('duration_sec'),
            ])
            ->sortByDesc('call_count')
            ->values()
            ->take(10)
            ->toArray();

        return [
            'period'      => $period,
            'total'       => $calls->count(),
            'incoming'    => $calls->where('call_type', 'incoming')->count(),
            'outgoing'    => $calls->where('call_type', 'outgoing')->count(),
            'missed'      => $calls->where('call_type', 'missed')->count(),
            'total_sec'   => (int) $calls->sum('duration_sec'),
            'top_numbers' => $topNumbers,
        ];
    }
}
