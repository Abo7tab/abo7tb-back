<?php

namespace App\Domain\Communication\Services;

use App\Domain\Communication\DTOs\SyncSmsLogsDTO;
use App\Domain\Communication\Models\SmsLog;

class SmsMonitoringService
{
    /**
     * مزامنة رسائل SMS
     *
     * @return array{synced: int, skipped: int, total: int}
     */
    public function syncSmsLogs(SyncSmsLogsDTO $dto): array
    {
        $synced  = 0;
        $skipped = 0;

        foreach ($dto->messages as $msg) {
            try {
                $number = $msg['phone_number'] ?? null;
                $sentAt = $msg['sent_at']      ?? null;

                if (!$number || !$sentAt) {
                    $skipped++;
                    continue;
                }

                SmsLog::updateOrCreate(
                    [
                        'device_id'    => $dto->deviceId,
                        'phone_hash'   => SmsLog::hashPhone($number),
                        'sent_at'      => $sentAt,
                        'message_type' => ($msg['direction'] ?? 'incoming') === 'outgoing' ? 'sent' : 'received',
                    ],
                    [
                        'phone_number' => $number,
                        'contact_name' => $msg['contact_name']  ?? null,
                        'message_body' => $msg['message_body']  ?? '',
                        'parent_read'  => (bool) ($msg['is_read'] ?? false),
                        'is_unknown'   => empty($msg['contact_name']),
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
            'total'   => count($dto->messages),
        ];
    }

    /**
     * إحصائيات الرسائل
     *
     * @return array{period: string, total: int, incoming: int, outgoing: int, top_numbers: array}
     */
    public function getSmsStats(int $deviceId, string $period = 'today'): array
    {
        $query = SmsLog::where('device_id', $deviceId);

        match ($period) {
            'week'  => $query->whereBetween('sent_at', [now()->startOfWeek(), now()->endOfWeek()]),
            'month' => $query->whereBetween('sent_at', [now()->startOfMonth(), now()->endOfMonth()]),
            default => $query->whereDate('sent_at', today()),
        };

        $messages = $query->get();

        $topNumbers = $messages
            ->groupBy('phone_number')
            ->map(fn ($group) => [
                'phone_number' => $group->first()->phone_number,
                'contact_name' => $group->first()->contact_name,
                'sms_count'    => $group->count(),
            ])
            ->sortByDesc('sms_count')
            ->values()
            ->take(10)
            ->toArray();

        return [
            'period'      => $period,
            'total'       => $messages->count(),
            'incoming'    => $messages->where('message_type', 'received')->count(),
            'outgoing'    => $messages->where('message_type', 'sent')->count(),
            'top_numbers' => $topNumbers,
        ];
    }

    /**
     * محادثة مع رقم معين
     */
    public function getConversation(int $deviceId, string $phoneNumber): array
    {
        $messages = SmsLog::where('device_id', $deviceId)
            ->byNumber($phoneNumber)
            ->orderBy('sent_at')
            ->get();

        return $messages->map(fn (SmsLog $m) => [
            'id'           => $m->id,
            'direction'    => $m->direction,
            'message_body' => $m->message_body,
            'preview'      => $m->preview,
            'is_read'      => $m->parent_read,
            'sent_at'      => $m->sent_at?->toIso8601String(),
        ])->toArray();
    }
}
