<?php


namespace Siruis\Hub;


use Siruis\Helpers\ArrayHelper;
use Swoole\Lock;

class Context
{
    private static $instances = [];
    private $lock;

    public function __construct()
    {
        $this->lock = new \Threaded();
    }

    public function __clone()
    {
        // TODO: Implement __clone() method.
    }

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    public static function getInstance(): Context
    {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }

    public function get($key) {

        $this->_ensure_ctx_exists(\Thread::getCurrentThreadId());
        return $this->_get_ctx($key);
    }

    public function set($key, $value) {
        $this->_ensure_ctx_exists(\Thread::getCurrentThreadId());
        $this->_set_ctx($key, $value);
    }

    public function clear() {
        $this->_clear_ctx(\Thread::getCurrentThreadId());
    }

    private function _get_ctx($key)
    {
        return $this->lock->synchronized(function () use ($key) {
            return ArrayHelper::getValueWithKeyFromArray($key, self::$instances);
        });
    }

    private function _set_ctx($key, $value)
    {
        $this->lock->synchronized(function () use ($key, $value) {
            self::$instances[$key] = $value;
        });
    }

    private function _clear_ctx($key)
    {
        $this->lock->synchronized(function () use ($key) {
            $context = $this->_get_ctx($key);
            if ($context) {
                unset($context);
                unset(self::$instances[$key]);
            }
        });
    }

    private function _ensure_ctx_exists($key) {
        $context = $this->_get_ctx($key);
        if ($context == null) {
            $context = [];
            $this->_set_ctx($key, $context);
        }
    }
}