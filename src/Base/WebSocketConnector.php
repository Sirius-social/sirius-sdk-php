<?php

namespace Siruis\Base;



use GuzzleHttp\Client;

class WebSocketConnector extends BaseConnector
{

    public $defTimeout = 30;
    public $enc = 'utf-8';
    public $server_address;
    public $path;
    public $credentials;
    private $session;
    private $webSocket;

    public function __construct($server_address, $path, $credentials, $enc = null, $defTimeout = null)
    {
        $this->server_address = $server_address;
        $this->path = $path;
        $this->credentials = $credentials;
        if ($defTimeout) {
            $this->defTimeout = $defTimeout;
        }
        if ($enc) {
            $this->enc = $enc;
        }
        $this->session = new Client([
            'base_uri' => $server_address,
            'timeout' => $this->defTimeout
        ]);
    }

    public function isOpen(): bool
    {
        if ($this->webSocket) {
            return true;
        }
        return false;
    }

    /**
     * Open communication
     */
    public function open()
    {
        if (!$this->isOpen()) {
            $this->webSocket = $this->session->requestAsync('GET', $this->path, $this->credentials);
        }
    }

    /**
     * Close communication
     */
    public function close()
    {

    }
}
