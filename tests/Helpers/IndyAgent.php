<?php


namespace Siruis\Tests\Helpers;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Siruis\Agent\Pairwise\Pairwise;
use function PHPUnit\Framework\assertTrue;

class IndyAgent
{
    const WALLET = 'test';
    const PASS_PHRASE = 'pass';
    const DEFAULT_LABEL = 'BackCompatibility';
    const SETUP_TIMEOUT = 60;

    protected $address;
    protected $auth_username;
    protected $auth_password;
    public $endpoint;
    protected $wallet_exists;
    public $default_invitation;

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

    public function invite(string $invitation_url, string $for_did = null, int $ttl = null)
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
        $http_post = $this->__http_post($url, $params);
        assert($http_post[0]);
    }

    public function load_invitations(string $label, string $seed = null)
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/endpoints/' . $this->endpoint['uid'] . '/invitations/';
        $http_get = $this->__http_get($url);
        $ok = $http_get[0];
        $collection = $http_get[1];
        assertTrue($ok);
        return $collection;
    }

    public function create_invitation(string $label, string $seed = null)
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/endpoints/' . $this->endpoint['uid'] . '/invitations/';
        $params = ['label' => $label, 'pass_phrase' => self::PASS_PHRASE];
        if ($seed) {
            $params['seed'] = $seed;
        }
        $http_post = $this->__http_post($url, $params);
        $ok = $http_post[0];
        $invitation = $http_post[1];
        assertTrue($ok);
        return $invitation;
    }

    public function create_and_store_my_did(string $seed = null)
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/did/create_and_store_my_did/';
        $params = ['pass_phrase' => self::PASS_PHRASE];
        if ($seed) {
            $params['seed'] = $seed;
        }
        $http_post = $this->__http_post($url, $params);
        $ok = $http_post[0];
        $resp = $http_post[1];
        assertTrue($ok);
        return [$resp['did'], $resp['verkey']];
    }

    public function create_pairwise_statically(Pairwise $pw)
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/pairwise/create_pairwise_statically/';
        $metadata = [
            'label' => $pw->their->label,
            'their_vk' => $pw->their->verkey,
            'my_vk' => $pw->me->verkey,
            'their_endpoint' => $pw->their->endpoint
        ];
        $params = ['pass_phrase' => self::PASS_PHRASE];
        array_push($params, [
            'my_did' => $pw->me->did,
            'their_did' => $pw->their->did,
            'their_verkey' => $pw->their->verkey,
            'metadata' => $metadata
        ]);
        $http_post = $this->__http_post($url, $params);
        assertTrue($http_post[0]);
    }

    public function register_schema(string $issuer_did, string $name, string $version, array $attributes)
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/did/' . $issuer_did . '/ledger/register_schema/';
        $params = [
            'pass_phrase' => self::PASS_PHRASE,
            'name' => $name,
            'version' => $version,
            'attributes' => $attributes
        ];
        $http_post = $this->__http_post($url, $params);
        $ok = $http_post[0];
        $resp = $http_post[1];
        assertTrue($ok);
        return [$resp['schema_id'], $resp['schema']];
    }

    public function register_cred_def(string $submitter_did, string $schema_id, string $tag, bool $support_revocation = false)
    {
        $url = '/agent/admin/wallets/' . self::WALLET . '/did/' . $submitter_did . '/cred_def/create_and_send/';
        $params = [
            'pass_phrase' => self::PASS_PHRASE,
            'schema_id' => $schema_id,
            'tag' => $tag,
            'support_revocation' => $support_revocation
        ];
        $http_post = $this->__http_post($url, $params);
        $ok = $http_post[0];
        $resp = $http_post[1];
        assertTrue($ok);
        return [$resp['id'], $resp['cred_def']];
    }

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
        $http_post = $this->__http_post($url, $params);
        $ok = $http_post[0];
        $resp = $http_post[1];
        assertTrue($resp);
        return $resp;
    }

    public function ensure_is_alive()
    {
        $inc_timeout = 10;
        foreach (range(1, self::SETUP_TIMEOUT, $inc_timeout) as $n) {
            $http_get = $this->__http_get('/agent/admin/wallets');
            $ok = $http_get[0];
            $wallets = $http_get[1];
            if ($ok) {
                break;
            }
            $progress = (float)($n / self::SETUP_TIMEOUT) * 100;
            echo 'Indy-Agent setup Progress: ' . $progress;
        }
        if (!$this->wallet_exists) {
            $http_post = $this->__http_post(
                '/agent/admin/wallets/ensure_exists/',
                ['uid' => self::WALLET, 'pass_phrase' => self::PASS_PHRASE]
            );
            assertTrue($http_post[0]);
            $this->wallet_exists = true;
        }
        $http_post = $this->__http_post(
            '/agent/admin/wallets/' . self::WALLET . '/open/',
            ['pass_phrase' => self::PASS_PHRASE]
        );
        assertTrue($http_post[0]);
        if (!$this->endpoint) {
            $url = '/agent/admin/wallets' . self::WALLET . '/endpoints/';
            $http_get = $this->__http_get($url);
            $ok = $http_get[0];
            $resp = $http_get[1];
            assertTrue($ok);
            if ($resp['results']) {
                $this->endpoint = $resp['results'][0];
            } else {
                $http_post = $this->__http_post($url, ['host' => $this->address]);
                assertTrue($http_post[0]);
                $this->endpoint = $http_post[1];
            }
        }
        if (!$this->default_invitation) {
            $url = '/agent/admin/wallets/' . self::WALLET . '/endpoints/' . $this->endpoint['uid'] . '/invitations/';
            $http_get = $this->__http_get($url);
            assertTrue($http_get[0]);
            $collection = [];
            foreach ($http_get[1] as $item) {
                if ($item['seed'] == 'default') {
                    array_push($collection, $item);
                }
            }
            if ($collection) {
                $this->default_invitation = $collection[0];
            } else {
                $http_post = $this->__http_post(
                    $url,
                    ['label' => self::DEFAULT_LABEL, 'pass_phrase' => self::PASS_PHRASE, 'seed' => 'default']
                );
                assertTrue($http_post[0]);
                $this->default_invitation = $http_post[1];
            }
        }
    }

    /**
     * @param string $path
     * @return array
     * @throws GuzzleException
     */
    protected function __http_get(string $path): array
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
            if (in_array($resp->getStatusCode(), [200])) {
                $content = json_decode($resp->getBody());
                return [true, $content];
            } else {
                $err_message = $resp->getBody();
                return [false, $err_message];
            }
        } catch (ClientException $e) {
            return [false, null];
        }
    }

    /**
     * @param string $path
     * @param array|null $json_
     * @return array
     * @throws GuzzleException
     */
    protected function __http_post(string $path, array $json_ = null): array
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
            $body = $json_ ? json_encode($json_) : null;
            $resp = $client->post($url, [
                'headers' => $headers,
                'body' => $body,
                'auth' => $auth
            ]);
            if (in_array($resp->getStatusCode(), [200, 201])) {
                try {
                    $content = json_decode($resp->getBody());
                } catch (\Exception $e) {
                    $content= null;
                }
                return [true, $content];
            } else {
                $err_message = $resp->getBody();
                return [false, $err_message];
            }
        } catch (ClientException $e) {
            return [false, null];
        }
    }
}