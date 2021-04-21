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

    /**
     * @var string
     */
    public $selected_db;

    /**
     * InWalletImmutableCollection constructor.
     * @param AbstractNonSecrets $in_wallet_storage
     * @return void
     */
    public function __construct(AbstractNonSecrets $in_wallet_storage)
    {
        $this->storage = $in_wallet_storage;
        $this->selected_db = '';
    }

    public function select_db(string $db_name)
    {
        $this->selected_db = $db_name;
    }

    public function add($value, array $tags)
    {
        $payload = json_encode($value, JSON_UNESCAPED_SLASHES);
        $this->storage->add_wallet_record(
            $this->selected_db,
            uniqid(),
            $payload,
            $tags
        );
    }

    public function fetch(array $tags, int $limit = null): array
    {
        list($collection, $total_count) = $this->storage->wallet_search(
            $this->selected_db,
            $tags,
            new RetrieveRecordOptions(true),
            $limit ? $limit : self::DEFAULT_FETCH_LIMIT
        );
        if ($collection) {
            $values = [];
            foreach ($collection as $item) {
                array_push($values, json_encode($item['value']));
            }
            return [$values, $total_count];
        } else {
            return [[], $total_count];
        }
    }
}
