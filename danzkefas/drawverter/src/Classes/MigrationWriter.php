<?php

namespace Danzkefas\Drawverter\Classes;

use Carbon\Carbon;
use Danzkefas\Drawverter\Model\Entity;

class MigrationWriter
{
    public $raw;
    public $notation;

    /*
    Method Constructor
    */
    public function __construct($raw, $notation)
    {
        $this->raw = $raw;
        $this->notation = $notation;
    }

    /*
    Main Method untuk menjankan kelas ini
    */
    public function main()
    {
        if ($this->notation == "chen") {
            $arr = $this->ReadFromArrayChen();
        } else {
            $arr = $this->ReadFromArrayCrow();
        }
        $this->WriteMigration($arr);
    }


    /*
    Method untuk membaca Array dengan notasi Crow
    Mengembalikan kembalian berupa array
    dengan Objek Entitas
    */
    public function ReadFromArrayCrow()
    {
        // Menambahkan nama dan atribut dari entitas
        foreach ($this->raw['entity'] as $entity) {
            //Mencari Nama
            $entityName = strtolower($entity['value']);
            $entityAttr = [];
            //Mencari Attr
            foreach ($entity['attributes'] as $attr) {
                $entityAttr[] = strtolower($attr['value']);
            }
            $res[] = new Entity($entityName, $entityAttr);
            //Menentukan relasi
            foreach($this->raw['line'] as $line){
                $relation = [];
                $src = $line['source'];
                $trg = $line['target'];
                foreach($this->raw['entity'] as $e){
                    $tempName = null;
                    foreach($e['attributes'] as $a){
                        if($a['id'] == $src){
                            $relateOn = strtolower($e['value']);
                            $reference = strtolower($a['value']);
                        }elseif ($a['id'] == $trg){
                            $foreign = strtolower($a['value']);
                            $tempName = $e['value'];
                        }
                    }
                    if(strcasecmp($relateOn, $e['value']) != 0){
                        $relation[] = array($relateOn, $reference, $foreign);
                        foreach($res as $i){
                            if(strcasecmp($i->get_name(), $tempName) == 0){
                                $i->set_relation($relation);
                                break;
                            }
                        }
                    }
                    
                }
            }
        }

        return $res;
    }

    /*
    Method untuk membaca Array dengan notasi Chen
    Mengembalikan kembalian berupa array
    dengan Objek Entitas
    */
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
                                $targetID2 = $obj3['id'];
                                //Terdapat garis dot yang menunjukan weak key attr
                                if(strpos($obj3['value'], "dotted") !== False){
                                    $cleanName = preg_replace('/\s+/', '', $obj3['value']);
                                    $cleanName = trim($cleanName, '<spanstyle="border-bottom:1px dotted">');
                                    $cleanName = trim($cleanName, '</span>');
                                    $entityAttr[] = strtolower($cleanName);
                                } else {
                                    $entityAttr[] = strtolower($obj3['value']);
                                    //Multivalued Finder
                                    foreach($this->raw as $obj4){
                                        $temp = null;
                                        if ($obj4['type'] == "line" and ($obj4['source'] == $targetID2 or $obj4['target'] == $targetID2)){
                                            if ($obj4['source'] == $targetID2) {
                                                $temp = $obj4['target'];
                                            } else {
                                                $temp = $obj4['source'];
                                            }
                                            foreach($this->raw as $obj5){
                                                if($obj5['id'] == $temp and $obj5['type'] = "Attribute" and strcasecmp($obj5['value'], $entityName) != 0){
                                                    $entityAttr[] = strtolower($obj5['value']);
                                                }
                                            }
                                        }
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
                $res[] = new Entity($entityName, $entityAttr);
            }
        }

        //Menentukan Relasi dari setiap Entitas
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
                                $relationID[] = array($tempName, strtolower($relObj2['valueRelation']), $relObj['id']);
                                break;
                            }
                        }
                        
                    }
                }
                $allRel[] = $relationID;
            }
        }

        //Memnentukan Relasi berdasarkan array relasi
        foreach($allRel as $relID){
            $checkType = 0;
            foreach($relID as $i){
                if($i[1] ==  "1"){
                    $checkType = 1;
                    break;
                }
            }
            //Adding some relID check
            if($checkType == 0){
                $relationName = "";
                $entityAttr = [];
                $relation = [];
                $reference = "";
                $foreign = "";
                $tempRel = [];
                $targetRelationship = "";

                foreach($relID as $i){
                    $tempRel[] = $i[0];
                    $targetRelationship = $i[2];
                }

                //Check if Relation have Attribute
                foreach ($this->raw as $i) {
                    if ($i['type'] == "line" and ($i['source'] == $targetRelationship or $i['target'] == $targetRelationship)) {
                        if ($i['source'] == $targetRelationship) {
                            $trg = $i['target'];
                        } else {
                            $trg = $i['source'];
                        }
                        foreach ($this->raw as $j) {
                            if ($j['id'] == $trg) {
                                $entityAttr[] = strtolower($j['value']);
                            }
                        }

                    }
                }

                foreach($res as $i){
                    if(in_array($i->get_name(), $tempRel)){
                        $relationName .= $i->get_name();
                        $relateOn = $i->get_name();
                        foreach($i->get_attribute() as $j){
                            if(strpos($j, "_id") !== False and strpos($j, "<u>") !== False){
                                $entityAttr[] = $j;
                                $foreign = trim($j, "</u>");
                                $reference = $j;
                            }
                        }
                        $relation[] = array($relateOn, $reference, $foreign);
                    }
                }
                $res[] = new Entity($relationName, $entityAttr, $relation);

            } else {
                //Relasi 1..N 
                foreach($relID as $i){
                    $foreign = "";
                    $reference = "";
                    $relateOn = "";
                    $relation = [];
                    if($i[1] == "n" or $i[1] == "m"){
                        $targetEntity = $i[0];
                        foreach($res as $j){
                            if($j->get_name() == $targetEntity){
                                foreach($j->get_attribute() as $k){
                                    if(strpos($k, "</u>") == False and strpos($k, "_id") !== False){
                                        $foreign = $k;
                                        $relateOn = strtolower(trim($k, "_id"));
                                        foreach($relID as $l){
                                            if(strcasecmp($l[0], $relateOn) == 0){
                                                foreach($res as $m){
                                                    if($m->get_name() == $relateOn){
                                                        foreach ($m->get_attribute() as $n){
                                                            if(strpos($n, "</u>") !== False){
                                                                $reference = $n;
                                                                break;
                                                            }
                                                        }
                                                        break;
                                                    }
                                                }
                                                $relation[] = array($relateOn, $reference, $foreign);
                                                break;
                                            }
                                        }
                                    }
                                }
                                $j->set_relation($relation);
                                break;
                            }
                        }
                    }
                }
            }
        }
        return $res;
    }

    /*
    Method untuk menulis array dari objek Entitas yang dibuat
    Mengembalikan kembalian berupa file migration
    yang dapat dipakai dengan perintah artisan
    */
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