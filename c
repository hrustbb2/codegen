#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use Src\ClassCreator;

$className = $argv[1] ?? '';
if(!$className){
    die('Class name not specified.' . PHP_EOL);
}

$creator = new ClassCreator();
$creator->saveFile($className);