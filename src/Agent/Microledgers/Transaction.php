<?php


namespace Siruis\Agent\Microledgers;


use ArrayObject;
use Siruis\Errors\Exceptions\SiriusContextError;
use Siruis\Helpers\ArrayHelper;

class Transaction extends ArrayObject
{
    /**
     * @var array
     */
    public $payload;

    /**
     * Transaction constructor.
     * @param array $payload
     * @param array $array
     * @param int $flags
     * @param string $iteratorClass
     */
    public function __construct(
        array &$payload,
        $array = array(),
        $flags = 0,
        $iteratorClass = "ArrayIterator"
    )
    {
        parent::__construct($array, $flags, $iteratorClass);
        if (!array_key_exists(Microledgers::METADATA_ATTR, $payload)) {
            $payload[Microledgers::METADATA_ATTR] = (object)[];
        }
        $this->payload = $payload;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        $metadata = ArrayHelper::getValueWithKeyFromArray(Microledgers::METADATA_ATTR, $this->payload, []);
        return $metadata[Microledgers::ATTR_TIME];
    }

    /**
     * @param string $value
     */
    public function setTime(string $value): void
    {
        $metadata = $this->payload[Microledgers::METADATA_ATTR] ?? [];
        $metadata->{Microledgers::ATTR_TIME} = $value;
        $this->payload[Microledgers::METADATA_ATTR] = $metadata;
    }

    /**
     * @return object
     */
    public function as_object(): object
    {
        return (object)$this->payload;
    }

    /**
     * @throws \JsonException
     */
    public function as_json()
    {
        return json_encode($this->payload, JSON_THROW_ON_ERROR);
    }

    /**
     * @return bool
     */
    public function has_metadata(): bool
    {
        if (array_key_exists(Microledgers::METADATA_ATTR, $this->payload)) {
            $meta = $this->payload[Microledgers::METADATA_ATTR];
            return count((array)$meta) > 0;
        }

        return false;
    }

    /**
     * @throws \JsonException
     */
    public function __toString()
    {
        $json = json_encode($this->payload, JSON_THROW_ON_ERROR);
        if (is_string($json)) {
            return $json;
        }

        return '';
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
        }

        return $inst;
    }
}