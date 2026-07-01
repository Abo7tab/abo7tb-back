<?php

namespace App\Jobs;

use App\Domain\Communication\Models\CallLog;
use App\Domain\Communication\Models\SmsLog;
use App\Domain\Device\Models\DeviceLocation;
use App\Domain\Notification\Models\Notification;
use App\Domain\Web\Models\BrowsingHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanupOldData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 2;

    public function handle(): void
    {
        $days       = config('parental-control.monitoring.max_history_days', 90);
        $cutoffDate = now()->subDays($days);
        $deleted    = [];

        $deleted['locations']     = DeviceLocation::where('recorded_at', '<', $cutoffDate)->delete();
        $deleted['browsing']      = BrowsingHistory::where('visited_at', '<', $cutoffDate)->delete();
        $deleted['calls']         = CallLog::where('called_at', '<', $cutoffDate)->delete();
        $deleted['sms']           = SmsLog::where('sent_at', '<', $cutoffDate)->delete();
        $deleted['notifications'] = Notification::where('is_read', true)
            ->where('created_at', '<', $cutoffDate)->delete();

        Log::info('CleanupOldData completed', array_merge(
            $deleted,
            ['cutoff_date' => $cutoffDate->toDateString(), 'retention_days' => $days]
        ));
    }
}
