<?php

namespace element;


class Element
{
    protected $name;
    protected $single = false;
    protected $id;
    protected $class = [];
    protected $content = '';
    protected $attr = [];
    protected $style = [];

    /**
     * Html constructor.
     * @param $name
     * @param $id
     * @param $class
     * @author xiongba
     * @date 2020-01-01 17:26:30
     */
    public function __construct($name, $id = null, $class = null)
    {
        $this->name = strtolower(trim($name));
        $this->single = $this->isSingle($this->name);
        $this->id = $id;
        $this->class[] = $class;
    }

    /**
     * 设置属性
     * @param array $attr
     * @return $this
     * @author xiongba
     * @date 2020-01-01 19:35:55
     */
    public function attr(array $attr)
    {
        $this->attr = merge_array($this->attr, $attr);
        return $this;
    }


    public function classes($class)
    {
        $this->class[] = $class;
        return $this;
    }


    public function style($name, $value)
    {
        $this->style[$name] = $value;
        return $this;
    }

    public function appendTo(self $object)
    {
        return $object->content($this);
    }

    public function parent($name, $id = null, $class = null)
    {
        $object = new self($name, $id, $class);
        return $object->content($this);
    }


    public function makeFather($name, $id = null, $class = null)
    {
        return $this->parent($name, $id, $class);
    }


    /**
     * 解析属性
     * @param $attr
     * @return string
     * @author xiongba
     * @date 2020-01-01 19:35:42
     */
    protected function parseAttr($attr)
    {
        if ($this->id && !isset($attr['id'])) {
            $attr['id'] = $this->id;
        }
        if (!empty($this->class) || !empty($attr['class'])) {
            $class = $attr['class'] ?? [];
            $class = is_array($class) ? $class : explode(' ', $class);
            $class = array_filter($class);
            $class = array_merge($class, $this->class);
            $attr['class'] = trim(join(' ', $class));
        }

        if (!empty($this->style) || !empty($attr['style'])) {
            $style = $attr['style'] ?? [];
            $style = is_array($style) ? $style : explode(' ', $style);
            $style = array_filter($style);
            $style = array_merge($style, $this->style);
            $styleAttr = [];
            foreach ($style as $name=>$value){
                $styleAttr[] = "$name:$value";
            }
            $attr['style'] = join(';', $styleAttr);
        }

        $strArray = [];
        foreach ($attr as $key => $value) {
            if ($value === null) {
                continue;
            }
            $strArray[] = "$key='$value'";
        }
        return join(' ', $strArray);
    }

    /**
     * 判断是不是带标签
     * @param $name
     * @return bool
     * @author xiongba
     * @date 2020-01-01 19:34:54
     */
    protected function isSingle($name)
    {
        $single = 'br|ht|img|input|param|meta|link|css';
        return strpos($single, $name) !== false;
    }

    /**
     * 设置content
     * @param $content
     * @return $this
     * @author xiongba
     * @date 2020-01-01 19:35:19
     */
    public function content($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * 转换成html
     * @return string
     * @author xiongba
     * @date 2020-01-01 19:35:04
     */
    public function toHtml()
    {
        if ($this->single) {
            if ($this->name == 'input') {
                $this->attr['value'] = $this->content;
            } elseif ($this->name == 'img') {
                $this->attr['src'] = $this->content;
            } elseif ($this->name == 'link') {
                $this->attr['href'] = $this->content;
            } elseif ($this->name == 'css') {
                $this->name = 'link';
                $_attr = ['href' => $this->content, 'rel' => 'stylesheet', 'media' => 'all'];
                $this->attr = merge_array($this->attr, $_attr);
            }
            return '<' . $this->name . ' ' . $this->parseAttr($this->attr) . '/>';
        } else {

            if (is_array($this->content)) {
                $content = join('',
                    array_map(function ($v) {
                        return $v . "";
                    }, (array)$this->content)
                );
            } else {
                $content = $this->content;
            }

            return '<' . $this->name . ' ' . $this->parseAttr($this->attr) . '>' . $content . '</' . $this->name . '>';
        }
    }

    /**
     * 转换成string
     * @return string
     * @author xiongba
     * @date 2020-01-01 19:35:31
     */
    public function __toString()
    {
        return $this->toHtml();
    }


}