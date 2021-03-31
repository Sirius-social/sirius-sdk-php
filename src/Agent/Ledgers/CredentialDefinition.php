<?php


namespace Siruis\Agent\Ledgers;


use RuntimeException;
use Siruis\Base\JsonSerializable;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Helpers\ArrayHelper;

class CredentialDefinition implements JsonSerializable
{
    /**
     * @var string
     */
    public $tag;
    /**
     * @var Schema
     */
    public $schema;
    /**
     * @var Config|null
     */
    public $config;
    /**
     * @var array|null
     */
    public $body;
    /**
     * @var int|null
     */
    public $seq_no;

    public function __construct(
        string $tag, Schema $schema, Config $config = null,
        array $body = null, int $seq_no = null
    )
    {
        $this->tag = $tag;
        $this->schema = $schema;
        $this->config = $config ? $config : new Config();
        $this->body = $body;
        $this->seq_no = $seq_no;
    }

    public function getId(): ?string
    {
        if ($this->body) {
            return ArrayHelper::getValueWithKeyFromArray('id', $this->body);
        } else {
            return null;
        }
    }

    public function getSubmitterDid(): ?string
    {
        if ($this->getId()) {
            $parts = explode(':', $this->getId());
            return $parts[0];
        } else {
            return null;
        }
    }

    public function serialize(): array
    {
        return [
            'schema' => $this->schema->serialize(),
            'config' => $this->config->serialize(),
            'body' => $this->body,
            'seq_no' => $this->seq_no
        ];
    }

    /**
     * @inheritDoc
     * @throws SiriusValidationError
     */
    public function deserialize($buffer): CredentialDefinition
    {
        if (is_string($buffer)) {
            $data = json_decode($buffer);
        } elseif (is_array($buffer)) {
            $data = $buffer;
        } else {
            throw new RuntimeException('Unexpected buffer type');
        }
        $schema = (new Schema)->deserialize($data['schema']);
        $config = (new Config)->deserialize($data['config']);
        $seq_no = $data['seq_no'];
        $body = $data['body'];
        return new CredentialDefinition($body['tag'], $schema, $config, $body, $seq_no);
    }
}