<?php


namespace App\console\Queue;


class WorkerProcess
{


    /**
     * @var RedisQueue
     */
    private $driver = null;


    /**
     * @var QueueJob
     */
    private $queue;

    private $sleep;

    private $args;

    /**
     * WorkerProcess constructor.
     * @param $queue
     * @param $sleep
     * @param $args
     * @author xiongba
     * @date 2019-11-30 19:30:45
     */
    public function __construct($queue, $sleep, $args) {
        $this->queue = $queue;
        $this->driver = new RedisQueue($queue);
        $this->sleep = $sleep;
        $this->args = $args;
    }


    public function runOnce() {
        list($job, $reserved) = $this->driver->pop();
        if ($reserved) {
            if ($job[0] == 's' && $job['1'] === ':') {
                $job = unserialize($job);
            }
            $ary = json_decode($job, 1);
            if (!class_exists($ary['class'])) {
                return false;
            }
            $ary['args'] = unserialize($ary['args']);
            $object = $this->newObject($ary['class'], $ary['args']);
            if ($object instanceof QueueJob) {
                $i = 0;
                while ($i < 10) {
                    echo "Running Process: " . get_class($object);
                    try {
                        $object->handle();
                        echo " success\r\n";
                        break;
                    } catch (\Throwable $e) {
                        echo " fail\r\n";
                    }
                    $i++;
                }
            }
            return true;
        }
        return false;
    }


    protected function newObject($class, $args) {
        try {
            $ref = new \ReflectionClass($class);
            if ($ref->getConstructor()) {
                return $ref->newInstanceArgs($args);
            } else {
                return $ref->newInstanceWithoutConstructor();
            }
        } catch (\ReflectionException $e) {
        }

    }


    public function daemon() {
        $this->loop();
    }


    public function loop() {
        while (true) {
            if (!$this->runOnce()) {
                $this->sleep($this->sleep);
            }
            $this->stopIfNecessary($this->args);
        }
    }


    protected function stopIfNecessary($options) {
        $memory = $options['memory'] ?? 64;

        if ($this->memoryExceeded($memory)) {
            exit(1);
        }
    }

    public function sleep($sleep) {
        if ($sleep < 1) {
            usleep($sleep * 1000000);
        } else {
            sleep($sleep);
        }
    }

    /**
     * 内存使用量是否超过量指定的的使用量
     * @param $memoryLimit
     * @return bool
     * @author xiongba
     * @date 2019-12-02 09:22:23
     */
    public function memoryExceeded($memoryLimit) {
        return (memory_get_usage(true) / 1024 / 1024) >= $memoryLimit;
    }


}