<?php
use Wms\Module\Web\Controller\Action;
use Wms\Module\Enderecamento\Form\Modelo as Modelo;

class Enderecamento_ModeloController extends Action
{

    public function indexAction()
    {
        $form = new Modelo();



    }
}