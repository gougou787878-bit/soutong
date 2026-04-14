<?php


namespace App\console\Queue;


class RedisQueue
{

    /**
     * @var QueueOption
     */
    protected $queue;


    /**
     * RedisQueue constructor.
     * @param $queueOption
     * @author xiongba
     * @date 2019-11-30 16:01:27
     */
    public function __construct($queueOption) {
        $this->queue = $queueOption;
    }

    public function push($class, $args) {
        return $this->queue->getDriver()->rPush(
            $this->queue->getName(),
            $this->createPlay($class, $args)
        );
    }


    public function pop() {
        $this->migrateJob();
        list($job, $reserved) = $this->queue->getDriver()->eval(
            LuaScript::pop(),
            [
                $this->queue->getName(),
                $this->queue->getName() . ':reserved',
                time()
            ],
            2
        );
        return [$job, $reserved];
    }


    public function later($delay, $class, $args) {
        return $this->queue->getDriver()->zAdd(
            $this->queue->getName() . ':delayed', time() + $delay,
            $this->createPlay($class, $args)
        );
    }

    protected function createPlay($class, $args) {
        return json_encode([
            'class' => $class,
            'args'  => serialize($args),
        ]);
    }


    protected function migrateJob() {
        $name = $this->queue->getName();
        $this->queue->getDriver()->eval(
            LuaScript::migrateExpiredJobs(),
            [
                $name . ':delayed',
                $name,
                time()
            ], 2
        );
    }


}