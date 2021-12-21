<?php

namespace Siruis\Tests\Threads\test_0036_issue_credential;

use Siruis\Tests\Helpers\IndyAgent;
use Threaded;

class RunIssuerIndyAgent extends Threaded
{
    /**
     * @var \Siruis\Tests\Helpers\IndyAgent
     */
    private $indy_agent;
    /**
     * @var string
     */
    private $cred_def_id;
    /**
     * @var array
     */
    private $cred_def;
    /**
     * @var array
     */
    private $values;
    /**
     * @var string
     */
    private $their_did;
    /**
     * @var array|null
     */
    private $issuer_schema;
    /**
     * @var \Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\ProposedAttrib[]|null
     */
    private $preview;
    /**
     * @var \Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\AttribTranslation[]|null
     */
    private $translation;
    /**
     * @var string|null
     */
    private $rev_reg_id;
    /**
     * @var string|null
     */
    private $cred_id;
    /**
     * @var int
     */
    private $ttl;
    /**
     * @var mixed
     */
    public $result;

    /**
     * @param \Siruis\Tests\Helpers\IndyAgent $indy_agent
     * @param string $cred_def_id
     * @param array $cred_def
     * @param array $values
     * @param string $their_did
     * @param array|null $issuer_schema
     * @param \Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\ProposedAttrib[]|null $preview
     * @param \Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\AttribTranslation[]|null $translation
     * @param string|null $rev_reg_id
     * @param string|null $cred_id
     * @param int $ttl
     */
    public function __construct(
        IndyAgent $indy_agent, string $cred_def_id, array $cred_def,
        array $values, string $their_did, array $issuer_schema = null,
        array $preview = null, array $translation = null,
        string $rev_reg_id = null, string $cred_id = null, int $ttl = 60
    )
    {
        $this->indy_agent = $indy_agent;
        $this->cred_def_id = $cred_def_id;
        $this->cred_def = $cred_def;
        $this->values = $values;
        $this->their_did = $their_did;
        $this->issuer_schema = $issuer_schema;
        $this->preview = $preview;
        $this->translation = $translation;
        $this->rev_reg_id = $rev_reg_id;
        $this->cred_id = $cred_id;
        $this->ttl = $ttl;
    }

    /**
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function work(): void
    {
        $log = $this->indy_agent->issue_credential(
            $this->cred_def_id, $this->cred_def, $this->values, $this->their_did,
            'Test issuer', null, $this->issuer_schema,
            $this->preview, $this->translation, $this->rev_reg_id, $this->cred_id, $this->ttl
        );
        if (count($log) > 2) {
            $last = end($log)['message'];
            $pred = $log[count($log)-2]['message'];
            $this->result = strpos('Received ACK', $pred) && strpos('Done', $last);
        } else {
            $this->result = false;
        }
    }
}