<?php

namespace Core\Grid\Column\Filter\Render;

use Core\Grid\Column\Filter\Render;

/**
 * Description of Text
 *
 * @author Administrator
 */
class Text extends Render\ARender implements Render\IRender {
    
    /**
     *
     * @return string
     */
    public function render()
    {        
        $attributes = $this->getAttributes();
//var_dump($this->getCondition());exit;
        //atributos customizados
        $attributes['size'] = isset($attributes['size']) ? $attributes['size'] : false;
        
        return new \Zend_Form_Element_Text($this->getFieldIndex(), 
                $attributes
                );
    }

}