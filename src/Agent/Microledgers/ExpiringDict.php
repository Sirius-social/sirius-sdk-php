<?php


namespace Siruis\Agent\Microledgers;


class ExpiringDict
{
    /**
     * @var array
     */
    public $store;
    /**
     * @var array
     */
    public $times;
    /**
     * @var int|null
     */
    public $ttl;

    /**
     * ExpiringDict constructor.
     *
     * @param int|null $ttl
     */
    public function __construct(int $ttl = null)
    {
        $this->store = [];
        $this->times = [];
        $this->ttl = $ttl;
    }

    /**
     * @param string $key
     * @param $value
     * @return void
     */
    public function flush(string $key, $value): void
    {
        $this->clean_store();
        $this->set_item($key, $value);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get_item(string $key)
    {
        return $this->store[$key];
    }

    /**
     * @return array
     */
    public function get_items(): array
    {
        $this->clean_store();
        return $this->store;
    }

    /**
     * @param $key
     * @return void
     */
    public function delete_item($key): void
    {
        unset($this->store[$key]);
    }

    /**
     * @param string $key
     * @param $value
     * @return void
     */
    protected function set_item(string $key, $value): void
    {
        if ($this->ttl) {
            $this->set_with_expire($key, $value);
        } else {
            $this->store[$key] = $value;
        }
    }

    /**
     * @param string $key
     * @param $value
     * @return void
     */
    protected function set_with_expire(string $key, $value): void
    {
        $this->store[$key] = $value;
        $this->times[$key] = time() + $this->ttl;
    }

    protected function clean_store(): void
    {
        foreach ($this->store as $key => $value) {
            if (array_key_exists($key, $this->times) && time() > $this->times[$key]) {
                unset($this->store[$key]);
            }
        }
    }
}