<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages;


use Siruis\Encryption\Encryption;
use Siruis\Helpers\ArrayHelper;

class PresentationMessage extends BasePresentProofMessage
{
    public $NAME = 'presentation';
    public const NAME = 'presentation';

    public function __construct(array $payload,
                                array $proof = null,
                                string $comment = null,
                                string $presentation_id = null,
                                ...$args)
    {
        parent::__construct($payload, ...$args);
        if (!is_null($comment)) {
            $this->payload['comment'] = $comment;
        }
        if (!is_null($proof)) {
            if (!$presentation_id) {
                $presentation_id = uniqid();
            }
            $this->payload['presentations~attach'] = [
                [
                    '@id' => 'libindy-presentation-'.$presentation_id,
                    'mime-type' => 'application/json',
                    'data' => [
                        'base64' => Encryption::bytes_to_b64(json_encode($proof))
                    ]
                ]
            ];
        }
    }

    public function getProof()
    {
        $attaches = $this->payload['presentations~attach'] ?? null;
        if (!$attaches) {
            return null;
        }
        if (is_array($attaches)) {
            $attaches = [$attaches];
        }
        $accum = [];
        foreach ($attaches as $attach) {
            $payload = json_decode(Encryption::b64_to_bytes($attach['data']['base64']));
            array_merge($accum, $payload);
        }
        return $accum;
    }

    public function getComment()
    {
        return ArrayHelper::getValueWithKeyFromArray('comment', $this->payload);
    }
}