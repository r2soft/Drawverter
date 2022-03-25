<?php

namespace Danzkefas\Drawverter\Console;

use Danzkefas\Drawverter\Classes\Drawverter;
use Exception;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\ConsoleOutput;

class DrawverterProcessERD extends Command
{
    protected $signature = 'drawverter:start
        {fileName : File name on XML directory.}';

    protected $description = "Convert ERD to Migration";

    public function handle(){
        $this->info("Converting your ERD to Migration file...");
        $fileName = $this->argument('fileName');

        if($this->confirm('Are you sure want to Continue?')){
            $obj = new Drawverter;
            $res = $obj->start($fileName);
            try{
                $res = $obj->start($fileName);
                if ($res == true){
                    $this->info("Success Converting ERD to Migration. Please check the migration directory!");
                } else {
                    $this->info("404 File Not Found!");
                }
            } catch (Exception $e) {
                $this->error("Something went wrong. Please check again!");
                if($this->confirm('Print Error?')){
                    $this->error($e->getMessage());
                }
            }
        }
    }
}