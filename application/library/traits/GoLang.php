<?php


namespace traits;


trait GoLang
{

    protected $defer = [];

    public function defer(\Closure $closure, $args = [])
    {
        array_unshift($this->defer, [$closure, $args]);
        return $this;
    }

    public function __release()
    {

    }

    final public function __destruct()
    {
        $this->__release();
        foreach ($this->defer as $item) {
            try {
                list($closure, $args) = $item;
                call_user_func_array($closure, $args);
            } catch (\Throwable $e) {

            }
        }
    }

}