<?php


namespace Siruis\Agent\Microledgers;


class LedgerCache
{
    private $items;

    public function __construct()
    {
        $this->items = [];
    }

    public function set($key, $value)
    {
        $this->items[$key] = $value;
    }

    public function delete($key)
    {
        if (key_exists($key, $this->items)) {
            unset($this->items[$key]);
        }
    }

    public function get($key)
    {
        return $this->items[$key];
    }

    public function clear()
    {
        $this->items = [];
    }

    public function is_exists($key): bool
    {
        return key_exists($key, $this->items);
    }
}