<?php

namespace App\Domain\Device\Repositories;

use App\Domain\Device\Models\Device;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface DeviceRepositoryInterface extends RepositoryInterface
{
    public function findByDeviceId(string $deviceId): ?Device;

    public function findByUuid(string $uuid): ?Device;

    public function getUserDevices(int $userId): Collection;

    public function countUserDevices(int $userId): int;

    public function updateStatus(int $deviceId, array $data): bool;

    public function updatePermissions(int $deviceId, array $permissions): bool;

    public function getPendingCommands(int $deviceId): Collection;
}
