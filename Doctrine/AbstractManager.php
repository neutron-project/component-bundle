<?php
namespace Neutron\ComponentBundle\Doctrine;

use Neutron\ComponentBundle\Model\ManagerInterface;

use Doctrine\Common\Persistence\ObjectManager;

abstract class AbstractManager implements ManagerInterface
{
    protected $om;
    
    protected $className;
    
    protected $repository;
    
    protected $meta;
    
    public function setClassName($className)
    {
        $this->className = (string) $className;
        return $this;
    }
    
    public function getClassName()
    {   
        return $this->className;
    }
    
    public function setObjectManager(ObjectManager $om)
    {
        $this->om = $om;
        $this->repository = $this->om->getRepository($this->className);
        $this->meta = $this->om->getClassMetadata($this->className);
        return $this;
    }
    
    public function getObjectManager()
    {
        return $this->om;
    }
    
    public function create($andFlush = false)
    {
        $class = $this->className;
        $entity = new $class();
        
        $this->validateEntity($entity);
        if ($andFlush){
            $this->om->persist($entity);
            $this->om->flush();
        }
    
        return $entity;
    }
    
    public function update($entity, $andFlush = false)
    {
        $this->validateEntity($entity);
        $this->om->persist($entity);
        if ($andFlush){
            $this->om->flush();
        }
    }
    
    public function delete($entity, $andFlush = false)
    {
        $this->validateEntity($entity);
        $this->om->remove($entity);
    
        if ($andFlush){
            $this->om->flush();
        }  
    }
    
    public function findOneBy(array $criteria)
    {
        return $this->repository->findOneBy($criteria);
    }
    
    public function findBy(array $criteria, array $order = null, $offset = null, $limit = null)
    {
        return $this->repository->findBy($criteria, $order, $offset, $limit);
    }
    
    public function getRepository()
    {
        return $this->repository;
    } 
    
    protected function validateEntity($entity)
    {
        $class = get_class($entity);
        
        if ($class !== $this->className){
            throw new \InvalidArgumentException(
                sprintf('Entity "%s" must be instance of "%s"', $class, $this->className)
            );
        }
    }
}