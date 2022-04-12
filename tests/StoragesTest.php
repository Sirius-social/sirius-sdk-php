<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Storages\InWalletImmutableCollection;
use Siruis\Storage\Impl\InMemoryImmutableCollection;
use Siruis\Storage\Impl\InMemoryKeyValueStorage;
use Siruis\Tests\Helpers\Conftest;

class StoragesTest extends TestCase
{
    public function test_inmemory_kv_storage(): void
    {
        $kv = new InMemoryKeyValueStorage();
        $kv->select_db('db1');

        $kv->set('key1', 'value1');
        self::assertEquals('value1', $kv->get('key1'));

        $kv->select_db('db2');
        $kv->set('key1', 1000);
        self::assertEquals(1000, $kv->get('key1'));

        $kv->select_db('db1');
        self::assertEquals('value1', $kv->get('key1'));

        $kv->delete('key1');
        self::assertNull($kv->get('key1'));

        $kv->delete('unknown-key');
    }

    public function test_inmemory_immutable_collection(): void
    {
        $collection = new InMemoryImmutableCollection();

        $collection->select_db('db1');
        $collection->add('Value1', ['tag1' => 'tag-val-1', 'tag2' => 'tag-val-2']);
        $collection->add('Value2', ['tag1' => 'tag-val-1', 'tag2' => 'tag-val-3']);

        $fetched = $collection->fetch(['tag1' => 'tag-val-1']);
        self::assertCount(2, $fetched);

        $fetched = $collection->fetch(['tag2' => 'tag-val-2']);
        self::assertCount(1, $fetched);
        self::assertEquals('Value1', $fetched[0]);

        $collection->select_db('db2');
        $fetched = $collection->fetch([]);
        self::assertCount(0, $fetched);
    }

    /**
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_inwallet_immutable_collection(): void
    {
        $agent = Conftest::agent1();
        $agent->open();
        try {
            $collection = new InWalletImmutableCollection($agent->wallet->non_secrets);

            $value1 = [
                'key1' => 'value1',
                'key2' => 10000
            ];
            $value2 = [
                'key1' => 'value2',
                'key2' => 50000
            ];

            $collection->select_db(uniqid('', true));
            $collection->add($value1, ['tag' => 'value1']);
            $collection->add($value2, ['tag' => 'value2']);

            [$fetched, $count] = $collection->fetch(['tag' => 'value1']);
            self::assertEquals(1, $count);
            self::assertCount(1, $fetched);
            self::assertEquals($value1, $fetched[0]);

            [$fetched, $count] = $collection->fetch(['tag' => 'value2']);
            self::assertEquals(1, $count);
            self::assertCount(1, $fetched);
            self::assertEquals($value2, $fetched[0]);

            [, $count] = $collection->fetch([]);
            self::assertEquals(2, $count);

            $collection->select_db(uniqid('', true));
            [, $count] = $collection->fetch([]);
            self::assertEquals(0, $count);
        } finally {
            $agent->close();
        }
    }
}
