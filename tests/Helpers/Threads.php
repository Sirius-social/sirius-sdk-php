<?php


namespace Siruis\Tests\Helpers;


use Siruis\Agent\Coprotocols\AbstractCoProtocolTransport;
use Siruis\Messaging\Message;
use Siruis\Tests\CoprotocolsTest;
use Thread;
use Threaded;

class Threads
{
    /**
     * @param Threaded|Threaded[] $tasks
     */
    public static function run_threads($tasks)
    {
        $threads = [];
        foreach ($tasks as $task) {
            array_push($threads, self::thread($task));
        }

        /** @var Thread $thread */
        foreach ($threads as $thread) {
            $thread->start() && $thread->join();
        }
    }

    protected static function thread(Threaded $task)
    {
        return new class($task) extends Thread
        {
            protected $task;

            public function __construct(Threaded $task)
            {
                $this->task = $task;
            }

            public function run()
            {
                $this->task->work();
            }
        };
    }
}