<?php
use Wms\Controller\Action,
    Wms\Domain\Entity\Enderecamento\Palete as Palete,
    Wms\Service\Recebimento as LeituraColetor,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Module\Mobile\Form\PickingLeitura as PickingLeitura,
    Wms\Domain\Entity\Enderecamento\Estoque;


class Mobile_EnderecamentoController extends Action
{

    public function leituraPickingAction()
    {
        $form = new PickingLeitura();
        $form->init();
        $this->view->form = $form;
        $codigoBarras = $this->_getParam('codigoBarras');
        if ($codigoBarras) {
            $LeituraColetor = new \Wms\Service\Coletor();
            $codigoBarras = $LeituraColetor->retiraDigitoIdentificador($codigoBarras);

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $idEndereco = $enderecoRepo->getEnderecoIdByDescricao($codigoBarras);

            if (count($idEndereco) == 0) {
                $this->addFlashMessage('error','Endereço não encontrado');
                $this->_redirect('/mobile/enderecamento/leitura-picking');
            }

            $nivelEndereco =  $idEndereco[0]['NUM_NIVEL'];

            if ($nivelEndereco > 0 )
            {
                $this->addFlashMessage('error','Código bipado não é um endereço de picking');
                $this->_redirect('/mobile/enderecamento/leitura-picking');
            }

            $idEndereco = $idEndereco[0]['COD_DEPOSITO_ENDERECO'];
            $enderecoEn = $enderecoRepo->find($idEndereco);

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $result = $enderecoRepo->getProdutoByEndereco($codigoBarras,false);

            if (count($result) == 0)
            {
               $this->addFlashMessage('error', 'Nenhum produto encontrado para este picking');
               $this->_redirect('/mobile/enderecamento/leitura-picking');
            }

            $existeEstoque = false;

            foreach($result as $estoque)
            {
                $codProduto = $estoque['codProduto'];
                $grade      = $estoque['grade'];

                /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
                $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
                $resultado = $estoqueRepo->getExisteEnderecoPulmao($codProduto, $grade);

                if ($resultado == true){
                    $existeEstoque = true;
                    break;
                }
            }

            if ($existeEstoque == false)
            {
                $this->addFlashMessage('error', 'O produto não possui endereço de estoque no pulmão');
                $this->_redirect('/mobile/enderecamento/leitura-picking');
            }

              $contagem = $this->em->getRepository("wms:Enderecamento\RelatorioPicking")->findBy(array('depositoEndereco'=>$enderecoEn));

              if ($contagem != NULL)
              {
                     $this->addFlashMessage('error', 'O endereço informado já foi bipado');
                     $this->_redirect('/mobile/enderecamento/leitura-picking');
              }

              else
              {
                 $contagem = new \Wms\Domain\Entity\Enderecamento\RelatorioPicking();
                 $contagem->setDepositoEndereco($enderecoEn);
                 $this->em->persist($contagem);
                 $this->em->flush();
              }

        }
    }

    public function listarPickingAction()
    {
        $relatorioRepo = $this->em->getRepository("wms:Enderecamento\RelatorioPicking");

        $removerId = $this->_getParam('remover');
        if ($removerId) {
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $removerId));
            $relatorioEn = $relatorioRepo->findOneBy(array('depositoEndereco' => $enderecoEn));
            $this->getEntityManager()->remove($relatorioEn);
            $this->getEntityManager()->flush();
        }

        $enderecosSelecionados = $relatorioRepo->getSelecionados();
        $this->view->pickings = $enderecosSelecionados;

    }

    public function lerCodigoBarrasAction()
    {
        $layout = \Zend_Layout::getMvcInstance();
        $layout->setLayout('leitura');
    }

    public function buscarAction()
    {
        $idPalete = $this->_getParam("uma");

        if (!isset($idPalete)) {
            $this->createXml('error','Nenhum Palete Informado');
        }

        $LeituraColetor = new \Wms\Service\Coletor();
        $idPalete = $LeituraColetor->retiraDigitoIdentificador($idPalete);

        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteRepo = $this->em->getRepository("wms:Enderecamento\Palete");
        $paleteEn = $paleteRepo->find($idPalete);

        if ($paleteEn == NULL) {
            $this->createXml('error','Palete não encontrado');
        }
        if ($paleteEn->getCodStatus() == Palete::STATUS_ENDERECADO) {
            $this->createXml('error','Palete já endereçado');
        }
        if ($paleteEn->getCodStatus() == Palete::STATUS_CANCELADO) {
            $this->createXml('error','Palete cancelado');
        }

        $this->validarEndereco($paleteEn, $LeituraColetor, $paleteRepo);
    }

    public function validarEndereco($paleteEn, $LeituraColetor, $paleteRepo)
    {
        $endereco   = $LeituraColetor->retiraDigitoIdentificador($this->_getParam("endereco"));

        if (!isset($endereco)) {
            $this->createXml('error','Nenhum Endereço Informado');
        }
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");
        $idEndereco = $enderecoRepo->getEnderecoIdByDescricao($endereco);
        if (empty($idEndereco)) {
            $this->createXml('error','Endereço não encontrado');
        }
        $idEndereco = $idEndereco[0]['COD_DEPOSITO_ENDERECO'];
        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoEn = $enderecoRepo->find($idEndereco);

        if ($enderecoEn->getNivel() == '0') {
            $elementos = array();
            $elementos[] = array('name' => 'nivelzero', 'value' => true);
            $elementos[] = array('name' => 'rua', 'value' => $enderecoEn->getRua());
            $elementos[] = array('name' => 'predio', 'value' => $enderecoEn->getPredio());
            $elementos[] = array('name' => 'apartamento', 'value' => $enderecoEn->getApartamento());
            $elementos[] = array('name' => 'uma', 'value' => $paleteEn->getId());
            $this->createXml('info','Escolha um nível',null, $elementos);
        }

        $enderecoReservado = $paleteEn->getDepositoEndereco();

        if (($enderecoReservado == NULL) || ($enderecoEn->getId() == $enderecoReservado->getId())) {
            $this->enderecar($enderecoEn,$paleteEn,$enderecoRepo, $paleteRepo);
        } else {
            $this->createXml('info','Confirmar novo endereço','/mobile/enderecamento/confirmar-novo-endereco/uma/' . $paleteEn->getId() . '/endereco/' . $idEndereco);
        }

    }

    public function validaNivelAction()
    {
        $idPalete   = $this->_getParam("uma");
        $rua         = $this->_getParam("rua");
        $predio      = $this->_getParam("predio");
        $nivel       = $this->_getParam("nivel");
        $apartamento = $this->_getParam("apartamento");

        if (isset($rua)) {
            $nivel = "00" . $nivel;
            $nivel = substr($nivel,strlen($nivel)-2);
            $codBarras = $rua . $predio . $nivel . $apartamento . "1";
        }

        $LeituraColetor = new LeituraColetor();
        $codBarras = $LeituraColetor->retiraDigitoIdentificador($codBarras);

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
        $idEndereco = $enderecoRepo->getEnderecoIdByDescricao($codBarras);
        if (count($idEndereco) == 0) {
            $this->createXml('error','Endereço não encontrado');
        }
        $idEndereco = $idEndereco[0]['COD_DEPOSITO_ENDERECO'];

        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoEn = $enderecoRepo->find($idEndereco);

        $paleteRepo = $this->em->getRepository("wms:Enderecamento\Palete");
        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $paleteRepo->find($idPalete);

        $this->validaEnderecoPicking($codBarras, $paleteEn, $nivel);

        $enderecoReservado = $paleteEn->getDepositoEndereco();

        if (($enderecoReservado == NULL) || ($enderecoEn->getId() == $enderecoReservado->getId())) {
            $this->enderecar($enderecoEn,$paleteEn,$enderecoRepo, $paleteRepo);
        } else {
            $this->createXml('info','Confirmar novo endereço','/mobile/enderecamento/confirmar-novo-endereco/uma/' . $idPalete . '/endereco/' . $idEndereco);
        }

    }

    /**
     *  Verifica se o endereço passado é o endereço de picking do produto
     * @param $codBarras
     * @return int
     */
    public function validaEnderecoPicking($endereco, $paleteEn, $nivel)
    {

        //Se for picking do produto entao o nivel poderá ser escolhido
        if ($nivel == '00') {

            $produtosEn = $paleteEn->getProdutos();
            $produto = $produtosEn[0];
            $codProduto = $produto->getCodProduto();
            $grade      = $produto->getGrade();

            /** @var \Wms\Domain\Entity\ProdutoRepository $ProdutoRepository */
            $ProdutoRepository   = $this->em->getRepository('wms:Produto');
            $ProdutoEntity = $ProdutoRepository->findOneBy(array('id' => $codProduto, 'grade' => $grade));
            $endPicking = $ProdutoRepository->getEnderecoPicking($ProdutoEntity);
            if ($endPicking) {
                $endPicking = (int) str_replace('.','',$endPicking);
                if ($endPicking != $endereco) {
                    $this->createXml('error','Endereço de picking não correspondente');
                }
            } else {
                $this->createXml('error','O produto não possui endereço de picking');
            }
            return true;
        }
        return false;
    }

    public function enderecar($enderecoEn, $paleteEn, $enderecoRepo = null, $paleteRepo = null)
    {
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");

        if($enderecoRepo->verificaBloqueioInventario($enderecoEn->getId())) {
            $this->createXml('error','Endereço bloqueado por inventário');
        }

        if ($enderecoRepo->enderecoOcupado($enderecoEn->getId())) {
            $this->createXml('error','Endereço já ocupado');
        }
        $idPalete = $paleteEn->getId();

        //Se for endereco de picking nao existe regra de espaco nem o endereco fica indisponivel
        $enderecoAntigo = $paleteEn->getDepositoEndereco();
        $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();
        $unitizadorEn = $paleteEn->getUnitizador();
        if ($enderecoEn->getNivel() == '0') {
            if ($paleteEn->getRecebimento()->getStatus()->getId() != \wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                throw new \Exception("Só é permitido endereçar no picking quando o recebimento estiver finalizado");
            }
            if ($enderecoAntigo != NULL) {
                $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo,$qtdAdjacente,"LIBERAR");
                $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
            }
            $reservaEstoqueRepo->adicionaReservaEstoque($enderecoEn->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
        } else {
            if ($enderecoRepo->getValidaTamanhoEndereco($enderecoEn->getId(),$unitizadorEn->getLargura(false) * 100) == false) {
                $this->createXml('error','Espaço insuficiente no endereço');
            }
            if ($enderecoAntigo != NULL) {
                $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo,$qtdAdjacente,"LIBERAR");
                $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
            }
            $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoEn,$qtdAdjacente,"OCUPAR");
            $reservaEstoqueRepo->adicionaReservaEstoque($enderecoEn->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
        }

        $paleteEn->setDepositoEndereco($enderecoEn);
        $this->em->persist($paleteEn);
        $this->em->flush();

        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
        $paleteRepo->finalizar(array($idPalete),$idPessoa, OrdemServicoEntity::COLETOR);

        $this->createXml('success','Palete endereçado com sucesso ');
    }

    public function confirmarNovoEnderecoAction()
    {
        $idPalete = $this->_getParam("uma");
        $idEndereco = $this->_getParam("endereco");

        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $this->em->getRepository("wms:Enderecamento\Palete")->find($idPalete);

        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoEn = $this->em->getRepository("wms:Deposito\Endereco")->find($idEndereco);

        $this->view->enderecoAntigo = $paleteEn->getDepositoEndereco()->getDescricao();;
        $this->view->enderecoSelecionado = $enderecoEn->getDescricao();
        $this->view->idEnderecoSelecionado = $enderecoEn->getId();
        $this->view->uma = $idPalete;
    }

    public function efetivarEnderecamentoAction()
    {
        $confirma = $this->_getParam("confirma");
        $idPalete = $this->_getParam("uma");
        $idEndereco = $this->_getParam("endereco");
        $nivel = $this->_getParam("nivel");

        if ($confirma == "N") {
            $this->addFlashMessage('error','Selecione outro endereço de pulmão');
            $this->_redirect('/mobile/enderecamento/ler-codigo-barras');
        }

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoEn = $this->em->getRepository("wms:Deposito\Endereco")->find($idEndereco);
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo = $this->em->getRepository("wms:Enderecamento\Palete");
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        if ($enderecoRepo->enderecoOcupado($enderecoEn->getId())) {
            $this->addFlashMessage('success','Endereço selecionado está ocupado');
            $this->_redirect('/mobile/enderecamento/ler-codigo-barras');
        }

        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $paleteRepo->find($idPalete);

        //REGRA PARA LIBERAR O ENDEREÇO ANTIGO
        $enderecoAntigo = $paleteEn->getDepositoEndereco();
        $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();
        $unitizadorEn = $paleteEn->getUnitizador();
        if ($nivel == '00') {
            if ($paleteEn->getRecebimento()->getStatus()->getId() != \wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                throw new \Exception("Só é permitido endereçar no picking quando o recebimento estiver finalizado");
            }
            if ($enderecoAntigo != NULL) {
                $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo,$qtdAdjacente,"LIBERAR");
                $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
            }
        } else {
            if ($enderecoRepo->getValidaTamanhoEndereco($idEndereco,$unitizadorEn->getLargura(false) * 100) == false) {
                $this->addFlashMessage('error','Espaço insuficiente no endereço');
                $this->_redirect('mobile/enderecamento/ler-codigo-barras');
            }
            if ($enderecoAntigo != NULL) {
                $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo,$qtdAdjacente,"LIBERAR");
                $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
            }
            $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoEn,$qtdAdjacente,"OCUPAR");
            $reservaEstoqueRepo->adicionaReservaEstoque($enderecoEn->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
        }

        $paleteEn->setDepositoEndereco($enderecoEn);
        $this->em->persist($paleteEn);
        $this->em->flush();

        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
        $paleteRepo->finalizar(array($idPalete),$idPessoa, OrdemServicoEntity::COLETOR);

        $this->addFlashMessage('success','Palete Endereçado com sucesso');
        $this->_redirect('/mobile/enderecamento/ler-codigo-barras');
    }
}

