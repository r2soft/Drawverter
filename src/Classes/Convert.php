<?php

namespace Danzkefas\Drawverter\Classes;

class Convert
{
    public $decode;
    public $status;

    public function __construct($decode, $status)
    {
        $this->decode = $decode;
        $this->status = $status;
    }

    public function convertRawInput(){

        $array_key_check = null;
        if($this->status == "single"){
            $array_key_check = array_key_exists('diagram', $this->decode);
        } else {
            $array_key_check = array_key_exists('mxGraphModel', $this->decode);
        }

        if ($array_key_check) {
            $notation = $this->determineNotation();
            if ($notation == 'chen') {
                $processedArray = new ChenConvert($this->decode, $this->status);
                $temp = new MigrationWriter($processedArray->convert(), $notation);
                $res = $temp->main();
            } else {
                $processedArray = new CrowConvert($this->decode, $this->status);
                $temp = new MigrationWriter($processedArray->convert(), $notation);
                $res = $temp->main();
                // $res = $processedArray->convert();
            }
            return $res;
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

    public function determineNotation()
    {   
        if($this->status == "single"){
            $array = $this->decode['diagram']['mxGraphModel']['root']['mxCell'];
        } else {
            $array = $this->decode['mxGraphModel']['root']['mxCell'];
        }

        $counter = 0;
        foreach($array as $d){
            if(array_key_exists('mxGeometry', $d)){
                if($this->matchValueObject($d['@attributes']['style'], 'swimlane')){
                    $counter++;
                    break;
                }
            }
        }
        $notation = '';
        if($counter > 0){
            $notation = 'crow';
        } else {
            $notation = 'chen';
        }
        return $notation;
    }
}