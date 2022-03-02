<?php

namespace Danzkefas\Drawverter\Classes;

class ChenConvert extends Convert
{
    public function convert(){
        $key_variable = $this->decode;
        $parentID = $this->decode;

        if($this->status == "single"){
            $key_variable = $this->decode['diagram']['mxGraphModel']['root']['mxCell'];
            $parentID = $this->decode['diagram']['mxGraphModel']['root']['mxCell'][1]['@attributes']['id'];
        } else {
            $key_variable = $this->decode['mxGraphModel']['root']['mxCell'];
            $parentID = $this->decode['mxGraphModel']['root']['mxCell'][1]['@attributes']['id'];
        }

        $tempArr = array();
        $attr = '@attributes';
        foreach ($key_variable as $d) {
            if (array_key_exists('mxGeometry', $d)) {
                if ((array_key_exists('source', $d[$attr]) or array_key_exists('target', $d[$attr])) and $d[$attr]['parent'] == $parentID) {
                    foreach ($key_variable as $e) {
                        if ((array_key_exists('mxGeometry', $e) and $e[$attr]['parent'] != $parentID) and $e[$attr]['parent'] == $d[$attr]['id']) {
                            $tempArr[] = array(
                                "id" => $d[$attr]['id'],
                                "style" => $d[$attr]['style'],
                                "source" => $d[$attr]['source'],
                                "target" => $d[$attr]['target'],
                                "valueRelation" => $e[$attr]['value'],
                                "type" => "lineRelationship"
                            );
                            break;
                        }
                    }
                    $tempArr[] = array(
                        "id" => $d[$attr]['id'],
                        "style" => $d[$attr]['style'],
                        "source" => $d[$attr]['source'],
                        "target" => $d[$attr]['target'],
                        "type" => "line"
                    );
                } elseif ($d[$attr]['parent'] == $parentID) {
                    if ($this->matchValueObject($d[$attr]['style'], "ellipse")) {
                        $tempArr[] = array(
                            "id" => $d[$attr]['id'],
                            "value" => $d[$attr]['value'],
                            "style" => $d[$attr]['style'],
                            "type" => "Attribute"
                        );
                    } elseif ($this->matchValueObject($d[$attr]['style'], "shape=rhombus")) {
                        $tempArr[] = array(
                            "id" => $d[$attr]['id'],
                            "value" => $d[$attr]['value'],
                            "style" => $d[$attr]['style'],
                            "type" => "Relationship"
                        );
                    } elseif ($this->matchValueObject($d[$attr]['style'], "rounded=0") or $this->matchValueObject($d[$attr]['style'], "whiteSpace=wrap")) {
                        if ($this->matchValueObject($d[$attr]['style'], "shape=ext")){
                            $tempArr[] = array(
                                "id" => $d[$attr]['id'],
                                "value" => $d[$attr]['value'],
                                "style" => $d[$attr]['style'],
                                "type" => "WeakEntity"
                            );
                        } else {
                            $tempArr[] = array(
                                "id" => $d[$attr]['id'],
                                "value" => $d[$attr]['value'],
                                "style" => $d[$attr]['style'],
                                "type" => "Entity"
                            );
                        }
                    } else {
                        $tempArr[] = array(
                            "id" => $d[$attr]['id'],
                            "type" => "Invalid Object"
                        );
                    }
                }
            }
        }
        $tempArr2 = array_unique(array_column($tempArr, 'id'));
        $newArr = array_intersect_key($tempArr, $tempArr2);
        return $newArr;
    }
}