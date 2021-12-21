<?php


namespace Siruis\Tests\Helpers;


use Thread;
use Threaded;

class Threads
{
    /**
     * @param Threaded|Threaded[] $tasks
     */
    public static function run_threads($tasks): void
    {
        $threads = [];
        foreach ($tasks as $task) {
            $threads[] = self::thread($task);
        }

        /** @var Thread $thread */
        foreach ($threads as $thread) {
            $thread->run() && $thread->join();
        }
    }

    protected static function thread(Threaded $task): Thread
    {
        return new class($task) extends Thread
        {
            protected $task;

            public function __construct(Threaded $task)
            {
                $this->task = $task;
            }

            public function run(): void
            {
                $this->task->work();
            }
        };
    }
}