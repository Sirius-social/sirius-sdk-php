<?php

namespace Siruis\Tests\Threads\test_0036_issue_credential;

use Siruis\Agent\AriesRFC\feature_0036_issue_credential\StateMachines\Issuer;
use Siruis\Agent\Ledgers\CredentialDefinition;
use Siruis\Agent\Ledgers\Schema;
use Siruis\Agent\Pairwise\Pairwise;
use Siruis\Encryption\P2PConnection;
use Siruis\Hub\Core\Hub;
use Threaded;

class RunIssuer extends Threaded
{
    /**
     * @var string
     */
    private $uri;
    /**
     * @var string
     */
    private $credentials;
    /**
     * @var \Siruis\Encryption\P2PConnection
     */
    private $p2p;
    /**
     * @var \Siruis\Agent\Pairwise\Pairwise
     */
    private $holder;
    /**
     * @var \Volatile
     */
    private $values;
    /**
     * @var \Siruis\Agent\Ledgers\Schema
     */
    private $schema;
    /**
     * @var \Siruis\Agent\Ledgers\CredentialDefinition
     */
    private $cred_def;
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
    private $cred_id;
    /**
     * @var mixed
     */
    public $result;

    /**
     * @param string $uri
     * @param string $credentials
     * @param \Siruis\Encryption\P2PConnection $p2p
     * @param \Siruis\Agent\Pairwise\Pairwise $holder
     * @param array $values
     * @param \Siruis\Agent\Ledgers\Schema $schema
     * @param \Siruis\Agent\Ledgers\CredentialDefinition $cred_def
     * @param \Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\ProposedAttrib[]|null $preview
     * @param \Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages\AttribTranslation[]|null $translation
     * @param string|null $cred_id
     */
    public function __construct(
        string $uri, string $credentials, P2PConnection $p2p, Pairwise $holder,
        array $values, Schema $schema, CredentialDefinition $cred_def,
        array $preview = null, array $translation = null, string $cred_id = null
    )
    {
        $this->uri = $uri;
        $this->credentials = $credentials;
        $this->p2p = $p2p;
        $this->holder = $holder;
        $this->values = $values;
        $this->schema = $schema;
        $this->cred_def = $cred_def;
        $this->preview = $preview;
        $this->translation = $translation;
        $this->cred_id = $cred_id;
    }

    /**
     * @throws \Siruis\Errors\Exceptions\SiriusConnectionClosed
     * @throws \Siruis\Errors\Exceptions\SiriusIOError
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusTimeoutIO
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     * @throws \Siruis\Errors\Exceptions\StateMachineAborted
     * @throws \SodiumException
     */
    public function work(): void
    {
        Hub::alloc_context($this->uri, $this->credentials, $this->p2p);
        $machine = new Issuer($this->holder);
        $success = $machine->issue(
            (array)$this->values,
            $this->schema,
            $this->cred_def,
            'Hello Iam issuer',
            $this->preview,
            $this->translation,
            $this->cred_id
        );
        $this->result = $success;
    }
}