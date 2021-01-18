<?php


namespace Siruis\Tests\Helpers;


use Siruis\Encryption\Encryption;
use Siruis\Encryption\P2PConnection;
use Siruis\Errors\Exceptions\SiriusCryptoError;
use Siruis\RPC\Tunnel\AddressedTunnel;
use SodiumException;

class Conftest
{
    /**
     * @return array[]
     * @throws SiriusCryptoError
     * @throws SodiumException
     */
    public static function p2p()
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
}