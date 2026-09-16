<?php

abstract class Model
{
    protected static function db(): PDO
    {
        return Database::getConnection();
    }
}
