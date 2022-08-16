<?php

namespace Siruis\Base;



use Siruis\Errors\Exceptions\SiriusConnectionClosed;
use Siruis\Errors\Exceptions\SiriusIOError;
use Siruis\Errors\Exceptions\SiriusTimeoutIO;
use Siruis\Messaging\Message;
use Throwable;
use WebSocket\Client;
use WebSocket\TimeoutException;


class WebSocketConnector extends BaseConnector
{
    public const DEF_TIMEOUT = 60;
    public const ENC = 'utf-8';

    public $timeout;
    public $server_address;
    public $path;
    public $credentials;
    private $session;
    private $url;
    private $options;

    public function __construct($server_address, $path, $credentials, $timeout = self::DEF_TIMEOUT, $enc = null)
    {
        $this->server_address = $server_address;
        $parsed = parse_url($server_address);
        $ws_address = $parsed['scheme'] === 'http' ? 'ws://'. $parsed['host'] : 'wss://' . $parsed['host'];
        if (array_key_exists('port', $parsed)) {
            $ws_address .= ':' . $parsed['port'];
        }
        $this->path = $path;
        $this->timeout = $timeout;
        $this->credentials = $credentials;
        if ($enc) {
            $this->enc = $enc;
        }
        $this->url = urljoin($ws_address, $path);
        $this->options = [
            'headers' => [
                'credentials' => mb_convert_encoding($this->credentials, 'ascii'),
                'origin' => $this->server_address,
                'mode' => 'text'
            ],
            'timeout' => $this->timeout,
            'filter' => ['text', 'binary', 'close']
        ];
        $this->session = new Client($this->url, $this->options);
    }

    public function isOpen(): bool
    {
        return $this->session->isConnected();
    }

    /**
     * Open communication
     *
     * @return void
     */
    public function open(): void
    {
        if (!$this->isOpen()) {
            $this->session = new Client($this->url, $this->options);
        }
    }

    /**
     * Close communication
     *
     * @return void
     */
    public function close(): void
    {
        if ($this->isOpen()) {
            $this->session->close();
        }
    }

    /**
     * Reconnect communication
     *
     * @return void
     */
    public function reconnect(): void
    {
        $this->session->close();
        $this->session = new Client($this->url, $this->options);
    }

    /**
     * @param null $timeout
     * @return string
     * @throws SiriusConnectionClosed
     * @throws SiriusIOError
     * @throws SiriusTimeoutIO
     */
    public function read($timeout = null): string
    {
        try {
            $this->session->setTimeout($timeout ?: $this->timeout);
            $msg = $this->session->receive();
        } catch (TimeoutException $e) {
            throw new SiriusTimeoutIO($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        $lastOpcode = $this->session->getLastOpcode();

        if ($lastOpcode === 'close') {
            throw new SiriusConnectionClosed();
        }

        if ($lastOpcode === 'text') {
            return mb_convert_encoding($msg, self::ENC);
        }

        if ($lastOpcode === 'binary') {
            return $msg;
        }

        throw new SiriusIOError();
    }

    /**
     * Send data to the communication
     *
     * @param string|Message $data
     * @return bool
     * @throws \JsonException
     */
    public function write($data): bool
    {
        if ($data instanceof Message) {
            $payload = mb_convert_encoding($data->serialize(), $this->enc);
        } else {
            $payload = $data;
        }

        try {
            $this->session->binary($payload);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
