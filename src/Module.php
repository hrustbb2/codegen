<?php

namespace Src;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;

class Module {

    use CommonTrait;

    protected PhpNamespace $namespace;

    protected PhpNamespace $interfacesNs;

    protected PhpNamespace $modulesProviderInterfaceNs;

    protected ClassType $factory;

    protected ClassType $factoryInterface;

    protected ClassType $modulesProviderInterface;

    protected string $moduleName;

    protected string $path;

    protected string $interfacesPath;

    /**
     * @var Dir[]
     */
    protected array $dirs = [];

    protected function init(string $moduleName)
    {
        $classNs = $this->findNamespace($this->path);
        $this->namespace = new PhpNamespace($classNs);
        $interfaceNs = $this->findNamespace($this->interfacesPath);
        $this->interfacesNs = new PhpNamespace($interfaceNs);

        $this->factory = ClassType::class('Factory');
        $this->factory->addImplement('IFactory');
        $this->namespace->add($this->factory);
        $this->namespace->addUse($interfaceNs . '\IFactory');
        $this->namespace->addUse($interfaceNs . '\IModulesProvider');
        $this->factoryInterface = ClassType::interface('IFactory');
        $this->interfacesNs->add($this->factoryInterface);
        $this->modulesProviderInterfaceNs = new PhpNamespace($interfaceNs);
        $this->modulesProviderInterface = ClassType::interface('IModulesProvider');
        $this->modulesProviderInterfaceNs->add($this->modulesProviderInterface);

        $this->dirs[] = $this->createDir('Application');
        $infrastructureDir = $this->createDir('Infrastructure');
        $subDir = $this->createDir('PersistLayer');
        $infrastructureDir->addDir($subDir);
        $subDir = $this->createDir('Queries');
        $infrastructureDir->addDir($subDir);
        $subDir = $this->createDir('Storage');
        $infrastructureDir->addDir($subDir);
        $this->dirs[] = $infrastructureDir;
        $this->dirs[] = $this->createDir('Dto');
        $this->dirs[] = $this->createDir('Pages');

        $this->addMethods();

        foreach($this->dirs as $dir){
            $dir->setBackInterfaceNs($interfaceNs);
            $dir->setUpDirName('Module');
        }
    }

    protected function addMethods()
    {
        $this->factory->addProperty('settings', [])->setProtected()->setType('array');
        $method = $this->factory->addMethod('injectModules');
        $method->setPublic();
        $method->addParameter('provider')->setType('IModulesProvider');
        $method->setReturnType('void');
        
        $method = $this->factoryInterface->addMethod('injectModules');
        $method->setPublic();
        $method->addParameter('provider')->setType('IModulesProvider');
        $method->setReturnType('void');

        $method = $this->factory->addMethod('loadSettings');
        $method->setPublic();
        $method->addParameter('settings')->setType('array');
        $method->addBody('$this->settings = $settings;');
        $method->setReturnType('void');

        $method = $this->factoryInterface->addMethod('loadSettings');
        $method->setPublic();
        $method->addParameter('settings')->setType('array');
        $method->setReturnType('void');

        $method = $this->factory->addMethod('getSetting');
        $method->setPublic();
        $method->addParameter('key')->setType('string');
        $method->addBody('return $this->settings[$key];');

        $method = $this->factoryInterface->addMethod('getSetting');
        $method->setPublic();
        $method->addParameter('key')->setType('string');

        foreach($this->dirs as $dir){
            $interfaceNs = $this->findNamespace($this->interfacesPath . '/' . $dir->getName());
            $this->namespace->addUse($interfaceNs . '\IFactory', 'I' . $dir->getName() . 'Factory');
            $this->interfacesNs->addUse($interfaceNs . '\IFactory', 'I' . $dir->getName() . 'Factory');

            $dir->setBackInterfaceNs($this->findNamespace($this->interfacesPath));
            $dir->setUpDirName($this->moduleName);

            $classNs = $this->findNamespace($this->path . '/' . $dir->getName());
            $this->namespace->addUse($classNs . '\Factory', $dir->getName() . 'Factory');
            $this->factory->addProperty(lcfirst($dir->getName()) . 'Factory', null)->setProtected()->setType('?I' . $dir->getName() . 'Factory');
            $method = $this->factory->addMethod('get' . $dir->getName() . 'Factory');
            $method->setPublic();
            $method->setReturnType('I' . $dir->getName() . 'Factory');
            $method->addBody('
if($this->' . lcfirst($dir->getName()) . 'Factory === null){
    $this->' . lcfirst($dir->getName()) . 'Factory = new ' . $dir->getName() . 'Factory();
    $this->' . lcfirst($dir->getName()) . 'Factory->setModuleFactory($this);
}
return $this->' . lcfirst($dir->getName()) . 'Factory;
            ');

            $method = $this->factoryInterface->addMethod('get' . $dir->getName() . 'Factory');
            $method->setPublic();
            $method->setReturnType('I' . $dir->getName() . 'Factory');
        }
    }

    protected function createDir(string $name):Dir
    {
        $dir = new Dir();
        $dir->setName($name);
        $dir->setPath($this->path);
        return $dir;
    }

    public function saveFiles(string $moduleName):void
    {
        $this->moduleName = $moduleName;
        $cwd = getcwd();
        $this->path = $cwd . '/' . $moduleName;
        $this->interfacesPath = $cwd . '/' . $moduleName . '/Interfaces';
        
        mkdir($this->path, 0777, true);
        mkdir($this->interfacesPath, 0777, true);

        $this->init($moduleName);

        $printer = new Printer();
        $printer->setTypeResolving(false);
        $str = "<?php\n\n" . $printer->printNamespace($this->namespace);
        file_put_contents($this->path . '/Factory.php', $str);
        $str = "<?php\n\n" . $printer->printNamespace($this->interfacesNs);
        file_put_contents($this->interfacesPath . '/IFactory.php', $str);
        $str = "<?php\n\n" . $printer->printNamespace($this->modulesProviderInterfaceNs);
        file_put_contents($this->interfacesPath . '/IModulesProvider.php', $str);
        foreach($this->dirs as $dir)
        {
            $dir->saveFiles();
        }
    }

}
