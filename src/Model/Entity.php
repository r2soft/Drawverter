<?php

namespace Danzkefas\Drawverter\Model;

class Entity
{
    public $name = "";
    public $attribute= [];
    public $relation = [];

    public function __construct($name, $attr, $relation = null)
    {
        $this->name = $name;
        $this->attribute = $attr;
        $this->relation = $relation;
    }

    function set_relation($relation){
        $this->relation = $relation;
    }

    function get_name(){
        return $this->name;
    }

    function get_attribute(){
        return $this->attribute;
    }

    function get_relation(){
        return $this->relation;
    }

}