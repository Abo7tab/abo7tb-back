<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Device\Models\Device;
use App\Domain\Device\Models\RemoteCommand;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class EloquentDeviceRepository extends BaseRepository implements DeviceRepositoryInterface
{
    protected function makeModel(): Model
    {
        return new Device();
    }

    public function findByDeviceId(string $deviceId): ?Device
    {
        return Device::where('device_id', $deviceId)->first();
    }

    public function findByUuid(string $uuid): ?Device
    {
        return Device::where('uuid', $uuid)
            ->with(['consent', 'user'])
            ->first();
    }

    public function getUserDevices(int $userId): Collection
    {
        return Device::where('user_id', $userId)
            ->with(['consent'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function countUserDevices(int $userId): int
    {
        return Device::where('user_id', $userId)
            ->where('is_active', true)
            ->count();
    }

    public function updateStatus(int $deviceId, array $data): bool
    {
        return Device::where('id', $deviceId)->update($data) > 0;
    }

    public function updatePermissions(int $deviceId, array $permissions): bool
    {
        $allowed = collect($permissions)
            ->filter(fn ($v, $k) => str_starts_with($k, 'perm_'))
            ->toArray();

        return Device::where('id', $deviceId)->update($allowed) > 0;
    }

    public function getPendingCommands(int $deviceId): Collection
    {
        return RemoteCommand::where('device_id', $deviceId)
            ->where('status', 'pending')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->orderByRaw("FIELD(priority,'urgent','high','normal','low')")
            ->orderBy('created_at')
            ->get();
    }
}
