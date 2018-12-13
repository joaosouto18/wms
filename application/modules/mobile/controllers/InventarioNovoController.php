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

        // lógica para verificar o número da contagem

        // retornar as contagens pra view

        echo 'seleciona contagem';


    }

}