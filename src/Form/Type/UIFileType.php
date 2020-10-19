<?php
namespace Uicms\Admin\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

use Intervention\Image\ImageManagerStatic as Image;

class UIFileType extends AbstractType
{
    public function getParent()
    {
        return FileType::class;
    }
}