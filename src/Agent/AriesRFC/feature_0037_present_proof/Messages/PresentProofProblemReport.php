<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages;


use Siruis\Agent\Base\AriesProblemReport;

class PresentProofProblemReport extends AriesProblemReport
{
    public $PROTOCOL = BasePresentProofMessage::PROTOCOL;

    public const PROTOCOL = BasePresentProofMessage::PROTOCOL;

    public function __construct(...$args)
    {
        parent::__construct(...$args);
        self::registerMessageClass(PresentProofProblemReport::class, $this->PROTOCOL, $this->NAME);
    }
}