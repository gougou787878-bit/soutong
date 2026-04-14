<?php


namespace App\console\Queue;


/**
 * Interface QueueJob
 * @package App\console\Queue
 * @author xiongba
 * @date 2019-11-30 16:02:57
 */
interface QueueJob
{

    /**
     * 具体执行的业务
     *
     * @return mixed
     * @author xiongba
     * @date 2019-11-30 16:02:55
     */
    public function handle();

}