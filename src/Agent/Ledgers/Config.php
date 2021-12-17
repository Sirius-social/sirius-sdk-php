<?php


namespace Siruis\Agent\Ledgers;


use RuntimeException;
use Siruis\Base\JsonSerializable;

class Config implements JsonSerializable
{
    /**
     * @var bool
     */
    public $support_revocation;

    /**
     * Config constructor.
     */
    public function __construct()
    {
        $this->support_revocation = false;
    }

    /**
     * Get support_revocation attribute.
     *
     * @return bool
     */
    public function getSupportRevocation(): bool
    {
        return $this->support_revocation;
    }

    /**
     * Set support_revocation attribute.
     *
     * @param bool $value
     * @return void
     */
    public function setSupportRevocation(bool $value): void
    {
        $this->support_revocation = $value;
    }

    /**
     * Serialize attributes.
     *
     * @return bool[]
     */
    public function serialize(): array
    {
        return [
            'support_revocation' => $this->support_revocation
        ];
    }

    /**
     * Deserialize from the given buffer.
     *
     * @param array|string $buffer
     * @return \Siruis\Agent\Ledgers\Config
     * @throws \JsonException
     */
    public function deserialize($buffer): Config
    {
        return self::unserialize($buffer);
    }

    /**
     * Call statically deserialize.
     *
     * @param $buffer
     * @return \Siruis\Agent\Ledgers\Config
     * @throws \JsonException
     */
    public static function unserialize($buffer): Config
    {
        if (is_string($buffer)) {
            $data = json_decode($buffer, false, 512, JSON_THROW_ON_ERROR);
        } elseif (is_array($buffer)) {
            $data = $buffer;
        } else {
            throw new RuntimeException('Unexpected buffer Type');
        }
        $instance = new Config();
        $instance->setSupportRevocation($data['support_revocation'] ?: false);
        return $instance;
    }
}