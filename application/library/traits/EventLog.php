<?php

namespace traits;


use Yaf\Exception;

trait EventLog
{

    public function newEloquentBuilder($query): \tools\LibBuilder
    {
        return new \tools\LibBuilder($query);
    }

    protected function bootIfNotBooted()
    {
        if (self::$dispatcher === null) {
            if (class_exists('Illuminate\Events\Dispatcher')){
                self::$dispatcher = new \Illuminate\Events\Dispatcher();
            }
        }
        parent::bootIfNotBooted();
    }

    protected static function boot()
    {
        parent::boot();
        static::booted();
    }

    protected static function booted()
    {
        static::created(function ($model) {
            $old = [];
            $new = $model->getAttributes();
            self::logInsert($model, 'created', "新加了{{$model->table}}的数据", $old, $new);
        });
        try{
            static::updated(function (self $model) {
                $attributes = $model->getAttributes();
                $original = $model->getOriginal();
                //errLog("EventLog".var_export([$attributes,$original],true));
                if(!is_array($original) || !is_array($attributes)){
                    return ;
                    $original = (array)$original;
                }
                if(isset($original['session'])){
                    unset($original['session']);
                }
                if(isset($attributes['session'])){
                    unset($attributes['session']);
                }

                $diff = array_diff($attributes, $original);
                $new = $old = ['_pk' => $attributes[$model->primaryKey]];
                foreach ($diff as $key => $_) {
                    $new[$key] = $attributes[$key] ?? null;
                    $old[$key] = $original[$key] ?? null;
                }
                self::logInsert($model, 'updated', "修改了{{$model->table}}的数据", $old, $new);
            });
            static::deleted(function (self $model) {
                $old = $model->getAttributes();
                $new = [];
                self::logInsert($model, 'deleted', "删除了{{$model->table}}的数据", $old, $new);
            });
        }catch (\Throwable|\Exception|Exception  $exception){

        }
    }

    protected static function logInsert($model, $action, $name, $old, $new)
    {
        $uri = strtolower($_SERVER['REQUEST_URI'] ?? '');
        if (strpos($uri, 'admin') !== false) {
            if ($model instanceof \AdminLogModel){
                return;
            }
            $username = $_SERVER['username'] ?? '';
            $context = json_encode(['table' => $model->getTable(), 'new' => $new, 'old' => $old, 'time' => time()]);
            $data = [
                'username' => $username,
                'action' => $action,
                'ip' => USER_IP,
                'log' => $username . $name,
                'referrer' => $uri,
                'context' => $context,
                'created_at' => date('Y-m-d H:i:s')
            ];
            \AdminLogModel::query()->insert($data);
        }
    }
}