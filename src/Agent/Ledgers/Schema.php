<?php


namespace Siruis\Agent\Ledgers;


use RuntimeException;
use Siruis\Agent\Wallet\Abstracts\Anoncreds\AnonCredSchema;
use Siruis\Base\JsonSerializable;
use Siruis\Errors\Exceptions\SiriusValidationError;

class Schema extends AnonCredSchema implements JsonSerializable
{
    /**
     * Schema constructor.
     * @param array|null $args
     * @throws SiriusValidationError
     */
    public function __construct(array $args = null)
    {
        parent::__construct($args);
    }

    public function getSeqNo(): int
    {
        return $this->body['seqNo'];
    }

    public function getIssuerDid(): string
    {
        $parts = explode(':', $this->getId());
        return $parts[0];
    }

    public function serialize(): ?array
    {
        return $this->body;
    }

    /**
     * @param array|string $buffer
     * @return Schema
     * @throws SiriusValidationError
     */
    public function deserialize($buffer): Schema
    {
        if (is_string($buffer)) {
            $kwargs = json_decode($buffer);
        } elseif (is_array($buffer)) {
            $kwargs = $buffer;
        } else {
            throw new RuntimeException('Unexpected buffer type');
        }
        return new Schema($kwargs);
    }
}