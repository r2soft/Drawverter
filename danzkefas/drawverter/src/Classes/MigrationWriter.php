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

    public function main(){
        if ($this->notation == "chen"){
            $arr = $this->ReadFromArrayChen();
        } else {
            $arr = $this->ReadFromArrayCrow();
        }
        $this->WriteMigration($arr);
        return true;
    }

    public function ReadFromArrayCrow(){
        //Hanya Menambahkan nama dan atribut dari entitas, belum sampai pada tahap relasi
        foreach($this->raw['entity'] as $entity){
            $entityName = $entity['value'];
            $entityAttr = [];
            foreach($entity['attributes'] as $attr){
                $entityAttr[] = $attr['value'];
            }
            $res[] = new Entity($entityName, $entityAttr);
        }

        return $res;
    }

    public function ReadFromArrayChen(){
        $res = [];
        foreach($this->raw as $obj){
            if($obj['type'] == "Entity" or $obj['type'] == "WeakEntity"){
                $entityName = $obj['value'];
                $entityAttr = [];
                foreach($this->raw as $obj2){
                    if($obj2['type'] == "line" and ($obj2['source'] == $obj['id'] or $obj2['target'] == $obj['id'])){
                        $targetID = null;
                        if($obj2['source'] == $obj['id']){
                            $targetID = $obj2['target'];
                        } else {
                            $targetID = $obj2['source'];
                        }

                        foreach($this->raw as $obj3){
                            if($obj3['id'] == $targetID){
                                $entityAttr[] = $obj3['value'];
                                break;
                            }
                        }
                    }
                }
                $res[] = new Entity($entityName, $entityAttr);
            }
        }
        return $res;
    }

    public function WriteMigration($entityArr){
        foreach($entityArr as $entity){
            $name = $entity->get_name();
            $attr = $entity->get_attribute();
            $lowerCaseName = strtolower($name);
            $sixDigitRandomNumber = mt_rand(100000,999999);
            $filename = Carbon::now()->format('Y_m_d').'_'.$sixDigitRandomNumber.'_create_'. $lowerCaseName . 's_table.php';
            $path = base_path().'/database/migrations/'.$filename;

            $handle = fopen($path, 'w') or die("can't open file!");
            $written = "<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Create{$name}sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('{$lowerCaseName}s', function (Blueprint \$table) {
            ";

            fwrite($handle, $written);
            $first = 0;
            foreach($attr as $a){
                if(strpos($a, "*") !== False or strpos($a, "<u>") !== False){
                    if($this->notation == "crow"){
                        $attrName = trim($a, "*");
                    } else {
                        $attrName = trim($a, "<u>");
                        $attrName = trim($a, "</u>");
                    }

                    if ($first == 0){
                        $written = "\$table->id('{$attrName}'); \n";
                    } else {
                        $written = "            \$table->id('{$attrName}'); \n";
                    }
                    fwrite($handle, $written);
                } else {
                    if ($first == 0){
                        $written = "\$table->string('{$a}'); \n";
                    } else {
                        $written = "            \$table->string('{$a}'); \n";
                    }
                    fwrite($handle, $written);
                }
                $first++;
            }

            $written="        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('{$lowerCaseName}s');
    }
}";
            fwrite($handle,$written);
            fclose($handle);
        }
        return true;
    }


}