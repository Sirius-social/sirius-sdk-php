<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages;


class ProposeCredentialMessage extends BaseIssueCredentialMessage
{
    public $NAME = 'propose-credential';

    public const NAME = 'propose-credential';

    /**
     * ProposeCredentialMessage constructor.
     * @param array $payload
     * @param string|null $comment
     * @param ProposedAttrib[]|null $proposal_attrib
     * @param string|null $schema_id
     * @param string|null $schema_name
     * @param string|null $schema_version
     * @param string|null $schema_issuer_did
     * @param string|null $cred_def_id
     * @param string|null $issuer_did
     * @param AttribTranslation[]|null $proposal_attrib_translation
     * @param mixed ...$args
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function __construct(
        array $payload,
        string $comment = null,
        array $proposal_attrib = null,
        string $schema_id = null,
        string $schema_name = null,
        string $schema_version = null,
        string $schema_issuer_did = null,
        string $cred_def_id = null,
        string $issuer_did = null,
        array $proposal_attrib_translation = null,
        ...$args
    )
    {
        parent::__construct($payload, ...$args);
        if ($comment) {
            $this->payload['comment'] = $comment;
        }
        if ($schema_id) {
            $this->payload['schema_id'] = $schema_id;
        }
        if ($schema_name) {
            $this->payload['schema_name'] = $schema_name;
        }
        if ($schema_version) {
            $this->payload['schema_version'] = $schema_version;
        }
        if ($schema_issuer_did) {
            $this->payload['schema_issuer_did'] = $schema_issuer_did;
        }
        if ($cred_def_id) {
            $this->payload['cred_def_id'] = $cred_def_id;
        }
        if ($issuer_did) {
            $this->payload['issuer_did'] = $issuer_did;
        }
        if ($proposal_attrib) {
            $attributes = [];
            foreach ($proposal_attrib as $item) {
                array_push($attributes, $item->to_json());
            }
            $this->payload['credential_proposal'] = [
                '@type' => self::CREDENTIAL_PREVIEW_TYPE,
                'attributes' => $attributes
            ];
            if ($proposal_attrib_translation) {
                $trans = [];
                foreach ($proposal_attrib_translation as $item) {
                    array_push($trans, $item->to_json());
                }
                $this->payload['~attach'] = [
                    [
                        '@type' => self::CREDENTIAL_TRANSLATION_TYPE,
                        'id' => self::CREDENTIAL_TRANSLATION_ID,
                        '~l10n' => ['locale' => $this->getLocale()],
                        'mime-type' => 'application/json',
                        'data' => [
                            'json' => $trans
                        ]
                    ]
                ];
            }
        }
    }
}