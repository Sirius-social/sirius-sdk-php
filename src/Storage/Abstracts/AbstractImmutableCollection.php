<?php

namespace Siruis\Storage\Abstracts;

abstract class AbstractImmutableCollection
{
    abstract public function select_db(string $db_name);

    abstract public function add($value, array $tags);

    abstract public function fetch(array $tags, int $limit = null);
}