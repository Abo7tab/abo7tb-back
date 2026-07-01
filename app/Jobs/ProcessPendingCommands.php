<?php

namespace App\Jobs;

use App\Domain\Device\Models\RemoteCommand;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessPendingCommands implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    public function handle(): void
    {
        // إلغاء الأوامر المنتهية الصلاحية
        RemoteCommand::where('status', 'pending')
            ->where('expires_at', '<', now())
            ->update(['status' => 'cancelled']);

        // إعادة محاولة الأوامر الفاشلة (التي لم تصل للحد الأقصى)
        RemoteCommand::where('status', 'failed')
            ->whereColumn('retry_count', '<', 'max_retries')
            ->where('created_at', '>', now()->subHour())
            ->update([
                'status'      => 'pending',
                'retry_count' => DB::raw('retry_count + 1'),
            ]);
    }
}
