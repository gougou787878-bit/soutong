<?php

use Illuminate\Database\Eloquent\Model;

/**
 * Class EloquentModel
 * @author xiongba
 * @date 2020-02-25 13:50:03
 * @mixin \Eloquent
 */
class EloquentModel extends Model
{

    // 将下面的注释打开，启用后台全局操作记录
    use \traits\EventLog;

    public $timestamps = false;

    /**
     * 使用pk值进行递增递减操作
     * @param $pkVal
     * @param array $array
     * @return int
     * @author xiongba
     * @date 2020-03-02 19:09:13
     */
    public static function incrPk($pkVal, array $array)
    {
        $model = static::find($pkVal);
        return $model->incrMustGE_raw($array);
    }

    /**
     * 减少值，最小，值最小减到0
     * @param $pkVal
     * @param array $array
     * @return int
     * @author xiongba
     */
    public static function incrPkLeast2Zero($pkVal, array $array)
    {
        $model = static::find($pkVal);
        return $model->incrMustGeCb($array, function ($where, $data) {
            $tmp = [];
            foreach ($data as $key => $v) {
                $tmp[$key] = $v <= 0 ? 0 : $v;
            }
            if (empty($tmp)) {
                return 1;
            }
            return self::where($where)->update($tmp);
        });
    }

    protected function incrMustGeCb($values, $callback)
    {
        $pkVal = $this->{$this->primaryKey};
        if ($pkVal === null) {
            trigger_error('当前对象主键数据为空，为了数据安全，禁止操作');
            return 0;
        }
        $where = [$this->primaryKey => $pkVal];
        $data = [];
        foreach ($values as $field => $val) {
            $oldVal = $this->{$field};
            if ($oldVal === null) {
                trigger_error('当前记录的值为空，为了数据安全，禁止操作');
                return 0;
            }
            $where[$field] = $oldVal;
            if ($val < 0) {
                $val = abs($val);
                if ($oldVal < $val) {
                    return 0;
                }
                $data[$field] = $oldVal - $val;
            } else {
                $data[$field] = $oldVal + $val;
            }
            if ($where[$field] == $data[$field]) {
                unset($where[$field] , $data[$field]);
            }
        }
        return call_user_func($this->_packageMustCallback($callback, $where, $data));
    }


    /**
     * 拨弄字段的值
     * @param array $where 条件
     * @param string $column 要拨弄的字段
     * @param array $values 拨弄的值，0，1  拨弄前，假设值是 1 ，那么拨弄后值将会变为 0
     * @return bool
     */
    public static function toggleColumn(array $where, string $column, array $values)
    {
        $model = self::where($where)->first();
        if (empty($model)) {
            return false;
        }
        $values = array_merge($values, $values);
        $key = array_search($model->{$column}, $values);
        reset($values);
        $model->{$column} = $key === false ? current($values) : $values[$key + 1];
        return $model->save();
    }

    public static function destroyWhere(array $where)
    {
        $count = 0;
        foreach (self::where($where)->get() as $model) {
            if ($model->delete()) {
                $count++;
            }
        }
        return $count;
    }

    protected function _packageMustCallback($callback, $where, $data)
    {
        return function () use ($callback, $where, $data) {
            if ($this->fireModelEvent('saving') === false || $this->fireModelEvent('updating') === false) {
                return false;
            }
            if ($this->usesTimestamps()) {
                $this->updateTimestamps();
            }
            $itOk = $callback($where, $data);
            $this->fill($data)->syncChanges();
            $this->fireModelEvent('updated', false);
            if ($itOk) {
                $this->finishSave([]);
            }
            return $itOk;
        };
    }


    /**
     * @var array|MemberModel 观察数据的用户，是哪个用户在对数据进行观察
     */
    protected static $watchUser = null;

    public static function setWatchUser(?MemberModel $watchUser)
    {
        self::$watchUser = $watchUser;
    }


    /**
     * @param ?MemberModel $watchUser
     *
     * @return static|object
     */
    public function watchByUser(?MemberModel $watchUser)
    {
        self::setWatchUser($watchUser);
        return $this;
    }

    /**
     * 如果值中有需要递减指定的值时，数据中原有的值必须大于等于要操作的值
     * @param $values
     * @return int
     * @author xiongba
     * @date 2020-03-02 14:47:07
     */
    public function incrMustGE_raw($values)
    {
        return $this->incrMustGeCb($values, function ($where, $data) {
            return self::where($where)->update($data);
        });
    }

    public static function incrMustGTPk($pk, $values)
    {
        return self::incrPk($pk, $values);
    }


    public static function incrMultiLine(...$args)
    {
        $update = [];
        foreach ($args as $arg) {
            foreach ($arg as $k => $item) {
                $update[$k] = $item;
            }
        }
        $row = 0;
        $that = new static();
        foreach ($update as $key => $item) {
            $_update = [];
            foreach ($item as $k => $val) {
                if (in_array($k, $that->fillable)) {
                    if ($val > 0) {
                        $_update[$k] = DB::raw("$k + $val");
                    } else {
                        $val = abs($val);
                        $_update[$k] = DB::raw("$k - $val");
                    }
                }
            }
            if (empty($update)) {
                continue;
            }
            $row += self::where($that->primaryKey, $key)->update($_update);
        }
        return $row;
    }

    /**
     * @param array|\Illuminate\Support\Collection $collect  一个model的数组或者model的集合
     * @param string|string[] $name 要关联哪些
     * @return \Illuminate\Support\Collection
     * @author xiongba
     * @date 2020-11-14 15:46:05
     */
    private static function related($collect, $name)
    {
        try {
            if (!$collect instanceof \Illuminate\Support\Collection) {
                return $collect;
            }

            $first = $collect->first();
            $nameArray = (array)$name;
            $results = collect([]);

            foreach ($nameArray as $name){
                if (!str_contains($name, ':')) {
                    $name .= ':*';
                }
                list($name, $column) = explode(':', $name);
                $columns = array_map('trim', explode(',', $column));

                if (!method_exists($first, $name)) {
                    return $collect;
                }
                $relatedModel = $first->{$name}();
                if (!$relatedModel instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                    return $collect;
                }
                if (!method_exists($relatedModel, 'getForeignKeyName')
                    || !method_exists($relatedModel, 'getLocalKeyName')) {
                    return $collect;
                }
                $query = $relatedModel->getModel();

                $foreignKey = $relatedModel->getForeignKeyName();
                $localKey = $relatedModel->getLocalKeyName();
                $idx = $collect->pluck($localKey);
                $list = $query->whereIn($foreignKey, $idx)->get($columns)->keyBy($foreignKey);
                foreach ($collect as $item){
                    $item->{$name} = $list[$item->{$localKey}] ?? null;
                    $results->push($item);
                }
            }
            return $results;
        } catch (\Throwable $e) {
            errLog((string)$e);
            return $collect;
        }
    }


    /**
     * @param array $array
     * @param string|string[] $name
     * @return \Illuminate\Support\Collection|static[]
     */
    public static function itRelated(array $array, $name)
    {
        $data = static::makeCollect($array);
        return self::related($data, $name);
    }

    /**
     * @param $attributes
     * @return static
     */
    public static function makeOnce(array $attributes)
    {
        $model = static::make();
        $model->exists = true;
        $model->setRawAttributes($attributes, true);
        return $model;
    }

    public static function makeCollect($ary, $sync = true)
    {
        $result = collect([]);
        $model = static::make();
        foreach ($ary as $item) {
            if (empty($item)) {
                continue;
            }
            $object = clone $model;
            $object->exists = true;
            $object->setRawAttributes($item, $sync);
            $result->push($object);
        }
        unset($object, $item, $ary);
        return $result;
    }

    public function update(array $attributes = [], array $options = [])
    {
        $r = parent::update($attributes, $options);
        $this->emitChange(false);
        return $r;
    }

    public function save(array $options = [])
    {
        $r = parent::save($options);
        $this->emitChange(false);
        return $r;
    }

    public function delete()
    {
        $r = parent::delete();
        $this->emitChange(true);
        return $r;
    }

    public function emitChange($release)
    {
    }

    /**
     * 替换grammar，使生成sql的时候。自动对字段的表明补齐
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return static|Illuminate\Database\Eloquent\Builder
     */
    public static function setGrammar(Illuminate\Database\Eloquent\Builder $query){
        //$query

        $_grammar = $query->getQuery()->getGrammar();
        $grammar = new MyGrammar();
        $grammar->setTablePrefix($_grammar->getTablePrefix());
        $grammar->setTableName($query->getQuery()->from);
        $query->getQuery()->grammar = $grammar;
        return $query;
    }

    /**
     * @return int|null 返回下一次入库的id
     */
    public static function next_insert_id()
    {
        $connection = self::query()->getQuery()->connection;
        $sql = "select AUTO_INCREMENT as next_id from information_schema.TABLES where TABLE_SCHEMA=? and TABLE_NAME=?;";
        $table_name = ($connection->getTablePrefix() ?? '') . self::getModel()->getTable();
        $data = DB::selectOne($sql, [$connection->getDatabaseName(), $table_name], false);
        return $data->next_id;
    }

    public function resetSetPathAttribute(string $string, $value)
    {
        $old = $this->getOriginal($string);
        if (empty($old) && empty($value)) {
            $this->attributes[$string] = $value;
            return;
        }
        if (!empty($value) && strpos($value, '://') !== false) {
            $value = parse_url($value, PHP_URL_PATH);
            $value = '/' . trim($value, '/');
        }
        $this->attributes[$string] = $value;
    }
}