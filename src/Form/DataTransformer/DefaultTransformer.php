<?php
namespace Uicms\Admin\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class DefaultTransformer implements DataTransformerInterface
{
    function __construct($field_config, $ui_config) {
        $this->field_config = $field_config;
        $this->ui_config = $ui_config;
    }
    
    public function transform($value)
    {
        
        return $value;
    }

    public function reverseTransform($value)
    {

        return $value;
    }
}