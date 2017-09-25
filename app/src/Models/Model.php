<?php

namespace App\Models;

/**
 * Class Model
 * @package App\Models
 *
 * @property \PDO db
 */
class Model
{
    private $db;

    public function __construct(&$db)
    {
        $this->db = $db;
    }

    public function __get($name): \PDO
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }

        throw new \RuntimeException("Property doesn\'t exists.");
    }

    public function __set($name, $value): void
    {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }

    public function __isset($name): bool
    {
        return isset($this->$name);
    }
}