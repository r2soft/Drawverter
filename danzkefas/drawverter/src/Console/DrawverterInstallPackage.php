<?php

namespace Danzkefas\Drawverter\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;


class DrawverterInstallPackage extends Command
{
    protected $signature = "drawverter:install";

    protected $description = "Install Drawverter Package";

    public function handle(){
        $this->info("Try to create directory 'xml-data' in public directory");
        $path = public_path().'/xml-data/';
        if (! File::exists($path)) {
            File::makeDirectory($path);
            $this->info("'xml-data' directory has been created succesfully");
        } else {
            $this->info("'xml-data' directory already exist!'");
        }
    }
}