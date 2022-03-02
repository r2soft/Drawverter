<?php

namespace Danzkefas\Drawverter\Classes;

use Exception;

class Drawverter
{

    public function start($filename)
    {
        try {
            $file = public_path() . '/xml-data/' . $filename;
            $xml = simplexml_load_file($file);
        } catch (Exception $e) {
            return false;
        }
        $json = json_encode($xml);
        $decode = json_decode($json, true);
        
        if ($this->CountPage($decode['diagram']) == 1){
            $diagram = new Convert($decode, "single");
            $diagram->convertRawInput();
        } else {
            foreach($decode['diagram'] as $one){
                $diagram = new Convert($one, "multi");
                $diagram->convertRawInput();
            }
        }
        return true;
    }

    public function CountPage($decode)
    {
        $counter = 0;
        foreach($decode as $v){
            $counter++;
        }
        return $counter-1;
    }
}
