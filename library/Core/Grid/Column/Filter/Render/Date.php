<?php

namespace Core\Grid\Column\Filter\Render;

use Core\Grid\Column\Filter\Render;

/**
 * Description of Text
 *
 * @author Administrator
 */
class Date extends Render\ARender implements Render\IRender {

    /**
     *
     * @return type 
     */
    public function getChilds()
    {
        return array('from' => 'De', 'to' => 'Para');
    }
    
    /**
     * Retuns current conditions
     *
     * @return array
     */
    public function getConditions ()
    {
        return array('from' => '>=', 'to' => '<=');
    }

    /**
     *
     * @return string
     */
    public function render()
    {
        $attributes = $this->getAttributes();

        //atributos customizados
        $attributes['alt'] = 'date';
        $attributes['size'] = isset($attributes['size']) ? $attributes['size'] : 10;
        $attributes['class'] .= ' date';


        //modo range
        if ($this->getRange() == true) {
            
            $field = '';
            $belongTo = $attributes['belongsTo'];
                    
            //loop for childs
            foreach ($this->getChilds() as $key => $child) {
                $attributes['belongsTo'] = "{$belongTo}[{$key}]";
                
                // checo valor padrao
                $attributes['value'] = isset($attributes["value[{$key}]"]) ?  $attributes["value[{$key}]"] : null;

                $field .= "
                <span>{$child}: </span>
                " . new \Zend_Form_Element_Text($this->getFieldIndex(),
                                $attributes
                );
            }
        } else {
            // campo unico
            $field = new \Zend_Form_Element_Text($this->getFieldIndex(),
                            $attributes
            );
        }

        return $field;
    }

}