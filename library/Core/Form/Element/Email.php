<?php
class Core_Form_Element_Email extends \Core\Form\Element\Xhtml
{
  public function init()
  {
	$this->setAttrib('class', 'email ' . $this->getAttrib('class'));
  }

}