<?php

namespace App\Domain\User\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use App\Domain\User\Models\User;

interface UserRepositoryInterface extends RepositoryInterface
{
    /**
     * Find user by email address
     */
    public function findByEmail(string $email): ?User;

    /**
     * Find user by UUID
     */
    public function findByUuid(string $uuid): ?User;

    /**
     * Check if email already exists
     */
    public function emailExists(string $email): bool;
}
