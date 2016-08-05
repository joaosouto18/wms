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
                if ($etiquetaEntity->getPedido()->getCarga()->getExpedicao()->getId() != $idExpedicao) {
                    $this->addFlashMessage('error', 'A Etiqueta código ' . $LeituraColetor->retiraDigitoIdentificador($codBarra) . ' não pertence a expedição ' . $idExpedicao);
                    $this->_redirect('/expedicao');
                }
                if ($etiquetaEntity->getCodStatus() == \Wms\Domain\Entity\Expedicao\EtiquetaSeparacao::STATUS_CORTADO) {
                    $this->addFlashMessage('error',"Etiqueta já cortada!");
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

        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
        $pedidoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Pedido');
        $pedidos = $pedidoRepo->getPedidoByExpedicao($id);

        $grid = new \Wms\Module\Web\Grid\Expedicao\CortePedido();
        $this->view->grid = $grid->init($pedidos,$id);

    }

    public function corteAntecipadoByMapaAction(){
        $id = $this->_getParam('COD_MAPA_SEPARACAO');
        $idExpedicao = $this->_getParam('id');

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoPedidoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoPedido');
        $pedidos = $mapaSeparacaoRepo->getPedidosByMapa($id);

        $grid = new \Wms\Module\Web\Grid\Expedicao\CortePedido();
        $this->view->grid = $grid->init($pedidos,$idExpedicao);

    }

    public function listAction()
    {
        $idExpedicao = $this->_getParam('expedicao');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        $produtos = $expedicaoRepo->getProdutosExpedicaoCorte($this->_getParam('id',0));

        $grid = new \Wms\Module\Web\Grid\Expedicao\CorteAntecipado();
        $this->view->grid = $grid->init($produtos,$this->_getParam('id',0),$idExpedicao);
    }

    public function cortarItemAction()
    {
        $this->view->pedido    = $pedido    = $this->_getParam('id',0);
        $this->view->produto   = $produto   = $this->_getParam('COD_PRODUTO',0);
        $this->view->grade     = $grade     = $this->_getParam('DSC_GRADE',0);
        $this->view->expedicao = $expedicao = $this->_getParam('expedicao');
        $quantidade            = $this->_getParam('quantidade');
        $motivo                = $this->_getParam('motivoCorte');

        $senha    = $this->_getParam('senha');

        if (isset($senha) && !empty($senha) && isset($quantidade) && !empty($quantidade) && isset($motivo) && !empty($motivo)) {

            try {
                $this->getEntityManager()->beginTransaction();

                if ($senha != $this->getSystemParameterValue('SENHA_AUTORIZAR_DIVERGENCIA'))
                    throw new \Exception("Senha Informada Inválida");

                /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
                $expedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
                /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $expedicaoAndamentoRepo */
                $expedicaoAndamentoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Andamento');
                $pedidoProduto = $this->getEntityManager()->getRepository('wms:Expedicao\PedidoProduto')
                    ->findOneBy(array('codPedido' => $pedido, 'codProduto' => $produto, 'grade' => $grade));

                if (!isset($pedidoProduto) || empty($pedidoProduto))
                    throw new \Exception("Produto $produto grade $grade não encontrado para o pedido $pedido");

                $expedicaoRepo->cortaPedido($pedido, $pedidoProduto->getCodProduto(), $pedidoProduto->getGrade(), $quantidade, $this->_getParam('motivoCorte',null));
                $observacao = 'Produto '.$pedidoProduto->getCodProduto().' Grade '.$pedidoProduto->getGrade().' referente ao pedido '.$pedido.' cortado - motivo: '.$motivo;
                $expedicaoAndamentoRepo->save($observacao, $expedicao);

                $this->getEntityManager()->commit();
                $this->addFlashMessage('success','Produto ' .$produto. ' grade ' .$grade. ' pedido '.$pedido.' cortado com Sucesso');
                $this->_redirect('/expedicao');
            } catch (\Exception $e) {
                $this->getEntityManager()->rollback();
                return $e->getMessage();
            }
        }

    }

}