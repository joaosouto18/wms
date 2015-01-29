<?php

/**
 * Description of Messenger
 *
 * @link    www.moveissimonetti.com.br/wms
 * @since   1.0
 * @version $Revision$
 * @author Desenvolvimento
 */
class Core_Controller_Action_Helper_Messenger extends \Zend_Controller_Action_Helper_Abstract
{

    protected $_flashMessenger = null;

    /**
     *
     * @param string $name msg|error|info|success|warning
     * @param string $message
     * @return \Core_Controller_Action_Helper_Messenger 
     */
    public function messenger($name = 'error', $message = null)
    {
        if ($name == 'error' && $message === null)
            return $this;

        if (!isset($this->_flashMessenger[$name])) {
            $this->_flashMessenger[$name] = $this->getActionController()
                    ->getHelper('FlashMessenger')
                    ->setNamespace($name . '_message');
        }

        if ($message !== null)
            $this->_flashMessenger[$name]->addMessage($message);

        return $this->_flashMessenger[$name];
    }

    public function direct($name = 'error', $message = null)
    {
        return $this->messenger($name, $message);
    }

}
