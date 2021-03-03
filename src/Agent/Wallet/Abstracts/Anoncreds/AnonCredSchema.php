<?php


namespace Siruis\Agent\Wallet\Abstracts\Anoncreds;


use Siruis\Errors\Exceptions\SiriusValidationError;

class AnonCredSchema
{
    public $body;

    /**
     * AnonCredSchema constructor.
     * @param array|null $args
     * @throws SiriusValidationError
     */
    public function __construct(array $args = null)
    {
        $this->body = array();
        $fields = ['ver', 'id', 'name', 'version', 'attrNames'];
        foreach ($fields as $field) {
            if (key_exists($field, $args)) {
                throw new SiriusValidationError('Except for '. $field . ' field exists');
            }
            $this->body[$field] = $args[$field];
        }
        $this->body = $args;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->body['id'];
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        sort($this->body['attrNames']);
        return $this->body['attrNames'];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->body['name'];
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->body['version'];
    }
}