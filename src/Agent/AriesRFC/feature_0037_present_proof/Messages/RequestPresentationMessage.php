<?php


namespace Siruis\Agent\AriesRFC\feature_0037_present_proof\Messages;


use Siruis\Encryption\Encryption;
use Siruis\Helpers\ArrayHelper;

class RequestPresentationMessage extends BasePresentProofMessage
{
    public $NAME = 'request-presentation';
    public const NAME = 'request-presentation';

    /**
     * RequestPresentationMessage constructor.
     * @param array $payload
     * @param array|null $proof_request
     * @param string|null $comment
     * @param AttribTranslation[]|null $translation
     * @param string|null $expires_time
     * @param mixed ...$args
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     */
    public function __construct(array $payload,
                                array $proof_request = null,
                                string $comment = null,
                                array $translation = null,
                                string $expires_time = null,
                                ...$args)
    {
        parent::__construct($payload, ...$args);
        if (!is_null($comment)) {
            $this->payload['comment'] = $comment;
        }
        $id_suffix = uniqid();
        if (!is_null($proof_request)) {
            $this->payload['request_presentations~attach'] = [
                [
                    '@id' => 'libindy-request-presentation-'.$id_suffix,
                    'mime-type' => 'application/json',
                    'data' => [
                        'base64' => Encryption::bytes_to_b64(json_encode($proof_request))
                    ]
                ]
            ];
        }
        if (!is_null($translation)) {
            $ts = [];
            $trans_jsons = [];
            foreach ($translation as $item) {
                $val = new AttribTranslation($item->data['attrib_name'], $item->data['translation']);
                array_push($ts, $val);
                array_push($trans_jsons, $val->toJson());
            }
            $this->payload['~attach'] = [
                [
                    '@type' => self::CREDENTIAL_TRANSLATION_TYPE,
                    'id' => self::CREDENTIAL_TRANSLATION_ID,
                    '~l10n' => ['locale' => $this->getLocale()],
                    'mime-type' => 'application/json',
                    'data' => [
                        'json' => $trans_jsons
                    ]
                ]
            ];
        }
        if (!is_null($expires_time)) {
            $this->payload['~timing'] = [
                'expires_time' => $expires_time
            ];
        }
    }

    public function getProofRequest()
    {
        $attaches = $this->payload['request_presentations~attach'] ?? null;
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
        return $this->payload['comment'] ?? null;
    }

    public function getTranslation()
    {
        $attaches = $this->payload['~attach'];
        $tr = null;
        foreach ($attaches as $item) {
            if ($item['@type'] == self::CREDENTIAL_TRANSLATION_TYPE) {
                $tr = $item;
                break;
            }
        }
        if ($tr) {
            $data = ArrayHelper::getValueWithKeyFromArray('data', $tr, []);
            $translation = ArrayHelper::getValueWithKeyFromArray('json', $data, []);
            $trs = [];
            foreach ($translation as $item) {
                $val = new AttribTranslation($item->data['attrib_name'], $item->data['translation']);
                array_push($trs, $val);
            }
            return $trs;
        } else {
            return null;
        }
    }

    public function getExpiresTime()
    {
        $timing = ArrayHelper::getValueWithKeyFromArray('~timing', $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('expires_time', $timing);
    }
}