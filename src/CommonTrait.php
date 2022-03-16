<?php

namespace Src;

trait CommonTrait {

    protected array $classMap = [];

    protected function findNamespace(string $path):string
    {
        $pathSegments = explode('/', $path);
        for($i = count($pathSegments); $i >= 0; $i--){
            $tailSegments = array_slice($pathSegments, 0, $i);
            $tailPath = implode('/', $tailSegments);
            $composerFile = $tailPath . '/composer.json';
            if(file_exists($composerFile)){
                $this->classmap = $this->getClassMap($composerFile);
                $namespaceSegments = array_slice($pathSegments, $i);
                $namespace = implode('\\', $namespaceSegments);
                foreach($this->classmap as $dir=>$ns){
                    $namespace = str_replace($dir, $ns, $namespace);
                }
                return $namespace;
            }
        }
        return '';
    }

    protected function getClassMap(string $composerFile):array
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

}