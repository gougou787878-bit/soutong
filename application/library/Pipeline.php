<?php

use filter\HandlerInterface;

class Pipeline
{
    private $passable;
    protected $pipes = [];

    public static function send($passable) {
        $object = new static();
        $object->passable = $passable;
        return $object;
    }

    public function through($pipes) {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();
        return $this;
    }

    public function then(\Closure $destination) {
        $pipeline = array_reduce(array_reverse($this->pipes), $this->getSlice(), $destination);
        return $pipeline($this->passable);
    }

    private function getSlice() {
        return function ($stack, $pipe) {
            return function ($request) use ($stack, $pipe) {
                if (is_string($pipe) && class_exists($pipe)) {
                    $pipe = new $pipe();
                }
                if ($pipe instanceof HandlerInterface) {
                    return $pipe->handler($request, $stack);
                } elseif (is_callable($pipe)) {
                    return $pipe($request, $stack);
                }
                return $request;
            };
        };
    }

}