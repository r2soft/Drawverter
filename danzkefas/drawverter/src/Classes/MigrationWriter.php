<?php

namespace Danzkefas\Drawverter\Classes;

use Carbon\Carbon;
use Danzkefas\Drawverter\Model\Entity;

class MigrationWriter
{
    public $raw;
    public $notation;

    public function __construct($raw, $notation)
    {
        $this->raw = $raw;
        $this->notation = $notation;
    }

    public function main()
    {
        if ($this->notation == "chen") {
            $arr = $this->ReadFromArrayChen();
        } else {
            $arr = $this->ReadFromArrayCrow();
        }
        $this->WriteMigration($arr);
    }

    public function ReadFromArrayCrow()
    {
        // Menambahkan nama dan atribut dari entitas
        foreach ($this->raw['entity'] as $entity) {
            $entityName = strtolower($entity['value']);
            $entityAttr = [];
            foreach ($entity['attributes'] as $attr) {
                $entityAttr[] = strtolower($attr['value']);
            }
            //Menentukan relasi
            foreach($this->raw['line'] as $line){
                $relation = [];
                $src = $line['source'];
                $trg = $line['target'];
                $relateOn = "";
                $reference = "";
                $foreign = "";
                foreach($this->raw['entity'] as $e){
                    foreach($e['attributes'] as $a){
                        if($a['id'] == $src){
                            $relateOn = strtolower($e['value']);
                            $reference = strtolower($a['value']);
                        }elseif ($a['id'] == $trg){
                            $foreign = strtolower($a['value']);
                        }
                    }
                    if(strcasecmp($relateOn, $e['value'])){
                        $relation[] = array($relateOn, $reference, $foreign);
                    }
                }
                
            }

            if($entityName != $relation[0][0]){
                $res[] = new Entity($entityName, $entityAttr, $relation);
            } else {
                $res[] = new Entity($entityName, $entityAttr);
            }
        }

        return $res;
    }

    public function ReadFromArrayChen()
    {
        $res = [];
        foreach ($this->raw as $obj) {
            if ($obj['type'] == "Entity" or $obj['type'] == "WeakEntity") {
                $entityName = strtolower($obj['value']);
                $entityAttr = [];
                //Mencari ID dari Entitas
                foreach ($this->raw as $obj2) {
                    if ($obj2['type'] == "line" and ($obj2['source'] == $obj['id'] or $obj2['target'] == $obj['id'])) {
                        $targetID = null;
                        if ($obj2['source'] == $obj['id']) {
                            $targetID = $obj2['target'];
                        } else {
                            $targetID = $obj2['source'];
                        }
                        //Mencari Atribut dengan entitas yang sesuai
                        foreach ($this->raw as $obj3) {
                            if ($obj3['id'] == $targetID) {
                                if(strpos($obj3['value'], "dotted") !== False){
                                    $cleanName = preg_replace('/\s+/', '', $obj3['value']);
                                    $cleanName = trim($cleanName, '<spanstyle="border-bottom:1px dotted">');
                                    $cleanName = trim($cleanName, '</span>');
                                    $entityAttr[] = strtolower($cleanName);
                                } else {
                                    $entityAttr[] = strtolower($obj3['value']);
                                }
                                break;
                            }
                        }
                    }
                }
                $res[] = new Entity($entityName, $entityAttr);
            }
        }

        foreach ($this->raw as $relObj){
            if($relObj['type'] == "Relationship"){
                $targetSrc = $relObj['id'];
                $relationID = [];
                foreach($this->raw as $relObj2){
                    if($relObj2['type'] == "lineRelationship" and ($relObj2['source'] == $targetSrc or $relObj2['target'] == $targetSrc)){
                        if ($relObj2['source'] == $targetSrc) {
                            $trg = $relObj2['target'];
                        } else {
                            $trg = $relObj2['source'];
                        }
                        foreach($this->raw as $relObj3){
                            if($relObj3['id'] == $trg){
                                $tempName = strtolower($relObj3['value']);
                                $relationID[] = array($tempName, $relObj2['valueRelation']);
                                break;
                            }
                        }
                        
                    }
                }
                $allRel[] = $relationID;
            }
        }

        //Memnentukan Relasi
        foreach($allRel as $relID){
            $checkType = 0;
            foreach($relID as $i){
                if($i[1] ==  "1"){
                    $checkType = 1;
                    break;
                }
            }

            if($checkType == 0){
                $relationName = "";
                $entityAttr = [];
                $relation = [];
                $reference = "";
                $foreign = "";
                foreach($res as $i){
                    $relationName .= $i->get_name();
                    $relateOn = $i->get_name();
                    $loopAttr = $i->get_attribute();
                    foreach($loopAttr as $j){
                        if(strpos($j, "_id") !== False and strpos($j, "<u>") !== False){
                            $entityAttr[] = $j;
                            $foreign = trim($j, "</u>");
                            $reference = $j;
                        }
                    }
                    $relation[] = array($relateOn, $reference, $foreign);
                }
                $res[] = new Entity($relationName, $entityAttr, $relation);
            } else {
                //Relasi 1..N 
                foreach($relID as $i){
                    $foreign = "";
                    $reference = "";
                    $relateOn = "";
                    $relation = [];
                    if($i[1] == "N"){
                        $targetEntity = $i[0];
                        foreach($res as $j){
                            if($j->get_name() == $targetEntity){
                                foreach($j->get_attribute() as $k){
                                    if(strpos($k, "</u>") == False and strpos($k, "id") !== False){
                                        $foreign = $k;
                                        $reference = $k;
                                        $relateOn = strtolower(trim($k, "_id"));
                                        foreach($relID as $l){
                                            if(strcasecmp($l[0], $relateOn)){
                                                $relation[] = array($relateOn, $reference, $foreign);
                                                break;
                                            }
                                        }
                                    }
                                }
                                $j->set_relation($relation);
                            }
                        }
                    }
                }
            }
        }
        return $res;
    }

    public function WriteMigration($entityArr)
    {
        $sixDigitCounter = 1;
        foreach ($entityArr as $entity) {
            $name = $entity->get_name();
            $attr = $entity->get_attribute();
            $relation = $entity->get_relation();
            $lowerCaseName = strtolower($name);
            $filename = Carbon::now()->format('Y_m_d') . '_' . sprintf('%06d', $sixDigitCounter) . '_create_' . $lowerCaseName . '_table.php';
            $sixDigitCounter ++;
            $path = base_path() . '/database/migrations/' . $filename;

            $handle = fopen($path, 'w') or die("can't open file!");
            $written = "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Create{$name}Table extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('{$lowerCaseName}', function (Blueprint \$table) {
            ";

            fwrite($handle, $written);
            $first = 0;
            foreach ($attr as $a) {
                if (strpos($a, "*") !== False or strpos($a, "<u>") !== False) {
                    $attrName = trim($a, "*");
                    $attrName = trim($attrName, "</u>");
                    if ($first == 0) {
                        $written = "\$table->id('{$attrName}'); \n";
                    } else {
                        $written = "            \$table->id('{$attrName}'); \n";
                    }
                    fwrite($handle, $written);
                } elseif(strpos($a, "_id") !== False){
                    if ($first == 0) {
                        $written = "\$table->unsignedBigInteger('{$a}'); \n";
                    } else {
                        $written = "            \$table->unsignedBigInteger('{$a}'); \n";
                    }
                    fwrite($handle, $written);
                } else {
                    if ($first == 0) {
                        $written = "\$table->string('{$a}'); \n";
                    } else {
                        $written = "            \$table->string('{$a}'); \n";
                    }
                    fwrite($handle, $written);
                }
                $first++;
            }

            if($relation != null){
                foreach($relation as $r){
                    $lowerCaseOn = strtolower($r[0]);
                    if($this->notation == "crow") {
                        $trimmed = trim($r[1], "*");
                    } else {
                        $trimmed = trim($r[1], "<u>");
                        $trimmed = trim($r[1], "</u>");
                    }
                    $written = "            \$table->foreign('{$r[2]}')->references('{$trimmed}')->on('{$lowerCaseOn}'); \n";
                    fwrite($handle, $written);
                }
            }

            $written = "        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('{$lowerCaseName}');
    }
}";
            fwrite($handle, $written);
            fclose($handle);
        }
        
    }
}
