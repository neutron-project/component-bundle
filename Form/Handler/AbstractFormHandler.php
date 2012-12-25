<?php
namespace Neutron\ComponentBundle\Form\Handler;

use Symfony\Component\DependencyInjection\ContainerAware;

use Symfony\Component\Form\FormInterface;

use Neutron\ComponentBundle\Form\Handler\FormHandlerInterface;

abstract class AbstractFormHandler extends ContainerAware
{
    protected $form;
    
    protected $translationDomain = 'messages';
    
    protected $result;
    
    public function setForm(FormInterface $form)
    {
        $this->form = $form;
        return $this;
    }
    
    public function setTranslationDomain($translationDomain)
    {
        $this->translationDomain = (string) $translationDomain;
        return $this;
    }

    public function process()
    {

        if ($this->container->get('request')->isXmlHttpRequest()) { 
            
            $this->form->bind($this->container->get('request'));
 
            if ($this->form->isValid()) {
                
                $this->onSuccess();
                
                $result['success'] = true;
                
                if($this->getRedirectUrl()){
                    $result['redirect_uri'] = $this->getRedirectUrl();
                } else {
                    $result['successMsg'] = $this->getSuccessMessage();
                }
                
                $this->result = $result;
                
                return true;
  
            } else {
                $this->result = array(
                    'success' => false,
                    'errors' => $this->container->get('neutron_component.form.helper.form_helper')
                        ->getErrorMessages($this->form, $this->translationDomain)
                );
                
                return false;
            }
  
        }
    }
    
    public function getResult()
    {
        return $this->result;
    }
    
    protected function getSuccessMessage()
    {
        return $this->container->get('translator')
            ->trans('form.success', array(), $this->translationDomain);
    }
    
    protected function getRedirectUrl()
    {}
    
    abstract protected function onSuccess();
}
