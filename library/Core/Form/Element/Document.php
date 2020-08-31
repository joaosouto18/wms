<?php

class Core_Form_Element_Document extends \Core\Form\Element\Xhtml
{

    public function init()
    {
	$this->setAttribs(array(
	    'size' => 18,
	    'maxLength' => 18,
	    'alt' => 'number',
	    'class' => 'document ' . $this->getAttrib('class')
	));
    }

}