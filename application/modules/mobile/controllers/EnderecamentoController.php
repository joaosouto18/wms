<?php
use Wms\Controller\Action,
    \Wms\Util\Endereco as EnderecoUtil,
    Wms\Domain\Entity\Enderecamento\Palete as Palete,
    Wms\Util\Coletor as ColetorUtil,
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
        $codigoBarrasEndereco = $this->_getParam('codigoBarras');
        $idCaracteristicaPicking = \Wms\Domain\Entity\Deposito\Endereco::PICKING;
        $idCaracteristicaPickingRotativo = \Wms\Domain\Entity\Deposito\Endereco::PICKING_DINAMICO;

        if ($codigoBarrasEndereco) {
            try {
                $codigoBarras = ColetorUtil::retiraDigitoIdentificador($codigoBarrasEndereco);

                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
                $endereco = EnderecoUtil::formatar($codigoBarras);
                /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
                $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));

                if (empty($enderecoEn)) {
                    throw new Exception('Endereço não encontrado');
                }

                $caracteristicaEndereco = $enderecoEn->getCaracteristica()->getId();

                if ($caracteristicaEndereco != $idCaracteristicaPicking && $caracteristicaEndereco != $idCaracteristicaPickingRotativo) {
                    throw new Exception('Código bipado não é um endereço de picking');
                }

                $result = $enderecoRepo->getProdutoByEndereco($endereco, false);

                if (count($result) == 0) {
                    throw new Exception('Nenhum produto encontrado para este picking');
                }

                $existeEstoque = false;

                foreach ($result as $estoque) {
                    $codProduto = $estoque['codProduto'];
                    $grade = $estoque['grade'];

                    /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
                    $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
                    $resultado = $estoqueRepo->getExisteEnderecoPulmao($codProduto, $grade);

                    if ($resultado == true) {
                        $existeEstoque = true;
                        break;
                    }
                }

                if ($existeEstoque == false) {
                    throw new Exception('O produto não possui endereço de estoque no pulmão');
                }

                $contagem = $this->em->getRepository("wms:Enderecamento\RelatorioPicking")->findBy(array('depositoEndereco' => $enderecoEn));

                if ($contagem != NULL) {
                    throw new Exception('O endereço informado já foi bipado');
                } else {
                    $atividadeEntity = $this->em->getReference('wms:Atividade', \Wms\Domain\Entity\Atividade::RESSUPRIMENTO);
                    $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
                    $pessoaEntity = $this->em->getReference('wms:Pessoa', $idPessoa);

                    $os = new OrdemServicoEntity();
                    $os->setDataInicial(new DateTime());
                    $os->setAtividade($atividadeEntity);
                    $os->setDscObservacao('Ressuprimento Manual Preventivo');
                    $os->setPessoa($pessoaEntity);
                    $os->setFormaConferencia('C');
                    $this->em->persist($os);

                    $contagem = new \Wms\Domain\Entity\Enderecamento\RelatorioPicking();
                    $contagem->setDepositoEndereco($enderecoEn);
                    $contagem->setCodProduto($codProduto);
                    $contagem->setGrade($grade);
                    $contagem->setOs($os);
                    $this->em->persist($contagem);

                    $this->em->flush();
                }

                $this->addFlashMessage('success', "O endereço $endereco foi adicionado");
            } catch (Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
                $this->_redirect('/mobile/enderecamento/leitura-picking');
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
            if (!empty($relatorioEn)) {
                $this->getEntityManager()->remove($relatorioEn);
                $this->getEntityManager()->flush();
                $this->addFlashMessage('info', "O endereço $removerId foi removido.");
            }
            $this->_redirect('/mobile/enderecamento/listar-picking');
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
        try {
            $idPalete = $this->_getParam("uma");

            if (!isset($idPalete)) {
                $this->createXml('error', 'Nenhum Palete Informado');
            }

            $idPalete = ColetorUtil::retiraDigitoIdentificador($idPalete);

            /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
            $paleteRepo = $this->em->getRepository("wms:Enderecamento\Palete");
            $paleteEn = $paleteRepo->find($idPalete);

            if ($paleteEn == NULL) {
                throw new Exception("Palete não encontrado");
            }
            if ($paleteEn->getCodStatus() == Palete::STATUS_CANCELADO) {
                throw new Exception("Palete cancelado");
            } elseif ($paleteEn->getCodStatus() == Palete::STATUS_ENDERECADO) {
                $endereco = $paleteEn->getDepositoEndereco()->getDescricao();
                throw new Exception("Palete $idPalete já está endereçado em $endereco");
            }

            $this->validarEndereco($paleteEn, $paleteRepo);
        } catch (Exception $e) {
            $this->createXml('error', $e->getMessage());
        }
    }

    public function validarEndereco($paleteEn, $paleteRepo)
    {
        try {
            $enderecoSD = ColetorUtil::retiraDigitoIdentificador($this->_getParam("endereco"));

            if (empty($enderecoSD)) {
                $this->createXml('error', 'Nenhum Endereço Informado');
            }

            $idCaracteristicaPicking = \Wms\Domain\Entity\Deposito\Endereco::PICKING;
            $idCaracteristicaPickingRotativo = \Wms\Domain\Entity\Deposito\Endereco::PICKING_DINAMICO;

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");

            $endereco = EnderecoUtil::formatar($enderecoSD);
            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));

            if (empty($enderecoEn)) {
                throw new Exception("Endereço $enderecoSD não encontrado");
            }

            if ($enderecoEn->getIdCaracteristica() == $idCaracteristicaPicking || $enderecoEn->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                $elementos = array();
                $elementos[] = array('name' => 'nivelzero', 'value' => true);
                $elementos[] = array('name' => 'rua', 'value' => $enderecoEn->getRua());
                $elementos[] = array('name' => 'predio', 'value' => $enderecoEn->getPredio());
                $elementos[] = array('name' => 'apartamento', 'value' => $enderecoEn->getApartamento());
                $elementos[] = array('name' => 'uma', 'value' => $paleteEn->getId());
                $this->createXml('info', 'Escolha um nível', null, $elementos);
            }

            $this->validaEnderecoPicking($paleteEn, $enderecoEn->getIdCaracteristica(), $enderecoEn);


            $enderecoReservado = $paleteEn->getDepositoEndereco();

            if (($enderecoReservado == NULL) || ($enderecoEn->getId() == $enderecoReservado->getId())) {
                $this->enderecar($enderecoEn, $paleteEn, $enderecoRepo, $paleteRepo);
            } else {
                $this->createXml('info', 'Confirmar novo endereço', '/mobile/enderecamento/confirmar-novo-endereco/uma/' . $paleteEn->getId() . '/endereco/' . $enderecoEn->getId());
            }

        } catch (Exception $e) {
            throw $e;
        }
    }

    public function validaNivelAction()
    {
        try {
            $arrEndereco = array(
                'rua' => $this->_getParam("rua"),
                'predio' => $this->_getParam("predio"),
                'nivel' => $this->_getParam("nivel"),
                'apartamento' => $this->_getParam("apartamento"),
            );

            $endereco = EnderecoUtil::formatar($arrEndereco);

            $idPalete = $this->_getParam("uma");
            $capacidadePicking = $this->_getParam('capacidadePicking');


            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");

            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));
            if (empty($enderecoEn)) {
                $this->createXml('error', 'Endereço não encontrado');
            }

            $paleteRepo = $this->em->getRepository("wms:Enderecamento\Palete");
            /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
            $paleteEn = $paleteRepo->find($idPalete);

            $ppEn = $paleteEn->getProdutos()[0];
            if ($ppEn->getCodProdutoEmbalagem() != null) {
                $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');

                $embalagemEn = $embalagemRepo->findOneBy(array(
                    'codProduto' => $ppEn->getCodProduto(),
                    'grade'=> $ppEn->getGrade(),
                    'isPadrao'=> 'S'
                ));
                if ($embalagemEn == null) {
                    throw new \Exception("O produto desta UMA não possui embalagem padrão de recebimento cadastrada");
                }
                $capacidadePicking = $embalagemEn->getQuantidade() * $capacidadePicking;
            }


            $enderecoReservado = null;
            if (isset($paleteEn) && !empty($paleteEn)) {
                $this->validaEnderecoPicking($paleteEn, $enderecoEn->getIdCaracteristica(), $enderecoEn, $capacidadePicking);
                $enderecoReservado = $paleteEn->getDepositoEndereco();
            }

            if (($enderecoReservado == null) || ($enderecoEn->getId() == $enderecoReservado->getId())) {
                $this->enderecar($enderecoEn, $paleteEn, $enderecoRepo, $paleteRepo);
            } else {
                $this->createXml('info', 'Confirmar novo endereço', '/mobile/enderecamento/confirmar-novo-endereco/uma/' . $idPalete . '/endereco/' . $enderecoEn->getId());
            }
        } catch (Exception $e) {
            $this->createXml('error', $e->getMessage());
        }

    }

    /**
     *  Verifica se o endereço passado é o endereço de picking do produto
     * @param $codBarras
     * @return int
     */
    public function validaEnderecoPicking($paleteEn, $caracteristicaEnd, $enderecoEn = null, $capacidadePicking = 0)
    {
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository $paleteProdutoRepo */
        $paleteProdutoRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\PaleteProduto');
        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');

        $idCaracteristicaPicking = \Wms\Domain\Entity\Deposito\Endereco::PICKING;
        $idCaracteristicaPickingRotativo = \Wms\Domain\Entity\Deposito\Endereco::PICKING_DINAMICO;

        //Se for picking do produto entao o nivel poderá ser escolhido
        //@TODO Validar se existe Picking Rotativo cadastrado para o produto.
        //Se sim, o sistema deverá exibir o endereço e só permitir armazenar no endereço cadastrado e permitir alterar a capacidade do picking (apenas para picking Dinâmico);
        //Se o produto não possuir endereço de Picking Dinâmico cadastrado, o sistema deverá solicitar a quantidade a ser endereçada e a capacidade do picking.

        if ($caracteristicaEnd == $idCaracteristicaPickingRotativo || $caracteristicaEnd == $idCaracteristicaPicking) {
            $produtosEn = $paleteProdutoRepo->getProdutoByUma($paleteEn->getId());
            foreach ($produtosEn as $produto) {
                $embalagens = $embalagemRepo->findBy(array('codProduto' => $produto->getId(), 'grade' => $produto->getGrade()));
                foreach ($embalagens as $embalagemEn) {
                    $enderecoEmbalagem = $embalagemEn->getEndereco();
                    if (isset($enderecoEmbalagem) && !empty($enderecoEmbalagem)) {
                        $caracteristicaEndAntigo = $embalagemEn->getEndereco()->getCaracteristica()->getId();
                        if (($caracteristicaEndAntigo == $idCaracteristicaPicking) && ($embalagemEn->getEndereco()->getId() != $enderecoEn->getId())) {
                            $this->createXml('error','Produto Ja cadastrado no Picking '.$embalagemEn->getEndereco()->getDescricao());
                        }
                    }

                    if ($caracteristicaEnd == $idCaracteristicaPickingRotativo) {
                        $embalagemEn->setEndereco($enderecoEn);
                        $embalagemEn->setCapacidadePicking($capacidadePicking);
                        $this->getEntityManager()->persist($embalagemEn);
                    }
                }
                $this->getEntityManager()->flush();
            }
            return true;
        }
        return false;
    }

    public function enderecar($enderecoEn, $paleteEn, $enderecoRepo = null, $paleteRepo = null)
    {
        try {
            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
            $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
            /** @var \Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository $paleteProdutoRepo */
            $paleteProdutoRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\PaleteProduto");
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");

            if ($enderecoRepo->verificaBloqueioInventario($enderecoEn->getId())) {
                $this->createXml('error', 'Endereço bloqueado por inventário');
            }

            $idPalete = $paleteEn->getId();

            //Se for endereco de picking nao existe regra de espaco nem o endereco fica indisponivel
            $enderecoAntigo = $paleteEn->getDepositoEndereco();
            $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();
            $unitizadorEn = $paleteEn->getUnitizador();
            $idCaracteristicaPicking = \Wms\Domain\Entity\Deposito\Endereco::PICKING;
            $idCaracteristicaPickingRotativo = \Wms\Domain\Entity\Deposito\Endereco::PICKING_DINAMICO;

            if ($enderecoEn->getIdCaracteristica() == $idCaracteristicaPicking || $enderecoEn->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                if ($paleteEn->getRecebimento()->getStatus()->getId() != \wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                    $this->createXml('error', "Só é permitido endereçar no picking quando o recebimento estiver finalizado");
                }
                if ($enderecoAntigo != NULL) {
                    $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo, $qtdAdjacente, "LIBERAR" , $paleteEn->getId());
                    $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(), $paleteEn->getProdutosArray(), "E", "U", $paleteEn->getId());
                }
                $reservaEstoqueRepo->adicionaReservaEstoque($enderecoEn->getId(), $paleteEn->getProdutosArray(), "E", "U", $paleteEn->getId());
            } else {
                if ($enderecoRepo->enderecoOcupado($enderecoEn->getId())) {
                    $this->createXml('error', 'Endereço já ocupado');
                }
                if ($enderecoRepo->getValidaTamanhoEndereco($enderecoEn->getId(), $unitizadorEn->getLargura(false) * 100) == false) {
                    $this->createXml('error', 'Espaço insuficiente no endereço');
                }
                if ($enderecoAntigo != NULL) {
                    $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo, $qtdAdjacente, "LIBERAR", $paleteEn->getId(), $paleteEn->getId());
                    $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(), $paleteEn->getProdutosArray(), "E", "U", $paleteEn->getId());
                }
                $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoEn, $qtdAdjacente, "OCUPAR", $paleteEn->getId());
                $reservaEstoqueRepo->adicionaReservaEstoque($enderecoEn->getId(), $paleteEn->getProdutosArray(), "E", "U", $paleteEn->getId());
            }

            $paleteEn->setDepositoEndereco($enderecoEn);
            $this->em->persist($paleteEn);
            $this->em->flush();
            $paleteProdutoEn = $paleteProdutoRepo->findOneBy(array('uma' => $idPalete));
            $validade = $paleteProdutoEn->getValidade();
            $dataValidade = null;
            if (isset($validade) && !empty($validade) && !is_null($validade))
                $dataValidade['dataValidade'] = $paleteProdutoEn->getValidade()->format('Y-m-d');

            $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
            $paleteRepo->finalizar(array($idPalete), $idPessoa, OrdemServicoEntity::COLETOR, $dataValidade);

            $this->createXml('success', 'Palete endereçado com sucesso ');
        } catch (Exception $e) {
            throw $e;
        }
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
        /** @var \Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository $paleteProdutoRepo */
        $paleteProdutoRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\PaleteProduto");
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");

        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $paleteRepo->find($idPalete);

        //REGRA PARA LIBERAR O ENDEREÇO ANTIGO
        $enderecoAntigo = $paleteEn->getDepositoEndereco();
        $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();
        $unitizadorEn = $paleteEn->getUnitizador();

        $paleteProdutoEn = $paleteProdutoRepo->findOneBy(array('uma' => $idPalete));
        $validade = $paleteProdutoEn->getValidade();
        $dataValidade = null;

        if (isset($validade) && !empty($validade) && !is_null($validade))
            $dataValidade['dataValidade'] = $paleteProdutoEn->getValidade()->format('Y-m-d');

        if ($enderecoEn->getIdCaracteristica() == \Wms\Domain\Entity\Deposito\Endereco::PICKING) {

            $pickingProduto = $produtoRepo->getEnderecoPicking($paleteProdutoEn->getProduto(),"ID");
            $pickingCorreto = false;
            foreach ($pickingProduto as $picking) {
                if ($picking == $enderecoEn->getId()) {
                    $pickingCorreto = true;
                    continue;
                }
            }

            if ($pickingCorreto == false) {
                $this->addFlashMessage('error'," O endereço " . $enderecoEn->getDescricao() . " não corresponde ao picking do produto");
                $this->_redirect('/mobile/enderecamento/ler-codigo-barras');

            }

            if ($paleteEn->getRecebimento()->getStatus()->getId() != \wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                $this->addFlashMessage('error','Só é permitido endereçar no picking quando o recebimento estiver finalizado');
                $this->_redirect('/mobile/enderecamento/ler-codigo-barras');
            }

        } else {

            if ($enderecoRepo->enderecoOcupado($enderecoEn->getId())) {
                $this->addFlashMessage('error','Endereço selecionado está ocupado');
                $this->_redirect('/mobile/enderecamento/ler-codigo-barras');
            }

            if ($enderecoRepo->getValidaTamanhoEndereco($idEndereco,$unitizadorEn->getLargura(false) * 100) == false) {
                $this->addFlashMessage('error','Espaço insuficiente no endereço');
                $this->_redirect('mobile/enderecamento/ler-codigo-barras');
            }

        }

        if ($enderecoAntigo != NULL) {
            $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo,$qtdAdjacente,"LIBERAR", $paleteEn->getId());
            $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco()->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());
        }

        $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoEn,$qtdAdjacente,"OCUPAR");
        $reservaEstoqueRepo->adicionaReservaEstoque($enderecoEn->getId(),$paleteEn->getProdutosArray(),"E","U",$paleteEn->getId());

        $paleteEn->setDepositoEndereco($enderecoEn);
        $this->em->persist($paleteEn);
        $this->em->flush();

        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
        $paleteRepo->finalizar(array($idPalete),$idPessoa, OrdemServicoEntity::COLETOR, $dataValidade);

        if ($this->getSystemParameterValue('IND_LIBERA_FATURAMENTO_NF_RECEBIMENTO_ERP') == 'S') {
            if ($this->getSystemParameterValue('STATUS_RECEBIMENTO_ENDERECADO') == 'S') {
                /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
                $recebimentoRepo = $this->getEntityManager()->getRepository("wms:Recebimento");

                $idRecebimento = $paleteEn->getRecebimento()->getId();

                if (empty($recebimentoRepo->checkRecebimentoEnderecado($idRecebimento))) {
                    /** @var \Wms\Domain\Entity\NotaFiscal[] $arrNotasEn */
                    $arrNotasEn = $this->_em->getRepository("wms:NotaFiscal")->findBy(['recebimento' => $idRecebimento]);
                    $recebimentoRepo->liberaFaturamentoNotaErp($arrNotasEn);
                }
            }
        }

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

            $paletesSelecionados = $this->_getParam('palete');

            $repositorios = array(
                'enderecoRepo'            => $this->getEntityManager()->getRepository("wms:Deposito\Endereco"),
                'normaPaletizacaoRepo'    => $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao"),
                'estoqueRepo'             => $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque"),
                'reservaEstoqueRepo'      => $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque"),
                'produtoRepo'             => $this->getEntityManager()->getRepository('wms:Produto'),
                'recebimentoRepo'         => $recebimentoRepo,
                'modeloEnderecamentoRepo' => $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo'),
            );

            //METODO PARA IMPRIMIR OS PALETES
            if ($this->_getParam('imprimir') != null) {
                if (count($paletesSelecionados) > 0) {
                    $Uma = new \Wms\Module\Enderecamento\Printer\UMA('L');
                    $Uma->imprimirPaletes ($paletesSelecionados, $this->getSystemParameterValue("MODELO_RELATORIOS"));
                } else {
                    $this->addFlashMessage('error','Selecione ao menos uma U.M.A');
                }
            }

            //METODO PARA ALTERAR A NORMA DE PALETIZAÇÂO CASO CONFERIDO ERRADO
            if ($this->_getParam('trocarNorma') != null) {
                if (count($paletesSelecionados) >0) {
                    foreach ($paletesSelecionados as $paletes) {
                        $idPaletes = explode(',',$paletes);
                        foreach ($idPaletes as $idPalete) {
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
                    }
                } else {
                    $this->addFlashMessage('error','Selecione ao menos uma U.M.A');
                }
            }

            //IDENTIFICO OS PRODUTOS DO RECEBIMENTO
            $produtos = $recebimentoRepo->getProdutosByRecebimento($idRecebimento);

            //GERAR OS PALETES PARA CADA PRODUTO DO RECEBIMENTO INDIVIDUALMENTE
            foreach ($produtos as $produto) {
                $codProduto = $produto['codigo'];
                $grade      = $produto['grade'];

                //PEGANDO OS PALETES GERADOS DO PRODUTO E ALOCANDO UM ENDEREÇO
                $paletes = $paleteRepo->getPaletes($idRecebimento,$codProduto,$grade,false,$tipoEnderecamento = 'A');
                $paleteRepo->alocaEnderecoAutomaticoPaletes($paletes,$repositorios);
            }

            $paletesResumo = $this->getPaletesExibirResumo($idRecebimento);
            if (count($paletesResumo) == 0) {
                $this->addFlashMessage('error','Nenhum Palete para imprimir no momento');
            }

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
                       COUNT(DISTINCT UMA) as QTD_UMA,
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
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PROD.COD_PRODUTO = PE.COD_PRODUTO AND PROD.DSC_GRADE = PE.DSC_GRADE
                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = PE.COD_PRODUTO AND PV.DSC_GRADE = PROD.DSC_GRADE
                 WHERE P.COD_RECEBIMENTO = $codRecebimento
                   AND P.COD_STATUS = $statusEnderecamento
                   AND P.IND_IMPRESSO = 'N'
                   AND P.COD_DEPOSITO_ENDERECO IS NOT NULL
                   AND PE.DTH_INATIVACAO IS NULL
                   AND PV.DTH_INATIVACAO IS NULL)
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
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo    = $this->em->getRepository('wms:Deposito\Endereco');

            $repositorios = array(
                'normaPaletizacaoRepo'    => $this->getEntityManager()->getRepository("wms:Produto\NormaPaletizacao"),
                'estoqueRepo'             => $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque"),
                'reservaEstoqueRepo'      => $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque"),
                'produtoRepo'             => $this->getEntityManager()->getRepository('wms:Produto'),
                'recebimentoRepo'         => $this->getEntityManager()->getRepository('wms:Recebimento'),
                'modeloEnderecamentoRepo' => $this->getEntityManager()->getRepository('wms:Enderecamento\Modelo'));

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

                        $sugestaoEndereco = $paleteRepo->getSugestaoEnderecoPalete($paleteEn, $repositorios);

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
            if (empty($codigoBarras))
                throw new \Exception ("Necessário informar o Endereço");

            if (is_null($nivel) || $nivel == '')
                throw new \Exception ("Necessário informar o Nivel");

            $codigoBarras = ColetorUtil::retiraDigitoIdentificador($codigoBarras);

            $endereco = EnderecoUtil::formatar($codigoBarras);

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
            $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));

            if (empty($enderecoEn))
                throw new \Exception ("Endereço não encontrado");

            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
            $result = $estoqueRepo->getProdutoByNivel($endereco, $nivel);

            if ($result == NULL) {
                throw new \Exception ("Endereço selecionado está vazio");
            } else {
                if ($enderecoEn->isBloqueadaSaida()) {
                    throw new Exception('error', "O endereço $endereco está bloqueado para: Saída" );
                }
                $idEstoque = $result[0]['id'];

                if ($result[0]['uma']) {
                    $this->_redirect('/mobile/enderecamento/endereco-uma/cb/' . $idEstoque . '/end/' . $codigoBarras . '/nivelAntigo/' . $nivel);
                } else {
                    $this->_redirect('/mobile/enderecamento/endereco-produto/cb/' . $idEstoque . '/end/' . $codigoBarras . '/nivelAntigo/' . $nivel);
                }
            }
        }  catch (\Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
            $this->_redirect('mobile/enderecamento/movimentacao');
        }

    }

    public function enderecoProdutoAction()
    {
        $idEstoque = $this->_getParam('cb');
        $this->view->cb = $idEstoque;
        $this->view->end = $this->_getParam('end');
        $this->view->nivel = $this->_getParam('nivel');

        /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueEn */
        $estoqueEn = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque")->findOneBy(array('id'=>$idEstoque));

        $rua         = $estoqueEn->getDepositoEndereco()->getRua();
        $predio      = $estoqueEn->getDepositoEndereco()->getPredio();
        $nivel       = $estoqueEn->getDepositoEndereco()->getNivel();
        $apartamento = $estoqueEn->getDepositoEndereco()->getApartamento();

        $this->view->rua = $rua;
        $this->view->predio = $predio;
        $this->view->nivel = $nivel;
        $this->view->apartamento = $apartamento;

    }

    public function enderecoUmaAction()
    {
        $idEstoque = $this->_getParam('cb');
        $this->view->cb = $idEstoque;
        $this->view->end = $this->_getParam('end');
        $this->view->nivel = $this->_getParam('nivel');

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
        $this->view->codigoBarrasUMA = $codBarrasUma = ColetorUtil::retiraDigitoIdentificador($this->_getParam('codigoBarrasUMA'));
        $this->view->etiquetaProduto = $codBarras = $this->_getParam('etiquetaProduto');
        $this->view->idEstoque = $idEstoque = $this->_getParam('cb');
        $enderecoParam = $this->_getParam('end');
        $nivel = $this->_getParam('nivelAntigo');

        try {
            $endereco = EnderecoUtil::formatar($enderecoParam, null, null, $nivel);

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');

            /** @var \Wms\Domain\Entity\Deposito\Endereco $endereco */
            $endereco = $enderecoRepo->findOneBy(array('descricao' => $endereco));

            $embalagemEn = null;

            $produtoEn = null;

            if (!empty($codBarrasUma)) {
                /** @var \Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository $paleteProdutoRepo */
                $paleteProdutoRepo = $this->em->getRepository('wms:Enderecamento\PaleteProduto');
                $produtoVolumeRepo = $this->getEntityManager()->getRepository('wms:Produto\Volume');

                /** @var \Wms\Domain\Entity\Enderecamento\PaleteProduto $paleteProduto */
                $paleteProduto = $paleteProdutoRepo->findOneBy(array('uma' => $codBarrasUma));
                if (empty($paleteProduto))
                    throw new Exception("UMA $codBarrasUma não encontrada!");

                $produtoEn = $paleteProduto->getProduto();
                $codProduto = $paleteProduto->getCodProduto();
                $grade = $paleteProduto->getGrade();
                $produtoVolumeEntity = $produtoVolumeRepo->findOneBy(array('codProduto' => $codProduto, 'grade' => $grade));

                if (empty($produtoVolumeEntity))
                    $embalagemEn = $paleteProduto->getEmbalagemEn();

            } else if (!empty($codBarras)) {
                $LeituraColetor = new \Wms\Service\Coletor();
                $produtoRepo = $this->getEntityManager()->getRepository('wms:Produto');
                $codBarras = $LeituraColetor->adequaCodigoBarras($codBarras);

                $produtoEn = $produtoRepo->getEmbalagensByCodBarras($codBarras);

                if (empty($produtoEn['produto'])) throw new Exception("Nenhum produto encontrado com esse código de barras: $codBarras");

                $produtoEn = $produtoEn['produto'];

                $codProduto = $produtoEn->getId();
                $grade = $produtoEn->getGrade();

                $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
                /** @var \Wms\Domain\Entity\Produto\Embalagem $embalagemEn */
                $embalagemEn = $embalagemRepo->findOneBy(array('codigoBarras' => $codBarras));
            }

            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->em->getRepository('wms:Enderecamento\Estoque');

            /** @var \Wms\Domain\Entity\Enderecamento\Estoque $estoqueEn */
            $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $endereco, 'codProduto' => $codProduto, 'grade' => $grade));
            if (empty($estoqueEn))
                throw new Exception("Não foi encontrado o estoque com endereco " . $endereco->getDescricao() . " produto " . $codProduto . " grade " . $grade);

            if ($embalagemEn != null) {
                $qtd = floor($estoqueEn->getQtd() / $embalagemEn->getQuantidade());
                $qtdReal = floor($estoqueEn->getQtd() / $embalagemEn->getQuantidade()) * $embalagemEn->getQuantidade();
                $qtdEmbalagem = $embalagemEn->getQuantidade();
            } else {
                $qtd = $estoqueEn->getQtd();
                $qtdReal = $estoqueEn->getQtd();
                $qtdEmbalagem = 1;
            }
            $this->view->qtd = $qtd;
            $this->view->qtdReal = $qtdReal;
            $this->view->qtdEmbalagem = $qtdEmbalagem;
            $this->view->controlaLote = ($produtoEn->getIndControlaLote() == 'S');

/*
            $idEndereco = $endereco->getId();

            $SQL = "SELECT RE.*
                  FROM RESERVA_ESTOQUE RE
                 INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON RE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                 WHERE RE.COD_DEPOSITO_ENDERECO = $idEndereco
                   AND REP.COD_PRODUTO = '$codProduto'
                   AND REP.DSC_GRADE = '$grade'
                   AND RE.TIPO_RESERVA = 'S'
                   AND RE.IND_ATENDIDA = 'N'";
            $verificaReservaSaida = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
            if (count($verificaReservaSaida) > 0) {
                throw new \Exception ("Existe reserva de saída para esse produto neste endereço que ainda não foi atendida!");
            }
*/
        } catch (Exception $e) {
            $this->addFlashMessage('error',$e->getMessage());
            $this->redirect('movimentacao');
        }
    }

    public function confirmaEnderecamentoAction()
    {
        ini_set('max_execution_time', 3000);
        $params = array();
        $qtd = $this->_getParam('qtd');
        $params['uma'] = $this->_getParam('uma');
        $params['etiquetaProduto'] = $this->_getParam('etiquetaProduto');
        $params['idEstoque'] = $this->_getParam('cb');
        $params['lote'] = $this->_getParam('lote');
        $enderecoNovo = $this->_getParam('novoEndereco');
        $nivelNovo = $this->_getParam('nivel');
        $enderecoAntigo = $this->_getParam('end');
        $nivelAntigo = $this->_getParam('nivelAntigo');

        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        /** @var \Wms\Domain\Entity\Produto\VolumeRepository $volumeRepo */
        $volumeRepo = $this->getEntityManager()->getRepository('wms:Produto\Volume');

        $idCaracteristicaPicking = \Wms\Domain\Entity\Deposito\Endereco::PICKING;
        $idCaracteristicaPickingRotativo = \Wms\Domain\Entity\Deposito\Endereco::PICKING_DINAMICO;

        try {

            $this->getEntityManager()->beginTransaction();

            if ($enderecoNovo) {
                $enderecoNovo = ColetorUtil::retiraDigitoIdentificador($enderecoNovo);
            }

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');

            $enderecoFrmt = EnderecoUtil::formatar($enderecoAntigo, null, null, $nivelAntigo);

            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoAntigo */
            $enderecoAntigo = $enderecoRepo->findOneBy(array('descricao' => $enderecoFrmt));
            if (empty($enderecoAntigo)) {
                throw new Exception('Endereço antigo não encontrado!');
            }

            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');
            $params['tipo'] = \Wms\Domain\Entity\Enderecamento\HistoricoEstoque::TIPO_TRANSFERENCIA;


            $dthEntrada = new \DateTime();
            $params['dthEntrada'] = $dthEntrada;

            if (isset($params['uma']) && !empty($params['uma'])) {
                $estoqueEn = $estoqueRepo->findBy(array('uma' => $params['uma'], 'depositoEndereco' => $enderecoAntigo));
                /** @var Estoque $estoque */
                foreach ($estoqueEn as $estoque) {
                    //INSERE NOVO ESTOQUE
                    $params['qtd'] = $qtd;
                    $params['unitizador'] = $estoque->getUnitizador();
                    $params['dthEntrada'] = $estoque->getDtPrimeiraEntrada();

                    $enderecoNovoFrmt = EnderecoUtil::formatar($enderecoNovo, null, null, $nivelNovo);

                    /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoNovoEn */
                    $params['endereco'] = $enderecoNovoEn = $enderecoRepo->findOneBy(array('descricao' => $enderecoNovoFrmt));
                    if (empty($enderecoNovoEn))
                        throw new Exception('Novo Endereço não Encontrado!');


                    /** @var \Wms\Domain\Entity\Produto $produtoEn */
                    $produtoEn = $params['produto'] = $estoque->getProduto();
                    $params['embalagem'] = $embalagemEn = $estoque->getProdutoEmbalagem();
                    $params['volume'] = $volumeEn = $estoque->getProdutoVolume();

                    if ($produtoEn->getIndControlaLote() == 'S') {
                        $params['lote'] = $estoque->getLote();
                    }

                    $embVol = $embalagemEn;
                    if ($embVol == null) $embVol = $volumeEn;

                    $enderecoRepo->verificaAlocacaoPickingDinamico($enderecoAntigo, $enderecoNovoEn, $embVol);

                    if ($produtoEn->getValidade() == 'S' ) {
                        $validade = $estoque->getValidade();
                        if (empty($validade)) {
                            /** @var \Wms\Domain\Entity\Enderecamento\Palete $umaOrigem */
                            $umaOrigem = null;
                            if (isset($estoque) && !empty($estoque)) {
                                $estoqueUma = $estoque->getUma();
                                if (isset($estoqueUma) && !empty($estoqueUma)) {
                                    $umaOrigem = $this->em->find('wms:Enderecamento\Palete', $estoque->getUma());
                                }
                            }
                            $validade = (!empty($umaOrigem))? $umaOrigem->getValidade() : null;
                        }
                        if (!empty($validade)) {
                            $params['validade'] = $validade->format('d/m/Y');
                        }
                    }

                    $estoqueRepo->validaMovimentaçãoExpedicaoFinalizada($enderecoAntigo->getId(), $produtoEn->getId(),$produtoEn->getGrade());
                    $estoqueRepo->validaMovimentaçãoExpedicaoFinalizada($enderecoNovoEn->getId(), $produtoEn->getId(),$produtoEn->getGrade());

                    $params['observacoes'] = "Transferencia de Estoque - Origem: ".$enderecoAntigo->getDescricao();
                    $estoqueRepo->movimentaEstoque($params);
                    //RETIRA ESTOQUE
                    $params['endereco'] = $enderecoAntigo;
                    if (!empty($estoque)) {
                        $estoqueValidade = $estoque->getValidade();
                        if (!empty($estoqueValidade)) {
                            $params['validade'] = $estoqueValidade->format('d/m/Y');
                        }
                    }

                    $params['qtd'] = $qtd * -1;
                    $params['observacoes'] = "Transferencia de Estoque - Destino: ".$enderecoNovoEn->getDescricao();
                    $estoqueRepo->movimentaEstoque($params, true, true);
                }
            }
            else if (isset($params['etiquetaProduto']) && !empty($params['etiquetaProduto'])) {
                $params['etiquetaProduto'] = ColetorUtil::adequaCodigoBarras($params['etiquetaProduto']);

                /** @var \Wms\Domain\Entity\Produto\Embalagem $embalagemEn */
                $params['embalagem'] = $embalagemEn = $embalagemRepo->findOneBy(array('codigoBarras' => $params['etiquetaProduto'], 'dataInativacao' => null));
                $volumeEn = $volumeRepo->findOneBy(array('codigoBarras' => $params['etiquetaProduto']));

                if (empty($embalagemEn) && empty($volumeEn))
                    throw new \Exception("CÓDIGO DE BARRAS $params[etiquetaProduto] NÃO ENCONTRADO! Verifique se é um código de barras válido");

                if ( !empty($embalagemEn)) {
                    /** @var Wms\Domain\Entity\Produto $produtoEn */
                    $produtoEn = $params['produto'] = $embalagemEn->getProduto();
                    $params['qtd'] = $qtd;

                    $enderecoFrmt = EnderecoUtil::formatar($enderecoNovo, null, null, $nivelNovo);

                    /** @var \Wms\Domain\Entity\Deposito\Endereco $endereco */
                    $params['endereco'] = $endereco = $enderecoRepo->findOneBy(array('descricao' => $enderecoFrmt));

                    if (empty($endereco))
                        throw new \Exception("Novo Endereço não encontrado!");

                    if ($enderecoAntigo->getIdCaracteristica() == $idCaracteristicaPicking ||
                        $enderecoAntigo->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPicking && $embalagemEn->getEndereco()->getId() != $endereco->getId()) {
                            throw new \Exception("Só é permitido transferir de Picking para Picking Dinâmico!");
                        }
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo && $endereco->liberadoPraSerPicking()) {
                            $embalagens = $embalagemRepo->findBy(array('codProduto' => $embalagemEn->getProduto(), 'grade' => $embalagemEn->getGrade()));
                            foreach ($embalagens as $embalagemEn) {
                                $embalagemEn->setEndereco($endereco);
                                $this->getEntityManager()->persist($embalagemEn);
                            }
                            $this->getEntityManager()->flush();
                        }
                    } else {
                        //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING E O ENDEREÇO DO PRODUTO ESTÁ VAZIO ... E TRAVA
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPicking) {
                            if (isset($embalagemEn) && is_null($embalagemEn->getEndereco())) {
                                throw new \Exception("Esse Endereço de Picking não está cadastrado para esse produto!");
                            }
                        }

                        //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING DINAMICO E SE O ENDERECO DO PRODUTO ESTÁ VAZIO E SALVA O ENDEREÇO DE DESTINO
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                            if (isset($embalagemEn) && is_null($embalagemEn->getEndereco()) && $endereco->liberadoPraSerPicking()) {
                                $embalagens = $embalagemRepo->findBy(array('codProduto' => $embalagemEn->getProduto(), 'grade' => $embalagemEn->getGrade()));
                                foreach ($embalagens as $embalagemEn) {
                                    $embalagemEn->setEndereco($endereco);
                                    $this->getEntityManager()->persist($embalagemEn);
                                }
                                $this->getEntityManager()->flush();
                            }
                        }

                        //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING E SE O ENDEREÇO DE DESTINO É DIFERENTE DO ENDEREÇO CADASTRADO NO PRODUTO E EXIBE MENSAGEM DE ERRO
                        if (($endereco->getIdCaracteristica() == $idCaracteristicaPicking || $endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo)) {
                            if (isset($embalagemEn)) {
                                if ($endereco->getId() !== $embalagemEn->getEndereco()->getId()) {
                                    throw new \Exception("Produto ja cadastrado no Picking " . $embalagemEn->getEndereco()->getDescricao() . "!");
                                }
                            }
                        }
                    }

                    $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $enderecoAntigo, 'codProduto' => $embalagemEn->getCodProduto(), 'grade' => $embalagemEn->getGrade()));
                    if (!$estoqueEn)
                        throw new \Exception("Estoque não Encontrado!");

                    $params['unitizador'] = $estoqueEn->getUnitizador();
                    $params['dthEntrada'] = $estoqueEn->getDtPrimeiraEntrada();

                    if ($produtoEn->getValidade() == 'S' ) {
                        $validade = $estoqueEn->getValidade();
                        if (empty($validade)){
                            $umaOrigem = null;
                            if (isset($estoqueEn) && !empty($estoqueEn)) {
                                $estoqueUma = $estoqueEn->getUma();
                                if (isset($estoqueUma) && !empty($estoqueUma)) {
                                    $umaOrigem = $this->em->find('wms:Enderecamento\Palete', $estoqueUma);
                                }
                            }
                            $validade = (!empty($umaOrigem))? $umaOrigem->getValidade() : null;
                        }
                        if (!empty($validade)) {
                            $params['validade'] = $validade->format('d/m/Y');
                        }
                    }

                    $estoqueRepo->validaMovimentaçãoExpedicaoFinalizada($enderecoAntigo->getId(), $produtoEn->getId(),$produtoEn->getGrade());
                    $estoqueRepo->validaMovimentaçãoExpedicaoFinalizada($endereco->getId(), $produtoEn->getId(),$produtoEn->getGrade());


                    $params['observacoes'] = "Transferencia de Estoque - Origem: ".$enderecoAntigo->getDescricao();
                    $estoqueRepo->movimentaEstoque($params);
                    //RETIRA ESTOQUE
                    $params['observacoes'] = "Transferencia de Estoque -  Destino: ".$params['endereco']->getDescricao();
                    $params['endereco'] = $enderecoAntigo;
                    if (!empty($estoqueEn)) {
                        $getValidadeEstoque = $estoqueEn->getValidade();
                        if (!empty($getValidadeEstoque)) {
                            $params['validade'] = $estoqueEn->getValidade()->format('d/m/Y');
                        }
                    }
                    $params['qtd'] = $qtd * -1;
                    $estoqueRepo->movimentaEstoque($params, true, true);
                }

                if (isset($volumeEn) && !empty($volumeEn)) {
                    $norma = $volumeEn->getNormaPaletizacao()->getId();
                    $params['produto'] = $volumeEn->getProduto();
                    $codProduto = $volumeEn->getCodProduto();
                    $grade = $volumeEn->getGrade();
                    $volumes = $volumeRepo->findBy(array('normaPaletizacao' => $norma, 'codProduto' => $codProduto, 'grade' => $grade, 'dataInativacao' => null));
                    /** @var \Wms\Domain\Entity\Produto\Volume $volume */
                    foreach ($volumes as $volume) {
                        $params['qtd'] = $qtd;

                        $enderecoFrmt = EnderecoUtil::formatar($enderecoNovo, null, null, $nivelNovo);

                        /** @var \Wms\Domain\Entity\Deposito\Endereco $newEndereco */
                        $endereco = $enderecoRepo->findOneBy(array('descricao' => $enderecoFrmt));
                        if (empty($endereco))
                            throw new \Exception("Novo Endereço não encontrado!");

                        $params['endereco'] = $endereco;
                        $params['volume'] = $volume;

                        /*
                         * COMENTANDO REGRA DE TRANSFERENCIA PICKING -> PICKING
                         *
                        if ($endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                            if (isset($volume) && is_null($volume->getEndereco())) {
                                $volume->setEndereco($endereco);
                                $this->getEntityManager()->persist($volume);
                                $this->getEntityManager()->flush();
                            }
                        }
                        */

                        if ($enderecoAntigo->getIdCaracteristica() == $idCaracteristicaPicking ||
                            $enderecoAntigo->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                            if ($endereco->getIdCaracteristica() == $idCaracteristicaPicking && $embalagemEn->getEndereco()->getId() != $endereco->getId()) {
                                throw new \Exception("Só é permitido transferir de Picking para Picking Dinâmico!");
                            }
                            if ($endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                                if ($endereco->isBloqueadaEntrada() || $endereco->isBloqueadaSaida()) {
                                    if ($endereco->isBloqueadaEntrada()) $str[] = "Entrada";
                                    if ($endereco->isBloqueadaSaida()) $str[] = "Saída";
                                    throw new Exception('error', "O endereço ".$endereco->getDescricao()." não pode ser atribuido como picking pois está bloqueado para: " . implode(" e ", $str));
                                }
                                $volume->setEndereco($endereco);
                                $this->getEntityManager()->persist($volume);
                                $this->getEntityManager()->flush();
                            }
                        } else {
                            //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING E O ENDEREÇO DO PRODUTO ESTÁ VAZIO ... E TRAVA
                            if ($endereco->getIdCaracteristica() == $idCaracteristicaPicking) {
                                if (isset($volume) && is_null($volume->getEndereco())) {
                                    throw new \Exception("Esse Endereço de Picking não está cadastrado para esse produto!");
                                }
                            }

                            //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING DINAMICO E SE O ENDERECO DO PRODUTO ESTÁ VAZIO E SALVA O ENDEREÇO DE DESTINO
                            if ($endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
                                if (isset($volume) && is_null($volume->getEndereco())) {
                                    if ($endereco->isBloqueadaEntrada() || $endereco->isBloqueadaSaida()) {
                                        if ($endereco->isBloqueadaEntrada()) $str[] = "Entrada";
                                        if ($endereco->isBloqueadaSaida()) $str[] = "Saída";
                                        throw new Exception('error', "O endereço ".$endereco->getDescricao()." não pode ser atribuido como picking pois está bloqueado para: " . implode(" e ", $str));
                                    }
                                    $volume->setEndereco($endereco);
                                    $this->getEntityManager()->persist($volume);
                                    $this->getEntityManager()->flush();
                                }
                            }

                            //VERIFICA SE O ENDEREÇO DE DESTINO É PICKING E SE O ENDEREÇO DE DESTINO É DIFERENTE DO ENDEREÇO CADASTRADO NO PRODUTO E EXIBE MENSAGEM DE ERRO
                            if (($endereco->getIdCaracteristica() == $idCaracteristicaPicking || $endereco->getIdCaracteristica() == $idCaracteristicaPickingRotativo)) {
                                if (isset($volume)) {
                                    if ($endereco->getId() !== $volume->getEndereco()->getId()) {
                                        throw new \Exception("Produto ja cadastrado no Picking " . $volume->getEndereco()->getDescricao() . "!");
                                    }
                                }
                            }
                        }

                        $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $enderecoAntigo, 'codProduto' => $volume->getProduto(), 'grade' => $volume->getGrade()));
                        if (!$estoqueEn)
                            throw new \Exception("Estoque não Encontrado!");

                        $params['dthEntrada'] = $estoqueEn->getDtPrimeiraEntrada();
                        $estoqueRepo->validaMovimentaçãoExpedicaoFinalizada($enderecoAntigo->getId(),$codProduto,$grade);
                        $estoqueRepo->validaMovimentaçãoExpedicaoFinalizada($endereco->getId(),$codProduto,$grade);

                        $params['unitizador'] = $estoqueEn->getUnitizador();
                        $estoqueEn->getDtPrimeiraEntrada();
                        $params['validade'] = null;
                        $params['observacoes'] = "Transferencia de Estoque - Origem: ".$enderecoAntigo->getDescricao();
                        $estoqueRepo->movimentaEstoque($params);

                        //RETIRA ESTOQUE
                        $params['observacoes'] = "Transferencia de Estoque -  Destino: ".$params['endereco']->getDescricao();
                        $params['endereco'] = $enderecoAntigo;
                        $params['qtd'] = $qtd * -1;
                        $estoqueRepo->movimentaEstoque($params, true, true);

                    }
                }
            }

            $this->getEntityManager()->commit();
            $this->addFlashMessage('success', 'Endereço transferido com sucesso!');
            $this->_redirect('/mobile/enderecamento/movimentacao');

        }  catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            $this->addFlashMessage('error', $e->getMessage());
            $this->_redirect('/mobile/enderecamento/movimentacao');
        }
    }

    private function getEnderecoNivel($dscEndereco, $nivel)
    {

        $endereco = EnderecoUtil::formatar($dscEndereco, null, null, $nivel);

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
        return $enderecoRepo->findOneBy(array('descricao' => $endereco));
    }

    public function getEnderecoByParametro($dscEndereco)
    {
        list($tamanhoRua, $tamanhoPredio, $tamanhoNivel, $tamanhoApartamento) = EnderecoUtil::getQtdDigitos();

        $sql = " SELECT DSC_DEPOSITO_ENDERECO, NUM_NIVEL, COD_DEPOSITO_ENDERECO, COD_CARACTERISTICA_ENDERECO
                 FROM DEPOSITO_ENDERECO
                 WHERE
                 (CAST(SUBSTR('00' || NUM_RUA,-$tamanhoRua,$tamanhoRua)
                    || SUBSTR('00' || NUM_PREDIO,-$tamanhoPredio,$tamanhoPredio)
                    || SUBSTR('00' || NUM_NIVEL,-$tamanhoNivel,$tamanhoNivel)
                    || SUBSTR('00' || NUM_APARTAMENTO,-$tamanhoApartamento,$tamanhoApartamento) as INT)) = " . $dscEndereco;

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

    }

    public function getCapacidadePickingAjaxAction()
    {
        try {
            $dscEndereco = $this->_getParam('endereco');
            $uma = $this->_getParam('uma');

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');

            $paleteRepo = $this->getEntityManager()->getRepository('wms:Enderecamento\Palete');
            $paleteEn = $paleteRepo->find($uma);

            $capacidade = 0;

            if ($paleteEn != null) {
                $ppEn = $paleteEn->getProdutos()[0];

                if ($ppEn->getCodProdutoVolume() != null) {
                    $embalagemEn = $ppEn->getEmbalagemEn();
                    $capacidade = $embalagemEn->getCapacidadePicking();
                } else {
                    $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');

                    $embalagemEn = $embalagemRepo->findOneBy(array(
                        'codProduto' => $ppEn->getCodProduto(),
                        'grade'=> $ppEn->getGrade(),
                        'isPadrao'=> 'S'
                    ));

                    if ($embalagemEn == null) {
                        throw new \Exception("O produto desta UMA não possui embalagem padrão de recebimento cadastrada");
                    }

                    $capacidade = round($embalagemEn->getCapacidadePicking() / $embalagemEn->getQuantidade(),3);
                }
            }

            $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => EnderecoUtil::formatar($dscEndereco)));
            if (empty($enderecoEn)) {
                throw new \Exception("Endereço não encontrado");
            }

            $this->_helper->json(array('status' => 'Ok', 'caracteristicaEndereco' => $enderecoEn->getIdCaracteristica(), 'capacidadePicking' =>  $capacidade));

        } catch (\Exception $e) {
            $this->_helper->json(array('status' => 'Error', 'Msg' => $e->getMessage()));
        }

    }

    public function cadastroProdutoEnderecoAction()
    {
        $codBarras = $this->_getParam('codigoBarras');
        $codigoBarrasEndereco = $this->_getParam('endereco');
        $capacidadePicking = $this->_getParam('capacidade');
        $embalado = $this->_getParam('embalado');
        $isEmbalagem = $this->_getParam('isEmbalagem');
        $lastro = $this->_getParam('lastro',0);
        $camada = $this->_getParam('camada',0);
        $unitizador = $this->_getParam('unitizador');

        $this->view->unitizadores = $unitizadores = $this->getEntityManager()->getRepository('wms:Armazenagem\Unitizador')->getIdDescricaoAssoc();
        try {
            if (!empty($codBarras) && !empty($codigoBarrasEndereco) && !empty($capacidadePicking)) {
                $codigoBarras = ColetorUtil::retiraDigitoIdentificador($codigoBarrasEndereco);

                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
                $endereco = EnderecoUtil::formatar($codigoBarras);
                /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
                $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));
                if (!isset($enderecoEn) || empty($enderecoEn)) {
                    throw new Exception('Endereço não encontrado');
                }

                if ($enderecoEn->isBloqueadaEntrada() || $enderecoEn->isBloqueadaSaida()) {
                    if ($enderecoEn->isBloqueadaEntrada()) $str[] = "Entrada";
                    if ($enderecoEn->isBloqueadaSaida()) $str[] = "Saída";
                    throw new Exception('error', "O endereço ".$enderecoEn->getDescricao()." não pode ser atribuido como picking pois está bloqueado para: " . implode(" e ", $str));
                }

                $codBarras = ColetorUtil::adequaCodigoBarras($codBarras);
                if (filter_var($isEmbalagem, FILTER_VALIDATE_BOOLEAN)) {
                    /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
                    $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
                    $embalagemRepo->setPickingEmbalagem($codBarras, $enderecoEn, $capacidadePicking, $embalado);
                    $embalagemRepo->setNormaPaletizacaoEmbalagem($codBarras, $lastro, $camada, $unitizador);
                } else {
                    /** @var \Wms\Domain\Entity\Produto\VolumeRepository $volumeRepo */
                    $volumeRepo = $this->em->getRepository('wms:Produto\Volume');
                    $volumeRepo->setPickingVolume($codBarras, $enderecoEn, $capacidadePicking);
                    $volumeRepo->setNormaPaletizacaoVolume($codBarras, $lastro, $camada, $unitizador);
                }
                /** @var \Wms\Domain\Entity\Produto\AndamentoRepository $andamentoRepository */
                $andamentoRepository = $this->em->getRepository('wms:Produto\Andamento');
                $andamentoRepository->saveBarCode($codBarras);

                $this->getEntityManager()->flush();

                $this->addFlashMessage('success', 'Cadastrado com sucesso!');
                $this->_redirect('/mobile/enderecamento/cadastro-produto-endereco');
            } else {
                if (!empty($codBarras)) {
                    if (empty($capacidadePicking)) {
                        $this->addFlashMessage('info', "Capacidade de picking não informada ou preenchido como 0");
                    }
                    if (empty($codigoBarrasEndereco)) {
                        $this->addFlashMessage('info', "Endereço de picking não informado");
                    }
                    $this->addFlashMessage('info', "Nenhuma informação foi alterada");
                }

            }
        } catch (\Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
            $this->_redirect('/mobile/enderecamento/cadastro-produto-endereco');
        }

    }

    public function dadosEmbalagemAction()
    {
        $status = null;
        $mensagem = null;
        $result = array();

        $codBarras = ColetorUtil::adequaCodigoBarras($this->_getParam('codigoBarras'));
        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepository */
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        /** @var \Wms\Domain\Entity\Produto\NormaPaletizacaoRepository $normaPaletizacaoRepository */
        $normaPaletizacaoRepository = $this->getEntityManager()->getRepository('wms:Produto\NormaPaletizacao');
        /** @var \Wms\Domain\Entity\Produto\Embalagem $embalagemEn */
        $embalagemEn = $embalagemRepo->findOneBy(array('codigoBarras' => $codBarras));

        if (empty($embalagemEn) ) {
            /** @var \Wms\Domain\Entity\Produto\VolumeRepository $volumeRepo */
            $volumeRepo = $this->em->getRepository('wms:Produto\Volume');
            /** @var \Wms\Domain\Entity\Produto\Volume $volumeEn */
            $volumeEn = $volumeRepo->findOneBy(array('codigoBarras' => $codBarras));
            $codProduto = $volumeEn->getCodProduto();
        } else {
            $codProduto = $embalagemEn->getCodProduto();
        }

        if (empty($embalagemEn) && empty($volumeEn)) {
            $status = 'error';
            $mensagem = 'Codigo de Barras nao encontrado!';
        } elseif (!empty($embalagemEn)) {
            $normaPaletizacaoEntity = $normaPaletizacaoRepository->getNormasByProduto($codProduto,'UNICA', true);
            $enderecoEmbalagem = $embalagemEn->getEndereco();
            $status = 'ok';
            $result['endereco'] = (!empty($enderecoEmbalagem)) ? $enderecoEmbalagem->getDescricao().'0' : null;
            $result['isEmbalagem'] = true;
            $result['capacidade'] = $embalagemEn->getCapacidadePicking() / $embalagemEn->getQuantidade();
            $result['embalado']   = $embalagemEn->getEmbalado();
            $result['referencia'] = $embalagemEn->getProduto()->getReferencia();
            $result['descricao']  = $embalagemEn->getProduto()->getDescricao();
            $result['lastro']     = is_array($normaPaletizacaoEntity) ? reset($normaPaletizacaoEntity)['NUM_LASTRO'] * reset($normaPaletizacaoEntity)['QTD_EMBALAGEM'] / $embalagemEn->getQuantidade(): 0;
            $result['camada']     = is_array($normaPaletizacaoEntity) ? reset($normaPaletizacaoEntity)['NUM_CAMADAS'] : 0;
        } else {
            $enderecoVolume = $volumeEn->getEndereco();
            $status = 'ok';
            $result['endereco'] = (!empty($enderecoVolume)) ? $enderecoVolume->getDescricao() : null;
            $result['isEmbalagem'] = false;
            $result['capacidade'] = $volumeEn->getCapacidadePicking();
            $result['referencia'] = $volumeEn->getProduto()->getReferencia();
            $result['descricao']  = $volumeEn->getProduto()->getDescricao();
        }

        $this->_helper->json(array('status' => $status, 'msg' => $mensagem, 'result' => $result));
    }
}

