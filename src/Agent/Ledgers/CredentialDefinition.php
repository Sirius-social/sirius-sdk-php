<?php


namespace Siruis\Agent\Ledgers;


use RuntimeException;
use Siruis\Base\JsonSerializable;
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

    /**
     * CredentialDefinition constructor.
     *
     * @param string|null $tag
     * @param \Siruis\Agent\Ledgers\Schema|null $schema
     * @param \Siruis\Agent\Ledgers\Config|null $config
     * @param array|null $body
     * @param int|null $seq_no
     */
    public function __construct(
        string $tag, Schema $schema, ?Config $config = null,
        ?array $body = null, ?int $seq_no = null
    )
    {
        $this->tag = $tag;
        $this->schema = $schema;
        $this->config = $config ?: new Config();
        $this->body = $body;
        $this->seq_no = $seq_no;
    }

    /**
     * Get id attribute.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        if ($this->body) {
            return ArrayHelper::getValueWithKeyFromArray('id', $this->body);
        }

        return null;
    }

    /**
     * Get submitter_did attribute.
     *
     * @return string|null
     */
    public function getSubmitterDid(): ?string
    {
        if ($this->getId()) {
            $parts = explode(':', $this->getId());
            return $parts[0];
        }

        return null;
    }

    /**
     * Serialize attributes.
     *
     * @return array
     */
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
     * Deserialize from the given buffer.
     *
     * @param array|string $buffer
     * @return \Siruis\Agent\Ledgers\CredentialDefinition
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function deserialize($buffer): CredentialDefinition
    {
        return self::unserialize($buffer);
    }

    /**
     * Call statically deserialize.
     *
     * @param $buffer
     * @return \Siruis\Agent\Ledgers\CredentialDefinition
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public static function unserialize($buffer): CredentialDefinition
    {
        if (is_string($buffer)) {
            $data = json_decode($buffer, false, 512, JSON_THROW_ON_ERROR);
        } elseif (is_array($buffer)) {
            $data = $buffer;
        } else {
            throw new RuntimeException('Unexpected buffer type');
        }
        $schema = Schema::unserialize($data['schema']);
        $config = Config::unserialize($data['config']);
        $seq_no = $data['seq_no'];
        $body = $data['body'];
        return new CredentialDefinition($body['tag'], $schema, $config, $body, $seq_no);
    }
}