<?php

class Expedicao_Relatorio_DadosRecebimentoController extends \Wms\Controller\Action
{
    public function indexAction() 
    {
        $form = new \Wms\Module\Expedicao\Form\DadosRecebimento();
        $params = $form->getParams();
        if ($params) {
            /** @var \Wms\Domain\Entity\RecebimentoRepository $RecebimentoRepository */
            $RecebimentoRepository   = $this->getEntityManager()->getRepository('wms:Recebimento');
            $recebimento = $RecebimentoRepository->getDadosRecebimento($params);
            $this->exportCSV($recebimento,'recebimento',true);
            $form->populate($params);
        }
        $this->view->form = $form;
    }

}