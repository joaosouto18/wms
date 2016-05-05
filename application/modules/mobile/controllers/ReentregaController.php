<?php
use Wms\Controller\Action;
use Wms\Module\Mobile\Form\Reentrega as FormReentrega;
use Wms\Module\Mobile\Form\ConferirProdutosReentrega as FormConferirProdutosReentrega;
use Wms\Service\Coletor as LeituraColetor;

class Mobile_ReentregaController extends Action
{

    public function indexAction()
    {

    }

    public function recebimentoAction()
    {
        /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaNotaRepository $recebimentoReentregaNotaRepo */
        $recebimentoReentregaNotaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentregaNota');
        $this->view->notasFiscais = $recebimentoReentregaNotaRepo->getRecebimentoReentregaByNota();
//        $this->view->form = new FormReentrega;
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
            $this->addFlashMessage('success', 'Nota fiscal ja se encontra em processo de reconferencia!');
            $this->redirect('recebimento', 'reentrega', 'mobile');
        }

        //verifica se as notas já foram recebidas e estão pendentes de expedição
        if ($this->getSystemParameterValue('CONFERE_EXPEDICAO_REENTREGA') == 'S') {
            if ($recebimentoReentregaRepo->verificaNotaExpedida($params) == false) {
                $this->addFlashMessage('error', 'Foram selecionados notas fiscais já recebidas que estão pendentes de expedição!');
                $this->redirect('recebimento', 'reentrega', 'mobile');
            }
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
        $this->redirect('reconferir-produtos', 'reentrega', 'mobile',array('id'=>$recebimentoReentregaEn->getId()));
    }

    public function reconferirProdutosAction()
    {
        $params = $this->_getAllParams();
        $this->view->id = $params['id'];

        $idModeloSeparacao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');
        $modeloSeparacao = $this->getEntityManager()->getRepository('wms:Expedicao\ModeloSeparacao')->findOneBy(array('id' => $idModeloSeparacao));
        $this->view->modeloSeparacao = $modeloSeparacao->getTipoSeparacaoFracionado();

        if (isset($params['submit'])) {
            if (isset($params['qtd']) && !empty($params['qtd']) && isset($params['codBarras']) && !empty($params['codBarras'])) {
                try {
                    /** @var \Wms\Domain\Entity\Expedicao\ConferenciaRecebimentoReentregaRepository $conferenciaRecebimentoReentregaRepo */
                    $conferenciaRecebimentoReentregaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ConferenciaRecebimentoReentrega');
                    $produtoEn = $result = $conferenciaRecebimentoReentregaRepo->save($params);
                    $this->_helper->messenger('success', "Produto " . $produtoEn->getId(). "/" . $produtoEn->getGrade() . " - " . $produtoEn->getDescricao() . " conferido com sucesso");

                } catch (\Exception $e) {
                    $this->_helper->messenger('error', utf8_decode($e->getMessage()));
                }
            } else {
                $this->_helper->messenger('error', 'Preencha todos os campos corretamente');
            }
        }
        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository $notaFiscalSaidaRepo */
        $notaFiscalSaidaRepo         = $this->getEntityManager()->getRepository('wms:Expedicao\NotaFiscalSaida');
        $getQtdProdutosDivergentes   = $notaFiscalSaidaRepo->getQtdProdutoDivergentesByNota(array('id' => $params['id']));
        $this->view->listaProdutos = $getQtdProdutosDivergentes;
    }

    public function finalizarConferenciaAction()
    {
        $params = $this->_getAllParams();

        try {
            /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
            $recebimentoReentregaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\RecebimentoReentrega');
            $result = $recebimentoReentregaRepo->finalizarConferencia($params);

            $this->addFlashMessage('success', "Notas Fiscais Recebidas com sucesso");
            $this->_redirect('/mobile/reentrega/recebimento');

        } catch (\Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
            $this->_redirect('/mobile/reentrega/reconferir-produtos/id/'.$params['id']);
        }

    }

    public function visualizarDivergenciaAction()
    {

    }

    public function cancelarConferenciaAction(){

        try {
            $this->getEntityManager()->beginTransaction();

            /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaRepository $recebimentoReentregaRepo */
            $recebimentoReentregaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\RecebimentoReentrega");
            /** @var \Wms\Domain\Entity\Expedicao\RecebimentoReentregaNotaRepository $recebimentoReentregaNfRepo */
            $recebimentoReentregaNfRepo = $this->getEntityManager()->getRepository("wms:Expedicao\RecebimentoReentregaNota");
            /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaAndamentoRepository $andamentoNFRepo */
            $andamentoNFRepo = $this->_em->getRepository("wms:Expedicao\NotaFiscalSaidaAndamento");
            /** @var \Wms\Domain\Entity\Util\Sigla $siglaRepo */
            $siglaRepo = $this->getEntityManager()->getRepository("wms:Util\Sigla");

            $params = $this->_getAllParams();
            $idRecebimento = $params['id'];
            $siglaEn = $siglaRepo->findOneBy(array('id' => \Wms\Domain\Entity\Expedicao\RecebimentoReentrega::RECEBIMENTO_CANCELADO));

            $recebimentoEn = $recebimentoReentregaRepo->findOneBy(array('id'=>$idRecebimento));
            $notas = $recebimentoReentregaNfRepo->findBy(array('recebimentoReentrega'=>$idRecebimento));

            foreach ($notas as $nfReceb) {
                $andamentoNFRepo->save($nfReceb->getNotaFiscalSaida(), \Wms\Domain\Entity\Expedicao\RecebimentoReentrega::RECEBIMENTO_CANCELADO,false,null,null,$recebimentoEn);
            }
            $recebimentoEn->setStatus($siglaEn);

            $this->getEntityManager()->persist($recebimentoEn);
            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
            $this->addFlashMessage('success', 'Recebimento '. $recebimentoEn->getId() . " cancelado com sucesso");
            $this->redirect('recebimento', 'reentrega', 'mobile');

        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            $this->addFlashMessage('error', $e->getMessage());
            $this->redirect('recebimento', 'reentrega', 'mobile');

        }
    }

    public function getNotaOrCodBarrasByCampoBipadoAction()
    {
        $params = $this->_getAllParams();
        $LeituraColetor = new LeituraColetor();
        $etiquetaSeparacao = $LeituraColetor->retiraDigitoIdentificador($params['etiqueta']);

        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('nfs.numeroNf, es.id')
            ->from('wms:Expedicao\NotaFiscalSaida', 'nfs')
            ->innerJoin('wms:Expedicao\NotaFiscalSaidaProduto', 'nfsp', 'WITH', 'nfsp.notaFiscalSaida = nfs.id')
            ->innerJoin('wms:Expedicao\NotaFiscalSaidaPedido', 'nfsped', 'WITH', 'nfsped.notaFiscalSaida = nfs.id')
            ->innerJoin('nfsped.pedido', 'ped')
            ->innerJoin('wms:Expedicao\EtiquetaSeparacao', 'es', 'WITH', 'nfsp.codProduto = es.codProduto AND nfsp.grade = es.dscGrade AND ped.id = es.pedido');

        if (isset($params['etiqueta']) && !empty($params['etiqueta'])) {
            $sql->orWhere("es.id = '$etiquetaSeparacao'");
        }

        $resultado = $sql->getQuery()->getResult();

        $this->_helper->json($resultado, true);
    }
}

