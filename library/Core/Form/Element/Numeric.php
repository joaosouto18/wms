<?php

class Core_Form_Element_Numeric extends \Core\Form\Element\Xhtml
{

    public function init()
    {
	$this->setAttribs(array(
	    'class' => 'number ' . $this->getAttrib('class'),
            'alt' => 'numero',
	));
    }

}