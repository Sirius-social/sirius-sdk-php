<?php

namespace Siruis\Base;



use Exception;
use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusIOError;
use Siruis\Messaging\Message;
use WebSocket\BadOpcodeException;
use WebSocket\Client;
use WebSocket\TimeoutException;
use function Ratchet\Client\connect;


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
    private $ws_address;
    private $options;

    public function __construct($server_address, $path, $credentials, $defTimeout = null, $port = null, $enc = null)
    {
        $this->server_address = $server_address;
        $parsed = parse_url($server_address);
        $ws_address = $parsed['scheme'] == 'http' ? 'ws://'. $parsed['host'] : 'wss://' . $parsed['host'];
        if (key_exists('port', $parsed)) {
            $ws_address .= ':' . $parsed['port'];
        }
        $this->ws_address = $ws_address;
        $this->path = $path;
        $this->credentials = $credentials;
        if ($defTimeout) {
            $this->defTimeout = $defTimeout;
        }
        if ($enc) {
            $this->enc = $enc;
        }
        $this->port = $port;
        $this->url = urljoin($this->ws_address, $path);
        $this->options = [
            'headers' => [
                'credentials' => mb_convert_encoding($this->credentials, 'ascii'),
                'origin' => $this->server_address,
                'mode' => 'text'
            ],
            'timeout' => $this->defTimeout
        ];
        $this->session = new Client($this->url, $this->options);
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
            $this->session = new Client($this->url, $this->options);
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
        $this->session = new Client($this->url, $this->options);
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
            $this->session->text(mb_convert_encoding($payload, 'utf-8'));
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
