<?php


namespace Siruis\Agent\Consensus\Messages;


use Siruis\Agent\Base\AriesProblemReport;

class SimpleConsensusProblemReport extends AriesProblemReport
{
    public $PROTOCOL = 'simple-consensus';
}