<?php

namespace Siruis\Storage\Abstracts;

abstract class AbstractImmutableCollection
{
    public abstract function select_db(string $db_name);

    public abstract function add($value, array $tags);

    public abstract function fetch(array $tags, int $limit = null);
}