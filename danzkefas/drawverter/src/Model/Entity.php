<?php

namespace Danzkefas\Drawverter\Model;

class Entity
{
    public $name = "";
    public $attribute= [];

    public function __construct($name, $attr)
    {
        $this->name = $name;
        $this->attribute = $attr;
    }

    function get_name(){
        return $this->name;
    }

    function get_attribute(){
        return $this->attribute;
    }

}