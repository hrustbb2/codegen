<?php

namespace Src;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class ClassCreator {

    use CommonTrait;

    protected PhpNamespace $namespace;

    protected PhpNamespace $interfacesNs;

    protected ClassType $class;

    protected ClassType $interface;

    protected string $className;

    protected string $path;

    protected string $interfacesPath;

    protected function init(string $className):void
    {
        $classNs = $this->findNamespace($this->path);
        $this->namespace = new PhpNamespace($classNs);
        $interfaceNs = $this->findNamespace($this->interfacesPath);
        $this->interfacesNs = new PhpNamespace($interfaceNs);

        $this->class = ClassType::class($className);
        $this->class->addImplement('I' . $className);
        $this->namespace->add($this->class);
        $this->namespace->addUse($interfaceNs . '\I' . $className);

        $this->interface = ClassType::interface('I' . $className);
        $this->interfacesNs->add($this->interface);
    }

    public function saveFile(string $className):void
    {
        $cwd = getcwd();
        $this->className = $className;
        $this->path = $cwd;
        $this->interfacesPath = $this->findInterfacesPath($cwd);

        $this->init($className);

        $printer = new Printer();
        $printer->setTypeResolving(false);
        $str = "<?php\n\n" . $printer->printNamespace($this->namespace);
        file_put_contents($this->path . '/' . $this->className . '.php', $str);
        $str = "<?php\n\n" . $printer->printNamespace($this->interfacesNs);
        file_put_contents($this->interfacesPath . '/I' . $this->className . '.php', $str);
    }

}