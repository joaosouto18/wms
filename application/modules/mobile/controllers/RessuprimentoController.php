<?php
use Wms\Controller\Action,
    Wms\Service\Recebimento as LeituraColetor;


class Mobile_RessuprimentoController extends Action
{
    public function indexAction()
    {
        $menu = array(

            1 => array (
                'url' => 'enderecamento/leitura-picking' ,
                'label' => 'SELECIONAR PICKING',
            ),
            2 => array (
                'url' => 'ressuprimento/listar-picking',
                'label' => 'RESSUPRIMENTO PREVENTIVO',
            ),
            3 => array (
                'url' => 'enderecamento_reabastecimento-manual',
                'label' => 'RESSUPRIMENTO MANUAL',
            )
        );
        $this->view->menu = $menu;
        $this->renderScript('menu.phtml');
    }

    public function listarPickingAction()
    {
        $codigoBarras = $this->_getParam('codigoBarras');
        $nivel = $this->_getParam('nivel');

        $this->view->codigoBarras = $codigoBarras;
        $this->view->nivel = $nivel;
    }

    public function enderecoEstoqueAction()
    {
        $codigoBarras = $this->_getParam('codigoBarras');
        $nivel = $this->_getParam('nivel');
        $this->view->codigoBarras = $codigoBarras;

        if ($codigoBarras) {
          $LeituraColetor = new LeituraColetor();
          $codigoBarras = $LeituraColetor->retiraDigitoIdentificador($codigoBarras);
        }

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
        $result = $estoqueRepo->getProdutoByNivel($codigoBarras, $nivel);

        if ($result == NULL)
        {
            $this->addFlashMessage("error","Endereço selecionado está vazio");
            $this->_redirect('/mobile/ressuprimento/listar-picking');
        } else {
            $idEstoque = $result[0]['id'];
            if ($result[0]['uma']) {
                $this->_redirect('/mobile/ressuprimento/endereco-uma/cb/' . $idEstoque);
            } else {
                $this->_redirect('/mobile/ressuprimento/endereco-produto/cb/' . $idEstoque );
            }
        }
    }

    public function enderecoProdutoAction()
    {
        $idEstoque = $this->_getParam('cb');
        $this->view->cb = $idEstoque;

        /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueEn */
        $estoqueEn = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque")->findOneBy(array('id'=>$idEstoque));

        $rua         = $estoqueEn->getDepositoEndereco()->getRua();
        $predio      = $estoqueEn->getDepositoEndereco()->getPredio();
        $nivel       = $estoqueEn->getDepositoEndereco()->getNivel();
        $apartamento = $estoqueEn->getDepositoEndereco()->getApartamento();
        $idEstoque   = $estoqueEn->getDepositoEndereco()->getId();

        $this->view->rua = $rua;
        $this->view->predio = $predio;
        $this->view->nivel = $nivel;
        $this->view->apartamento = $apartamento;

    }

    public function enderecoUmaAction()
    {
        $idEstoque = $this->_getParam('cb');
        $this->view->cb = $idEstoque;

        /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueEn */
        $estoqueEn = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque")->findOneBy(array('id'=>$idEstoque));

        $rua         = $estoqueEn->getDepositoEndereco()->getRua();
        $predio      = $estoqueEn->getDepositoEndereco()->getPredio();
        $nivel       = $estoqueEn->getDepositoEndereco()->getNivel();
        $apartamento = $estoqueEn->getDepositoEndereco()->getApartamento();
        $idEstoque   = $estoqueEn->getDepositoEndereco()->getId();

        $this->view->rua = $rua;
        $this->view->predio = $predio;
        $this->view->nivel = $nivel;
        $this->view->apartamento = $apartamento;
    }

    public function retirarEstoqueAction()
    {
        $params = $this->_getAllParams();
        $codigoBarrasUMA = $this->_getParam('codigoBarrasUMA');
        $etiquetaProduto = $this->_getParam('etiquetaProduto');
        $idEstoque = $this->_getParam('cb');

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");

        /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueEn */
        $estoqueEn = $estoqueRepo->findOneBy(array('id'=>$idEstoque));
        $idEndereco = $estoqueEn->getDepositoEndereco()->getId();
        $this->view->idEndereco = $idEndereco;

        if ($codigoBarrasUMA)
        {
            $LeituraColetor = new LeituraColetor();
            $codigoBarrasUMA = $LeituraColetor->retiraDigitoIdentificador($codigoBarrasUMA);

            $result = $estoqueRepo->getProdutoByUMA($codigoBarrasUMA, $idEndereco);
            if ($result == NULL) {
                $this->addFlashMessage("error","UMA $codigoBarrasUMA Não encontrada neste endereço");
                $this->_redirect('/mobile/ressuprimento/endereco-uma/cb/' . $idEstoque );
            } else {
                $this->view->codProduto = $result[0]['ID'];
                $this->view->grade = $result[0]['GRADE'];
                $this->view->descricao = $result[0]['DESCRICAO'];
                $this->view->endereco = $result[0]['ENDERECO'];
                $this->view->qtd = $result[0]['QTD'].' '.$result[0]['DSC_EMBALAGEM'];
            }

        }

        if ($etiquetaProduto)
        {
            $LeituraColetor = new LeituraColetor();
            $etiquetaProduto = $LeituraColetor->analisarCodigoBarras($etiquetaProduto);

            $result = $estoqueRepo->getProdutoByCodBarrasAndEstoque($etiquetaProduto, $idEndereco);
            if ($result == NULL) {
                $this->addFlashMessage("error","Produto $etiquetaProduto não encontrado neste endereço");
                $this->_redirect('/mobile/ressuprimento/endereco-produto/cb/' . $idEstoque );
            } else {
                $this->view->codProduto = $result[0]['ID'];
                $this->view->grade = $result[0]['GRADE'];
                $this->view->descricaoProduto = $result[0]['DESCRICAO'];
                $this->view->endereco = $result[0]['ENDERECO'];
                $this->view->qtd = $result[0]['QTD'].' '.$result[0]['DSC_EMBALAGEM'];
            }
        }
    }

    public function confirmaMovimentacaoAction() {

        $idProduto = $this->_getParam('idProduto');
        $grade = $this->_getParam('grade');
        $idEndereco = $this->_getParam('idEndereco');
        $qtd = $this->_getParam('quantidade');

        try {
            $this->em->beginTransaction();
            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
            $embalagens = $estoqueRepo->findBy(array('depositoEndereco'=>$idEndereco, 'codProduto'=>$idProduto, 'grade'=>$grade));

            /** @var \Wms\Domain\Entity\Enderecamento\Estoque $volEstoque */
            foreach ($embalagens as $volEstoque) {
                $params = array();
                $produtoEn = $volEstoque->getProduto();
                $enderecoEn = $volEstoque->getDepositoEndereco();

                $idPicking = null;
                if ($volEstoque->getProdutoVolume() != NULL) {
                    $params['volume'] = $volEstoque->getProdutoVolume();
                    if ($volEstoque->getProdutoVolume()->getEndereco() != NULL) {
                        $idPicking = $volEstoque->getProdutoVolume()->getEndereco()->getId();
                    }
                } else{
                    $params['embalagem'] = $volEstoque->getProdutoEmbalagem();
                    if ($volEstoque->getProdutoEmbalagem()->getEndereco() != NULL) {
                        $idPicking   = $volEstoque->getProdutoEmbalagem()->getEndereco()->getId();
                    }
                }

                if ($idPicking == NULL){
                    throw new \Exception("Não foi encontrado endereço de picking para o produto");
                }

                $params['validade'] = null;
                if ($produtoEn->getValidade() == 'S' ) {
                    $validade = $volEstoque->getValidade();
                    if (!empty($validade)) {
                        $params['validade'] = $validade->format('d/m/Y');
                    }
                }

                $params['produto'] = $produtoEn;
                $params['endereco'] = $enderecoEn;
                $params['observacoes'] = "Mov. ref. ressuprimento preventivo coletor";
                $params['estoqueRepo'] = $estoqueRepo;
                $params['qtd'] = $qtd * -1;
                $estoqueRepo->movimentaEstoque($params);

                if ($idPicking != NULL) {
                    $enderecoEn = $this->getEntityManager()->getRepository("wms:Deposito\Endereco")->findOneBy(array('id'=>$idPicking));
                    $params['endereco'] = $enderecoEn;
                    $params['qtd'] = $qtd;
                    $estoqueRepo->movimentaEstoque($params);
                }

                $relatorioPickingRepo = $this->em->getRepository('wms:Enderecamento\RelatorioPicking');
                $relatorioPicking = $relatorioPickingRepo->findOneBy(array('depositoEndereco' => $enderecoEn));
                if (!empty($relatorioPicking))
                    $this->getEntityManager()->remove($relatorioPicking);
            }

            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
            $this->addFlashMessage("success","Movimentação efetivada com sucesso");
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->addFlashMessage("error",$e->getMessage());
        }
        $this->_redirect('/mobile/ressuprimento/listar-picking');
    }
}