<?php

namespace App\Models;

use \App\Helpers\ErrorHelper;

/**
 * Class Model
 * @package App\Models
 *
 * @property \PDO db
 * @property ErrorHelper errorHelper
 */
class Model
{
    private $db;
    private $errorHelper;

    public function __construct(&$db, &$errorHelper)
    {
        $this->db = $db;
        $this->errorHelper = $errorHelper;
    }

    /**
     * @param $name
     * @return mixed
     * @throws \RuntimeException
     */
    public function __get($name)
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