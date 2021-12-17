<?php


namespace Siruis\Tests\Helpers;

use Siruis\Agent\Agent\Agent;
use Siruis\Agent\Connections\Endpoint;
use Siruis\Agent\Pairwise\Me;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Pairwise\Their;
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
            'test_suite_baseurl' => getenv('TEST_SUITE_BASE_URL') ?: 'http://localhost',
            'test_suite_overlay_address' => 'http://10.0.0.90',
            'old_agent_address' => getenv('INDY_AGENT_BASE_URL') ?: 'http://127.0.0.1:88',
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

    public static function test_suite(): ServerTestSuite
    {
        return static::get_suite_singleton();
    }

    public static function prover_master_secret_name(): string
    {
        return 'prover_master_secret_name';
    }

    public static function indy_agent(): IndyAgent
    {
        return self::get_indy_agent_singleton();
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

    public static function A(): Agent
    {
        return self::get_agent('agent1');
    }

    public static function B(): Agent
    {
        return self::get_agent('agent2');
    }

    public static function C(): Agent
    {
        return self::get_agent('agent3');
    }

    public static function D(): Agent
    {
        return self::get_agent('agent4');
    }

    public static function ledger_name(): string
    {
        return 'Ledger-'.uniqid();
    }

    public static function ledger_names(int $range = 2): array
    {
        $ledger_names = [];
        for ($i = 0; $i < $range; $i++) {
            array_push($ledger_names, 'Ledger-'.uniqid());
        }
        return $ledger_names;
    }

    public static function default_network(): string
    {
        return 'default';
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

    /**
     * @param array $endpoints
     * @return Endpoint[]
     */
    public static function get_endpoints(array $endpoints): array
    {
        $return = [];
        foreach ($endpoints as $endpoint) {
            if ($endpoint->routingKeys == []) {
                array_push($return, $endpoint);
            }
        }
        return $return;
    }

    public static function get_pairwise(Agent $me, Agent $their)
    {
        $suite = self::get_suite_singleton();
        $me_params = $suite->get_agent_params($me->name);
        $their_params = $suite->get_agent_params($their->name);
        $me_label = array_keys($me_params['entities'])[0];
        $me_entity = array_values($me_params['entities'])[0];
        $their_label = array_keys($their_params['entities'])[0];
        $their_entity = array_values($their_params['entities'])[0];
        $me_endpoint_address = self::get_endpoints($me->endpoints)[0]->address;
        $their_endpoint_address = self::get_endpoints($their->endpoints)[0]->address;
        foreach ([
            [$me, $me_entity, $their_entity, $their_label, $their_endpoint_address],
            [$their, $their_entity, $me_entity, $me_label, $me_endpoint_address]
        ] as list($agent, $entity_me, $entity_their, $label_their, $endpoint_their)) {
            /** @var Agent $agent */
            $pairwise = $agent->pairwise_list->load_for_did($their_entity['did']);
            $is_filled = $pairwise && $pairwise->metadata;
            if (!$is_filled) {
                $me_ = new Me($entity_me['did'], $entity_me['verkey']);
                $their_ = new Their($entity_their['did'], $their_label, $endpoint_their, $entity_their['verkey']);
                $metadata = [
                    'me' => [
                        'did' => $entity_me['did'],
                        'verkey' => $entity_me['verkey'],
                        'did_doc' => null
                    ],
                    'their' => [
                        'did' => $entity_their['did'],
                        'verkey' => $entity_their['verkey'],
                        'label' => $label_their,
                        'endpoint' => [
                            'address' => $endpoint_their,
                            'routing_keys' =>  []
                        ],
                        'did_doc' => null,
                    ]
                ];
                $pairwise = new Pairwise($me_, $their_, $metadata);
                $agent->wallet->did->store_their_did($entity_their['did'], $entity_their['verkey']);
                $agent->pairwise_list->ensure_exists($pairwise);
            }
        }
        return $me->pairwise_list->load_for_did($their_entity['did']);
    }
}
