<?php
namespace Neutron\ComponentBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\Config\Resource\FileResource;

class ValidationPass implements CompilerPassInterface
{
    
    protected $dir;
    
    public function __construct($dir)
    {
        $this->dir = $dir;
    }
    
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {  
        if (!$container->hasParameter('validator.mapping.loader.xml_files_loader.mapping_files')) {
            return;
        }

        $files = $container->getParameter('validator.mapping.loader.xml_files_loader.mapping_files');
       
        foreach ($this->getValidationFiles() as $file){
            $files[] = $file;
            $container->addResource(new FileResource($file));
        }
        
        $container->setParameter('validator.mapping.loader.xml_files_loader.mapping_files', $files);
    }
    
    protected function getValidationFiles()
    {
        $iterator = new \DirectoryIterator($this->dir);
        $files = array();
        
        foreach ($iterator as $fileinfo){
            if ($fileinfo->isFile()) {
                $files[] = $fileinfo->getPathname();
            }
        }
        
        return $files;
    }
}
