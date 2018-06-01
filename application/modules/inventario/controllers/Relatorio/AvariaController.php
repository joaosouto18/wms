<?php

class Inventario_Relatorio_AvariaController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        $idInventario = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
        $inventarioRepo = $this->em->getRepository("wms:Inventario");
        $avariados = $inventarioRepo->getAvariados($idInventario);
        $this->exportPDF($avariados,'Produtos-Avariados-Inv-'.$idInventario,'Produtos Avariados','P');
    }

}