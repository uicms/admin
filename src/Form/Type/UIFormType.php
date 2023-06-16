<?php
namespace Uicms\Admin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use A2lix\TranslationFormBundle\Form\Type\TranslationsType;
use Uicms\Admin\Form\DataTransformer\DefaultTransformer;
use Uicms\Admin\Form\DataTransformer\StringToFileTransformer;
use Uicms\Admin\Form\DataTransformer\TranslationsTransformer;
use Uicms\Admin\Form\DataModifier\SlugModifier;

class UIFormType extends AbstractType
{
    private $model = null;
    
    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $ui_config = $options['ui_config'];
        $form_config = $options['form_config'];
        $data = $options['data'];
        $fields = $form_config['fields'];
        if(isset($options['translator'])) {
            $translator = $options['translator'];
        }
        if(isset($options['model'])) {
            $this->model = $options['model'];
        }
        
        # Translations
        if(isset($form_config['translations']) && $form_config['translations']) {
            $translations_fields = $form_config['translations'];
            $displayed_fields = [];
            $excluded_fields = [];
            
            foreach($translations_fields as $field_name=>$field_config) {
                if(isset($field_config['excluded']) && $field_config['excluded']) {
                    $excluded_fields[] = $field_name;
                } else {
                    $translations_fields[$field_name]['options']['field_type'] = $field_config['namespace'] . '\\' . $field_config['type'];
                    $displayed_fields[$field_name] = $translations_fields[$field_name]['options'];

                    # Translate label
                    if(isset($translator) && isset($displayed_fields[$field_name]['label']) && $displayed_fields[$field_name]['label']) {
                        $displayed_fields[$field_name]['label'] = $translator->trans($displayed_fields[$field_name]['label'], [], 'admin');
                    }

                    # Translate help
                    if(isset($translator) && isset($displayed_fields[$field_name]['help']) && $displayed_fields[$field_name]['help']) {
                        $displayed_fields[$field_name]['help'] = $translator->trans($displayed_fields[$field_name]['help'], [], 'admin');
                    }
                }
            }
            
            $builder->add('translations', TranslationsType::class, array('excluded_fields'=>$excluded_fields, 'fields'=>$displayed_fields));
            $builder->get('translations')->addModelTransformer(new TranslationsTransformer($form_config, $ui_config));
        }
        
        # Fields
        foreach($form_config['fields'] as $field_config) {
            if(!isset($field_config['excluded']) || !$field_config['excluded']) {
                # Entity Type : order by main field asc
                if($field_config['type'] == 'EntityType') {
                    $field_config['options']['query_builder'] = function($model) {
                        $query = $model->createQueryBuilder('t');
                        $name_field = $model->getConfig('name_field');
                        if($model->isTranslatable() && $model->isFieldTranslatable($name_field)) {
                            $query->join('t.translations', 'i');
                            $query->where("i.locale = '" . $this->params->get('locale') . "'");
                            $query->orderBy("i.$name_field", 'ASC');
                        } else {
                            $query->orderBy("t.$name_field", 'ASC');
                        }
                        return $query;
                    };
                }
            
                # Translate label
                if(isset($translator) && isset($field_config['options']['label']) && $field_config['options']['label']) {
                    $field_config['options']['label'] = $translator->trans($field_config['options']['label'], [], 'admin');
                }

                # Translate help
                if(isset($translator) && isset($field_config['options']['help']) && $field_config['options']['help']) {
                    $field_config['options']['help'] = $translator->trans($field_config['options']['help'], [], 'admin');
                }

                # Translate choices
                if($field_config['type'] == 'ChoiceType' && isset($translator)) {
                    foreach($field_config['options']['choices'] as $label=>$key) {
                        unset($field_config['options']['choices'][$label]);
                        $field_config['options']['choices'][$translator->trans($label, [], 'admin')] = $key;
                    }
                }
            
                # Add field to form
                $builder->add($field_config['name'], $field_config['namespace'] . '\\' . $field_config['type'], $field_config['options']);
            
                # Add transformer to field
                if(isset($field_config['transformer']) && $field_config['transformer']) {
                    eval("\$transformer = new " . $field_config['transformer'] . "(\$field_config, \$ui_config);");
                    $builder->get($field_config['name'])->addModelTransformer($transformer);
                }
            }
        }
         
        # Pre submit event
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) {
            $entity_name = $event->getForm()->getConfig()->getDataClass();
            $options = $event->getForm()->getConfig()->getOptions();
            $form_config = $options['form_config'];
            
            $current_data = $event->getForm()->getNormData();
            $new_data = $event->getData();
            
            /* Hook on pre-submit */
            if(isset($form_config['on_pre_submit'])) {
                $service = new $form_config['on_pre_submit']($this->params, $this->model);
                $service->execute($current_data, $new_data);
                $event->setData($service->getData());
            }
        });
        
        # Submit event
        $builder->addEventListener(FormEvents::SUBMIT, function(FormEvent $event) {
            $entity_name = $event->getForm()->getConfig()->getDataClass();
            $options = $event->getForm()->getConfig()->getOptions();
            $parent = $options['parent'];
            $form_config = $options['form_config'];

            /* Treat each fields before sending data */
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
            
            /* Hook on submit */
            if(isset($form_config['on_submit'])) {
                $current_data = $event->getForm()->getNormData();
                $new_data = $event->getData();
                
                $service = new $form_config['on_submit']($this->params);
                $service->execute($current_data, $new_data);
                $data = $service->getData();
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
            'translator'=>null,
            'model'=>null,
        ]);
    }
}