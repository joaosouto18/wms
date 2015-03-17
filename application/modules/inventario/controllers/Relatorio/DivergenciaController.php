<?php

class Inventario_Relatorio_DivergenciaController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        $idInventario = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\InventarioRepository $inventarioRepo */
        $inventarioRepo = $this->em->getRepository("wms:Inventario");
        $avariados = $inventarioRepo->getDivergencias($idInventario);
        $this->exportPDF($avariados,'Enderecos-Divergencia-Inv-'.$idInventario,'Endereços Divergência','P');
    }

}