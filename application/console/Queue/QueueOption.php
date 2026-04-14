<?php


namespace App\console\Queue;


class QueueOption
{


    /**
     * @var \Redis
     */
    protected $driver;

    protected $name;

    protected static $instance;

    /**
     * QueueOption constructor.
     * @param $driver
     * @param $name
     * @author xiongba
     * @date 2019-11-30 19:57:46
     */
    public function __construct($driver, $name) {
        $this->driver = $driver;
        $this->name = $name;
        self::$instance = $this;
    }


    public static function register($driver, $name){
        return new self($driver , $name);
    }


    public static function getInstance() {
        return self::$instance;
    }

    /**
     * @return mixed|\Redis
     * @author xiongba
     * @date 2019-12-02 14:15:44
     */
    public function getDriver() {

        if (is_callable($this->driver)){
            $this->driver = call_user_func($this->driver);
        }
        return $this->driver;
    }

    /**
     * @return mixed
     * @author xiongba
     */
    public function getName() {
        return $this->name;
    }


}