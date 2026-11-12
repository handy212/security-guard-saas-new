<?php

namespace App\Exceptions;

use RuntimeException;

class AssignmentException extends RuntimeException
{
    public function __construct(string $message, public readonly string $field = 'guard_id')
    {
        parent::__construct($message);
    }
}
