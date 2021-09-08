<?php


namespace Siruis\Tests\Threads\feature_0160_conn_protocol;


class Task extends \Threaded
{
    public $response;

    public function work()
    {
        $content = file_get_contents('http://google.com');
        preg_match('~<title>(.+)</title>~', $content, $matches);
        $this->response = $matches[1];
    }
}