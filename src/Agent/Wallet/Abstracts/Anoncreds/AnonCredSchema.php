<?php


namespace Siruis\Agent\Wallet\Abstracts\Anoncreds;


use Siruis\Errors\Exceptions\SiriusValidationError;

class AnonCredSchema
{
    /**
     * @var array|null
     */
    public $body;

    /**
     * AnonCredSchema constructor.
     * @param array|null $args
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function __construct(array $args = null)
    {
        $this->body = array();
        if (!is_null($args)) {
            $fields = ['ver', 'id', 'name', 'version', 'attrNames'];
            foreach ($fields as $field) {
                if (!array_key_exists($field, $args)) {
                    throw new SiriusValidationError('Except for '. $field . ' field exists');
                }
                $this->body[$field] = $args[$field];
            }
        }
        $this->body = $args;
    }

    /**
     * Get id attribute from the body.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->body['id'];
    }

    /**
     * Get attributes from the body.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        sort($this->body['attrNames']);
        return $this->body['attrNames'];
    }

    /**
     * Get name attribute from the body.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->body['name'];
    }

    /**
     * Get version attribute from the body.
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->body['version'];
    }
}