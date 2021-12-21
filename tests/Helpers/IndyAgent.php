<?php


namespace Siruis\Tests\Helpers;


use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Siruis\Agent\Pairwise\Pairwise;
use function PHPUnit\Framework\assertTrue;

class IndyAgent
{
    public const WALLET = 'test';
    public const PASS_PHRASE = 'pass';
    public const DEFAULT_LABEL = 'BackCompatibility';
    public const SETUP_TIMEOUT = 60;

    /**
     * @var mixed
     */
    protected $address;
    /**
     * @var mixed
     */
    protected $auth_username;
    /**
     * @var mixed
     */
    protected $auth_password;
    /**
     * @var mixed
     */
    public $endpoint;
    /**
     * @var bool
     */
    protected $wallet_exists;
    /**
     * @var mixed
     */
    public $default_invitation;

    /**
     * IndyAgent constructor.
     */
    public function __construct()
    {
        $configs = Conftest::phpunit_configs();
        $this->address = $configs['old_agent_address'];
        $this->auth_username = $configs['old_agent_root']['username'];
        $this->auth_password = $configs['old_agent_root']['password'];
        $this->endpoint = null;
        $this->wallet_exists = false;
        $this->default_invitation = null;
    }

    /**
     * @param string $invitation_url
     * @param string|null $for_did
     * @param int|null $ttl
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function invite(string $invitation_url, string $for_did = null, int $ttl = null): void
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/endpoints/' . $this->endpoint['uid'] . '/invite/';
        $params = [
            'url' => $invitation_url,
            'pass_phrase' => self::PASS_PHRASE
        ];
        if ($for_did) {
            $params['my_did'] = $for_did;
        }
        if ($ttl) {
            $params['ttl'] = $ttl;
        }
        [$ok,] = $this->http_post($url, $params);
        assert($ok);
    }

    /**
     * @param string $label
     * @param string|null $seed
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function load_invitations(string $label, string $seed = null)
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/endpoints/' . $this->endpoint['uid'] . '/invitations/';
        [$ok, $collection] = $this->http_get($url);
        assertTrue($ok);
        return $collection;
    }

    /**
     * @param string $label
     * @param string|null $seed
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function create_invitation(string $label, string $seed = null)
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/endpoints/' . $this->endpoint['uid'] . '/invitations/';
        $params = ['label' => $label, 'pass_phrase' => self::PASS_PHRASE];
        if ($seed) {
            $params['seed'] = $seed;
        }
        [$ok, $invitation] = $this->http_post($url, $params);
        assertTrue($ok);
        return $invitation;
    }

    /**
     * @param string|null $seed
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function create_and_store_my_did(string $seed = null): array
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/did/create_and_store_my_did/';
        $params = ['pass_phrase' => self::PASS_PHRASE];
        if ($seed) {
            $params['seed'] = $seed;
        }
        [$ok, $resp] = $this->http_post($url, $params);
        assertTrue($ok);
        return [$resp['did'], $resp['verkey']];
    }

    /**
     * @param \Siruis\Agent\Pairwise\Pairwise $pw
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function create_pairwise_statically(Pairwise $pw): void
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/pairwise/create_pairwise_statically/';
        $metadata = [
            'label' => $pw->their->label,
            'their_vk' => $pw->their->verkey,
            'my_vk' => $pw->me->verkey,
            'their_endpoint' => $pw->their->endpoint
        ];
        $params = ['pass_phrase' => self::PASS_PHRASE];
        $params = array_merge($params, [
            'my_did' => $pw->me->did,
            'their_did' => $pw->their->did,
            'their_verkey' => $pw->their->verkey,
            'metadata' => $metadata
        ]);
        [$ok,] = $this->http_post($url, $params);
        assertTrue($ok);
    }

    /**
     * @param string $issuer_did
     * @param string $name
     * @param string $version
     * @param array $attributes
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function register_schema(string $issuer_did, string $name, string $version, array $attributes): array
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/did/' . $issuer_did . '/ledger/register_schema/';
        $params = [
            'pass_phrase' => self::PASS_PHRASE,
            'name' => $name,
            'version' => $version,
            'attributes' => $attributes
        ];
        [$ok, $resp] = $this->http_post($url, $params);
        assertTrue($ok);
        return [$resp['schema_id'], $resp['schema']];
    }

    /**
     * @param string $submitter_did
     * @param string $schema_id
     * @param string $tag
     * @param bool $support_revocation
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function register_cred_def(string $submitter_did, string $schema_id, string $tag, bool $support_revocation = false): array
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/did/' . $submitter_did . '/cred_def/create_and_send/';
        $params = [
            'pass_phrase' => self::PASS_PHRASE,
            'schema_id' => $schema_id,
            'tag' => $tag,
            'support_revocation' => $support_revocation
        ];
        [$ok, $resp] = $this->http_post($url, $params);
        assertTrue($ok);
        return [$resp['id'], $resp['cred_def']];
    }

    /**
     * @param string $cred_def_id
     * @param array $cred_def
     * @param array $values
     * @param string $their_did
     * @param string|null $comment
     * @param string|null $locale
     * @param array|null $issuer_schema
     * @param array|null $preview
     * @param array|null $translation
     * @param string|null $rev_reg_id
     * @param string|null $cred_id
     * @param int $ttl
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function issue_credential(
        string $cred_def_id, array $cred_def, array $values, string $their_did,
        string $comment = null, string $locale = null, array $issuer_schema = null,
        array $preview = null, array $translation = null, string $rev_reg_id = null,
        string $cred_id = null, int $ttl = 60
    )
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/messaging/issue_credential/';
        $params = [
            'pass_phrase' => self::PASS_PHRASE,
            'cred_def_id' => $cred_def_id,
            'cred_def' => $cred_def,
            'values' => $values,
            'their_did' => $their_did,
        ];
        if ($comment) {
            $params['comment'] = $comment;
        }
        if ($locale) {
            $params['locale'] = $locale;
        }
        if ($issuer_schema) {
            $params['issuer_schema'] = $issuer_schema;
        }
        if ($preview) {
            $params['preview'] = $preview;
        }
        if ($translation) {
            $params['translation'] = $translation;
        }
        if ($rev_reg_id) {
            $params['rev_reg_id'] = $rev_reg_id;
        }
        if ($cred_id) {
            $params['cred_id'] = $cred_id;
        }
        if ($ttl) {
            $params['ttl'] = $ttl;
        }
        $params['collect_log'] = true;
        [$ok, $resp] = $this->http_post($url, $params);
        assertTrue($ok);
        return $resp;
    }

    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    public function ensure_is_alive(): void
    {
        $inc_timeout = 10;
        foreach (range(1, self::SETUP_TIMEOUT, $inc_timeout) as $n) {
            [$ok,] = $this->http_get('/agent/admin/wallets');
            if ($ok) {
                break;
            }
            $progress = (float)($n / self::SETUP_TIMEOUT) * 100;
            echo 'Indy-Agent setup Progress: ' . $progress;
        }
        if (!$this->wallet_exists) {
            $http_post = $this->http_post(
                '/agent/admin/wallets/ensure_exists/',
                ['uid' => self::WALLET, 'pass_phrase' => self::PASS_PHRASE]
            );
            assertTrue($http_post[0]);
            $this->wallet_exists = true;
        }
        $http_post = $this->http_post(
            '/agent/admin/wallets/' . self::WALLET . '/open/',
            ['pass_phrase' => self::PASS_PHRASE]
        );
        assertTrue($http_post[0]);
        if (!$this->endpoint) {
            $url = '/agent/admin/wallets/' . self::WALLET . '/endpoints/';
            [$ok, $resp] = $this->http_get($url);
            assertTrue($ok);
            if ($resp['results']) {
                $this->endpoint = $resp['results'][0];
            } else {
                [$ok, $resp] = $this->http_post($url, ['host' => $this->address]);
                assertTrue($ok);
                $this->endpoint = $resp;
            }
        }
        if (!$this->default_invitation) {
            $url = '/agent/admin/wallets/' . self::WALLET . '/endpoints/' . $this->endpoint['uid'] . '/invitations/';
            [$ok, $resp] = $this->http_get($url);
            assertTrue($ok);
            $collection = [];
            foreach ($resp as $item) {
                if ($item['seed'] === 'default') {
                    $collection[] = $item;
                }
            }
            if ($collection) {
                $this->default_invitation = $collection[0];
            } else {
                [$ok, $resp] = $this->http_post(
                    $url,
                    ['label' => self::DEFAULT_LABEL, 'pass_phrase' => self::PASS_PHRASE, 'seed' => 'default']
                );
                assertTrue($ok);
                $this->default_invitation = $resp;
            }
        }
    }

    /**
     * @param string $path
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    protected function http_get(string $path): array
    {
        $url = urljoin($this->address, $path);
        $netloc = parse_url($this->address)['host'];
        $host = explode(':', $netloc)[0];
        $auth = [$this->auth_username, $this->auth_password];
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
            'host' => $host
        ];
        try {
            $resp = $client->get($url, [
                'headers' => $headers,
                'auth' => $auth
            ]);
            if ($resp->getStatusCode() === 200) {
                $content = json_decode($resp->getBody(), true, 512, JSON_THROW_ON_ERROR);
                return [true, $content];
            }

            $err_message = $resp->getBody();
            return [false, $err_message];
        } catch (ClientException $e) {
            return [false, null];
        }
    }

    /**
     * @param string $path
     * @param array|null $json_
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \JsonException
     */
    protected function http_post(string $path, array $json_ = null): array
    {
        $url = urljoin($this->address, $path);
        $netloc = parse_url($this->address)['host'];
        $host = explode(':', $netloc)[0];
        $auth = [$this->auth_username, $this->auth_password];
        $client = new Client();
        $headers = [
            'content-type' => 'application/json',
            'host' => $host
        ];
        try {
            $body = $json_ ? json_encode($json_, JSON_THROW_ON_ERROR) : null;
            $resp = $client->post($url, [
                'headers' => $headers,
                'body' => $body,
                'auth' => $auth
            ]);
            if (in_array($resp->getStatusCode(), [200, 201])) {
                try {
                    $content = json_decode($resp->getBody(), true, 512, JSON_THROW_ON_ERROR);
                } catch (Exception $e) {
                    $content= null;
                }
                return [true, $content];
            }

            $err_message = $resp->getBody();
            return [false, $err_message];
        } catch (ClientException $e) {
            return [false, null];
        }
    }
}