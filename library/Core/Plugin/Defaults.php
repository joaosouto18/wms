<?php

namespace Core\Plugin;

class Defaults extends \Zend_Controller_Plugin_Abstract
{

    public function postDispatch(\Zend_Controller_Request_Abstract $request)
    {
        //get view
        $viewRenderer = \Zend_Controller_Action_HelperBroker::getExistingHelper('ViewRenderer');
        $view = $viewRenderer->view;
        $em = $this->getEntityManager();
        $auth = \Zend_Auth::getInstance();
        $nomeRecurso = $request->getControllerName();
        $nomeAcao = $request->getActionName();

        if ($request->isXMLHttpRequest()) {
            $layout = \Zend_Layout::getMvcInstance();
            $layout->disableLayout();
            $viewRenderer->setNoRender(true);
        } else {
            if ($auth->hasIdentity()) {

                $usuarioSessao = $auth->getIdentity();
                $usuario = $em->getRepository('wms:Usuario')
                        ->find($usuarioSessao->getId());

                $view->nomeUsuario = $usuario->getPessoa()->getNome();

                $view->mostrarIconeAjuda = $this->mostrarIconeAjuda(
                        $nomeRecurso, $nomeAcao
                );

                if ($view->mostrarIconeAjuda) {
                    $repo = $em->getRepository('wms:Ajuda');

                    $ajuda = $repo->findOneByNomeRecursoAndAcao(
                            $nomeRecurso, $nomeAcao
                    );

                    $view->textoAjuda = 'Texto de ajuda não definido';

                    if ($ajuda != null) {
                        $view->textoAjuda = $ajuda->getDscConteudo();
                    }
                }
            }

            $view->request = $request;
        }


        // Dispositivos mobile
        $bootstrap = \Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $userAgent = $bootstrap->getResource('useragent');
        
        $device = $userAgent->getDevice();
        
        if ($device->getType() == 'mobile') {
            $layout = \Zend_Layout::getMvcInstance();

            $layoutFile = (!\Zend_Auth::getInstance()->hasIdentity()) ? 'login' : 'layout';

            $layout->setLayout($layoutFile)
                    ->setLayoutPath(APPLICATION_PATH . "/modules/mobile/views/layout/");
        }
    }

    /**
     * Verifica se há algum vinculo entre recurso x acao cadastrado para definir
     * se irá mostrar o ícone de ajuda ou não
     * @param string $nomeRecurso
     * @param string $nomeAcao
     * @return boolean 
     */
    public function mostrarIconeAjuda($nomeRecurso, $nomeAcao)
    {
        $em = $this->getEntityManager();
        $recursoRepository = $em->getRepository('wms:Sistema\Recurso');
        return $recursoRepository->getVinculoByNomeAndAcao(
                        $nomeRecurso, $nomeAcao
                ) != null;
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return \Zend_Registry::get('doctrine')->getEntityManager();
    }

}