<?php


namespace Siruis\Agent\Ledgers;


class CredentialDefinitionFilters
{
    public $extras;
    public $tags;

    public function __construct()
    {
        $this->extras = [];
        $this->tags = ['category', 'cred_def'];
    }

    public function getTags()
    {
        $d = $this->tags;
        array_push($d, $this->extras);
        return $d;
    }

    public function setExtras(array $value)
    {
        $this->extras = $value;
    }

    public function extra(string $name, string $value)
    {
        $this->extras[$name] = $value;
    }

    public function getTag(): ?string
    {
        return $this->getTagWithKey('tag');
    }

    public function setTag(string $value = null)
    {
        if ($value) {
            $this->tags['tag'] = $value;
        } elseif (key_exists('tag', $this->tags)) {
            unset($this->tags['tag']);
        }
    }

    public function getId()
    {
        return $this->getTagWithKey('id');
    }

    public function setId(string $value)
    {
        $this->tags['id'] = $value;
    }

    public function getSubmitterDid()
    {
        return $this->getTagWithKey('submitter_did');
    }

    public function setSubmitterDid(string $value)
    {
        $this->tags['submitter_did'] = $value;
    }

    public function getSchemaId()
    {
        return $this->getTagWithKey('schema_id');
    }

    public function setSchemaId(string $value)
    {
        $this->tags['schema_id'] = $value;
    }

    public function getSeqNo(): ?int
    {
        $value = $this->getTagWithKey('seq_no');
        return $value ? (int) $value : null;
    }

    public function setSeqNo(int $value)
    {
        $this->tags['seq_no'] = (string) $value;
    }

    protected function getTagWithKey(string $key, $ret = null): ?string
    {
        return $this->tags[$key] ? $this->tags[$key] : $ret;
    }
}