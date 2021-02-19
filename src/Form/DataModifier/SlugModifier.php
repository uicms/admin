<?php
namespace Uicms\Admin\Form\DataModifier;

use Symfony\Component\String\Slugger\AsciiSlugger;

class SlugModifier
{
    function __construct($field_config, $table_config, $data) {
        $this->field_config = $field_config;
        $this->table_config = $table_config;
        $this->data = $data;
    }
    
    public function modify($value)
    {
        $slugger = new AsciiSlugger();
        
        # Set slug only if value is empty or equal to previous slug
        if(!trim($value) || ($value == strtolower($slugger->slug($this->data->_name)))) {
            $get_method = 'get' . str_replace('_', '', ucwords($this->table_config['name_field'], '_'));
            $slug = strtolower($slugger->slug($this->data->$get_method()));
            return $slug;
        } else {
            return $value;
        }
    }
}