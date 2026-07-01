<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $locations = \App\Domain\Device\Models\DeviceLocation::orderBy('id', 'desc')->limit(5)->get();
    echo "Count: " . \App\Domain\Device\Models\DeviceLocation::count() . "\n";
    foreach ($locations as $loc) {
        echo "Lat: {$loc->latitude}, Lng: {$loc->longitude}, Device ID: {$loc->device_id}, Recorded at: {$loc->recorded_at}\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
