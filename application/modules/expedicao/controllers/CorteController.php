<?php
use Wms\Module\Web\Controller\Action,
    Wms\Service\Recebimento as LeituraColetor;

class Expedicao_CorteController  extends Action
{

    public function indexAction()
    {
        $id = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $codEtiqueta = $etiquetaRepo->getEtiquetasByExpedicao($id, \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_PENDENTE_CORTE, null);

        if (isset($codEtiqueta) && !empty($codEtiqueta)) {
            $this->view->codBarras = $codEtiqueta[0]['codBarras'];
        }
        //$this->view->codBarras = $codEtiqueta[0]['codBarras'];
    }

    public function salvarAction()
    {
        $LeituraColetor = new LeituraColetor();
        $request = $this->getRequest();
        $idExpedicao = $this->getRequest()->getParam('id');
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoReentregaRepository $EtiquetaReentregaRepo */
        $EtiquetaReentregaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacaoReentrega');

        if ($request->isPost()) {
            $senhaDigitada    = $request->getParam('senhaConfirmacao');

            if ($EtiquetaRepo->checkAutorizacao($senhaDigitada)) {
                $codBarra    = $request->getParam('codBarra');

                if (!$codBarra) {
                    $this->addFlashMessage('error', 'É necessário preencher todos os campos');
                    $this->_redirect('/expedicao');
                }
                $etiquetaEntity = $EtiquetaRepo->findOneBy(array('id' => $LeituraColetor->retiraDigitoIdentificador($codBarra)));
                if ($etiquetaEntity == null ) {
                    $this->addFlashMessage('error', 'Etiqueta não encontrada');
                    $this->_redirect('/expedicao');
                }

                $encontrouEtiqueta = true;
                if ($etiquetaEntity->getPedido()->getCarga()->getExpedicao()->getId() != $idExpedicao) {
                    $encontrouEtiqueta = false;
                    $etiquetasReentrega = $EtiquetaReentregaRepo->findBy(array('codEtiquetaSeparacao'=>$etiquetaEntity->getId()));
                    foreach ($etiquetasReentrega as $etiquetaReentregaEn) {
                        $idExpedicaoEtqReentrega = $etiquetaReentregaEn->getReentrega()->getCarga()->getExpedicao()->getId();
                        if ($idExpedicao == $idExpedicaoEtqReentrega) {
                            $encontrouEtiqueta = true;
                            continue;
                        }
                    }
                }

                if ($encontrouEtiqueta == false) {
                    $this->addFlashMessage('error', 'A Etiqueta código ' . $LeituraColetor->retiraDigitoIdentificador($codBarra) . ' não pertence a expedição ' . $idExpedicao);
                    $this->_redirect('/expedicao');
                }

                $EtiquetaRepo->cortar($etiquetaEntity);

                if ($etiquetaEntity->getProdutoEmbalagem() != NULL) {
                    $codBarrasProdutos = $etiquetaEntity->getProdutoEmbalagem()->getCodigoBarras();
                } else {
                    $codBarrasProdutos = $etiquetaEntity->getProdutoVolume()->getCodigoBarras();
                }

                /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
                $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
                $andamentoRepo->save('Etiqueta '. $LeituraColetor->retiraDigitoIdentificador($codBarra) .' cortada', $idExpedicao, false, true, $codBarra, $codBarrasProdutos);
                $this->addFlashMessage('success', 'Etiqueta cortada com sucesso');

            }else {
                $this->addFlashMessage('error', 'Senha informada não é válida');
            }

        }

        $this->_redirect('/expedicao/os/index/id/'.$idExpedicao);

    }

    public function corteAntecipadoAjaxAction(){
        $id = $this->_getParam('id');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $produtos = $expedicaoRepo->getProdutosExpedicaoCorte($id);

        $grid = new \Wms\Module\Web\Grid\Expedicao\CorteAntecipado();
        $this->view->grid = $grid->init($produtos, $id);
    }

}