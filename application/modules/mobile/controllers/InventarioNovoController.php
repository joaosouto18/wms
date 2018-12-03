<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 29/11/2018
 * Time: 15:44
 */

use Wms\Controller\Action;

class Mobile_InventarioNovoController extends Action
{
    public function listagemInventariosAction()
    {
        /** @var \Wms\Domain\Entity\InventarioNovoRepository $inventarioRepo */
        $inventarioRepo = $this->em->getRepository('wms:InventarioNovo');
        $inventarios    = $inventarioRepo->findBy(['status' => \Wms\Domain\Entity\InventarioNovo::STATUS_LIBERADO]);
        $this->view->inventarios = $inventarios;
    }

    public function selecionaContagemAction()
    {

        // pega o id do inventario pra recuperar a descricao

        // lógica para verificar o número da contagem

        // retornar as contagens pra view

        /** @var \Wms\Service\Mobile\InventarioNovo $inventarioService */
        $inventarioService               = $this->_service;
        $idInventario                    = $this->_getParam('idInventario');
        $descricaoInventario             = $this->_getParam('descricaoInventario');
        $numContagensRegra               = $this->getSystemParameterValue('REGRA_CONTAGEM');
        $numContagens                    = $inventarioService->getContagens(array('idInventario' => $idInventario, 'regraContagem' => $numContagensRegra));
        $this->view->numContagens        = $numContagens;
        $this->view->idInventario        = $idInventario;
        $this->view->descricaoInventario = $descricaoInventario;

    }

}