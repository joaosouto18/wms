<?php

use Wms\Domain\Entity\Recebimento as RecebimentoEntity,
    Wms\Controller\Action,
    Wms\Module\Web\Form\Relatorio\Ressuprimento\FiltroDadosOnda;

class Web_Relatorio_RelatorioOndasController extends Action
{

    public function indexAction()
    {
        $form = new FiltroDadosOnda;

        if ($form->getParams()){
            $values = $form->getParams();
            $dataInicial    = $values['dataInicial'];
            $dataFinal      = $values['dataFinal'];
            $status         = $values['status'];
            /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoRepository $ondaRessuprimentoRepo */
            $ondaRessuprimentoRepo = $this->em->getRepository("wms:Ressuprimento\OndaRessuprimento");
            $result = $ondaRessuprimentoRepo->getOndasEmAbertoCompleto($dataInicial,$dataFinal,$status,false, null,null,null,true);

            $pdf = new \Wms\Module\Web\Report\Ressuprimento('L');
            $pdf->init($result, 'relatorio-Onda', 'Ondas de Ressuprimento Abertas - '.count($result).' ondas');
        }

        $this->view->form = $form;
    }

}
