<?php
namespace Neutron\ComponentBundle\Model;

use Doctrine\Common\Persistence\ObjectManager;

interface ManagerInterface
{
    public function setClassName($className);
    
    public function getClassName();
    
    public function setObjectManager(ObjectManager $om);
    
    public function getObjectManager();
    
    public function create($andFlush = false);
    
    public function update($entity, $andFlush = false);
    
    public function delete($entity, $andFlush = false);
    
    public function findOneBy(array $criteria);
    
    public function findBy(array $criteria, array $order = null, $offset = null, $limit = null);
    
    public function getRepository();
}