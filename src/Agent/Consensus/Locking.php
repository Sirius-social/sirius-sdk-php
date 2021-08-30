<?php


namespace Siruis\Agent\Consensus;


use Siruis\Hub\Core\Hub;
use Siruis\Hub\Init;

class Locking
{
    public const NAMESPACE = 'ledgers';

    /**
     * Lock ledgers given by names.
     *
     * @param array $names names of microledgers
     * @param float $lock_timeout lock timeout, resources will be released automatically after timeout expired
     * @return array
     */
    public static function acquire(array $names, float $lock_timeout)
    {
        $ledger_names = $names;
        $ledger_names = array_unique($ledger_names);
        $ledger_resources = [];
        foreach ($ledger_names as $name) {
            array_push($ledger_resources, self::NAMESPACE."/{$name}");
        }
        list($ok, $locked_ledgers) = Init::acquire($ledger_resources, $lock_timeout);
        $sorted = [];
        foreach ($locked_ledgers as $item) {
            array_push($sorted, explode('/', $item)[1]);
        }
        return [$ok, $sorted];
    }

    /**
     * Released all resources locked in current context
     */
    public static function release()
    {
        Init::release();
    }
}