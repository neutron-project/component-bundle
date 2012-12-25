<?php
namespace Neutron\ComponentBundle\Form\Helper;

use Symfony\Component\Form\Form;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;


class FormHelper
{
    protected $translator;
    
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }
    
    
    public function getErrorMessages(Form $form, $translationDomain = 'messages')
    {
        
        $errors = array();
    
        foreach ($form->getErrors() as $key => $error) {
            $errors[] = $this->translator->trans(/** @Ignore */$error->getMessageTemplate(), $error->getMessageParameters(), $translationDomain);
        }
    
        if (count($form)) {
            foreach ($form->getChildren() as $child) {
                if (!$child->isValid()) {
                    $errors[$child->createView()->vars['id']] = $this->getErrorMessages($child, $translationDomain);
                }
            }
        }
        
        return $errors;
    }
}