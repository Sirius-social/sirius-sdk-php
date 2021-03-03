<?php

namespace Siruis\Base;



use Exception;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusIOError;
use Siruis\Messaging\Message;
use WebSocket\BadOpcodeException;
use WebSocket\Client;
use WebSocket\TimeoutException;


class WebSocketConnector extends BaseConnector
{

    public $defTimeout = 30;
    public $enc = 'utf-8';
    public $server_address;
    public $path;
    public $credentials;
    private $session;
    private $port;
    private $url;

    public function __construct($server_address, $path, $credentials, $defTimeout = null, $port = null, $enc = null)
    {
        $parsed = parse_url($server_address);
        $this->server_address = $parsed['scheme'] == 'http' ? 'ws://'. $parsed['host'] : 'wss://' . $parsed['host'];
        $this->path = $path;
        $this->credentials = $credentials;
        if ($defTimeout) {
            $this->defTimeout = $defTimeout;
        }
        if ($enc) {
            $this->enc = $enc;
        }
        $this->port = $port;
        $this->url = urljoin($this->server_address, $path);
        $this->session = new Client($this->url, ['headers' => ['origin' => $server_address, 'credentials' => $credentials], 'timeout' => $this->defTimeout]);
    }

    public function isOpen(): bool
    {
        return $this->session->isConnected();
    }

    /**
     * Open communication
     */
    public function open()
    {
        if (!$this->isOpen()) {
            $this->session->ping();
        }
    }

    /**
     * Close communication
     */
    public function close()
    {
        if ($this->isOpen()) {
            $this->session->close();
        }
    }

    /**
     * Reconnect communication
     */
    public function reconnect()
    {
        $this->session->close();
        $this->session->ping();
    }

    /**
     * @param null $timeout
     * @return string
     * @throws SiriusConnectionClosed
     * @throws SiriusIOError
     */
    public function read($timeout = null): string
    {
        if ($timeout) {
            $this->session->setTimeout($timeout);
        }
        $msg = $this->session->receive();
        $lastOpcode = $this->session->getLastOpcode();
        if (in_array($lastOpcode, ['close'])) {
            throw new SiriusConnectionClosed();
        } elseif ($lastOpcode == 'text') {
            return mb_convert_encoding($msg, $this->enc);
        } elseif ($lastOpcode == 'binary') {
            return $msg;
        } else {
            throw new SiriusIOError();
        }
    }

    /**
     * Send data to the communication
     *
     * @param string|Message $data
     * @return bool
     */
    public function write($data): bool
    {
        if ($data instanceof Message) {
            $payload = $data->serialize();
        } else {
            $payload = $data;
        }
        try {
            $this->session->binary($payload);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
