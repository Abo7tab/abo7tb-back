<?php

namespace App\Console\Commands;

use App\Domain\Dashboard\Services\DashboardService;
use App\Domain\Notification\Services\NotificationService;
use App\Domain\User\Models\User;
use Illuminate\Console\Command;

class SendDailyReport extends Command
{
    protected $signature   = 'report:daily';
    protected $description = 'إرسال التقرير اليومي للآباء';

    public function handle(
        DashboardService    $dashboard,
        NotificationService $notifService
    ): int {
        $users = User::where('is_active', true)->get();

        $sent = 0;
        foreach ($users as $user) {
            $summary = $dashboard->getSummary($user->id);

            if ($summary['devices']['total'] === 0) {
                continue;
            }

            $today = $summary['today'];
            $notifService->send(
                userId:   $user->id,
                title:    '📊 التقرير اليومي',
                message:  "وقت الشاشة: {$today['screen_time_min']} دقيقة | مكالمات: {$today['calls']} | رسائل: {$today['sms']}",
                type:     'daily_report',
                priority: 'low'
            );
            $sent++;
        }

        $this->info("Daily reports sent to {$sent}/{$users->count()} users.");
        return Command::SUCCESS;
    }
}
