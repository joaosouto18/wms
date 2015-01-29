<?php
class Core_Form_Element_Cep extends \Core\Form\Element\Xhtml
{
  public function init()
  {
	$this->setAttribs(array(
		'size'      => 9,
		'maxLength' => 9,
		'alt'       => 'cep',
		'class'     => 'cep ' . $this->getAttrib('class')
	));
  }
}