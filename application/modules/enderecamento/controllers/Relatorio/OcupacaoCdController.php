<?php
use  Wms\Module\Armazenagem\Report\OcupacaoCD;

class Enderecamento_Relatorio_OcupacaoCdController extends \Wms\Controller\Action
{
    public function indexAction(){
        $form = new \Wms\Module\Armazenagem\Form\OcupacaocdPeriodo\Filtro();
        $form->init(false);
        $values = $form->getParams();

        if ($values)
        {
            $RelAcompanhamento = new OcupacaoCD();
            $RelAcompanhamento->imprimir($values);
        }
        $this->view->form = $form;
    }

}