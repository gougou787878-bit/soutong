<?php


/**
 * Class RedisCached
 * @author xiongba
 * @date 2019-11-28 15:36:23
 */
class RedisPipeline
{

    protected $commands = [];

    public function __construct()
    {
    }

    public function __call($name, $arguments)
    {
        $this->push($name, $arguments);
        return $this;
    }

    public function push($command, $args)
    {
        $this->commands[] = ['name' => $command, 'args' => $args];
    }


    public function each(\Closure $callback)
    {
        foreach ($this->commands as $command) {
            $callback($command['name'], $command['args']);
        }
    }


}