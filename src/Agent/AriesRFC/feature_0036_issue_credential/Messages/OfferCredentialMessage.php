<?php


namespace Siruis\Agent\AriesRFC\feature_0036_issue_credential\Messages;


use Siruis\Encryption\Encryption;
use Siruis\Errors\Exceptions\SiriusValidationError;
use Siruis\Helpers\ArrayHelper;

class OfferCredentialMessage extends BaseIssueCredentialMessage
{
    public $NAME = 'offer-credential';

    public const NAME = 'offer-credential';

    /**
     * OfferCredentialMessage constructor.
     * @param array $payload
     * @param string|null $comment
     * @param array|null $offer
     * @param array|null $cred_def
     * @param ProposedAttrib[]|null $preview
     * @param array|null $issuer_schema
     * @param AttribTranslation[]|null $translation
     * @param string|null $expires_time
     * @param mixed ...$args
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidMessageClass
     * @throws \Siruis\Errors\Exceptions\SiriusInvalidType
     * @throws \Siruis\Errors\Exceptions\SiriusValidationError
     * @throws \SodiumException
     * @throws \JsonException
     */
    public function __construct(
        array $payload,
        string $comment = null,
        array $offer = null,
        array $cred_def = null,
        array $preview = null,
        array $issuer_schema = null,
        array $translation = null,
        string $expires_time = null,
        ...$args
    )
    {
        parent::__construct($payload, ...$args);
        if ($comment) {
            $this->payload['comment'] = $comment;
        }
        if ($preview) {
            $attributes = [];
            foreach ($preview as $item) {
                $attributes[] = $item->to_json();
            }
            $this->payload['credential_preview'] = [
                '@type' => self::CREDENTIAL_PREVIEW_TYPE,
                'attributes' => $attributes
            ];
        }
        if ($translation) {
            foreach ($translation as $item) {
                $attributes[] = new AttribTranslation($item->data['attrib_name'], $item->data['translation']);
            }
        }
        if ($offer && $cred_def) {
            $payload = [$offer, $cred_def];
            $this->payload['offers~attach'] = [
                [
                    '@id' => 'libindy-cred-offer-'.$this->getId(),
                    'mime-type' => 'application/json',
                    'data' => [
                        'base64' => Encryption::bytes_to_b64(json_encode($payload, JSON_THROW_ON_ERROR))
                    ]
                ]
            ];
        }
        if ($translation || $issuer_schema) {
            $this->payload['~attach'] = [];
            if ($translation) {
                $attributes = [];
                foreach ($translation as $item) {
                    $attributes[] = $item->to_json();
                }
                $this->payload['~attach'][] = [
                    '@type' => self::CREDENTIAL_TRANSLATION_TYPE,
                    'id' => self::CREDENTIAL_TRANSLATION_ID,
                    '~l10n' => ['locale' => $this->getLocale()],
                    'mime-type' => 'application/json',
                    'data' => [
                        'json' => $attributes
                    ]
                ];
            }
            if ($issuer_schema) {
                $this->payload['~attach'][] = [
                    '@type' => self::ISSUER_SCHEMA_TYPE,
                    'id' => self::ISSUER_SCHEMA_ID,
                    'mime-type' => 'application/json',
                    'data' => [
                        'json' => $issuer_schema
                    ]
                ];
            }
        }
        if ($expires_time) {
            $this->payload['~timing'] = [
                'expires_time' => $expires_time
            ];
        }
    }

    public function getComment()
    {
        return ArrayHelper::getValueWithKeyFromArray('comment', $this->payload);
    }

    public function getPreview()
    {
        $preview = ArrayHelper::getValueWithKeyFromArray('credential_preview', $this->payload);
        if (is_array($preview) && $preview['@type'] == self::CREDENTIAL_PREVIEW_TYPE) {
            $attribs = $preview['attributes'] ?? [];
            $attributes = [];
            foreach ($attribs as $item) {
                array_push($attributes, new ProposedAttrib($item['name'], $item['value'], $item['mime-type']));
            }
            return $attributes;
        } else {
            return null;
        }
    }

    public function getTranslation()
    {
        $attaches = ArrayHelper::getValueWithKeyFromArray('~attach', $this->payload, []);
        $tr = null;
        foreach ($attaches as $attach) {
            if ($attach['@type'] == self::CREDENTIAL_TRANSLATION_TYPE) {
                $tr = $attach;
                break;
            }
        }
        if ($tr) {
            $translation = $tr['data']['json'];
            $ret = [];
            foreach ($translation as $item) {
                array_push($ret, new AttribTranslation(...$item));
            }
            return $ret;
        } else {
            return null;
        }
    }

    public function getIssuerSchema()
    {
        $attaches = $this->payload['~attach'];
        $cs = null;
        foreach ($attaches as $attach) {
            if ($attach['@type'] == self::ISSUER_SCHEMA_TYPE) {
                $cs = $attach;
                break;
            }
        }
        if ($cs) {
            return $cs['data']['json'];
        } else {
            return null;
        }
    }

    public function getOffer()
    {
        try {
            list($_, $offer, $_) = $this->parse();
        } catch (SiriusValidationError $error) {
            return null;
        }
        return $offer;
    }

    public function getCredDef()
    {
        try {
            list($_, $_, $cred_def) = $this->parse();
        } catch (SiriusValidationError $error) {
            return null;
        }
        return $cred_def;
    }

    public function getExpiresTime()
    {
        $timing = ArrayHelper::getValueWithKeyFromArray('~timing', $this->payload, []);
        return ArrayHelper::getValueWithKeyFromArray('expires_time', $timing);
    }

    public function parse(bool $mute_errors = false)
    {
        $offer_attaches = ArrayHelper::getValueWithKeyFromArray('offers~attach', $this->payload);
        if (ArrayHelper::is_assoc($offer_attaches)) {
            $offer_attaches = [$offer_attaches];
        }
        if (!is_array($offer_attaches) || count($offer_attaches) == 0) {
            throw new SiriusValidationError('Expected attribute "offer~attach" must contains cred-Offer and cred-Def');
        }
        $offer = $offer_attaches[0];
        $offer_body = null;
        $cred_def_body = null;

        foreach ($offer_attaches as $attach) {
            $raw_base64 = $attach['data']['base64'];
            if ($raw_base64) {
                $payload = json_decode(Encryption::b64_to_bytes($raw_base64));
                $offer_fields = ['key_correctness_proof', 'nonce', 'schema_id', 'cred_def_id'];
                $cred_def_fields = ['value', 'type', 'ver', 'schemaId', 'id', 'tag'];
                if (ArrayHelper::all_keys_exists($payload, $offer_fields)) {
                    foreach (array_values($payload) as $attr => $val) {
                        if (in_array($attr, $offer_fields)) {
                            array_push($offer_body, [$attr => $val]);
                        }
                    }
                }
                if (ArrayHelper::all_keys_exists($payload, $cred_def_fields)) {
                    foreach (array_values($payload) as $attr => $val) {
                        if (in_array($attr, $cred_def_fields)) {
                            array_push($cred_def_body, [$attr => $val]);
                        }
                    }
                }
            }
        }
        if (!$offer_body) {
            if (!$mute_errors) {
                throw new SiriusValidationError('Expected offer~attach must contains Payload with offer');
            }
        }
        if (!$cred_def_body) {
            if (!$mute_errors) {
                throw new SiriusValidationError('Expected offer~attach must contains Payload with cred_def data');
            }
        }

        return [$offer, $offer_body, $cred_def_body];
    }

    public function validate()
    {
        parent::validate();
        if (!key_exists('offers~attach', $this->payload)) {
            throw new SiriusValidationError('Expected offer attribute "offers~attach" missing');
        }
        $this->parse();
    }
}