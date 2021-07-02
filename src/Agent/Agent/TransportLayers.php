<?php


namespace Siruis\Agent\Agent;


use Siruis\Agent\Coprotocols\PairwiseCoProtocolTransport;
use Siruis\Agent\Coprotocols\TheirEndpointCoProtocolTransport;
use Siruis\Agent\Coprotocols\ThreadBasedCoProtocolTransport;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Agent\Pairwise\TheirEndpoint;

abstract class TransportLayers
{
    public abstract function spawnTheirEndpoint(string $my_verkey, TheirEndpoint $endpoint): TheirEndpointCoProtocolTransport;

    public abstract function spawnPairwise(Pairwise $pairwise): PairwiseCoProtocolTransport;

    public abstract function spawnThidPairwise(string $thid, Pairwise $pairwise): ThreadBasedCoProtocolTransport;

    public abstract function spawnThid(string $thid): ThreadBasedCoProtocolTransport;

    public abstract function spawnThidPairwisePthd(string $thid, Pairwise $pairwise, string $pthid): ThreadBasedCoProtocolTransport;

    public abstract function spawnThidPthid(string $thid, string $pthid): ThreadBasedCoProtocolTransport;
}