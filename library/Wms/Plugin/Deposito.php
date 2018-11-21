<?php

namespace Wms\Plugin;

use \Core\Controller\PluginAbstract;

class Deposito extends PluginAbstract
{

    /**
     * @var array nome dos módulos em que o plugin será executado
     */
    protected $useInModules = array('web', 'expedicao','mobile');

    /**
     * @var array nome dos controllers que este plugin não será executado
     */
    protected $notUseInControllers = array('error', 'auth');
    public $rotasProibidas = array(
        array(
            'controller' => 'usuario',
            'action' => 'mudar-senha-provisoria',
            'module' => 'web',
        ),
        array(
            'controller' => 'auth',
            'action' => 'login',
            'module' => 'web',
        ),
        array(
            'controller' => 'auth',
            'action' => 'logout',
            'module' => 'web',
        ),
        array(
            'controller' => 'auth',
            'action' => 'login',
            'module' => 'mobile',
        ),
        array(
            'controller' => 'auth',
            'action' => 'logout',
            'module' => 'mobile',
        )
    );

    /**
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @return type 
     */
    public function preDispatch(\Zend_Controller_Request_Abstract $request)
    {
        if (!$this->verificaRotas($request))
            return;

        //get view
        $viewRenderer = \Zend_Controller_Action_HelperBroker::getExistingHelper('ViewRenderer');
        $view = $viewRenderer->view;
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $auth = \Zend_Auth::getInstance();
        $sessao = new \Zend_Session_Namespace('deposito');

        if (isset($sessao->depositosPermitidos) && isset($sessao->idDepositoLogado)) {
            $view->idDepositoLogado = $sessao->idDepositoLogado;
            $view->depositosPermitidos = $sessao->depositosPermitidos;
            $view->centraisPermitidas = $sessao->centraisPermitidas;
            return;
        }

        $usuarioSessao = $auth->getIdentity();
        $usuario = $em->find('wms:Usuario', $usuarioSessao->getId());
        $depositosPermitidos = $usuario->getDepositos()->toArray();

        foreach ($depositosPermitidos as $key => $deposito) {
            if ((!$deposito->getFilial()->getIsAtivo()) || (!$deposito->getIsAtivo()))
                continue;

            $arrayDepositos[$deposito->getId()] = $deposito->getDescricao();
            $centrais[] = $deposito->getFilial()->getCodExterno();
        };

        switch (count($arrayDepositos)) {
            case 0:
                $request->setControllerName('error');
                $request->setActionName('sem-permissao-depositos');
                return;
            break;
            default:
                $sessao->idDepositoLogado = key($arrayDepositos);
            break;
        }

        $view->idDepositoLogado = $sessao->idDepositoLogado;
        $view->depositosPermitidos = $arrayDepositos;
        $sessao->depositosPermitidos = $arrayDepositos;
        $view->centraisPermitidas = $centrais;
        $sessao->centraisPermitidas = $centrais;

        //verifica se já tem algum depósito selecionado
        if ($sessao->idDepositoLogado == null && $request->getModuleName() != 'mobile') {
            $request->setControllerName('error');
            $request->setActionName('sem-deposito-logado');
            $sessao->codFilialExterno = '';
        } else {
            /** @var \Wms\Domain\Entity\Deposito $depositoLogado */
            $depositoLogado = $em->find('wms:Deposito', $sessao->idDepositoLogado);
            $sessao->codFilialExterno = $depositoLogado->getFilial()->getCodExterno();
        }
    }

}