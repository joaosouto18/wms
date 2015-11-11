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
//        if ($paleteEn->getCodStatus() == Palete::STATUS_ENDERECADO) {
//            $this->createXml('error','Palete já endereçado');
//        }
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

        if ($enderecoEn->getIdEstruturaArmazenagem() == Wms\Domain\Entity\Armazenagem\Estrutura\Tipo::BLOCADO) {
            $paleteRepo->alocaEnderecoPaleteByBlocado($paleteEn->getId(), $idEndereco);
        } else {
            $enderecoReservado = $paleteEn->getDepositoEndereco();

            if (($enderecoReservado == NULL) || ($enderecoEn->getId() == $enderecoReservado->getId())) {
                $this->enderecar($enderecoEn,$paleteEn,$enderecoRepo, $paleteRepo);
            } else {
                $this->createXml('info','Confirmar novo endereço','/mobile/enderecamento/confirmar-novo-endereco/uma/' . $paleteEn->getId() . '/endereco/' . $idEndereco);
            }
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

        $this->validaEnderecoPicking($codBarras, $paleteEn, $enderecoEn->getIdCaracteristica());

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
    public function validaEnderecoPicking($endereco, $paleteEn, $caracteristicaEnd)
    {

        //Se for picking do produto entao o nivel poderá ser escolhido
        if ($caracteristicaEnd == '37') {

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

    public function listarPaletesAction()
    {
        ini_set('max_execution_time', 3000);
        $idRecebimento = $this->_getParam("id");

        try {
            $this->getEntityManager()->beginTransaction();
            /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
            $paleteRepo    = $this->em->getRepository('wms:Enderecamento\Palete');
            /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
            $recebimentoRepo    = $this->em->getRepository('wms:Recebimento');
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo    = $this->em->getRepository('wms:Deposito\Endereco');

            $paletesSelecionados = $this->_getParam('palete');

            if ($this->_getParam('imprimir') != null) {
                if (count($paletesSelecionados) > 0) {
                    $Uma = new \Wms\Module\Enderecamento\Printer\UMA('L');
                    $Uma->imprimirPaletes ($paletesSelecionados, $this->getSystemParameterValue("MODELO_RELATORIOS"));
                } else {
                    $this->addFlashMessage('error','Selecione ao menos uma U.M.A');
                }
            }

            if ($this->_getParam('trocarNorma') != null) {
                if (count($paletesSelecionados) >0) {
                    foreach ($paletesSelecionados as $idPalete) {
                        $paleteEn = $paleteRepo->findOneBy(array('id'=>$idPalete));
                        if ($paleteEn != null) {
                            $produto = $paleteEn->getProdutos();
                            $codProduto     = $produto[0]->getProduto()->getId();
                            $grade          = $produto[0]->getProduto()->getGrade();
                            $codRecebimento = $paleteEn->getRecebimento()->getId();
                            if ($paleteEn->getImpresso() == 'N') {
                                $paleteRepo->desfazerPalete($idPalete);
                                $paleteEn->setCodStatus(\Wms\Domain\Entity\Enderecamento\Palete::STATUS_EM_RECEBIMENTO);
                                $this->getEntityManager()->persist($paleteEn);
                                $this->getEntityManager()->flush();
                            }
                            $paleteRepo->alterarNorma($codProduto,$grade,$codRecebimento,$idPalete);
                        }
                    }
                } else {
                    $this->addFlashMessage('error','Selecione ao menos uma U.M.A');
                }
            }

            $produtos = $recebimentoRepo->getProdutosByRecebimento($idRecebimento);

            $paletes = array();
            foreach ($produtos as $produto) {
                $codProduto = $produto['codigo'];
                $grade      = $produto['grade'];

                $tmpPaletes = $paleteRepo->getPaletes($idRecebimento,$codProduto,$grade, false, true);
                foreach ($tmpPaletes as $tmpPalete) {
                    if (($tmpPalete['IND_IMPRESSO'] != 'S') &&
                        ($tmpPalete['COD_SIGLA'] != Palete::STATUS_ENDERECADO) &&
                        ($tmpPalete['COD_SIGLA'] != Palete::STATUS_CANCELADO)) {
                        $tmp = array();
                        $tmp['uma'] = $tmpPalete['UMA'];
                        $tmp['unitizador'] = $tmpPalete['UNITIZADOR'];
                        $tmp['qtd'] = $tmpPalete['QTD'];
                        $tmp['produto'] = $tmpPalete['COD_PRODUTO'] . ' / ' . $tmpPalete['DSC_GRADE'] . ' - ' . $tmpPalete['DSC_PRODUTO'];
                        $tmp['codProduto'] = $tmpPalete['COD_PRODUTO'];
                        $tmp['dscGrade'] = $tmpPalete['DSC_GRADE'];
                        $tmp['dscProduto'] = $tmpPalete['DSC_PRODUTO'];
                        $tmp['idEndereco'] = 0;
                        $tmp['endereco'] = '';
                        $tmp['motivoNaoLiberar'] = '';

                        if ($tmpPalete['QTD_VOL_TOTAL'] > $tmpPalete['QTD_VOL_CONFERIDO']) {
                            $tmp['motivoNaoLiberar'] = 'Aguardando conf. todos volumes';
                        }

                        $paleteEn = $paleteRepo->findOneBy(array('id'=>$tmp['uma']));
                        if ($paleteEn->getDepositoEndereco() == null) {

                            $sugestaoEndereco = $paleteRepo->getSugestaoEnderecoPalete($paleteEn);
			    
                            if ($sugestaoEndereco != null) {
                                $tmp['idEndereco'] = $sugestaoEndereco['COD_DEPOSITO_ENDERECO'];
                                $tmp['endereco'] = $sugestaoEndereco['DSC_DEPOSITO_ENDERECO'];

                                $permiteEnderecar = $enderecoRepo->getValidaTamanhoEndereco($tmp['idEndereco'],$paleteEn->getUnitizador()->getLargura(false) * 100);

                                if ($permiteEnderecar == true) {
                                    $paleteRepo->alocaEnderecoPalete($tmp['uma'],$sugestaoEndereco['COD_DEPOSITO_ENDERECO']);
                                    $this->getEntityManager()->flush();
                                } else {
                                    $tmp['motivoNaoLiberar'] = "Palete " . $tmp['uma'] . " não cabe no endereço " . $tmp['endereco'];
                                }
                            }
                        } else {
                            $tmp['idEndereco'] = $paleteEn->getDepositoEndereco()->getId();
                            $tmp['endereco'] = $paleteEn->getDepositoEndereco()->getDescricao();
                        }

                        if (($tmp['motivoNaoLiberar'] == '') && ($tmp['idEndereco'] == 0)) {
                            $tmp['motivoNaoLiberar'] = 'Sem Sugestão de Endereço';
                        }
                        $paletes[] = $tmp;

                    }
                }
            }

            if (count($paletes) == 0) {
                $this->addFlashMessage('error','Nenhum Palete para imprimir no momento');
            }

            $paletesResumo = $this->getPaletesExibirResumo($idRecebimento);

            $this->view->paletes = $paletesResumo;
            $this->getEntityManager()->commit();
        } catch(Exception $e) {
            $this->getEntityManager()->rollback();
            $this->addFlashMessage('error',$e->getMessage());
        }
    }

    public function getPaletesExibirResumo($codRecebimento){
        $statusEnderecamento = Palete::STATUS_EM_ENDERECAMENTO;
        $SQL = "SELECT LISTAGG(UMA, ', ') WITHIN GROUP (ORDER BY UMA) ALL_UMA,
                       COD_PRODUTO,
                       DSC_GRADE,
                       DSC_PRODUTO,
                       DSC_UNITIZADOR,
                       SUM(QTD) as QTD
                  FROM (
                SELECT DISTINCT PROD.COD_PRODUTO,
                                PROD.DSC_GRADE,
                                PROD.DSC_PRODUTO,
                                PP.UMA,
                                PP.QTD,
                                UN.DSC_UNITIZADOR
                  FROM PALETE_PRODUTO PP
                  LEFT JOIN PALETE P ON P.UMA = PP.UMA
                  LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                  LEFT JOIN UNITIZADOR UN ON UN.COD_UNITIZADOR = P.COD_UNITIZADOR
                 WHERE P.COD_RECEBIMENTO = $codRecebimento
                   AND P.COD_STATUS = $statusEnderecamento
                   AND P.IND_IMPRESSO = 'N'
                   AND P.COD_DEPOSITO_ENDERECO IS NOT NULL)
                GROUP BY COD_PRODUTO, DSC_GRADE, DSC_PRODUTO, DSC_UNITIZADOR";

        $result=$this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;

    }

    public function detalheEnderecoAction()
    {
        ini_set('max_execution_time', 3000);
        $idRecebimento = $this->_getParam("id");

        try {
            $this->getEntityManager()->beginTransaction();
            /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
            $paleteRepo    = $this->em->getRepository('wms:Enderecamento\Palete');
            /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
            $recebimentoRepo    = $this->em->getRepository('wms:Recebimento');
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo    = $this->em->getRepository('wms:Deposito\Endereco');

            $paletesSelecionados = $this->_getParam('palete');

            if ($this->_getParam('imprimir') != null) {
                if (count($paletesSelecionados) >0) {
                    $Uma = new \Wms\Module\Enderecamento\Printer\UMA('L');
                    $Uma->imprimirPaletes ($paletesSelecionados, $this->getSystemParameterValue("MODELO_RELATORIOS"));
                } else {
                    $this->addFlashMessage('error','Selecione ao menos uma U.M.A');
                }
            }

            if ($this->_getParam('trocarNorma') != null) {
                if (count($paletesSelecionados) >0) {
                    foreach ($paletesSelecionados as $idPalete) {
                        $paleteEn = $paleteRepo->findOneBy(array('id'=>$idPalete));
                        if ($paleteEn != null) {
                            $produto = $paleteEn->getProdutos();
                            $codProduto     = $produto[0]->getProduto()->getId();
                            $grade          = $produto[0]->getProduto()->getGrade();
                            $codRecebimento = $paleteEn->getRecebimento()->getId();
                            if ($paleteEn->getImpresso() == 'N') {
                                $paleteRepo->desfazerPalete($idPalete);
                                $paleteEn->setCodStatus(\Wms\Domain\Entity\Enderecamento\Palete::STATUS_EM_RECEBIMENTO);
                                $this->getEntityManager()->persist($paleteEn);
                                $this->getEntityManager()->flush();
                            }
                            $paleteRepo->alterarNorma($codProduto,$grade,$codRecebimento,$idPalete);
                        }
                    }
                } else {
                    $this->addFlashMessage('error','Selecione ao menos uma U.M.A');
                }
            }

            $codProduto = $this->_getParam('produto');
            $grade = $this->_getParam('grade');

            $paletes = array();

            $tmpPaletes = $paleteRepo->getPaletes($idRecebimento,$codProduto,$grade, false, true, true);
            foreach ($tmpPaletes as $tmpPalete) {
                if (($tmpPalete['IND_IMPRESSO'] != 'S') &&
                    ($tmpPalete['COD_SIGLA'] != Palete::STATUS_ENDERECADO) &&
                    ($tmpPalete['COD_SIGLA'] != Palete::STATUS_CANCELADO)) {
                    $tmp = array();
                    $tmp['uma'] = $tmpPalete['UMA'];
                    $tmp['unitizador'] = $tmpPalete['UNITIZADOR'];
                    $tmp['qtd'] = $tmpPalete['QTD'];
                    $tmp['produto'] = $tmpPalete['COD_PRODUTO'] . ' / ' . $tmpPalete['DSC_GRADE'] . ' - ' . $tmpPalete['DSC_PRODUTO'];
                    $tmp['codProduto'] = $tmpPalete['COD_PRODUTO'];
                    $tmp['dscGrade'] = $tmpPalete['DSC_GRADE'];
                    $tmp['dscProduto'] = $tmpPalete['DSC_PRODUTO'];
                    $tmp['idEndereco'] = 0;
                    $tmp['endereco'] = '';
                    $tmp['motivoNaoLiberar'] = '';

                    if ($tmpPalete['QTD_VOL_TOTAL'] > $tmpPalete['QTD_VOL_CONFERIDO']) {
                        $tmp['motivoNaoLiberar'] = 'Aguardando conf. todos volumes';
                    }

                    $paleteEn = $paleteRepo->findOneBy(array('id'=>$tmp['uma']));
                    if ($paleteEn->getDepositoEndereco() == null) {

                        $sugestaoEndereco = $paleteRepo->getSugestaoEnderecoPalete($paleteEn);

                        if ($sugestaoEndereco != null) {
                            foreach($sugestaoEndereco as $sugestao) {

                                $tmp['idEndereco'] = $sugestao['COD_DEPOSITO_ENDERECO'];
                                $tmp['endereco'] = $sugestao['DSC_DEPOSITO_ENDERECO'];

                                $permiteEnderecar = $enderecoRepo->getValidaTamanhoEndereco($tmp['idEndereco'],$paleteEn->getUnitizador()->getLargura(false) * 100);
                                if ($permiteEnderecar == true) {
                                    $paleteRepo->alocaEnderecoPalete($tmp['uma'],$tmp['idEndereco']);
                                    $this->getEntityManager()->flush();
                                    break;
                                }
                            }





//                            if ($permiteEnderecar == true) {
//                                $paleteRepo->alocaEnderecoPalete($tmp['uma'],$sugestaoEndereco['COD_DEPOSITO_ENDERECO']);
//                                $this->getEntityManager()->flush();
//                            } else {
//                                $tmp['motivoNaoLiberar'] = "Palete " . $tmp['uma'] . " não cabe no endereço " . $tmp['endereco'];
//                            }
                        }
                    } else {
                        $tmp['idEndereco'] = $paleteEn->getDepositoEndereco()->getId();
                        $tmp['endereco'] = $paleteEn->getDepositoEndereco()->getDescricao();
                    }

                    if (($tmp['motivoNaoLiberar'] == '') && ($tmp['idEndereco'] == 0)) {
                        $tmp['motivoNaoLiberar'] = 'Sem Sugestão de Endereço';
                    }
                    $paletes[] = $tmp;

                }
            }

            if (count($paletes) == 0) {
                $this->addFlashMessage('error','Nenhum Palete para imprimir no momento');
            }

            $this->view->paletes = $paletes;
            $this->getEntityManager()->commit();
        } catch(Exception $e) {
            $this->getEntityManager()->rollback();
            $this->addFlashMessage('error',$e->getMessage());
        }

    }

    public function imprimirUmaAction()
    {
        $idRecebimento = $this->_getParam("id");
        $Uma = new \Wms\Module\Enderecamento\Printer\UMA('L');

        $dadosPalete = array();
        $dadosPalete['endereco'] = '01.00.000.01';
        $dadosPalete['idUma']    = '1231';
        $dadosPalete['picking']  = '01.00.000.00';
        $dadosPalete['qtd']      = 123;
        $paletesArray = array(0=>$dadosPalete);

        $param = array();
        $param['idRecebimento'] = $idRecebimento;
        $param['codProduto']    = '3908040';
        $param['grade']         = 'PRETO...';

        $param['codProduto']    = '688037';
        $param['grade']         = 'IMB/PRET';

        $param['paletes']       = $paletesArray;
        $param['dataValidade']  = null;

        $Uma->imprimir($param, $this->getSystemParameterValue("MODELO_RELATORIOS"));

        //$this->redirect('ler-codigo-barras','recebimento','mobile',array('idRecebimento'=>$idRecebimento));
    }

    public function movimentacaoAction()
    {
        $codigoBarras = $this->_getParam('codigoBarras');
        $nivel = $this->_getParam('nivel');

        $this->view->codigoBarras = $codigoBarras;
        $this->view->nivel = $nivel;
    }

    public function umaByEnderecoAction()
    {
        $codigoBarras = $this->_getParam('codigoBarras');
        $nivel = $this->_getParam('nivel');
        $this->view->codigoBarras = $codigoBarras;

        try {
            if ($codigoBarras) {
                $LeituraColetor = new LeituraColetor();
                $codigoBarras = $LeituraColetor->retiraDigitoIdentificador($codigoBarras);
            }

            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
            $result = $estoqueRepo->getProdutoByNivel($codigoBarras, $nivel, false);

            if ($result == NULL) {
                throw new \Exception ("Endereço selecionado está vazio");
            } else {
                $idEstoque = $result[0]['id'];

                /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
                $reservaEstoqueRepo = $this->getEntityManager()->getRepository('wms:Ressuprimento\ReservaEstoque');
                $verificaReservaSaida = $reservaEstoqueRepo->findBy(array('endereco' => $result[0]['idEndereco'], 'tipoReserva' => 'S', 'atendida' => 'N'));

                if (count($verificaReservaSaida) > 0) {
                    throw new \Exception ("Existe Reserva de Saída para esse endereço que ainda não foi atendida!");
                }

                if ($result[0]['uma']) {
                    $this->_redirect('/mobile/enderecamento/endereco-uma/cb/' . $idEstoque);
                } else {
                    $this->_redirect('/mobile/enderecamento/endereco-produto/cb/' . $idEstoque );
                }
            }
        }  catch (\Exception $e) {
            throw new \Exception ($e->getMessage());
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

    public function enderecoDestinoAction()
    {
        $this->view->codigoBarrasUMA = $this->_getParam('codigoBarrasUMA');
        $this->view->etiquetaProduto = $this->_getParam('etiquetaProduto');
        $this->view->idEstoque = $idEstoque = $this->_getParam('cb');

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");

        /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueEn */
        $estoqueEn = $estoqueRepo->findOneBy(array('id' => $idEstoque));
        $this->view->qtd = $qtd = $estoqueEn->getQtd();
    }

    public function confirmaEnderecamentoAction()
    {
        $params = array();
        $params['qtd'] = $this->_getParam('qtd');
        $params['uma'] = $this->_getParam('uma');
        $params['etiquetaProduto'] = $this->_getParam('etiquetaProduto');
        $params['idEstoque'] = $this->_getParam('cb');
        $params['novoEndereco'] = $this->_getParam('novoEndereco');
        $params['nivel'] = $this->_getParam('nivel');

        try {

            if ($params['novoEndereco']) {
                $LeituraColetor = new LeituraColetor();
                $params['novoEndereco'] = $LeituraColetor->retiraDigitoIdentificador($params['novoEndereco']);
            }

            $params['novoEndereco'] = $this->getEnderecoNivel($params['novoEndereco'], $params['nivel']);

            /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
            $paleteRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Palete');

            if (isset($params['uma']) && !empty($params['uma'])) {
                $LeituraColetor = new LeituraColetor();
                $params['uma'] = $LeituraColetor->retiraDigitoIdentificador($params['uma']);
            } else if (isset($params['etiquetaProduto']) && !empty($params['etiquetaProduto'])) {
                $LeituraColetor = new LeituraColetor();
                $params['etiquetaProduto'] = $LeituraColetor->analisarCodigoBarras($params['etiquetaProduto']);
            }

            $paleteRepo->updateUmaByEndereco($params);
            $this->addFlashMessage('success', 'Endereço alterado com sucesso!');
            $this->_redirect('/mobile/enderecamento/movimentacao');

        }  catch (\Exception $e) {
            throw new \Exception ($e->getMessage());
        }
    }

    private function getEnderecoNivel($dscEndereco, $nivel)
    {
        if (strlen($dscEndereco) < 8) {
            $rua = 0;
            $predio = 0;
            $nivel = 0;
            $apartamento = 0;
        } else {
            $dscEndereco = str_replace('.','',$dscEndereco);
            if (strlen($dscEndereco) == 8){
                $tempEndereco = "0" . $dscEndereco;
            } else {
                $tempEndereco = $dscEndereco;
            }
            $rua = intval( substr($tempEndereco,0,2));
            $predio = intval(substr($tempEndereco,2,3));
            $apartamento = intval(substr($tempEndereco,7,2));
        }

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
        return $enderecoRepo->findOneBy(array('rua' => $rua, 'predio' => $predio, 'apartamento' => $apartamento, 'nivel' => $nivel));
    }


}

