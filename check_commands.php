<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Domain\Device\Models\RemoteCommand;

$commands = RemoteCommand::orderBy('created_at', 'desc')->take(10)->get();
foreach ($commands as $cmd) {
    echo "ID: " . $cmd->id . " | Type: " . $cmd->command_type . " | Status: " . $cmd->status . " | Created: " . $cmd->created_at . "\n";
}
