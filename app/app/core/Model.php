<?php
namespace App\Core;

/**
 * Base MVC Model Class
 */
abstract class Model {
    protected Database $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }
}
