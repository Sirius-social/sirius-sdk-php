<?php


namespace Siruis\Agent\Storages;


use Siruis\Agent\Wallet\Abstracts\NonSecrets\AbstractNonSecrets;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\RetrieveRecordOptions;
use Siruis\Storage\Abstracts\AbstractImmutableCollection;

class InWalletImmutableCollection extends AbstractImmutableCollection
{
    const DEFAULT_FETCH_LIMIT = 1000;

    /**
     * @var AbstractNonSecrets
     */
    public $storage;

    public $selected_db;

    public function __construct(AbstractNonSecrets $in_wallet_storage)
    {
        $this->storage = $in_wallet_storage;
        $this->selected_db = null;
    }

    public function select_db(string $db_name)
    {
        $this->selected_db = $db_name;
    }

    public function add($value, array $tags)
    {
        $payload = json_decode($value);
        $this->storage->add_wallet_record(
            $this->selected_db,
            uniqid(),
            $payload,
            $tags
        );
    }

    public function fetch(array $tags, int $limit = null): array
    {
        $result = $this->storage->wallet_search(
            $this->selected_db,
            $tags,
            new RetrieveRecordOptions(true),
            $limit ? $limit : self::DEFAULT_FETCH_LIMIT
        );
        if ($result[0]) {
            $values = [];
            foreach ($result[0] as $item) {
                array_push($values, json_encode($item['value']));
            }
            return [$values, $result[1]];
        } else {
            return [[], $result[1]];
        }
    }
}