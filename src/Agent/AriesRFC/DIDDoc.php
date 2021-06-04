<?php


namespace Siruis\Agent\AriesRFC;


use ArrayObject;
use Siruis\Messaging\Validators;

class DIDDoc extends ArrayObject
{
    const DID = 'did';
    const DID_DOC = 'did_doc';
    const VCX_DID = 'DID';
    const VCX_DID_DOC = 'DIDDoc';
    public $payload;

    public function __construct(array $payload, $array = array(), $flags = 0, $iteratorClass = "ArrayIterator")
    {
        parent::__construct($array, $flags, $iteratorClass);
        $this->payload = $payload;
    }

    public function validate()
    {
        Validators::check_for_attributes($this->payload, [
            '@context',
            'publicKey',
            'service'
        ]);

        foreach ($this->payload['publicKey'] as $publicKeyBlock) {
            Validators::check_for_attributes($publicKeyBlock, [
                'id',
                'type',
                'controller',
                'publicKeyBase58'
            ]);
        }

        foreach ($this->payload['service'] as $serviceBlock) {
            Validators::check_for_attributes($serviceBlock, [
                ['type', 'IndyAgent'],
                'recipientKeys',
                'serviceEndpoint'
            ]);
        }
    }

    public function extractService(array $payload = null, bool $high_priority = true, string $type = 'IndyAgent')
    {
        if (!$payload) {
            $payload = $this->payload;
        }
        $services = $payload['service'] ? $payload['service'] : [];
        if ($services) {
            $ret = null;
            foreach ($services as $service) {
                if ($service['type'] != $type) {
                    continue;
                }
                if (!$ret) {
                    $ret = $service;
                } else {
                    if ($high_priority) {
                        if ($service['priority'] > $ret['priority']) {
                            $ret = $service;
                        }
                    }
                }
            }
            return $ret;
        } else {
            return null;
        }
    }
}