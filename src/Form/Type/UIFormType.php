<?php
namespace Uicms\Admin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Uicms\Admin\Form\DataTransformer\DefaultTransformer;
use Uicms\Admin\Form\DataTransformer\StringToFileTransformer;
use Uicms\Admin\Form\DataTransformer\TranslationsTransformer;
use Uicms\Admin\Form\DataModifier\SlugModifier;

class UIFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $ui_config = $options['ui_config'];
        $form_config = $options['form_config'];
        $data = $options['data'];
        $fields = $form_config['fields'];
        
        # Translations
        if(isset($form_config['translations']) && $form_config['translations']) {
            $translations_fields = $form_config['translations'];
            $options = array();
            foreach($translations_fields as $field_name=>$field_config) {
                $translations_fields[$field_name]['options']['field_type'] = $field_config['namespace'] . '\\' . $field_config['type'];
                $options[$field_name] = $translations_fields[$field_name]['options'];
            }
            
            $builder->add('translations', TranslationsType::class, array('fields'=>$options));
            $builder->get('translations')->addModelTransformer(new TranslationsTransformer($form_config, $ui_config));
        }
        
        # Fields
        foreach($form_config['fields'] as $field_config) {
            
            # Entity Type : order by main field asc
            if($field_config['type'] == 'EntityType') {
                $field_config['options']['query_builder'] = function($model) {
                    $query = $model->createQueryBuilder('t');
                    $name_field = $model->getConfig('name_field');
                    if($model->isTranslatable()) {
                        $query->join('t.translations', 'i');
                        $query->where("i.locale = 'fr'");
                        $query->orderBy("i.$name_field", 'ASC');
                    } else {
                        $query->orderBy("t.$name_field", 'ASC');
                    }
                    return $query;
                };
            }

            # Add field to form
            $builder->add($field_config['name'], $field_config['namespace'] . '\\' . $field_config['type'], $field_config['options']);
            
            # Add transformer to field
            if(isset($field_config['transformer']) && $field_config['transformer']) {
                eval("\$transformer = new " . $field_config['transformer'] . "(\$field_config, \$ui_config);");
                $builder->get($field_config['name'])->addModelTransformer($transformer);
            }
        }
         
        # Pre-submit event
        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) {
            $entity_name = $event->getForm()->getConfig()->getDataClass();
            $options = $event->getForm()->getConfig()->getOptions();
            $parent = $options['parent'];
            $form_config = $options['form_config'];
            $fields = $form_config['fields'];
            if(isset($form_config['translations']) && !empty($form_config['translations'])) {
                $fields = array_merge($fields, $form_config['translations']);
            }
            $ui_config = $options['ui_config'];
            foreach($ui_config['entity'] as $entity_config) {
                if($entity_config['name'] == $entity_name) break;
            }
            $data = $event->getData();
            
            # Parent
            $data->setParent($parent);
            
            # Modifiers
            foreach($fields as $field_name => $field_config) {
                if(isset($field_config['modifier']) && $field_config['modifier']) {
                    eval("\$modifier = new " . $field_config['modifier'] . "(\$field_config, \$entity_config, \$data, \$parent);");
                    $set_method = 'set' . str_replace('_', '', ucwords($field_name, '_'));
                    $get_method = 'get' . str_replace('_', '', ucwords($field_name, '_'));
                    $data->$set_method($modifier->modify($data->$get_method()));
                }
            }
            
            $event->setData($data);
        });
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'ui_config' => array(),
            'form_config' => array(),
            'parent' => null,
        ]);
    }
}