<?php
namespace Neutron\ComponentBundle\Util\Filter;

class SlugFilter
{
    public function filter($value, $separator = '-')
    {
        if ($this->isUnicodeEnabled()){
            $pattern = '/[^\p{L}\p{N}\s]/u';
        } else {
            $pattern = '/[^a-zA-Z0-9/s]/u';
        }
        
        // allowing only alnum
        $value = preg_replace($pattern, ' ', (string) $value);
        
        $chars = '\\s`~!@#$%\^&*()\\-=_+\\[\\]{};\':",.\\/\<\>?|\\\\„”';
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace("/[{$chars}]+/u", $separator, $value);
        $value = preg_replace("/[-]$/u", '', $value);
        return $value;
    }
    
    protected function isUnicodeEnabled()
    {
        return (@preg_match('/\pL/u', 'a')) ? true : false;
    }
}
