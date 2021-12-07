<?php


namespace Siruis\Agent\Ledgers;


class SchemaFilters
{
    /**
     * @var string[]
     */
    public $tags;

    /**
     * SchemaFilters constructor.
     */
    public function __construct()
    {
        $this->tags = ['category' => 'schema'];
    }

    /**
     * Get id attribute from the tags.
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->get('id');
    }

    /**
     * Set id attribute in the tags.
     *
     * @param string $value
     * @return void
     */
    public function setId(string $value): void
    {
        $this->tags['id'] = $value;
    }

    /**
     * Get name attribute from the tags.
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->get('name');
    }

    /**
     * Set name attribute in the tags.
     *
     * @param string $value
     * @return void
     */
    public function setName(string $value): void
    {
        $this->tags['name'] = $value;
    }

    /**
     * Get version attribute from the tags.
     *
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->get('version');
    }

    /**
     * Set version attribute in the tags.
     *
     * @param string $value
     * @return void
     */
    public function setVersion(string $value): void
    {
        $this->tags['version'] = $value;
    }

    /**
     * Get submitter_did attribute from the tags.
     *
     * @return string|null
     */
    public function getSubmitterDid(): ?string
    {
        return $this->get('submitter_did');
    }

    /**
     * Set submitter_did attribute in the tags.
     *
     * @param string $value
     * @return void
     */
    public function setSubmitterDid(string $value): void
    {
        $this->tags['submitter_did'] = $value;
    }

    /**
     * Get tag with key from the tags array.
     *
     * @param string $key
     * @return string|null
     */
    protected function get(string $key): ?string
    {
        return $this->tags[$key] ?: null;
    }
}