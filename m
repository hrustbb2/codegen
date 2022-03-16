#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use Src\Module;

$moduleName = $argv[1] ?? '';
if(!$moduleName){
    die('Module name not specified.' . PHP_EOL);
}

$module = new Module();
$module->saveFiles($moduleName);