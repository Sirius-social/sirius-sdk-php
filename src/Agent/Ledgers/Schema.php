<?php


namespace Siruis\Agent\Ledgers;


use RuntimeException;
use Siruis\Agent\Wallet\Abstracts\Anoncreds\AnonCredSchema;
use Siruis\Base\JsonSerializable;

class Schema extends AnonCredSchema implements JsonSerializable
{
    /**
     * Get seq_no attribute.
     *
     * @return int
     */
    public function getSeqNo(): int
    {
        return $this->body['seqNo'];
    }

    /**
     * Get issuer_did attribute.
     *
     * @return string
     */
    public function getIssuerDid(): string
    {
        $parts = explode(':', $this->getId());
        return $parts[0];
    }

    /**
     * Serialize attributes.
     *
     * @return array|null
     */
    public function serialize(): ?array
    {
        return $this->body;
    }

    /**
     * Deserialize from the given buffer.
     *
     * @param array|string $buffer
     * @return \Siruis\Agent\Ledgers\Schema
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function deserialize($buffer): Schema
    {
        if (is_string($buffer)) {
            $kwargs = json_decode($buffer, false, 512, JSON_THROW_ON_ERROR);
        } elseif (is_array($buffer)) {
            $kwargs = $buffer;
        } else {
            throw new RuntimeException('Unexpected buffer type');
        }
        return new Schema($kwargs);
    }

    /**
     * Call statically deserialize.
     *
     * @param $buffer
     * @return \Siruis\Agent\Ledgers\Schema
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public static function unserialize($buffer): Schema
    {
        if (is_string($buffer)) {
            $kwargs = json_decode($buffer, false, 512, JSON_THROW_ON_ERROR);
        } elseif (is_array($buffer)) {
            $kwargs = $buffer;
        } else {
            throw new RuntimeException('Unexpected buffer type');
        }
        return new Schema($kwargs);
    }
}