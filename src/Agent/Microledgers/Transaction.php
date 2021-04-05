<?php


namespace Siruis\Agent\Microledgers;


use ArrayObject;
use Siruis\Errors\Exceptions\SiriusContextError;

class Transaction extends ArrayObject
{
    public $payload;

    public function __construct(
        array &$payload,
        $array = array(),
        $flags = 0,
        $iteratorClass = "ArrayIterator")
    {
        parent::__construct($array, $flags, $iteratorClass);
        if (!key_exists(Microledgers::METADATA_ATTR, $payload)) {
            $payload[Microledgers::METADATA_ATTR] = (object)[];
        }
        $this->payload = $payload;
    }

    public function as_object()
    {
        return (object)$this->payload;
    }

    public function as_json()
    {
        return json_encode($this->payload);
    }

    public function has_metadata(): bool
    {
        if (key_exists(Microledgers::METADATA_ATTR, $this->payload)) {
            $meta = $this->payload[Microledgers::METADATA_ATTR];
            return count($meta) > 0;
        } else {
            return false;
        }
    }

    /**
     * @param array $payload
     * @return Transaction
     * @throws SiriusContextError
     */
    public static function create(array $payload): Transaction
    {
        $inst = new Transaction($payload);
        if (!empty($inst[Microledgers::METADATA_ATTR])) {
            throw new SiriusContextError(Microledgers::METADATA_ATTR . ' attribute must be empty for new transaction');
        } else {
            return $inst;
        }
    }
}