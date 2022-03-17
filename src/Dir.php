<?php

namespace Src;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class Dir {

    use CommonTrait;

    protected string $name;

    protected string $path;

    protected string $interfacesPath;

    protected PhpNamespace $namespace;

    protected PhpNamespace $interfacesNs;

    protected ClassType $factory;

    protected ClassType $factoryInterface;

    protected array $classMap = [];

    protected string $backInterfaceNs = '';

    protected string $upDirName = '';

    /**
     * @var Dir[]
     */
    protected array $dirs = [];

    public function setName(string $name):void
    {
        $this->name = $name;
    }

    public function getName():string
    {
        return $this->name;
    }

    public function setPath(string $path):void
    {
        $this->path = $path;
    }

    public function setBackInterfaceNs(string $ns)
    {
        $this->backInterfaceNs = $ns;
    }

    public function setUpDirName(string $name)
    {
        $this->upDirName = $name;
    }

    public function addDir(Dir $dir):void
    {
        $dir->setPath($this->path . '/' . $this->name);
        $this->dirs[] = $dir;
    }

    protected function init()
    {     
        $classNs = $this->findNamespace($this->path . '/' . $this->name);
        $this->namespace = new PhpNamespace($classNs);
        $interfaceNs = $this->findNamespace($this->interfacesPath);
        $this->interfacesNs = new PhpNamespace($interfaceNs);

        $this->factory = ClassType::class('Factory');
        $this->factory->addImplement('IFactory');
        $this->namespace->add($this->factory);
        $this->namespace->addUse($interfaceNs . '\IFactory');
        $this->factoryInterface = ClassType::interface('IFactory');
        $this->interfacesNs->add($this->factoryInterface);

        $this->addMethods();
    }

    protected function addMethods()
    {
        if($this->backInterfaceNs){
            $this->namespace->addUse($this->backInterfaceNs . '\IFactory', 'I' . $this->upDirName . 'Factory');
            $this->interfacesNs->addUse($this->backInterfaceNs . '\IFactory', 'I' . $this->upDirName . 'Factory');
        }
        if($this->backInterfaceNs){
            $this->factory->addProperty(lcfirst($this->upDirName) . 'Factory')->setProtected()->setType('I' . $this->upDirName . 'Factory');
        }
        if($this->backInterfaceNs){
            $method = $this->factory->addMethod('set' . $this->upDirName . 'Factory');
            $method->setPublic();
            $method->addParameter('factory')->setType('I' . $this->upDirName . 'Factory');
            $method->setReturnType('void');
            $method->addBody('$this->' . lcfirst($this->upDirName) . 'Factory = $factory;');

            $method = $this->factoryInterface->addMethod('set' . $this->upDirName . 'Factory');
            $method->setPublic();
            $method->addParameter('factory')->setType('I' . $this->upDirName . 'Factory');
            $method->setReturnType('void');

            if($this->dirs){
                $method = $this->factory->addMethod('get' . $this->upDirName . 'Factory');
                $method->setPublic();
                $method->setReturnType('I' . $this->upDirName . 'Factory');
                $method->addBody('return $this->' . lcfirst($this->upDirName) . 'Factory;');

                $method = $this->factoryInterface->addMethod('get' . $this->upDirName . 'Factory');
                $method->setPublic();
                $method->setReturnType('I' . $this->upDirName . 'Factory');
            }
        }

        foreach($this->dirs as $dir){
            $interfaceNs = $this->findNamespace($this->interfacesPath . '/' . $dir->getName());
            $this->namespace->addUse($interfaceNs . '\IFactory', 'I' . $dir->getName() . 'Factory');
            $this->interfacesNs->addUse($interfaceNs . '\IFactory', 'I' . $dir->getName() . 'Factory');

            $dir->setBackInterfaceNs($this->findNamespace($this->interfacesPath));
            $dir->setUpDirName($this->name);

            $classNs = $this->findNamespace($this->path . '/' . $this->name . '/' . $dir->getName());
            $this->namespace->addUse($classNs . '\Factory', $dir->getName() . 'Factory');
            $this->factory->addProperty(lcfirst($dir->getName()) . 'Factory', null)->setProtected()->setType('?I' . $dir->getName() . 'Factory');
            $method = $this->factory->addMethod('get' . $dir->getName() . 'Factory');
            $method->setPublic();
            $method->setReturnType('I' . $dir->getName() . 'Factory');
            $method->addBody('
if($this->' . lcfirst($dir->getName()) . 'Factory === null){
    $this->' . lcfirst($dir->getName()) . 'Factory = new ' . $dir->getName() . 'Factory();
    $this->' . lcfirst($dir->getName()) . 'Factory->set' . $this->name . 'Factory($this);
}
return $this->' . lcfirst($dir->getName()) . 'Factory;
            ');

            $method = $this->factoryInterface->addMethod('get' . $dir->getName() . 'Factory');
            $method->setPublic();
            $method->setReturnType('I' . $dir->getName() . 'Factory');
        }
    }

    public function saveFiles():void
    {
        $this->interfacesPath = $this->findInterfacesPath($this->path . '/' . $this->name);
        mkdir($this->path . '/' . $this->name, 0777, true);
        mkdir($this->interfacesPath, 0777, true);
        $this->init();

        $printer = new Printer();
        $printer->setTypeResolving(false);
        $str = "<?php\n\n" . $printer->printNamespace($this->namespace);
        file_put_contents($this->path . '/' . $this->name . '/Factory.php', $str);
        $str = "<?php\n\n" . $printer->printNamespace($this->interfacesNs);
        file_put_contents($this->interfacesPath . '/IFactory.php', $str);
        foreach($this->dirs as $dir)
        {
            $dir->saveFiles();
        }
    }

}
