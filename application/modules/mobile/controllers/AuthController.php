<?php

use Wms\Module\Web\Form\Login as Form;

/**
 * Web_AuthController
 *
 * @author : Renato Medina [medinadato@gmail.com]
 */
class Mobile_AuthController extends \Wms\Controller\Action
{

    public function init()
    {
        parent::init();

        // add specific js/css
        $this->view->jQuery()
                ->addStyleSheet($this->view->baseUrl('css/admin/login.css'));
        // reset layout
        $layout = Zend_Layout::getMvcInstance();
        $layout->setLayout('login');
    }

    public function indexAction()
    {
        return $this->redirect('login');
    }

    public function loginAction()
    {
        $form = new Form;
        $this->view->form = $form;

        // data has been sent
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            // failed validation, redisplay form
            if ($form->isValid($data)) {
                
                $params = $this->getRequest()->getParams();

                try {
                    \Wms\Service\Auth::login($params['username'], $params['password']);
                    // redirect to protected controller
                    $this->redirect('index','index');
                } catch (Exception $e) {
                    // invalid data
                    $this->_helper->messenger('error', $e->getMessage());
                    $this->_helper->redirector('login');
                }
            } else {
                // form filled incorrectly
                $form->populate($data);
            }
        }
    }

    public function logoutAction()
    {
        \Wms\Service\Auth::logout();

        $this->_helper->messenger('success', 'Logout executado com sucesso!');
        $this->_helper->redirector('login', 'index', 'mobile');
    }

}