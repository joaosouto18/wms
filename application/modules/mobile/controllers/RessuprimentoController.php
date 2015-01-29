<?php
use Wms\Controller\Action,
    Wms\Service\Recebimento as LeituraColetor;


class Mobile_RessuprimentoController extends Action
{

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
        $result = $estoqueRepo->getProdutoByNivel($codigoBarras, $nivel, false);

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
                $this->view->codProduto = $result[0]['id'];
                $this->view->grade = $result[0]['grade'];
                $this->view->descricao = $result[0]['descricao'];
                $this->view->endereco = $result[0]['endereco'];
                $this->view->qtd = $result[0]['qtd'];
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
                $this->view->codProduto = $result[0]['id'];
                $this->view->grade = $result[0]['grade'];
                $this->view->descricaoProduto = $result[0]['descricao'];
                $this->view->endereco = $result[0]['endereco'];
                $this->view->qtd = $result[0]['qtd'];
            }
        }
    }

    public function confirmaMovimentacaoAction() {

        $idProduto = $this->_getParam('idProduto');
        $grade = $this->_getParam('grade');
        $idEndereco = $this->_getParam('idEndereco');
        $qtd = $this->_getParam('quantidade');
        $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();

        try {
            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
            $estoqueRepo->movimentaEstoque($idProduto,$grade,$idEndereco,$qtd * -1,$idUsuario,"Mov. efetuada no coletor");

             /** @var \Wms\Domain\Entity\ProdutoRepository $ProdutoRepository */
            $ProdutoRepository   = $this->_em->getRepository('wms:Produto');
            $produtoEn = $ProdutoRepository->findOneBy(array('id' => $idProduto, 'grade' => $grade));

            $endPicking=$ProdutoRepository->getEnderecoPicking($produtoEn, "COD");
            if ($endPicking != null) {
                //REMOVIDO TEMPORARIAMENTE PARA NÂO POPULAR o PICKING ENQUANTO RESSUPRIMENTO NÂO ESTA NO AR
                //$estoqueRepo->movimentaEstoque($idProduto,$grade,$endPicking,$qtd ,$idUsuario,"Mov. efetuada no coletor");
            }

            $this->addFlashMessage("success","Movimentação efetivada com sucesso");
        } catch (\Exception $e) {
            $this->addFlashMessage("error",$e->getMessage());
        }
        $this->_redirect('/mobile/ressuprimento/listar-picking');

    }


  }




