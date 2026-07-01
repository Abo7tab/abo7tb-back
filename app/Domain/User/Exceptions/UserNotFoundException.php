<?php
namespace App\Domain\User\Exceptions;
use RuntimeException;
class UserNotFoundException extends RuntimeException
{
    public static function withId(int ): self
    {
        return new self("User with ID [{}] not found.");
    }
    public static function withEmail(string ): self
    {
        return new self("User with email [{}] not found.");
    }
    public static function withUuid(string ): self
    {
        return new self("User with UUID [{}] not found.");
    }
}
