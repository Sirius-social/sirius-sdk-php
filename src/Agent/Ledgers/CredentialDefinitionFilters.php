<?php


namespace Siruis\Agent\Ledgers;


class CredentialDefinitionFilters
{
    /**
     * @var array
     */
    public $extras;
    /**
     * @var string[]
     */
    public $tags;

    /**
     * CredentialDefinitionFilters constructor.
     */
    public function __construct()
    {
        $this->extras = [];
        $this->tags = ['category', 'cred_def'];
    }

    /**
     * Get tags attribute.
     *
     * @return string[]
     */
    public function getTags(): array
    {
        $d = $this->tags;
        $d[] = $this->extras;
        return $d;
    }

    /**
     * Set extras attribute.
     *
     * @param array $value
     * @return void
     */
    public function setExtras(array $value): void
    {
        $this->extras = $value;
    }

    /**
     * Set one extra in the extras attribute.
     *
     * @param string $name
     * @param string $value
     * @return void
     */
    public function extra(string $name, string $value): void
    {
        $this->extras[$name] = $value;
    }

    /**
     * Get one tag from the tags attribute.
     *
     * @return string|null
     */
    public function getTag(): ?string
    {
        return $this->getTagWithKey('tag');
    }

    /**
     * Set one tag in the tags attribute.
     *
     * @param string|null $value
     * @return void
     */
    public function setTag(string $value = null): void
    {
        if ($value) {
            $this->tags['tag'] = $value;
        } elseif (array_key_exists('tag', $this->tags)) {
            unset($this->tags['tag']);
        }
    }

    /**
     * Get id from tags attribute.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->getTagWithKey('id');
    }

    /**
     * Set id in the tags attribute.
     *
     * @param string $value
     * @return void
     */
    public function setId(string $value): void
    {
        $this->tags['id'] = $value;
    }

    /**
     * Get submitter_did from the tags attribute.
     *
     * @return string|null
     */
    public function getSubmitterDid(): ?string
    {
        return $this->getTagWithKey('submitter_did');
    }

    /**
     * Set submitter_did in the tags attribute.
     *
     * @param string $value
     * @return void
     */
    public function setSubmitterDid(string $value): void
    {
        $this->tags['submitter_did'] = $value;
    }

    /**
     * Get schema_id from the tags attribute.
     *
     * @return string|null
     */
    public function getSchemaId(): ?string
    {
        return $this->getTagWithKey('schema_id');
    }

    /**
     * Set schema_id in the tags attribute.
     *
     * @param string $value
     * @return void
     */
    public function setSchemaId(string $value): void
    {
        $this->tags['schema_id'] = $value;
    }

    /**
     * Get seq_no from the tags attribute.
     *
     * @return int|null
     */
    public function getSeqNo(): ?int
    {
        $value = $this->getTagWithKey('seq_no');
        return $value ? (int) $value : null;
    }

    /**
     * Set seq_no in the tags attribute.
     *
     * @param int $value
     * @return void
     */
    public function setSeqNo(int $value): void
    {
        $this->tags['seq_no'] = (string) $value;
    }

    /**
     * Get tag with key from the tags attribute.
     *
     * @param string $key
     * @param null $ret
     * @return string|null
     */
    protected function getTagWithKey(string $key, $ret = null): ?string
    {
        return $this->tags[$key] ?: $ret;
    }
}