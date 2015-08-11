<?php
use Wms\Controller\Action;
use Wms\Module\Mobile\Form\Reentrega as FormReentrega;
use Wms\Domain\Entity\Expedicao\NotaFiscalSaida as NotaFiscalSaida;

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
            $this->view->notasFiscaisByCarga = $notaFiscalSaidaRepo->getNotaFiscalOuCarga($params);
        }
    }

    public function gerarRecebimentoAction()
    {
        $params = $this->_getAllParams();

        /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
        $recebimentoReentregaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\RecebimentoReentrega");
        $recebimentoReentregaEn = $recebimentoReentregaRepo->save();

        /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaNotaRepository $recebimentoReentregaNotaRepo */
        $recebimentoReentregaNotaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\RecebimentoReentregaNota");
        $recebimentoReentregaNotaRepo->save($recebimentoReentregaEn, $params);

        /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
        $ordemServicoRepo = $this->getEntityManager()->getRepository("wms:OrdemServico");
        $ordemServicoRepo->criarOsByReentrega($recebimentoReentregaEn);

        $this->addFlashMessage('success', 'Recebimento de Reentrega concluido com sucesso!');
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
        $params = $this->_getAllParams();
        if (isset($params['id'])) {
            $this->view->id = $params['id'];
        } else {
            $this->view->id = $params['numeroNota'];
        }

        if (isset($params['qtd']) && isset($params['codBarras'])) {
            /** @var \Wms\Domain\Entity\Expedicao\ConferenciaRecebimentoReentregaRepository $conferenciaRecebimentoReentregaRepo */
            $conferenciaRecebimentoReentregaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ConferenciaRecebimentoReentrega');
            $result = $conferenciaRecebimentoReentregaRepo->save($params);

            if ($result == true) {
                $this->addFlashMessage('success', 'Produto conferido com sucesso!');
            } else {
                $this->addFlashMessage('error', 'Erro! Tente Novamente');
            }
        }
    }

    public function finalizarConferenciaAction()
    {
        $params = $this->_getAllParams();

        /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
        $recebimentoReentregaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentrega');
        $result = $recebimentoReentregaRepo->finalizarConferencia($params);

        if ($result == false) {
            $this->addFlashMessage('error', 'Existe divergencia na conferencia de produtos');
        } else {
            $this->addFlashMessage('success', 'Conferencia Finalizada com sucesso!');
        }
        $this->_redirect('/mobile/reentrega/reconferir-produtos/id/'.$params['id']);
    }

}

