<?php

/**
 * 
 */
class Core_Form_Element_Checkbox extends Zend_Form_Element_Checkbox
{

    public function init()
    {
	$this->setDecorators(array(
	    'ViewHelper',
	    'Label',
	    array('HtmlTag', array('tag' => '<div>', 'class' => 'checkbox'))
	));
    }

}
