<?php


namespace Siruis\Tests\Helpers;


use Siruis\Agent\Agent\Agent;
use Siruis\Encryption\Encryption;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use Siruis\RPC\Tunnel\AddressedTunnel;
use SodiumException;

class Conftest
{
    public static $SERVER_SUITE = null;
    public static $INDY_AGENT = null;

    public static function phpunit_configs(): array
    {
        return [
            'test_suite_baseurl' => getenv('TEST_SUITE_BASE_URL') ? getenv('TEST_SUITE_BASE_URL') : 'http://localhost',
            'test_suite_overlay_address' => 'http://10.0.0.90',
            'old_agent_address' => getenv('INDY_AGENT_BASE_URL') ? getenv('INDY_AGENT_BASE_URL') : 'http://127.0.0.1:88',
            'old_agent_overlay_address' => 'http://10.0.0.52:8888',
            'old_agent_root' => [
                'username' => 'root',
                'password' => 'root'
            ]
        ];
    }

    /**
     * @return array[]
     * @throws SiriusCryptoError
     * @throws SodiumException
     */
    public static function p2p(): array
    {
        $keys_agent = Encryption::create_keypair(b'000000000000000000000000000AGENT');
        $keys_sdk = Encryption::create_keypair(b'00000000000000000000000000000SDK');
        $agent = new P2PConnection(
            [
                Encryption::bytes_to_b58($keys_agent['verkey']),
                Encryption::bytes_to_b58($keys_agent['sigkey'])
            ],
            Encryption::bytes_to_b58($keys_sdk['verkey'])
        );
        $smart_contract = new P2PConnection(
            [
                Encryption::bytes_to_b58($keys_sdk['verkey']),
                Encryption::bytes_to_b58($keys_sdk['sigkey'])
            ],
            Encryption::bytes_to_b58($keys_agent['verkey'])
        );
        $downstream = new InMemoryChannel();
        $upstream = new InMemoryChannel();

        return [
            'agent' => [
                'p2p' => $agent,
                'tunnel' => new AddressedTunnel('memory://agent->sdk', $upstream, $downstream, $agent)
            ],
            'sdk' => [
                'p2p' => $smart_contract,
                'tunnel' => new AddressedTunnel('memory://sdk->agent', $downstream, $upstream, $smart_contract)
            ]
        ];
    }

    public static function agent1(): Agent
    {
        return self::get_agent('agent1');
    }

    public static function agent2(): Agent
    {
        return self::get_agent('agent2');
    }

    public static function agent3(): Agent
    {
        return self::get_agent('agent3');
    }

    public static function agent4(): Agent
    {
        return self::get_agent('agent4');
    }

    public static function get_suite_singleton(): ServerTestSuite
    {
        if (!self::$SERVER_SUITE instanceof ServerTestSuite) {
            $suite = new ServerTestSuite();
            $suite->ensure_is_alive();
            self::$SERVER_SUITE = $suite;
        }
        return self::$SERVER_SUITE;
    }

    public static function get_indy_agent_singleton(): IndyAgent
    {
        if (!self::$INDY_AGENT instanceof IndyAgent) {
            $agent = new IndyAgent();
            $agent->ensure_is_alive();
            self::$INDY_AGENT = $agent;
        }
        return self::$INDY_AGENT;
    }

    public static function get_agent(string $name): Agent
    {
        $params = self::get_suite_singleton()->get_agent_params($name);
        return new Agent(
            $params['server_address'],
            $params['credentials'],
            $params['p2p'],
            30,
            null,
            $name
        );
    }
}