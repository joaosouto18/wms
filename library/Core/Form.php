<?php

namespace Core;

/**
 * Form
 *
 * @author Administrator
 */
class Form extends \ZendX_JQuery_Form
{
    /**
     * @var array
     */
    protected $session;
    
    /**
     * @param type $options 
     */
    public static $nameParamsSession = 'formParams';

    public function __construct($options = null)
    {
        $this->addPrefixPath('Core_Form', 'Core/Form/');
        $this->addElementPrefixPath('Core_Validate', 'Core/Validate/', 'validate');

        $this->setAttrib('accept-charset', 'UTF-8');

        $this->setDecorators(array('FormElements', 'Form'));
        //set session of the form
        $this->session = new \Zend_Session_Namespace(get_class($this));
        $this->session->setExpirationSeconds(90);

        //setando os decorators
        $this->setElementDecorators(array(
            'ViewHelper',
            'Errors',
            array('Label', array('class' => 'field')),
            new \Zend_Form_Decorator_HtmlTag(array('tag' => 'div', 'class' => 'field')),
        ));
        
        parent::__construct($options);
        
        $this->setDisplayGroupDecorators(array(
            'FormElements',
            'Fieldset',
            'FormErrors',
            new \Zend_Form_Decorator_HtmlTag(array('tag' => 'div')),
        ));
    }

    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('FormElements');
            $this->removeDecorator('DtDdWrapper');
        }
    }

    /**
     * Gets the entity manager
     * 
     * @param string $name
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEm($name = null)
    {
        return \Zend_Registry::get('doctrine')->getEntityManager($name);
    }

    /**
     *
     * @param array $session
     * @return \Core\Form 
     */
    public function setSession(array $session)
    {
        $this->session->params = $session;
        return $this;
    }
    
    /**
     *
     * @return array 
     */
    public function getParams()
    {
        $params = array_merge($_POST, $_GET);

        if (is_array($this->session->params))
            $params = array_merge($this->session->params, $params);

        if (empty($params))
            return false;

        if (!$this->isValid($params)) {

            if (count($this->getMessages()) > 0) {
                foreach ($this->getMessages() as $message) {
                    $this->messenger('info', $message);
                }
            }

            return false;
        }

        return $params;
    }
    
    /**
     * Check if all fields from the form are empty
     * @param String $groupName Name of the group which the fiedls belong to
     * @return boolean 
     */
    protected function checkAllEmpty($groupName = false) {
        
        $formValues = $this->getValues();
        
         if($groupName)
            $formValues = $formValues[$groupName];
         
        //checking if all fields are empty
        $allEmpty = true;

        foreach ($formValues as $value) {
            if (!empty($value))
                $allEmpty = false;
        }

        if ($allEmpty)
            $this->addError('Favor preencher algum item para efetuar a pesquisa');
        
        return $allEmpty;
    }
    

    /**
     *
     * @param string $name msg|error|info|success|warning
     * @param string $message
     * @return \Core\Form 
     */
    public function messenger($name = 'error', $message = null)
    {
        $helper = \Zend_Controller_Action_HelperBroker::getExistingHelper('FlashMessenger');

        $helper->setNamespace($name . '_message')
                ->addMessage($message);

        return $this;
    }

}
