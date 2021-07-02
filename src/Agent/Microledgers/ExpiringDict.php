<?php


namespace Siruis\Agent\Microledgers;


class ExpiringDict
{
    /**
     * @var array
     */
    public $store;
    public $times;
    public $ttl;

    public function __construct(int $ttl = null)
    {
        $this->store = [];
        $this->times = [];
        $this->ttl = $ttl;
    }

    public function flush(string $key, $value)
    {
        $this->clean_store();
        $this->set_item($key, $value);
    }

    public function get_item(string $key)
    {
        return $this->store[$key];
    }

    public function get_items(): array
    {
        $this->clean_store();
        return $this->store;
    }

    public function delete_item($key)
    {
        unset($this->store[$key]);
    }

    protected function set_item(string $key, $value)
    {
        if ($this->ttl) {
            $this->set_with_expire($key, $value);
        } else {
            $this->store[$key] = $value;
        }
    }

    protected function set_with_expire(string $key, $value)
    {
        $this->store[$key] = $value;
        $this->times[$key] = time() + $this->ttl;
    }

    protected function clean_store()
    {
        foreach ($this->store as $key => $value) {
            if (key_exists($key, $this->times) && time() > $this->times[$key]) {
                unset($this->store[$key]);
            }
        }
    }
}