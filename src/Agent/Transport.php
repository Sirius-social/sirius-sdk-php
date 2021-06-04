<?php


namespace Siruis\Agent;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;

class Transport
{
    /**
     * Send over HTTP
     *
     * @param string $msg
     * @param string $endpoint
     * @param float $timeout
     * @param string $content_type
     * @return array
     * @throws GuzzleException
     */
    public static function http_send(
        string $msg, string $endpoint, float $timeout,
        string $content_type = 'application/ssi-agent-wire'
    ): array
    {
        $headers = ['content-type' => $content_type];
        $client = new Client();
        $request = new Request('post', $endpoint, $headers, $msg);
        $response = $client->send($request);
        if (in_array($response->getStatusCode(), [200, 202])) {
            return [true, $response->getBody()];
        } else {
            return [false, $response->getBody()];
        }
    }
}