<?php

namespace Siruis\Tests;

use PHPUnit\Framework\TestCase;
use Siruis\Agent\Wallet\Abstracts\NonSecrets\RetrieveRecordOptions;
use Siruis\Agent\Wallet\Impl\NonSecretsProxy;
use Siruis\Hub\Core\Hub;
use Siruis\Tests\Helpers\Conftest;

class NonSecretsTest extends TestCase
{
    /**
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_record_value_ops(): void
    {
        $test_suite = Conftest::test_suite();
        $params = $test_suite->get_agent_params('agent4');
        Hub::alloc_context($params['server_address'], $params['credentials'], $params['p2p']);
        $value = 'my-value';
        $my_id = 'my-id'.uniqid('', true);
        NonSecretsProxy::add_wallet_record('type', $my_id, $value);
        $opts = new RetrieveRecordOptions();
        $opts->checkAll();
        $value_info = NonSecretsProxy::get_wallet_record('type', $my_id, $opts);
        self::assertEquals($my_id, $value_info['id']);
        self::assertEquals([], $value_info['tags']);
        self::assertEquals($value, $value_info['value']);
        self::assertEquals('type', $value_info['type']);

        $value_new = 'my-new-value';
        NonSecretsProxy::update_wallet_record_value('type', $my_id, $value_new);
        $value_info = NonSecretsProxy::get_wallet_record('type', $my_id, $opts);
        self::assertEquals($value_new, $value_info['value']);
        NonSecretsProxy::delete_wallet_record('type', $my_id);
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_record_tags_ops(): void
    {
        $test_suite = Conftest::test_suite();
        $params = $test_suite->get_agent_params('agent4');
        Hub::alloc_context($params['server_address'], $params['credentials'], $params['p2p']);
        $my_id = 'my-id'.uniqid('', true);
        $value = 'my-value';
        $tags = ['tag1' => 'val1', '~tag2' => 'val2'];
        NonSecretsProxy::add_wallet_record('type', $my_id, $value, $tags);
        $opts = new RetrieveRecordOptions();
        $opts->checkAll();
        $value_info = NonSecretsProxy::get_wallet_record('type', $my_id, $opts);

        self::assertEquals($my_id, $value_info['id']);
        self::assertEquals($tags, $value_info['tags']);
        self::assertEquals($value, $value_info['value']);
        self::assertEquals('type', $value_info['type']);

        $upd_tags = [
            'ext-tag' => 'val3'
        ];
        NonSecretsProxy::update_wallet_record_tags('type', $my_id, $upd_tags);
        $value_info = NonSecretsProxy::get_wallet_record('type', $my_id, $opts);
        self::assertEquals($upd_tags, $value_info['tags']);

        NonSecretsProxy::add_wallet_record_tags('type', $my_id, $tags);
        $value_info = NonSecretsProxy::get_wallet_record('type', $my_id, $opts);
        self::assertEquals(array_merge($tags, $upd_tags), $value_info['tags']);

        NonSecretsProxy::delete_wallet_record_tags('type', $my_id, ['ext-tag']);
        $value_info = NonSecretsProxy::get_wallet_record('type', $my_id, $opts);
        self::assertEquals($tags, $value_info['tags']);
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_maintain_tags_only_update_ops(): void
    {
        $test_suite = Conftest::test_suite();
        $params = $test_suite->get_agent_params('agent4');
        Hub::alloc_context($params['server_address'], $params['credentials'], $params['p2p']);
        $my_id = 'my-id'.uniqid('', true);
        $value = 'my-value';
        NonSecretsProxy::add_wallet_record('type', $my_id, $value);
        $opts = new RetrieveRecordOptions();
        $opts->checkAll();
        $value_info = NonSecretsProxy::get_wallet_record('type', $my_id, $opts);
        self::assertEquals($my_id, $value_info['id']);
        self::assertEquals([], $value_info['tags']);
        self::assertEquals($value, $value_info['value']);
        self::assertEquals('type', $value_info['type']);

        $tags1 = [
            'tag1' => 'val1',
            '~tag2' => 'val2'
        ];

        NonSecretsProxy::update_wallet_record_tags('type', $my_id, $tags1);
        $value_info = NonSecretsProxy::get_wallet_record('type', $my_id, $opts);
        self::assertEquals($tags1, $value_info['tags']);

        $tags2 = [
            'tag3' => 'val3'
        ];
        NonSecretsProxy::update_wallet_record_tags('type', $my_id, $tags2);
        $value_info = NonSecretsProxy::get_wallet_record('type', $my_id, $opts);
        self::assertEquals($tags2, $value_info['tags']);
    }

    /**
     * @throws \JsonException
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     */
    public function test_wallet_search_sqlite(): void
    {
        $test_suite = Conftest::test_suite();
        $params = $test_suite->get_agent_params('agent4');
        Hub::alloc_context($params['server_address'], $params['credentials'], $params['p2p']);
        $my_id1 = 'my-id-'.uniqid('', true);
        $my_id2 = 'my-id-'.uniqid('', true);
        $type_ = 'type_'.uniqid('', true);
        $opts = new RetrieveRecordOptions();
        $opts->checkAll();
        $tags1 = [
            'tag1' => 'val1',
            '~tag2' => '5',
            'marker' => 'A'
        ];
        $tags2 = [
            'tag3' => 'val3',
            '~tag4' => '6',
            'marker' => 'B'
        ];
        NonSecretsProxy::add_wallet_record($type_, $my_id1, 'value1', $tags1);
        NonSecretsProxy::add_wallet_record($type_, $my_id2, 'value2', $tags2);

        $query = ['tag1' => 'val1'];
        [$records, $total] = NonSecretsProxy::wallet_search($type_, $query, $opts);
        self::assertEquals(1, $total);
        self::assertStringContainsString('value1', json_encode($records, JSON_THROW_ON_ERROR));

        $query = [
            '$or' => [
                ['tag1' => 'val1'],
                ['~tag4' => '6']
            ]
        ];
        [$records, $total] = NonSecretsProxy::wallet_search($type_, $query, $opts);
        self::assertCount(1, $records);
        self::assertEquals(2, $total);

        [$records, $total] = NonSecretsProxy::wallet_search($type_, $query, $opts, 1000);
        self::assertCount(2, $records);
        self::assertEquals(2, $total);

        $query = [
            'marker' => ['$in' => ['A', 'C']]
        ];
        [, $total] = NonSecretsProxy::wallet_search($type_, $query, $opts);
        self::assertEquals(1, $total);
    }
}
