<?php


namespace Siruis\Agent\Consensus\Messages;


class InitResponseLedgerMessage extends InitRequestLedgerMessage
{
    public $NAME = 'initialize-response';

    public function assign_from(BaseInitLedgerMessage $source)
    {
        $sourceArray = json_decode($source->serialize());
        $partial = [];
        foreach ($sourceArray as $key => $value) {
            if (!in_array($key, ['@id', '@type', self::THREAD_DECORATOR])) {
                array_push($partial, [$key => $value]);
            }
        }
        array_push($this->payload, $partial);
    }

    public function signature(string $did): ?array
    {
        $filtered = [];
        foreach ($this->signatures as $p) {
            if ($p['participant'] == $did) {
                array_push($filtered, $p);
            }
        }
        return $filtered ? $filtered[0] : null;
    }
}