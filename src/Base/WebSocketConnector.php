<?php

namespace Siruis\Base;



use Bloatless\WebSocket\Client;

class WebSocketConnector extends BaseConnector
{

    public $defTimeout = 30;
    public $enc = 'utf-8';
    public $server_address;
    public $path;
    public $credentials;
    private $session;
    private $port;

    public function __construct($server_address, $path, $credentials, $defTimeout = null, $port = null, $enc = null)
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
        $this->session = new Client();
    }

    public function isOpen(): bool
    {
        return $this->session->checkConnection();
    }

    /**
     * Open communication
     */
    public function open()
    {
        if (!$this->isOpen()) {
            $this->session->connect($this->server_address, $this->port, $this->path, $this->credentials);
        }
    }

    /**
     * Close communication
     */
    public function close()
    {
        if ($this->isOpen()) {
            $this->session->disconnect();
        }
    }

    /**
     * Reconnect communication
     */
    public function reconnect()
    {
        $this->session->reconnect();
    }

    /**
     * Send data to the communication
     *
     * @param string $data
     *
     * @return bool|mixed
     */
    public function write(string $data)
    {
        return $this->session->sendData($data);
    }
}
