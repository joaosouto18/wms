<?php
use Wms\Controller\Action;
use Wms\Module\Mobile\Form\Reentrega as FormReentrega;
use Wms\Module\Mobile\Form\ConferirProdutosReentrega as FormConferirProdutosReentrega;

class Mobile_ReentregaController extends Action
{

    public function indexAction()
    {

    }

    public function recebimentoAction()
    {
        $this->view->form = new FormReentrega;
    }

    public function buscarAction()
    {
        $params = $this->_getAllParams();
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);

        if ((!empty($params['carga']) && isset($params['carga'])) || (!empty($params['notaFiscal']) && isset($params['notaFiscal']))) {
            /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository $notaFiscalSaidaRepo */
            $notaFiscalSaidaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\NotaFiscalSaida");
            $result = $notaFiscalSaidaRepo->getNotaFiscalOuCarga($params);

            if (count($result) > 0) {
                $this->view->notasFiscaisByCarga = $result;
            } else {
                $this->addFlashMessage('error', 'Nenhuma nota fiscal encontrada!');
                $this->_redirect('/mobile/reentrega/recebimento');
            }
        }
    }

    public function gerarRecebimentoAction()
    {
        $params = $this->_getAllParams();

        /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
        $recebimentoReentregaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\RecebimentoReentrega");

        //verifica se a nota fiscal ja foi gerada
        $verificaRecebimento = $recebimentoReentregaRepo->verificaRecebimento($params);
        if (count($verificaRecebimento) > 0) {
            $this->addFlashMessage('success', 'Nota fiscal ja gerada e em conferencia!');
            $this->redirect('recebimento', 'reentrega', 'mobile');
        }

        //caso a nota nao tenha sido gerada salva os dados nas tabelas RECEBIMENTO_REENTREGA, RECEBIMENTO_REENTREGA_NOTA e ORDEM_SERVICO
        $recebimentoReentregaEn = $recebimentoReentregaRepo->save();

        /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaNotaRepository $recebimentoReentregaNotaRepo */
        $recebimentoReentregaNotaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\RecebimentoReentregaNota");
        $recebimentoReentregaNotaRepo->save($recebimentoReentregaEn, $params);

        /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
        $ordemServicoRepo = $this->getEntityManager()->getRepository("wms:OrdemServico");
        $ordemServicoRepo->criarOsByReentrega($recebimentoReentregaEn);

        $this->addFlashMessage('success', 'Recebimento de Reentrega gerado com sucesso!');
        $this->redirect('reconferencia', 'reentrega', 'mobile');
    }

    public function reconferenciaAction()
    {
        /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaNotaRepository $recebimentoReentregaNotaRepo */
        $recebimentoReentregaNotaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentregaNota');
        $this->view->notasFiscais = $recebimentoReentregaNotaRepo->getRecebimentoReentregaByNota();
    }

    public function reconferirProdutosAction()
    {
        $this->view->form = new FormConferirProdutosReentrega;
        $params = $this->_getAllParams();

        if (isset($params['qtd']) && !empty($params['qtd']) && isset($params['codBarras']) && !empty($params['codBarras'])) {
            try {
                /** @var \Wms\Domain\Entity\Expedicao\ConferenciaRecebimentoReentregaRepository $conferenciaRecebimentoReentregaRepo */
                $conferenciaRecebimentoReentregaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ConferenciaRecebimentoReentrega');
                $result = $conferenciaRecebimentoReentregaRepo->save($params);
            } catch (\Exception $e) {
                $this->_helper->messenger('error', $e->getMessage());
            }
        } else {
            $this->_helper->messenger('error', 'Preencha todos os campos corretamente');
        }
    }

    public function finalizarConferenciaAction()
    {
        $params = $this->_getAllParams();

        try {
            /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
            $recebimentoReentregaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentrega');
            $result = $recebimentoReentregaRepo->finalizarConferencia($params);
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->_redirect('/mobile/reentrega/reconferir-produtos/id/'.$params['id']);
    }
}

