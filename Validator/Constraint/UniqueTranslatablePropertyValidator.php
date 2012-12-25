<?php
namespace Neutron\ComponentBundle\Validator\Constraint;

use Doctrine\ORM\Query;

use Gedmo\Translatable\Mapping\Driver\Annotation;

use Doctrine\Common\Annotations\Reader;

use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Neutron\ComponentBundle\Util\Filter\SlugFilter;

use Symfony\Component\Validator\Constraint;

use Symfony\Component\Validator\ConstraintValidator;

class UniqueTranslatablePropertyValidator extends ConstraintValidator
{
    protected $registry;
    
    protected $slugFilter;
    
    protected $reader;
    
    public function __construct(ManagerRegistry $registry, SlugFilter $slugFilter, Reader $reader)
    {
        $this->registry = $registry;
        $this->slugFilter = $slugFilter;
        $this->reader = $reader;
    }
    
    public function validate($entity, Constraint $constraint)
    {
        $om = $this->registry->getManagerForClass(get_class($entity));
        $className = $this->context->getCurrentClass();
        $meta = $om->getClassMetadata($className);

        
        if (count($meta->getIdentifierFieldNames()) > 1){
            throw new ConstraintDefinitionException('Validator does not support compositive keys.');    
        }
        
        if (!$meta->hasField($constraint->property)) {
            throw new ConstraintDefinitionException("Only field names mapped by Doctrine can be validated for uniqueness.");
        }
        
        if ($meta->hasAssociation($constraint->property)) {
            throw new ConstraintDefinitionException("Association property can not be checked for uniqueness.");
        }
        
        $translationClass = 'Gedmo\Translatable\Entity\Translation';
        
        $annot = $this->reader->getClassAnnotation($meta->getReflectionClass(), Annotation::ENTITY_CLASS);
        
        if ($annot){
            $translationClass = $annot->class;
        }

        $id = $meta->getReflectionProperty($meta->getSingleIdentifierFieldName())->getValue($entity);

        $value = $meta->reflFields[$constraint->property]->getValue($entity);
        
        if ($constraint->enableSlugFilter){
            $value = $this->slugFilter->filter($value);
        }
        
        $repo = $om->getRepository($translationClass);
    
        $result = $this->getResult($repo, $className, $constraint->property, $value);
        
        if (0 === count($result) || (1 === count($result) && $id == $result[0]->getForeignKey())) {
            return;
        }
        
        $this->context->addViolationAtSubPath($constraint->property, $constraint->message, array('%string%' => $value), $value);
    }
    
    private function getResult($repo, $className, $field, $value)
    {
        $qb = $repo->createQueryBuilder('t');
        $qb->where("t.field = ?1 AND t.content = ?2 AND t.objectClass = ?3");
        $qb->setParameters(array(1 => $field, 2 => $value, 3 => $className));
        $query = $qb->getQuery();
  
        return $query->getResult();
    }
}