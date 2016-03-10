<?php

class Web_ReentregaController extends \Wms\Controller\Action {

    public function indexAction()
    {
        $formReentrega = new \Wms\Module\Web\Form\Subform\FiltroRecebimentoReentrega();

        $values = $formReentrega->getParams();

        if (!$values) {
            $dataI1 = new \DateTime;
            $dataI1->modify('-1 day');
            $dataI2 = new \DateTime();
            $values = array(
                'dataInicial1' => $dataI1->format('d/m/Y'),
                'dataInicial2' => $dataI2->format('d/m/Y'),
                'notaFiscal' => ''
            );
        } else {
            if ($values['notaFiscal']) {
                $values['dataInicial1'] = null;
                $values['dataInicial2'] = null;
            }
        }

        // grid
        $grid = new \Wms\Module\Web\Grid\Recebimento\Reentrega();
        $this->view->grid = $grid->init($values)
            ->render();

        $this->view->formReentrega = $formReentrega;
    }

    /**
     * Ordem Serviço
     */
    public function viewOrdemServicoAjaxAction()
    {
        $id = $this->getRequest()->getParam('id');

        $source = $this->em->createQueryBuilder()
                ->select('os, r.id idRecebimento, p.nome, a.descricao as dscAtividade, s.id statusId, s.sigla status')
                ->from('wms:OrdemServico', 'os')
                ->join('os.recebimentoReentrega', 'r')
                ->join('r.status', 's')
                ->leftJoin('os.atividade', 'a')
                ->leftJoin('os.pessoa', 'p')
                ->where('os.recebimentoReentrega = :idRecebimento')
                ->setParameter('idRecebimento', $id)
                ->orderBy('os.id');

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($source));
        $grid->setId('recebimento-view-ordem-servico-ajax-grid')
                ->addColumn(array(
                    'label' => 'Ordem de Serviço',
                    'index' => 'id'
                ))
                ->addColumn(array(
                    'label' => 'Responsável',
                    'index' => 'nome'
                ))
                ->addColumn(array(
                    'label' => 'Atividade',
                    'index' => 'dscAtividade'
                ))
                ->addColumn(array(
                    'label' => 'Data Início',
                    'index' => 'dataInicial',
                    'render' => 'Data'
                ))
                ->addColumn(array(
                    'label' => 'Data Final',
                    'index' => 'dataFinal',
                    'render' => 'Data'
                ))
                ->addAction(array(
                    'label' => 'Visualizar Conferência',
                    'actionName' => 'view-conferencia-ajax',
                    'cssClass' => 'view-conferencia',
                    'pkIndex' => 'id'
                ))
                ->setShowExport(false);

        $this->view->grid = $grid->build();
    }

    /**
     * Visualizar Conferencia
     */
    public function viewConferenciaAjaxAction()
    {
        $id = $this->getRequest()->getParam('id');

        $ordemServicoEntity = $this->em->find('wms:OrdemServico', $id);
        $this->view->ordemServico = $ordemServicoEntity;

        // grid da conferencia
        $grid = new \Wms\Module\Web\Grid\Recebimento\ConferenciaReentrega();
        $this->view->grid = $grid->init(array('idOrdemServico' => $id))
                ->render();
    }

}