<?php

class Expedicao_Relatorio_SaidaController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        $form = new \Wms\Module\Expedicao\Form\SaidaProduto();

        $params = $form->getParams();

        if ($params) {
            $form->populate($params);
            $Report = new \Wms\Module\Expedicao\Report\SaidaProduto();
            if ($Report->init($params) == false) {
                $this->addFlashMessage('error', 'Produto não encontrado');
            }
        }

        $this->view->form = $form;
    }

}