<?php


namespace Siruis\Storage\Impl;


use Siruis\Storage\Abstracts\AbstractImmutableCollection;

class InMemoryImmutableCollection extends AbstractImmutableCollection
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

    public function add($value, array $tags)
    {
        $item = [$value, $tags];
        array_push($this->databases[$this->selected_db], $item);
    }

    public function fetch(array $tags, int $limit = null)
    {
        $result = [];
        foreach ($this->databases[$this->selected_db] as $item) {
            list($value_, $tags_) = $item;
            $key = array_search($tags, $tags_);
            foreach ($tags as $key => $value) {
                if (key_exists($key, $tags_) && $tags_[$key] == $value) {
                    array_push($result, $value_);
                }
            }
        }
        return $result;
    }
}