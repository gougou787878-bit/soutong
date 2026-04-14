<?php


namespace helper;

class Validator
{

    protected $data;
    protected $rules;


    protected static $ruleMethod = [
        'required' => [self::class, '_required'],
        'max'      => [self::class, '_max'],
        'min'      => [self::class, '_min'],
        'numeric'  => [self::class, '_numeric'],
        'enum'     => [self::class, '_enum'],
        'phone'    => [self::class, '_phone'],
    ];


    public static function bindValidator($name, $validator)
    {
        self::$ruleMethod[$name] = $validator;
    }


    public static function default(&$data , $default)
    {
        foreach ($default as $key => $item) {
            if (!isset($data[$key])) {
                $data[$key] = $item;
            }
        }
    }

    protected static function _required($value)
    {
        if (is_string($value)) {
            $value = trim($value);
        }
        if (is_string($value) && strlen($value) == 0) {
            return ':attribute属性不能为空字符串';
        } elseif (empty($value)) {
            return ':attribute属性必传递';
        }
    }

    protected static function _max($value, $size)
    {
        if (mb_strlen($value) > $size) {
            return ':attribute长度不能超过' . $size;
        }
    }

    protected static function _min($value, $size)
    {
        if (mb_strlen($value) < $size) {
            return ':attribute长度不能小于' . $size;
        }
    }

    protected static function _numeric($value)
    {
        if (!is_numeric($value)) {
            return ':attribute只能是数字';
        }
    }

    protected static function _enum()
    {
        $ary = func_get_args();
        $value = array_shift($ary);
        if (!in_array($value, $ary)) {
            return ':attribute值只能为:' . join(',', $ary);
        }
    }

    protected static function _phone($value)
    {
        if (empty($value) || !preg_match("#^1[\d]{10}$#", $value)) {
            return ':attribute格式必须是手机号格式';
        }
    }


    protected function __construct($data, $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }


    public static function make($data, $rules)
    {
        return new self($data, $rules);
    }


    /**
     * 验证接口是否有错误，
     * @param null $error
     * @return bool 有错误返回true，没有返回false
     * @author xiongba
     * @date 2019-12-31 15:03:36
     */
    public function fail(&$error = null)
    {
        foreach ($this->rules as $key => $rule) {
            $value = $this->data[$key] ?? null;
            $errorMsg = null;
            if (is_array($rule)) {
                list($rule, $errorMsg) = $rule;
            }

            $result = $this->_validateCallback($rule, $value);
            if (is_array($result)) {
                list($result, $msg) = $result;
                $error = str_replace(
                    [':attribute', ':value'],
                    [$key, $value],
                    $errorMsg ?? $msg);

                return $result === false;
            }
        }
        return false;
    }

    protected function _validateCallback($rule, $value)
    {
        $ruleArray = explode('|', $rule);
        foreach ($ruleArray as $item) {
            if (false !== strpos($item, ':')) {
                list($valid, $params) = explode(':', $item, 2);
                $params = explode(',', $params);
                array_unshift($params, $value);
            } else {
                $valid = $item;
                $params = [$value];
            }

            if (isset(self::$ruleMethod[$valid])) {
                $fn = function () use ($valid) {
                    return call_user_func_array(self::$ruleMethod[$valid], func_get_args());
                };
            } elseif (is_callable($valid)) {
                $fn = $valid;
            } else {
                continue;
            }

            $v = call_user_func_array($fn, $params);
            if ($v !== null) {
                return [false, $v];
            }
        }
    }

}