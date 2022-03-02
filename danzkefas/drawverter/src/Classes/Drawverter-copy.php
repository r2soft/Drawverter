<?php

namespace Danzkefas\Drawverter\Classes;

use Exception;

class Drawverter
{

    public function convert($filename)
    {
        try {
            $file = dirname(__DIR__, 1) . '/XML/' . $filename;
            $xml = simplexml_load_file($file);
        } catch (Exception $e) {
            echo "404 File not Found!";
            return false;
        }
        $json = json_encode($xml);
        $decode = json_decode($json, true);
        // return response()->json($xml);
        if (array_key_exists('diagram', $decode)) {
            $notation = $this->determineNotation($decode);
            if ($notation == 'chen') {
                $processedArray = $this->chenConvert($decode);
                return response()->json($processedArray);
            } else {
                $processedArray = $this->crowsConvert($decode);
                return response()->json($processedArray);
            }
        } else {
            echo "Invalid Diagram!";
            return false;
        }
    }

    public function matchValueObject($array, $string)
    {
        $pattern = "/" . $string . "/i";
        if (preg_match($pattern, $array)) {
            return true;
        }
        return false;
    }

    public function determineNotation($decode){
        $counter = 0;
        foreach($decode['diagram']['mxGraphModel']['root']['mxCell'] as $d){
            if(array_key_exists('mxGeometry', $d)){
                if($this->matchValueObject($d['@attributes']['style'], 'swimlane')){
                    $counter++;
                    break;
                }
            }
        }
        $notation = '';
        if($counter > 0){
            $notation = 'crows';
        } else {
            $notation = 'chen';
        }
        return $notation;
    }

    public function crowsConvert($decode){
        $newArr = array();
        $newArr["line"] = array();
        $newArr["entity"] = array();
        $attr = '@attributes';
        $parentID = $decode['diagram']['mxGraphModel']['root']['mxCell'][1]['@attributes']['id'];
        $counterEntity = 0;
        foreach($decode['diagram']['mxGraphModel']['root']['mxCell'] as $d){
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
                        foreach($decode['diagram']['mxGraphModel']['root']['mxCell'] as $e){
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

    public function chenConvert($decode)
    {
        $tempArr = array();
        $parentID = $decode['diagram']['mxGraphModel']['root']['mxCell'][1]['@attributes']['id'];
        $attr = '@attributes';
        foreach ($decode['diagram']['mxGraphModel']['root']['mxCell'] as $d) {
            if (array_key_exists('mxGeometry', $d)) {
                if ((array_key_exists('source', $d[$attr]) or array_key_exists('target', $d[$attr])) and $d[$attr]['parent'] == $parentID) {
                    foreach ($decode['diagram']['mxGraphModel']['root']['mxCell'] as $e) {
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
                        $tempArr[] = array(
                            "id" => $d[$attr]['id'],
                            "value" => $d[$attr]['value'],
                            "style" => $d[$attr]['style'],
                            "type" => "Entity"
                        );
                    } else {
                        $tempArr[] = array(
                            "id" => $d[$attr]['value'],
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
