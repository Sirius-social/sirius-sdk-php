<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages;


use Siruis\Encryption\Encryption;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Helpers\ArrayHelper;

class RequestCredentialMessage extends BaseIssueCredentialMessage
{
    public $NAME = 'request-credential';

    public const NAME = 'request-credential';

    public function __construct(
        array $payload,
        string $comment = null,
        array $cred_request = null,
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
        if ($cred_request) {
            $this->payload['requests~attach'] = [
                [
                    '@id' => 'cred-request-'.$this->getId(),
                    'mime-type' => 'application/json',
                    'data' => [
                        'base64' => Encryption::bytes_to_b64(json_encode($cred_request))
                    ]
                ],
            ];
        }
    }

    /**
     * @return array|null
     * @throws \SodiumException
     */
    public function getCredRequest()
    {
        $request = ArrayHelper::getValueWithKeyFromArray('requests~attach', $this->payload);
        if ($request) {
            if (!ArrayHelper::is_assoc($request)) {
                $request = $request[0];
            }
            $body = $request['data']['base64'];
            $body = Encryption::b64_to_bytes($body);
            $body = json_decode($body);
            return $body;
        } else {
            return null;
        }
    }

    public function validate()
    {
        parent::validate();
        if (!key_exists('requests~attach', $this->payload)) {
            throw new SiriusValidationError('Expected offer attribute "requests~attach" missing');
        }
    }
}