<?php


class ObserverDispatcher implements \Illuminate\Contracts\Events\Dispatcher
{

    protected static $observers = [];

    /**
     * @inheritDoc
     */
    public function listen($events, $listener)
    {
        list($class, $method) = explode('@', $listener);
        self::$observers[$events] = function ($model) use ($class, $method) {
            return (new $class)->$method($model);
        };
    }

    /**
     * @inheritDoc
     */
    public function hasListeners($eventName)
    {

    }

    /**
     * @inheritDoc
     */
    public function subscribe($subscriber)
    {
        // TODO: Implement subscribe() method.
    }

    /**
     * @inheritDoc
     */
    public function until($event, $payload = [])
    {
        return $this->dispatch($event, $payload);
    }

    /**
     * @inheritDoc
     */
    public function dispatch($event, $payload = [], $halt = false)
    {
        if (is_string($event) && isset(self::$observers[$event])) {
            return call_user_func(self::$observers[$event], $payload);
        }
    }

    public function fire($event , $payload){
        return $this->dispatch($event , $payload);
    }

    /**
     * @inheritDoc
     */
    public function push($event, $payload = [])
    {
        // TODO: Implement push() method.
    }

    /**
     * @inheritDoc
     */
    public function flush($event)
    {
        // TODO: Implement flush() method.
    }

    /**
     * @inheritDoc
     */
    public function forget($event)
    {
        // TODO: Implement forget() method.
    }

    /**
     * @inheritDoc
     */
    public function forgetPushed()
    {
        // TODO: Implement forgetPushed() method.
    }
}