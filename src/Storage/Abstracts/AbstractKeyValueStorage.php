<?php


namespace Siruis\Storage\Abstracts;


abstract class AbstractKeyValueStorage
{
    public abstract function select_db(string $db_name);
    
    public abstract function set(string $key, $value);
    
    public abstract function get(string $key);
    
    public abstract function delete(string $key);
}