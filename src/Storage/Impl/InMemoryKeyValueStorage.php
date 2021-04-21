<?php


namespace Siruis\Storage\Impl;


use Siruis\Helpers\ArrayHelper;
use Siruis\Storage\Abstracts\AbstractKeyValueStorage;

class InMemoryKeyValueStorage extends AbstractKeyValueStorage
{
    public $databases;
    public $selected_db;

    public function __construct()
    {
        $this->databases = [];
        $this->selected_db = null;
    }

    public function select_db(string $db_name)
    {
        if (!key_exists($db_name, $this->databases)) {
            $this->databases[$db_name] = [];
        }
        $this->selected_db = $db_name;
    }

    public function set(string $key, $value)
    {
        $this->databases[$this->selected_db][$key] = $value;
    }

    public function get(string $key)
    {
        return ArrayHelper::getValueWithKeyFromArray($key, $this->databases[$this->selected_db]);
    }

    public function delete(string $key)
    {
        if (key_exists($key, $this->databases[$this->selected_db])) {
            unset($this->databases[$this->selected_db][$key]);
        }
    }
}