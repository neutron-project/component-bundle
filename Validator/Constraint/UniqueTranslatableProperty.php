<?php
namespace Neutron\ComponentBundle\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueTranslatableProperty extends Constraint
{
    public $message = 'Value "%string%" already exist';
    
    public $property;
    
    public $enableSlugFilter = false;
    
    public function validatedBy()
    {
        return 'unique_translatable_property_validator';
    }
    
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}