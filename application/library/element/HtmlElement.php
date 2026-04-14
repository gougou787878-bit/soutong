<?php

namespace element;


class HtmlElement
{


    public function select($name, $array, array $attr = [])
    {
        if (is_array($name)){
            $attr = $array;
            $array = $name;
            $name = null;
        }
        $select = new Element('select');
        $option = [];
        foreach ($array as $key => $item) {
            $el = new Element('option');
            if (is_array($item)){
                $keys = array_keys($item);
                $key = $keys[0];
            }
            $option[] = $el->attr(['value' => $key])->content($item);
        }
        $select->attr($attr)->content($option)->attr(['name' => $name]);
        return $select;
    }

    public function input($name, $value, $type = 'text', $placeholder = null, array $attr = [], $autocomplete = 'off')
    {
        $input = new Element('input');
        return $input->content($value)->attr($attr)->attr(compact('name', 'value', 'type', 'placeholder',
            'autocomplete'));
    }

    public function radio($name, $value, array $attr = [])
    {
        return $this->input($name, $value, 'radio', null, $attr);
    }

    public function radioMulti($name, $values, $default = [], array $attr = [])
    {
        $input = [];
        if ($default == null) {
            $default = [];
        }
        foreach ($values as $key => $item) {
            $attr['title'] = $item;
            if (in_array($key, $default)) {
                $attr['checked'] = 'true';
            }
            $input[] = $this->radio($name, $key, $attr);
        }
        return join('', $input);
    }

    public function textarea($name, $value, array $attr = [])
    {
        $attr['name'] = $name;
        return (new Element('textarea'))->content($value)->attr($attr)->attr(['name' => $name]);
    }

    public function checkbox($name, $values, $default = [], array $attr = [])
    {
        $input = [];
        $default = $default ?? [];
        foreach ($values as $key => $item) {
            $attr['title'] = $item;
            if (in_array($key, $default)) {
                $attr['checked'] = 'true';
            }
            $input[] = $this->input($name, $key,'checkbox', null, $attr);
        }
        return join('', $input);
    }

    public function css($href)
    {
        return (new Element('css'))->content($href);
    }


}