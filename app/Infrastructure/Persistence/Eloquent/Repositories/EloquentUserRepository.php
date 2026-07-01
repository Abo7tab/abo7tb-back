<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class EloquentUserRepository extends BaseRepository implements UserRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    protected function makeModel(): Model
    {
        return new User();
    }

    /**
     * {@inheritDoc}
     */
    public function findByEmail(string $email): ?User
    {
        /** @var User|null */
        return $this->model->where('email', strtolower($email))->first();
    }

    /**
     * {@inheritDoc}
     */
    public function findByUuid(string $uuid): ?User
    {
        /** @var User|null */
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * {@inheritDoc}
     */
    public function emailExists(string $email): bool
    {
        return $this->model->where('email', strtolower($email))->exists();
    }
}
