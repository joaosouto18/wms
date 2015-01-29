<?php

class Expedicao_Relatorio_DadosExpedicaoController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        $form = new \Wms\Module\Expedicao\Form\DadosExpedicao();
        $params = $form->getParams();
        if ($params) {
            /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepository */
            $ExpedicaoRepository   = $this->getEntityManager()->getRepository('wms:Expedicao');
            $expedicao = $ExpedicaoRepository->getDadosExpedicao($params);
            $this->exportCSV($expedicao,'Dados-expedicao',true);
            $form->populate($params);
        }
        $this->view->form = $form;
    }

}