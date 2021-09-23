<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages;


use Siruis\Encryption\Encryption;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Helpers\ArrayHelper;

class IssueCredentialMessage extends BaseIssueCredentialMessage
{
    public $NAME = 'issue-credential';

    public const NAME = 'issue-credential';

    public function __construct(
        array $payload,
        string $comment = null,
        array $cred = null,
        string $cred_id = null,
        string $locale = self::DEF_LOCALE,
        string $id_ = null,
        string $version = null,
        string $doc_uri = null
    )
    {
        parent::__construct($payload, $locale, $id_, $version, $doc_uri);
        if ($comment) {
            $this->payload['comment'] = $comment;
        }
        if ($cred) {
            if ($cred_id) {
                $message_id = $cred_id;
            } else {
                $message_id = 'libindy-cred-'.$this->getId();
            }
            $this->payload['credentials~attach'] = [
                [
                    '@id' => $message_id,
                    'mime-type' => 'application/json',
                    'data' => Encryption::bytes_to_b64(json_encode($cred))
                ]
            ];
        }
    }

    public function getCredId()
    {
        $attaches = ArrayHelper::getValueWithKeyFromArray('credentials~attach', $this->payload);
        if ($attaches) {
            if (ArrayHelper::is_assoc($attaches)) {
                $attaches = [$attaches];
            }
            if (!ArrayHelper::is_assoc($attaches)) {
                $attach = $attaches[0];
                return $attach['@id'] ?? null;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    public function getCred()
    {
        $attaches = ArrayHelper::getValueWithKeyFromArray('credentials~attach', $this->payload);
        if ($attaches) {
            if (ArrayHelper::is_assoc($attaches)) {
                $attaches = [$attaches];
            }
            if (!ArrayHelper::is_assoc($attaches)) {
                $attach = $attaches[0];
                $b64 = $attach['data']['bas64'];
                if ($b64) {
                    $body = Encryption::b64_to_bytes($b64);
                    return json_decode($body);
                } else {
                    return null;
                }
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    public function validate()
    {
        parent::validate();
        if (!key_exists('credentials~attach', $this->payload)) {
            throw new SiriusValidationError('Expected issue attribute "credentials~attach" missing');
        }
        if (is_null($this->getCred())) {
            throw new SiriusValidationError('Credential is empty in "credentials~attach" field');
        }
    }
}