<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

try {
    DB::statement("ALTER TABLE `remote_commands` MODIFY `command_category` ENUM('camera', 'microphone', 'screen', 'gallery', 'location', 'system', 'app', 'notification', 'calls', 'sms', 'contacts') NOT NULL;");
    echo "<h1>Database updated successfully!</h1>";
} catch (\Exception $e) {
    echo "<h1>Error:</h1> <p>" . $e->getMessage() . "</p>";
}
