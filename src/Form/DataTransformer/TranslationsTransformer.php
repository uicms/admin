<?php
namespace Uicms\Admin\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\HttpFoundation\File\File;

use Uicms\Admin\Form\DataTransformer\DefaultTransformer;
use Uicms\Admin\Form\DataTransformer\StringToFileTransformer;
use Uicms\Admin\Form\DataTransformer\TranslationsTransformer;

class TranslationsTransformer implements DataTransformerInterface
{
    public function __construct($form_config, $ui_config)
    {
        $this->form_config = $form_config;
        $this->ui_config = $ui_config;
    }
    
    public function transform($translations)
    {
        if(null !== $translations) {
            $iterator = $translations->getIterator();
            foreach($iterator as $entity) {
                foreach($this->form_config['translations'] as $field_config) {
                    if(isset($field_config['transformer']) && $field_config['transformer']) {
                        eval("\$transformer = new " . $field_config['transformer'] . "(\$field_config, \$this->ui_config);");
                        eval("\$entity->set" . $field_config['name'] . "(\$transformer->transform(\$entity->get" . $field_config['name'] . "()));");
                    }
                }
            }
        }
        
        return $translations;
    }

    public function reverseTransform($translations)
    {
        if(null !== $translations) {
            $iterator = $translations->getIterator();
            foreach($iterator as $entity) {
                foreach($this->form_config['translations'] as $field_config) {
                    if(isset($field_config['transformer']) && $field_config['transformer']) {
                        eval("\$transformer = new " . $field_config['transformer'] . "(\$field_config, \$this->ui_config);");
                        eval("\$entity->set" . $field_config['name'] . "(\$transformer->reverseTransform(\$entity->get" . $field_config['name'] . "()));");
                    }
                }
            }
        }
        return $translations;
    }
}