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
        $this->renderScript('inventario-novo\inventarios.phtml');
    }

    public function getInventariosAction()
    {
        $this->_helper->json($this->em->getRepository('wms:InventarioNovo')->getInventarios('stdClass',['status' => \Wms\Domain\Entity\InventarioNovo::STATUS_LIBERADO]));
    }

    public function getContagensAction()
    {
        $this->_helper->json($this->em->getRepository('wms:InventarioNovo\InventarioContEnd')->getContagens($this->_getParam("id")));
    }

    public function getEnderecosAction()
    {
        $this->_helper->json($this->em->getRepository("wms:InventarioNovo\InventarioEnderecoNovo")->getArrEnderecos($this->_getParam("id"), $this->_getParam("sq")));
    }
}