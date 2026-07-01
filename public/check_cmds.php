<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;

try {
    $commands = DB::select("SELECT * FROM remote_commands ORDER BY created_at DESC LIMIT 5");
    foreach($commands as $cmd) {
        echo "ID: {$cmd->id} | Type: {$cmd->command_category} | Status: {$cmd->status} | Created: {$cmd->created_at}<br>";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
