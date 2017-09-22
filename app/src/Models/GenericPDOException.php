<?php

namespace App\Models;

use Throwable;

class GenericPDOException extends \PDOException
{
    public function __construct($message = "", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}