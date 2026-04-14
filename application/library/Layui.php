<?php


class Layui extends Html
{


    public static function switch($name, $values, $default, array $attr = [])
    {
        $attr['lay-skin'] = 'switch';
        $attr['lay-text'] = join('|', $values);
        if ($default === $values[0]) {
            $attr['checked'] = 'true';
        }
        return parent::input($name, null, 'checkbox', null);
    }

    public static function checkbox($name, $values, $default = [], array $attr = [])
    {
        return parent::checkbox($name, $values, $default, merge_array($attr, ['lay-skin' => 'primary']));
    }

    public static function textarea($name, $value, array $attr = [])
    {
        if (isset($attr['class'])) {
            $attr['class'] .= ' layui-textarea';
        } else {
            $attr['class'] = 'layui-textarea';
        }
        return parent::textarea($name, $value, $attr);
    }

    public static function date($name, $value = '', array $attr = [])
    {
        return parent::input($name, $value, 'text', 'yyyy-MM-dd')
            ->attr(['id' => md5(microtime())])
            ->attr(['class' => 'x-date layui-input']);
    }

    public static function datetime($name, $value = '')
    {
        return parent::input($name, $value, 'text', 'yyyy-MM-dd')
            ->attr(['id' => md5(microtime())])
            ->attr(['class' => 'x-date-time layui-input']);
    }


    public static function dateBetween($name,$value = '')
    {
        $ary = [];
        foreach (['from', 'to'] as $item) {
            $input = parent::input("between[$name][$item]", $value, 'text', 'yyyy-MM-dd')
                ->classes('layui-input x-date')
                ->makeFather('div', null, 'layui-input-inline')
                ->style('width', '180px');
            $ary[] = $input;
        }
        return join('', [$ary[0], '<div class="layui-form-mid">-</div>', $ary[1]]);
    }


    public static function between($name, $placeholder = '请输入')
    {
        $ary = [];
        foreach (['from', 'to'] as $item) {
            $input = parent::input("between[$name][$item]", '', 'text', $placeholder)
                ->classes('layui-input')
                ->makeFather('div', null, 'layui-input-inline')
                ->style('width', '120px');
            $ary[] = $input;
        }
        return join('', [$ary[0], '<div class="layui-form-mid">-</div>', $ary[1]]);
    }


    public static function datetimeBetween($name)
    {
        $ary = [];
        foreach (['from', 'to'] as $item) {
            $input = parent::input("between[$name][$item]", '', 'text', 'yyyy-MM-dd')
                ->classes('layui-input x-date-time')
                ->makeFather('div', null, 'layui-input-inline')
                ->style('width', '180px');
            $ary[] = $input;
        }
        return join('', [$ary[0], '<div class="layui-form-mid">-</div>', $ary[1]]);
    }

    public static function uploadEle(
        $imgColumn,
        $inputColumn,
        $width = "100",
        $height = "50",
        $postData = [],
        $inputType = 'hidden'
    ) {
        static $rand = null;
        if ($rand === null) {
            $rand = mt_rand(100000, 999999);
        }
        $dataJson = '';
        if (!empty($postData)) {
            $dataJson = sprintf("data-json='%s'", json_encode($postData));
        }
        $randStr = 'A' . (++$rand);
        return <<<HTML
                <button type="button" class="but-upload-img layui-btn" $dataJson data-img="#img-$randStr" data-input="#input-$randStr">
                <i class="layui-icon">&#xe67c;</i>上传
                </button>
                <img src="{{=d.$imgColumn }}" id="img-$randStr" width="$width" height="$height"/>
                <input type="$inputType" name="$inputColumn" id="input-$randStr" value="{{=d.$inputColumn }}">
HTML;
    }

    public static function uploadPos($imgColumn, $inputColumn, $width = "100", $height = "50", $position = null)
    {
        $dataJson = [];
        if (!empty($position)) {
            $dataJson = ['position' => $position];
        }
        return self::uploadEle($imgColumn, $inputColumn, $width, $height, $dataJson);
    }


    public static function uploadFile($url, $inputColumn, $postData = [], $inputType = 'text')
    {
        static $rand = null;
        if ($rand === null) {
            $rand = mt_rand(100000, 999999);
        }
        $dataJson = '';
        if (!empty($postData)) {
            $dataJson = sprintf("data-json='%s'", json_encode($postData));
        }
        $randStr = 'A' . (++$rand);
        return <<<HTML
                <button type="button" class="but-upload-img layui-btn" data-url="$url" $dataJson data-input="#input-$randStr"><i class="layui-icon">&#xe67c;</i>上传</button>
                <input type="$inputType" style="display: inline-block;width:80%" name="$inputColumn" id="input-$randStr" class="layui-input" value="{{=d.$inputColumn }}">
HTML;
    }


    public static function renderJs()
    {
        $status = LAY_UI_STATIC;
        $time = time();
        return <<<HTML
<script type="text/javascript" src="$status/data-list.js?v=$time"></script>
HTML;
    }

    public static function timestamp($date, $format = 'Y-m-d H:i:s')
    {

    }

}