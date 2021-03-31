<?php


namespace Siruis\Tests\Helpers;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use RuntimeException;
use Siruis\Encryption\P2PConnection;
use Siruis\Helpers\ArrayHelper;

class ServerTestSuite
{
    const SETUP_TIMEOUT = 60;
    public $server_address;
    public $url;
    public $metadata;
    public $test_suite_exists_locally;

    public function __construct()
    {
        $this->server_address = Conftest::phpunit_configs()['test_suite_baseurl'];
        $this->url = urljoin($this->server_address, '/test_suite');
        $this->metadata = null;
        $test_suite_path = getenv('TEST_SUITE') ? getenv('TEST_SUITE') : null;
        if (!$test_suite_path) {
            $this->test_suite_exists_locally = false;
        } else {
            $this->test_suite_exists_locally = is_file($test_suite_path) && strpos($this->server_address, 'localhost');
        }
    }

    public function get_agent_params(string $name): array
    {
        if (!$this->metadata) {
            throw new RuntimeException('TestSuite is not running...');
        }
        $agent = ArrayHelper::getValueWithKeyFromArray($name, $this->metadata);
        if (!$agent) {
            throw new RuntimeException('TestSuite does not have agent with name ' . $name);
        }
        $p2p = $agent['p2p'];
        return [
            'server_address' => $this->server_address,
            'credentials' => $agent['credentials'],
            'p2p' => new P2PConnection(
                [
                    $p2p['smart_contract']['verkey'],
                    $p2p['smart_contract']['secret_key']
                ],
                $p2p['agent']['verkey']
            ),
            'entities' => $agent['entities']
        ];
    }

    public function ensure_is_alive()
    {
        $http_get = self::__http_get($this->url);
        $ok = $http_get[0];
        $meta = $http_get[1];

        if ($ok) {
            $this->metadata = json_decode($meta, true);
        } else {
            if ($this->test_suite_exists_locally) {
                self::__run_suite_locally();
            }
            $inc_timeout = 10;
            echo 'Starting test suite locally...';

            foreach (range(1, self::SETUP_TIMEOUT, $inc_timeout) as $n) {
                $progress = (float)($n / self::SETUP_TIMEOUT) * 100;
                echo 'TestSuite setup progress: ' . $progress;
                $http_get = self::__http_get($this->url);
                $ok = $http_get[0];
                $meta = $http_get[1];
                if ($ok) {
                    $this->metadata = $meta;
                    echo 'Server test suite was detected';
                    return;
                }
            }
            echo 'Timeout for waiting TestSuite is alive expired';
            throw new RuntimeException('Except server with running TestSuite.');
        }
    }

    public static function __run_suite_locally()
    {

    }

    public static function __http_get(string $url): array
    {
        $session = new Client();
        $headers = [
            'content-type' => 'application/json'
        ];
        try {
            $resp = $session->get($url, $headers);
            if (in_array($resp->getStatusCode(), [200])) {
                $content = $resp->getBody();
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