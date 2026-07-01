<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\App\Models\InstalledApp;
use App\Domain\App\Models\AppUsage;
use App\Domain\Web\Models\WebHistory;
use App\Domain\Communication\Models\CallHistory;
use App\Domain\Communication\Models\SmsHistory;

echo "installed_apps count: " . InstalledApp::count() . "\n";
echo "app_usage count: " . AppUsage::count() . "\n";
echo "web_history count: " . WebHistory::count() . "\n";
echo "calls_history count: " . CallHistory::count() . "\n";
echo "sms_history count: " . SmsHistory::count() . "\n";
