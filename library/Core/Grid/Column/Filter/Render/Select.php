<?php

namespace Core\Grid\Column\Filter\Render;

use Core\Grid\Column\Filter\Render;

/**
 * Description of Text
 *
 * @author Administrator
 */
class Select extends Render\ARender implements Render\IRender {

    /**
     *
     * @return string
     */
    public function render()
    {
        //adiciono primeira opcao como tudo
        $attributes = $this->getAttributes();
        $attributes['multiOptions'] = array('firstOpt' => ' -- Tudo -- ', 'options' => $attributes['multiOptions']);
        
        return new \Core_Form_Element_Select($this->getFieldIndex(), 
                $attributes
        );
    }
    
    public function getConditions() {
        return array();
    }

}