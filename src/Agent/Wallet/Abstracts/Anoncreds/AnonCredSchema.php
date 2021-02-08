<?php


namespace Siruis\Agent\Wallet\Abstracts\Anoncreds;


use Siruis\Errors\Exceptions\SiriusValidationError;

class AnonCredSchema
{
    public $body;

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

    public function getId(): string
    {
        return $this->body['id'];
    }

    public function getAttributes()
    {
        return sort($this->body['attrNames']);
    }

    public function getName(): string
    {
        return $this->body['name'];
    }

    public function version()
    {
        return $this->body['version'];
    }
}