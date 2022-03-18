#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use Src\Dir;

$dirName = $argv[1] ?? '';
if(!$dirName){
    die('Dir name not specified.' . PHP_EOL);
}

function getClassMap(string $composerFile):array
{
    $result = [];
    $jsonStr = file_get_contents($composerFile);
    $composerArr = json_decode($jsonStr, true);
    if(key_exists('autoload', $composerArr) && key_exists('psr-4', $composerArr['autoload'])){
        foreach($composerArr['autoload']['psr-4'] as $ns=>$dir){
            $dir = str_replace('/', '\\', $dir);
            $result[$dir] = $ns;
        }
    }
    return $result;
}

function findNamespace(string $path):string
{
    $pathSegments = explode('/', $path);
    for($i = count($pathSegments); $i >= 0; $i--){
        $tailSegments = array_slice($pathSegments, 0, $i);
        $tailPath = implode('/', $tailSegments);
        $composerFile = $tailPath . '/composer.json';
        if(file_exists($composerFile)){
            $classmap = getClassMap($composerFile);
            $namespaceSegments = array_slice($pathSegments, $i);
            $namespace = implode('\\', $namespaceSegments);
            foreach($classmap as $dir=>$ns){
                $namespace = str_replace($dir, $ns, $namespace);
            }
            return $namespace;
        }
    }
    return '';
}

function findInterfacesPath(string $path)
{
    $pathSegments = explode('/', $path);
    $interfacesPathSegments = [];
    for($i = count($pathSegments); $i >= 0; $i--){
        $tailSegments = array_slice($pathSegments, 0, $i);
        $tailPath = implode('/', $tailSegments);
        $interfacesDir = $tailPath . '/Interfaces';
        array_unshift($interfacesPathSegments, $pathSegments[$i] ?? null);
        if(is_dir($interfacesDir)){
            array_unshift($interfacesPathSegments, 'Interfaces');
        }
    }
    return '/' . trim(implode('/', $interfacesPathSegments), '/');
}

$dir = new Dir();
$dir->setName($dirName);
$path = getcwd();
$dir->setPath($path);
$backInterfacePath = findInterfacesPath($path);
$backInterfaceNs = findNamespace($backInterfacePath);
$dir->setBackInterfaceNs($backInterfaceNs);
$pathSegments = explode('/', $path);
$upDirName = $pathSegments[count($pathSegments) - 1];
$dir->setUpDirName($upDirName);

$dir->saveFiles();