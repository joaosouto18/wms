<?php

namespace Wms\Module\Web;

class Form extends \Core\Form
{
    
    public $elementDecorators = array(
	'ViewHelper',
	'Errors',
	array('Description', array('tag' => 'p', 'class' => 'description')),
	array('HtmlTag', array('tag' => 'dd')),
	array(array('labelDtClose' => 'HtmlTag'),
	    array('tag' => 'dt', 'closeOnly' => true, 'placement' => 'prepend')),
	'Label',
	array(array('labelDtOpen' => 'HtmlTag'),
	    array('tag' => 'dt', 'openOnly' => true, 'placement' => 'prepend', 'class' => 'labelPequena'))
    );

    public function __construct($options = null)
    {
	$this->setAttrib('id', 'mainForm');
	parent::__construct($options);
    }

    /**
     * Adds a tab to the form with a subform as content
     * @param string $title tab title
     * @param \Core\Form\SubForm $subForm subform to be placed on the tab content
     * @param type $name subform name
     */
    public function addSubFormTab($title, \Core\Form\SubForm $subForm, $name, $customScript = null)
    {
	if ($this->getDecorator('TabContainer') == null) {
	    $this->setDecorators(array(
		'FormElements',
		array('TabContainer', array(
			'class' => 'tabContainer',
		)),
		'Form',
	    ));
	}
	
	$decorators[] = 'PrepareElements';
	
	if(null !== $customScript) {
	    $decorators[] = array('viewScript', array('viewScript' => $customScript));
	} elseif ($subForm->getDecorator('ViewScript')) {
	    $customScript = $subForm->getDecorator('ViewScript')->getOption('viewScript');
	    $decorators[] = array('viewScript', array('viewScript' => $customScript));
	} else {
	    $decorators[] = 'FormElements';
	}
	
	$decorators[] = array('HtmlTag', array('tag' => 'div'));
	$decorators[] = array('TabPane', array('jQueryParams' => array(
	    'containerId' => $this->getId(),
	    'title' => $title
	)));
        
	$subForm->setDecorators($decorators);
        
        $subForm->setDisplayGroupDecorators(array(
            'FormElements',
            'Fieldset',
            'FormErrors',
            new \Zend_Form_Decorator_HtmlTag(array('tag' => 'div')),
        ));
        
	$this->addSubForm($subForm, $name);
    }

    /**
     * Return the front controller request object.
     *
     * @return null|Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
	return \Zend_Controller_Front::getInstance()->getRequest();
    }

    public function addFieldSet($legend)
    {
	return $this;
    }

    public function addTab($options)
    {
	if ($this->getDecorator('TabContainer') == null) {
	    $this->setDecorators(array(
		'FormElements',
		array('TabContainer', array(
			'class' => 'tabContainer',
		)),
		'Form',
	    ));
	}

	$title = $options['title'];
	$elements = $options['elements'];

	$content = '';
	foreach ($elements as $element) {

	    if (null != $this->getElement($element))
		$obj = $this->getElement($element);
	    elseif (null != $this->getDisplayGroup($element))
		$obj = $this->getDisplayGroup($element);
	    elseif (null != $this->getSubForm($element))
		$obj = $this->getSubForm($element);
	    else
		throw new \Exception('invalid element');

	    $content .= $obj;
	}


	$this->addDecorators(array(
	    'FormElements',
	    array('HtmlTag', array('tag' => 'dl')),
	    array('TabPane', array('jQueryParams' => array(
			'containerId' => $this->getId(),
			'title' => $title,
			'content' => $content,
		))
	    )
	));
    }

}