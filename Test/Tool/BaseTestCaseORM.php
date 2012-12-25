<?php
namespace Neutron\ComponentBundle\Test\Tool;

use Doctrine\ORM\Mapping\DefaultNamingStrategy;

use Doctrine\ORM\Mapping\DefaultQuoteStrategy;

use Doctrine\Common\EventManager;

use Doctrine\ORM\Tools\SchemaTool;

use Doctrine\ORM\EntityManager;

use Doctrine\Common\Annotations\AnnotationReader;

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

/**
 * BaseTestCaseORM with mocked ORM EntityManager
 * Modified by Nikolay Georgiev
 *
 * @author Gediminas Morkevicius <gediminas.morkevicius@gmail.com>
 * @link http://www.gediminasm.org
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
abstract class BaseTestCaseORM extends \PHPUnit_Framework_TestCase
{
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;
    
    /**
     * (non-PHPdoc)
     * @see PHPUnit_Framework_TestCase::setUp()
     */
    protected function setUp()
    {
        parent::setUp();
    }
    
    /**
     * EntityManager mock object together with
     * annotation mapping driver and pdo_sqlite
     * database in memory
     *
     * @param EventManager $evm
     * @param array $conn
     * @return EntityManager
     */
    protected function createMockEntityManager(EventManager $evm = null, $conn = null)
    {
        if (!$conn){
            $conn = array(
                'driver' => 'pdo_sqlite',
                'memory' => true,
            );
        }
    
        $config = $this->getMockAnnotatedConfig();
        
        $em = EntityManager::create($conn, $config, $evm ?: $this->getEventManager());
        
        $schema = array_map(function($class) use ($em) {
            return $em->getClassMetadata($class);
        }, (array)$this->getUsedEntityFixtures());
        
        $schemaTool = new SchemaTool($em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema($schema);
        $this->em = $em;
        
        return $this;
    }
    
    /**
     * Build event manager
     *
     * @return EventManager
     */
    private function getEventManager()
    {
        $evm = new EventManager();
        return $evm;
    }
    
    /**
     * Get annotation mapping configuration
     *
     * @return Doctrine\ORM\Configuration
     */
    private function getMockAnnotatedConfig()
    {
        // We need to mock every method except the ones which
        // handle the filters
        $configurationClass = 'Doctrine\ORM\Configuration';
        $refl = new \ReflectionClass($configurationClass);
        $methods = $refl->getMethods();

        $mockMethods = array();

        foreach ($methods as $method) {
            if ($method->name !== 'addFilter' && $method->name !== 'getFilterClassName') {
                $mockMethods[] = $method->name;
            }
        }

        $config = $this->getMock($configurationClass, $mockMethods);
        
        $config
            ->expects($this->once())
            ->method('getProxyDir')
            ->will($this->returnValue(\sys_get_temp_dir()))
        ;
    
        $config
            ->expects($this->once())
            ->method('getProxyNamespace')
            ->will($this->returnValue('EntityProxy'))
        ;
    
        $config
            ->expects($this->once())
            ->method('getAutoGenerateProxyClasses')
            ->will($this->returnValue(true))
        ;
    
        $config
            ->expects($this->once())
            ->method('getClassMetadataFactoryName')
            ->will($this->returnValue('Doctrine\\ORM\\Mapping\\ClassMetadataFactory'))
        ;
    
        $config
            ->expects($this->any())
            ->method('getMetadataDriverImpl')
            ->will($this->returnValue($this->getMetadataDriverImplementation()))
        ;
    
        $config
            ->expects($this->any())
            ->method('getDefaultRepositoryClassName')
            ->will($this->returnValue('Doctrine\\ORM\\EntityRepository'))
        ;
        
        $config
            ->expects($this->any())
            ->method('getQuoteStrategy')
            ->will($this->returnValue(new DefaultQuoteStrategy()))
        ;
        
        $config
            ->expects($this->any())
            ->method('getNamingStrategy')
            ->will($this->returnValue(new DefaultNamingStrategy()))
        ;
    
        return $config;
    }
    
    /**
     * Creates default mapping driver
     *
     * @return \Doctrine\ORM\Mapping\Driver\Driver
     */
    protected function getMetadataDriverImplementation()
    {
        $reader = new AnnotationReader();
        
        return new AnnotationDriver($reader);
    
    }
    
    /**
     * Get a list of used fixture classes
     *
     * @return array
     */
    abstract protected function getUsedEntityFixtures();

}