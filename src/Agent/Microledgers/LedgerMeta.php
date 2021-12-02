<?php


namespace Siruis\Agent\Microledgers;


use ArrayObject;

class LedgerMeta extends ArrayObject
{
    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $uuid;
    /**
     * @var string
     */
    public $created;

    /**
     * LedgerMeta constructor.
     * @param string $name
     * @param string $uuid
     * @param string $created
     * @param array $array
     * @param int $flags
     * @param string $iteratorClass
     */
    public function __construct(
        string $name, string $uuid, string $created,
        $array = array(), $flags = 0, $iteratorClass = "ArrayIterator"
    )
    {
        parent::__construct($array, $flags, $iteratorClass);
        $this->name = $name;
        $this->uuid = $uuid;
        $this->created = $created;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return '{"name": '. $this->name .', "uuid": '.$this->uuid.', "created": '.$this->created.'}';
    }
}