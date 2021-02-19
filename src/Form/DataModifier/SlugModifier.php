<?php
namespace Uicms\Admin\Form\DataModifier;

use Symfony\Component\String\Slugger\AsciiSlugger;

class SlugModifier
{
    function __construct($field_config, $table_config, $data, $parent) {
        $this->field_config = $field_config;
        $this->table_config = $table_config;
        $this->data = $data;
        $this->parent = $parent;
    }
    
    public function modify($value)
    {
        $slugger = new AsciiSlugger();
        if($this->parent) {
            $current_slug = strtolower($slugger->slug($this->parent->_name . ' - ' . $this->data->_name));
        } else {
            $current_slug = strtolower($slugger->slug($this->data->_name));
        }
        
        # Set slug only if value is empty or equal to previous slug
        if(!trim($value) || ($value == $current_slug)) {
            $name_get_method = 'get' . str_replace('_', '', ucwords($this->table_config['name_field'], '_'));
            
            if($this->parent) {
                $source = $this->parent->_name . ' - ' . $this->data->$name_get_method();
            } else {
                $source = $this->data->$name_get_method();
            }
            $slug = strtolower($slugger->slug($source));
            return $slug;
        } else {
            return $value;
        }
    }
}