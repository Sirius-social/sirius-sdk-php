<?php


namespace Siruis\Agent\Ledgers;


use Siruis\Base\JsonSerializable;

class Config implements JsonSerializable
{
    /**
     * @var bool
     */
    public $support_revocation;

    public function __construct()
    {
        $this->support_revocation = false;
    }

    public function setSupportRevocation(bool $value)
    {
        $this->support_revocation = $value;
    }

    public function serialize()
    {
        return [
            'support_revocation' => $this->support_revocation
        ];
    }

    /**
     * @inheritDoc
     */
    public function deserialize($buffer): Config
    {
        if (is_string($buffer)) {
            $data = json_decode($buffer);
        } elseif (is_array($buffer)) {
            $data = $buffer;
        } else {
            throw new \RuntimeException('Unexpected buffer Type');
        }
        $instance = new Config();
        $instance->setSupportRevocation($this->get('support_revocation', $data));
        return $instance;
    }

    protected function get(string $key, array $array): bool
    {
        return $array[$key] ? $array[$key] : false;
    }
}