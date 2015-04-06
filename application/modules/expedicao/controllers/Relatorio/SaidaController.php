<?php

class Expedicao_Relatorio_SaidaController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");
        $form = new \Wms\Module\Expedicao\Form\SaidaProduto();
        $form->init($utilizaGrade);
        
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