<?php
namespace App\Domain\User\Exceptions;
use RuntimeException;
class InvalidCredentialsException extends RuntimeException
{
    public function __construct(string $message = 'The provided credentials are incorrect.')
    {
        parent::__construct($message);
    }
}
