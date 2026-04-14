<?php

namespace tools;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class LibBuilder extends Builder
{

    protected function notBackstage(): bool
    {
        $uri = strtolower($_SERVER['REQUEST_URI'] ?? '');
        if (strpos($uri, '/admin/') === false) {
            return true;
        }
        $traces = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($traces as $trace) {
            $fn = $trace['function'] ?? '';
            if (!strcasecmp($fn, 'performInsert')) {
                return true;
            } elseif (!strcasecmp($fn, 'performUpdate')) {
                return true;
            } elseif (!strcasecmp($fn, 'performDeleteOnModel')) {
                return true;
            }
        }
        return false;
    }


    public function insert(array $values)
    {
        if ($this->notBackstage()) {
            return parent::insert($values);
        }
        if (empty($values)) {
            return true;
        }
        if (!is_array(reset($values))) {
            $values = [$values];
        }

        $ok = 0;
        foreach ($values as $value) {
            if (static::create($value)) {
                $ok++;
            }
        }
        return (bool)$ok;
    }


    public function update(array $values)
    {
        if ($this->notBackstage() || $this->getModel()->getKey()) {
            return parent::update($values);
        }
        $ok = 0;
        $this->selfEach(function ($model) use ($values, &$ok) {
            if ($model->getKey()) {
                $model->fill($values);
                if ($model->save()) {
                    $ok++;
                }
            } elseif (parent::update($values)) {
                $ok++;
            }
        });
        return $ok;
    }

    public function increment($column, $amount = 1, array $extra = [])
    {
        if ($this->notBackstage() || $this->getModel()->getKey()) {
            return parent::increment($column, $amount, $extra);
        }
        $ok = 0;
        $this->selfEach(function (Model $model) use ( &$ok, $column, $amount, $extra ) {
            if ($model->increment($column, $amount, $extra)) {
                $ok++;
            }
        });
        return $ok;
    }

    public function decrement($column, $amount = 1, array $extra = [])
    {
        if ($this->notBackstage() || $this->getModel()->getKey()) {
            return parent::decrement($column, $amount, $extra);
        }
        $ok = 0;
        $this->selfEach(function (Model $model) use ( &$ok, $column, $amount, $extra ) {
            if ($model->decrement($column, $amount, $extra)) {
                $ok++;
            }
        });
        return $ok;
    }

    public function delete()
    {
        if ($this->notBackstage() || $this->getModel()->getKey()) {
            return parent::delete();
        }
        $ok = 0;
        $this->selfEach(function (Model $model) use (&$ok) {
            if ($model->delete()) {
                $ok++;
            }
        });
        return $ok;
    }

    protected function selfEach(callable $callback){
        if ($this->getQuery()->limit !== null || $this->getQuery()->offset !== null){
            $this->get()->each($callback);
        }else{
            $this->each($callback);
        }
    }

}