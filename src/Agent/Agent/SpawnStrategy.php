<?php


namespace Siruis\Agent\Agent;


use MyCLabs\Enum\Enum;

class SpawnStrategy extends Enum
{
    const PARALLEL = 1;
    const CONCURRENT = 2;
}