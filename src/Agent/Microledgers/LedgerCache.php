<?php


namespace Siruis\Agent\Microledgers;


class LedgerCache
{
    /**
     * @var array
     */
    private $items;

    /**
     * LedgerCache constructor.
     */
    public function __construct()
    {
        $this->items = [];
    }

    /**
     * @param $key
     * @param $value
     * @return void
     */
    public function set($key, $value): void
    {
        $this->items[$key] = $value;
    }

    /**
     * @param $key
     * @return void
     */
    public function delete($key): void
    {
        if (array_key_exists($key, $this->items)) {
            unset($this->items[$key]);
        }
    }

    /**
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        return $this->items[$key];
    }

    /**
     * @return void
     */
    public function clear(): void
    {
        $this->items = [];
    }

    /**
     * @param string $key
     * @return bool
     */
    public function is_exists(string $key): bool
    {
        return array_key_exists($key, $this->items);
    }
}