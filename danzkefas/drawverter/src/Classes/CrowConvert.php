<?php

namespace Danzkefas\Drawverter\Classes;

class CrowConvert extends Convert
{
    public function convert(){
        $key_variable = null;
        $parentID = null;

        if($this->status == "single"){
            $key_variable = $this->decode['diagram']['mxGraphModel']['root']['mxCell'];
            $parentID = $this->decode['diagram']['mxGraphModel']['root']['mxCell'][1]['@attributes']['id'];
        } else {
            $key_variable = $this->decode['mxGraphModel']['root']['mxCell'];
            $parentID = $this->decode['mxGraphModel']['root']['mxCell'][1]['@attributes']['id'];
        }

        $newArr = array();
        $newArr["line"] = array();
        $newArr["entity"] = array();
        $attr = '@attributes';
        $counterEntity = 0;
        foreach($key_variable as $d){
            if(array_key_exists('mxGeometry', $d)){
                if((array_key_exists('source', $d[$attr]) or array_key_exists('target', $d[$attr])) and $d[$attr]['parent'] == $parentID){
                    $newArr["line"][] = array(
                        "id" => $d[$attr]['id'],
                        "style" => $d[$attr]['style'],
                        "source" => $d[$attr]['source'],
                        "target" => $d[$attr]['target'],  
                    );
                } elseif($d[$attr]['parent'] == $parentID){
                    if($this->matchValueObject($d['@attributes']['style'], 'swimlane')){
                        $newArr["entity"][] = array(
                            "id" => $d[$attr]['id'],
                            "value" => $d[$attr]['value'],
                            "attributes" => array(),
                        );
                        foreach($key_variable as $e){
                            if ((array_key_exists('mxGeometry', $e) and $e[$attr]['parent'] != $parentID) and $e[$attr]['parent'] == $d[$attr]['id']) {
                                array_push($newArr['entity'][$counterEntity]['attributes'], array("id" => $e[$attr]['id'], "value" => $e[$attr]['value']));
                            }
                        }
                        $counterEntity++;
                    }
                }
            }
        }
        return $newArr;
    }
}