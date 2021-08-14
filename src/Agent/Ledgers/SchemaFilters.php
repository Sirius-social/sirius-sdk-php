<?php


namespace Siruis\Agent\Ledgers;


class SchemaFilters
{
    public $tags;

    public function __construct()
    {
        $this->tags = ['category' => 'schema'];
    }

    public function getId(): ?string
    {
        return $this->get('id');
    }

    public function setId(string $value)
    {
        $this->tags['id'] = $value;
    }

    public function getName(): ?string
    {
        return $this->get('name');
    }

    public function setName(string $value)
    {
        $this->tags['name'] = $value;
    }

    public function getVersion(): ?string
    {
        return $this->get('version');
    }

    public function setVersion(string $value)
    {
        $this->tags['version'] = $value;
    }

    public function getSubmitterDid(): ?string
    {
        return $this->get('submitter_did');
    }

    public function setSubmitterDid(string $value)
    {
        $this->tags['submitter_did'] = $value;
    }

    protected function get(string $key): ?string
    {
        return $this->tags[$key] ? $this->tags[$key] : null;
    }
}