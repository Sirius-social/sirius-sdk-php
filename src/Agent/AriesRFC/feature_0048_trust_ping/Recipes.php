<?php


namespace Siruis\Agent\AriesRFC\feature_0048_trust_ping;


use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Errors\Exceptions\SiriusInvalidMessageClass;
use Siruis\Errors\Exceptions\SiriusInvalidType;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Hub\Coprotocols\CoProtocolThreadedP2P;

class Recipes
{
    /**
     * Send pin g to remote participant and wait ong response
     *
     * @param Pairwise $their
     * @param string|null $comment
     * @param int $wait_timeout
     * @return array
     * @throws SiriusInvalidMessageClass
     * @throws SiriusInvalidType
     * @throws SiriusValidationError
     */
    public static function ping_their(Pairwise $their, string $comment = null, int $wait_timeout = 15): array
    {
        $ping = new Ping(
            [], null, null, null, $comment, true
        );
        $co = new CoProtocolThreadedP2P(
            $ping->id, $their, null,  $wait_timeout
        );

        list($success, $pong) = $co->switch($ping);
        if ($success && $ping instanceof Pong) {
            return [true, $pong];
        } else {
            return [false, null];
        }
    }
}