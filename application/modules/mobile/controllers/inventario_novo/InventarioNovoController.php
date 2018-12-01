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
        /** @var \Wms\Domain\Entity\InventarioNovo\InventarioNovoRepository $inventarioRepo */
        $inventarioRepo = $this->em->getRepository('wms:inventario_novo');
        $this->view->inventarios = $inventarioRepo->getInventarios("WHERE I.STATUS = " . \Wms\Domain\Entity\InventarioNovo\InventarioNovo::STATUS_LIBERADO);
    }

    public function selecionaContagemAction()
    {

        // pega o id do inventario pra recuperar a descricao

        // lógica para verificar o número da contagem

        // retornar as contagens pra view


    }


}