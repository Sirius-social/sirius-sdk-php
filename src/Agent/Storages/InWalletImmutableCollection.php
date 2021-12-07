<?php


namespace Siruis\Agent\Storages;

use Siruis\Agent\Wallet\Abstracts\NonSecrets\AbstractNonSecrets;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\RetrieveRecordOptions;
use Siruis\Storage\Abstracts\AbstractImmutableCollection;

class InWalletImmutableCollection extends AbstractImmutableCollection
{
    public const DEFAULT_FETCH_LIMIT = 1000;

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
     *
     * @param AbstractNonSecrets $in_wallet_storage
     * @return void
     */
    public function __construct(AbstractNonSecrets $in_wallet_storage)
    {
        $this->storage = $in_wallet_storage;
        $this->selected_db = '';
    }

    /**
     * @param string $db_name
     * @return void
     */
    public function select_db(string $db_name): void
    {
        $this->selected_db = $db_name;
    }

    /**
     * @param $value
     * @param array $tags
     * @return void
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function add($value, array $tags): void
    {
        $payload = json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
        $this->storage::add_wallet_record(
            $this->selected_db,
            uniqid('', true),
            $payload,
            $tags
        );
    }

    /**
     * @param array $tags
     * @param int|null $limit
     * @return array
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function fetch(array $tags, int $limit = null): array
    {
        [$collection, $total_count] = $this->storage::wallet_search(
            $this->selected_db,
            $tags,
            new RetrieveRecordOptions(false, true),
            $limit ?: self::DEFAULT_FETCH_LIMIT
        );

        if ($collection) {
            $values = [];
            foreach ($collection as $item) {
                $values[] = json_decode($item['value'], true, 512, JSON_THROW_ON_ERROR);
            }
            return [$values, $total_count];
        }

        return [[], $total_count];
    }
}
