<?php
namespace Uicms\Admin\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Uicms\Admin\Form\Type\UITextType;
use Uicms\Admin\Form\Type\UIFileType;
use Uicms\Admin\Form\Type\UICollectionType;

class UITypeExtension extends AbstractTypeExtension
{
    function buildForm(FormBuilderInterface $builder, array $options)
    {
        
    }
    
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['options'] = $options;
    }
    
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'empty_data'=>'',
            'template'=>'default',
            'transformer'=>'',
            'type'=>'',
            'namespace'=>'',
            'name'=>'',
            'thumbnail_width'=>'',
            'thumbnail_height'=>'',
        ]);
    }
    

    public static function getExtendedTypes(): iterable
    {
        return [UITextType::class, UIFileType::class, UICollectionType::class];
    }
}