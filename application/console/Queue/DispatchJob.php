<?php


namespace App\console\Queue;


use Factory\Log\Log;

trait DispatchJob
{


    final public static function dispatch() {
        $class = get_called_class();
        $queue = new RedisQueue(QueueOption::getInstance());
        return $queue->push($class, func_get_args());
    }


    final public static function dispatchDelay($delay, $args = []) {
        $class = get_called_class();
        $queue = new RedisQueue(QueueOption::getInstance());
        $args = array_unshift($args, $delay);
        return call_user_func([$queue, $class], $args);
    }


    /**
     * 马上执行。不需要延迟确认
     * @return bool
     * @author xiongba
     * @date 2020-01-13 14:35:05
     */
    final public static function now()
    {
        $class = get_called_class();
        //如果要设置异步功能
        //self::dispatch(...func_get_args());

        /** @var QueueJob $object */
        try {
            $object = new $class(...func_get_args());
            $object->handle();
            return true;
        } catch (\Throwable $e) {
            Log::error($e);
            return false;
        }
    }


}