<?php
use Wms\Controller\Action,
    Wms\Util\Coletor as ColetorUtil,
    \Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity;

class Mobile_Enderecamento_ManualController extends Action
{
    public function indexAction()
    {
        $recebimentoService = new \Mobile\Service\Recebimento($this->em);
        $this->view->recebimentos = $recebimentoService->listarRecebimentosNaoEnderecados(null);
    }

    public function lerCodigoBarrasAction()
    {
        $params = $this->_getAllParams();
        $em = $this->getEntityManager();
        try{
            if ($this->getRequest()->isPost()) {
                if (isset($params['produto']) && trim($params['produto']) == "") {
                    throw new \Exception("Informe um produto!");
                }
                elseif (isset($params['endereco']) && trim($params['endereco']) == "") {
                    throw new \Exception("Informe um endereço!");
                }
                elseif (!isset($params['qtd']) || empty($params['qtd'])) {
                    throw new \Exception("Informe uma quantidade!");
                }
                elseif (\Wms\Math::compare($params['qtd'], 0, '<')) {
                    throw new \Exception("Não é possível endereçar uma quantidade negativa!");
                }

                unset($params['module']);
                unset($params['controller']);
                unset($params['action']);

                if (empty($params['reservas'])) {

                $params['produto'] = ColetorUtil::adequaCodigoBarras($params['produto']);
                /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $produtoEmbalagemRepo */
                $produtoEmbalagemRepo = $em->getRepository('wms:Produto\Embalagem');
                /** @var \Wms\Domain\Entity\Produto\Embalagem $embalagemEn */
                $embalagemEn = $produtoEmbalagemRepo->findOneBy(array('codigoBarras' => $params['produto'], 'dataInativacao' => null));

                    /** @var \Wms\Domain\Entity\Produto\VolumeRepository $produtoVolumeRepo */
                    $produtoVolumeRepo = $em->getRepository('wms:Produto\Volume');
                    $volumeEn = $produtoVolumeRepo->findOneBy(array('codigoBarras' => $params['produto'], 'dataInativacao' => null));

                    if (!$embalagemEn && !$volumeEn)
                        throw new \Exception("O código de barras informado não existe!");

                    if ($embalagemEn) {
                        $params['codProduto'] = $codProduto = $embalagemEn->getCodProduto();
                        $params['grade'] = $grade = $embalagemEn->getGrade();
                        $this->view->capacidadePicking = $embalagemEn->getCapacidadePicking();
                        $params['qtdEmbalagem'] = $embalagemEn->getQuantidade();
                    } else {
                        $params['codProduto'] = $codProduto = $volumeEn->getCodProduto();
                        $params['grade'] = $grade = $volumeEn->getGrade();
                        $this->view->capacidadePicking = $volumeEn->getCapacidadePicking();
                    }

                    /** @var \Wms\Domain\Entity\Recebimento\EmbalagemRepository $recebimentoEmbalagemRepo */
                    $recebimentoEmbalagemRepo = $em->getRepository('wms:Recebimento\Embalagem');
                    $recebimentoEmbalagem = $recebimentoEmbalagemRepo->getEmbalagemByRecebimento($params['id'], $codProduto, $grade);

                    /** @var \Wms\Domain\Entity\Recebimento\VolumeRepository $recebimentoVolumeRepo */
                    $recebimentoVolumeRepo = $em->getRepository('wms:Recebimento\Volume');
                    $recebimentoVolume = $recebimentoVolumeRepo->getVolumeByRecebimento($params['id'], $codProduto, $grade);

                    if (count($recebimentoEmbalagem) <= 0 && count($recebimentoVolume) <= 0)
                        throw new \Exception("O Produto Informado não pertence ao recebimento");

                    /** @var \Wms\Domain\Entity\Recebimento\VQtdRecebimentoRepository $qtdRecebimentoRepo */
                    $qtdRecebimentoRepo = $em->getRepository('wms:Recebimento\VQtdRecebimento');
                    $qtdRecebimentoEn = $qtdRecebimentoRepo->getQtdByRecebimento($params['id'], $codProduto, $grade);
                    $sumQtdRecebimento = $qtdRecebimentoEn[0]['qtd'];

                /** @var \Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository $paleteProdutoRepo */
                $paleteProdutoRepo = $em->getRepository('wms:Enderecamento\PaleteProduto');
                $paleteProdutoEn = $paleteProdutoRepo->getQtdTotalEnderecadaByRecebimento($params['id'], $codProduto, $grade, $params['produto']);

                    $qtdEndTotalFator = \Wms\Math::multiplicar($params['qtd'], $params['qtdEmbalagem']);
                    $enderecadoMaisEnderecar = \Wms\Math::adicionar($qtdEndTotalFator, $paleteProdutoEn[0]['qtd']);
                    if (\Wms\Math::compare($sumQtdRecebimento, $enderecadoMaisEnderecar, '<')) {
                        if (isset($params['paleteGerado'])) unset($params['paleteGerado']);
                        throw new \Exception("Não é possível armazenar mais itens do que a quantidade recebida!");
                    }
                }
                $this->validarEndereco($params['endereco'], $params, 'ler-codigo-barras', 'enderecar-manual');

            }
        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage());
            $this->redirect('ler-codigo-barras','enderecamento_manual','mobile', array('id'=>$params['id']));
        }
    }

    public function validarEndereco($codBarraEndereco, $params, $urlOrigem, $urlDestino) {
        try{
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");
            $endereco = \Wms\Util\Endereco::formatar(ColetorUtil::retiraDigitoIdentificador($codBarraEndereco));
            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));
            if (empty($enderecoEn)) {
                throw new Exception("Endereço não encontrado");
            }

            $params['endereco'] = $enderecoEn->getId();

            if ($enderecoEn->getIdCaracteristica() == EnderecoEntity::ENDERECO_PICKING
                || $enderecoEn->getIdCaracteristica() == EnderecoEntity::ENDERECO_PICKING_DINAMICO) {
                $params['urlOrigem'] = $urlOrigem;
                $params['urlDestino'] = $urlDestino;
                $this->redirect('selecionar-nivel','enderecamento_manual','mobile', $params);
            }

            unset($params['urlDestino']);
            unset($params['urlOrigem']);
            $this->redirect($urlDestino,'enderecamento_manual','mobile', $params);

        } catch (\Exception $ex) {
            $this->addFlashMessage('error',$ex->getMessage());
            $this->redirect($urlOrigem,'enderecamento_manual','mobile', array('id'=>$params['id']));
        }
    }

    public function selecionarNivelAction() {
        $params = $this->_getAllParams();

        $urlDestino = $params['urlDestino'];
        $urlOrigem = $params['urlOrigem'];

        try {
            $this->view->idEndereco = $idEndereco = $params['endereco'];
            $produtoEmbalagemEn = $this->getEntityManager()->getRepository('wms:Produto\Embalagem')->findOneBy(array('codigoBarras' => $params['produto']));
            $produtoVolumeEn = $this->getEntityManager()->getRepository('wms:Produto\Volume')->findOneBy(array('codigoBarras' => $params['produto']));
            if (is_object($produtoEmbalagemEn)) {
                $this->view->capacidadePicking = $produtoEmbalagemEn->getCapacidadePicking();
            } else if (is_object($produtoVolumeEn)) {
                $this->view->capacidadePicking = $produtoVolumeEn->getCapacidadePicking();
            }

            $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");

            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->find($idEndereco);
            $this->view->rua = $enderecoEn->getRua();
            $this->view->predio = $enderecoEn->getPredio();
            $this->view->apartamento = $enderecoEn->getApartamento();
            $this->view->endereco = $enderecoEn->getDescricao();
            $this->view->caracteristica = $enderecoEn->getIdCaracteristica();

            if (isset($params['nivel']) and trim($params['nivel']) != "") {
                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                $enderecoRepo = $this->em->getRepository("wms:Deposito\Endereco");

                $arrEndereco = array(
                    'rua' => $enderecoEn->getRua(),
                    'predio' => $enderecoEn->getPredio(),
                    'nivel' => $params['nivel'],
                    'apartamento' => $enderecoEn->getApartamento()
                );

                $endereco = \Wms\Util\Endereco::formatar($arrEndereco);
                /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
                $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));
                if (empty($enderecoEn)) {
                    throw new Exception("Endereço não encontrado");
                }

                $params['endereco'] = $enderecoEn->getId();

                unset($params['module']);
                unset($params['controller']);
                unset($params['action']);
                unset($params['submit']);
                unset($params['urlDestino']);
                unset($params['urlOrigem']);
                unset($params['nivel']);

                $this->redirect($urlDestino,'enderecamento_manual','mobile', $params);
            } else {
                $this->addFlashMessage('info', "Informe um nível");
            }

        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage());
            $this->redirect($urlOrigem,'enderecamento_manual','mobile', array('id'=>$params['id']));
        }
    }

    public function enderecarManualAction(){
        $params = $this->_getAllParams();
        try {
            $this->getEntityManager()->beginTransaction();
            $codBarras = $produto = $params['produto'];
            $codProduto = $params['codProduto'];
            $grade = $params['grade'];
            $idEndereco = $params['endereco'];
            $idRecebimento = $params['id'];
            $qtd = $params['qtd'] * $params['qtdEmbalagem'];
            if ($qtd == 0)
                $qtd = $params['qtd'];

            if (!isset($params['reservas']) || empty($params['reservas'])) {

                $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();

                /** @var \Wms\Domain\Entity\Recebimento\VQtdRecebimentoRepository $qtdRecebimentoRepo */
                $qtdRecebimentoRepo = $this->em->getRepository('wms:Recebimento\VQtdRecebimento');
                $qtdRecebimentoEn = $qtdRecebimentoRepo->getQtdByRecebimento($idRecebimento, $codProduto, $grade);
                $sumQtdRecebimento = $qtdRecebimentoEn[0]['qtd'];

            /** @var \Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository $paleteProdutoRepo */
            $paleteProdutoRepo = $this->em->getRepository('wms:Enderecamento\PaleteProduto');
            $paleteProdutoEn = $paleteProdutoRepo->getQtdTotalEnderecadaByRecebimento($idRecebimento, $codProduto, $grade, $codBarras);

                $qtdEndTotalFator = \Wms\Math::multiplicar($params['qtd'], $params['qtdEmbalagem']);
                $enderecadoMaisEnderecar = \Wms\Math::adicionar($qtdEndTotalFator, $paleteProdutoEn[0]['qtd']);

                if (\Wms\Math::compare($sumQtdRecebimento, $enderecadoMaisEnderecar, '<')) {
                    throw new \Exception("Não é possível armazenar mais itens do que a quantidade recebida!");
                }

                /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
                $estoqueRepo = $this->em->getRepository('wms:Enderecamento\Estoque');
                /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
                $paleteRepo = $this->em->getRepository('wms:Enderecamento\Palete');
                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $ederecoRepo */
                $enderecoRepo = $this->em->getRepository('wms:Deposito\Endereco');
                /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
                $produtoRepo = $this->em->getRepository('wms:Produto');
                /** @var \Wms\Domain\Entity\Produto\DadoLogisticoRepository $dadoLogisticoRepo */
                $dadoLogisticoRepo = $this->em->getRepository('wms:Produto\DadoLogistico');

                /** @var \Wms\Domain\Entity\Produto $produtoEn */
                $produtoEn = $produtoRepo->getProdutoByCodBarrasOrCodProduto($produto);
                /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
                $enderecoEn = $enderecoRepo->find($idEndereco);

                if ($enderecoEn->getIdCaracteristica() == EnderecoEntity::ENDERECO_PICKING_DINAMICO) {
                    if (isset($params['capacidadePicking']) && empty($params['capacidadePicking']))
                        throw new \Exception('Necessário informar a capacidade de picking para esse produto!');
                } else {
                    $params['capacidadePicking'] = null;
                }

                $novaCapacidadePicking = $params['capacidadePicking'];

                $embalagens = $produtoEn->getEmbalagens()->filter(
                    function ($item) {
                        return is_null($item->getDataInativacao());
                    }
                )->toArray();

                $arrDL = array();
                $normaRelativa = null;
                /** @var \Wms\Domain\Entity\Produto\Embalagem $embalagem */
                foreach ($embalagens as $embalagem) {
                    /** @var \Wms\Domain\Entity\Produto\DadoLogistico $dadoLogisticoEn */
                    $dadoLogisticoEn = $dadoLogisticoRepo->findOneBy(array('embalagem' => $embalagem));
                    if (!empty($dadoLogisticoEn)) {
                        $arrDL[$embalagem->getId()] = $dadoLogisticoEn;
                        if (empty($normaRelativa))
                            $normaRelativa = $dadoLogisticoEn->getNormaPaletizacao();
                    }
                }

            if (!empty($embalagens) && empty($arrDL)){
                throw new Exception("Nenhuma das embalagens deste produto contem dados logisticos ou norma de paletização cadastrada");
            }

                foreach ($embalagens as $embalagemEn) {
                    $endereco = null;
                    if (!is_null($embalagemEn->getEndereco()))
                        $endereco = $embalagemEn->getEndereco()->getId();

                    if ($enderecoEn->getIdCaracteristica() == EnderecoEntity::ENDERECO_PICKING && $endereco != $enderecoEn->getId() && !is_null($endereco)) {
                        throw new \Exception('O produto já está cadastrado no Picking ' . $embalagemEn->getEndereco()->getDescricao());
                    }
                    if ($endereco != $enderecoEn->getId() && $enderecoEn->getIdCaracteristica() == EnderecoEntity::ENDERECO_PICKING_DINAMICO) {
                        $estoqueEn = $estoqueRepo->findOneBy(array('codProduto' => $produtoEn->getId(), 'grade' => $produtoEn->getGrade()));
                        if (isset($estoqueEn) && !empty($estoqueEn)) {
                            throw new \Exception('Não é possível endereçar produto com estoque em outro endereço');
                        } else {
                            $embalagemEn->setCapacidadePicking($novaCapacidadePicking);
                            $embalagemEn->setEndereco($enderecoEn);
                            $this->getEntityManager()->persist($embalagemEn);
                        }
                    }
                }

                $volumes = $produtoEn->getVolumes();
                foreach ($volumes as $volumeEn) {
                    $endereco = null;
                    if (!is_null($volumeEn->getEndereco()))
                        $endereco = $volumeEn->getEndereco()->getId();

                    if ($enderecoEn->getIdCaracteristica() == EnderecoEntity::ENDERECO_PICKING && $endereco != $enderecoEn->getId() && !is_null($endereco)) {
                        throw new \Exception('O produto já está cadastrado no Picking ' . $volumeEn->getEndereco()->getDescricao());
                    }
                    if ($endereco != $enderecoEn->getId() && $enderecoEn->getIdCaracteristica() == EnderecoEntity::ENDERECO_PICKING_DINAMICO) {
                        $estoqueEn = $estoqueRepo->findOneBy(array('codProduto' => $produtoEn->getId(), 'grade' => $produtoEn->getGrade()));
                        if (isset($estoqueEn) && !empty($estoqueEn)) {
                            throw new \Exception('Não é possível endereçar produto com estoque em outro endereço');
                        } else {
                            $volumeEn->setCapacidadePicking($novaCapacidadePicking);
                            $volumeEn->setEndereco($enderecoEn);
                            $this->getEntityManager()->persist($volumeEn);
                        }
                    }
                }

                /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
                $notaFiscalRepo = $this->getEntityManager()->getRepository('wms:NotaFiscal');
                $getDataValidadeUltimoProduto = $notaFiscalRepo->buscaRecebimentoProduto($idRecebimento, null, $codProduto, $grade);

                if (isset($getDataValidadeUltimoProduto) && !empty($getDataValidadeUltimoProduto)) {
                    $dataValidade['dataValidade'] = $getDataValidadeUltimoProduto['dataValidade'];
                } else {
                    $dataValidade['dataValidade'] = null;
                }

            $paleteEn = $this->createPalete($qtd,$produtoEn,$idRecebimento,$codBarras);
            $paleteRepo->alocaEnderecoPalete($paleteEn->getId(),$idEndereco);
            $paleteRepo->finalizar(array($paleteEn->getId()), $idPessoa, null, $dataValidade);

                $this->addFlashMessage('success', 'Palete ' . $paleteEn->getId() . ' criado e endereçado com sucesso');

            } elseif (isset($params['reservas']) && !empty($params['reservas'])) {
                /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueEnderecamentoRepository $reservaEndRepo */
                $reservaEndRepo = $this->em->getRepository('wms:Ressuprimento\ReservaEstoqueEnderecamento');

                /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
                $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

                /** @var \Wms\Domain\Entity\Util\SiglaRepository $siglaRepo */
                $siglaRepo = $this->getEntityManager()->getRepository("wms:Util\Sigla");

                /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
                $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");

                $resevasExp = explode(",", $params['reservas']);
                /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueEnderecamento[] $reservasEn */
                $reservasEn = $reservaEndRepo->findBy(['reservaEstoque' => $resevasExp]);

                $siglaEn = $siglaRepo->find(\Wms\Domain\Entity\Enderecamento\Palete::STATUS_ENDERECADO);

                $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
                $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
                $usuarioEn = $usuarioRepo->find($idPessoa);

                $paletes = [];

                foreach ($reservasEn as $reserva) {
                    $reservaEstoqueEn = $reserva->getReservaEstoque();
                    if ($reservaEstoqueEn->getAtendida() == 'N') {
                        $paleteEn = $reserva->getPalete();
                        $paleteId = $paleteEn->getId();
                        $paletes[] = $paleteId;
                        $reservaEstoqueRepo->efetivaReservaByReservaEntity($estoqueRepo, $reservaEstoqueEn, "U", $paleteId, $usuarioEn);
                        $paleteEn->setStatus($siglaEn);
                        $this->em->persist($paleteEn);
                    }
                }
                $this->em->flush();
                $this->addFlashMessage('success', 'Palete(s) ' . implode(", ", $paletes) . ' endereçado(s) com sucesso');
            }
            $this->getEntityManager()->commit();
            $this->_redirect('/mobile/enderecamento_manual/ler-codigo-barras/id/'.$params['id']);

        } catch (\Exception $ex) {
            $this->addFlashMessage('error',$ex->getMessage());
            $this->getEntityManager()->rollback();
            $this->redirect('ler-codigo-barras','enderecamento_manual','mobile',array('id'=>$params['id']));
        }
    }

    /**
     * @param $qtd
     * @param $produtoEn \Wms\Domain\Entity\Produto
     * @param $idRecebimento
     * @return object|\Wms\Domain\Entity\Enderecamento\Palete
     * @throws Exception
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function createPalete($qtd, $produtoEn, $idRecebimento, $codBarras)
    {
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo    = $this->em->getRepository('wms:Produto');
        $volumeRepository = $this->em->getRepository('wms:Produto\Volume');

        $idProduto = $produtoEn->getId();
        $grade = $produtoEn->getGrade();
        $tipoComercializacao = $produtoEn->getTipoComercializacao()->getId();

        if ($tipoComercializacao == \Wms\Domain\Entity\Produto::TIPO_COMPOSTO) {
            $volumeEntity = $volumeRepository->findOneBy(array('codigoBarras' => $codBarras));

            if (!$volumeEntity)
                throw  new \Exception("Código de Barras  ". $codBarras . " não encontrado!");

            if (is_null($volumeEntity->getNormaPaletizacao()))
                throw  new \Exception("Volume sem norma de paletização definida");

            $norma = $volumeEntity->getNormaPaletizacao()->getId();
            $unidadePadrao = $volumeEntity->getDescricao();
            $qtdNorma = $volumeEntity->getNormaPaletizacao()->getNumNorma();
            $lastro = $volumeEntity->getNormaPaletizacao()->getNumLastro();
            $camadas = $volumeEntity->getNormaPaletizacao()->getNumCamadas();
            $unitizador = $volumeEntity->getNormaPaletizacao()->getUnitizador()->getDescricao();
            $idUnitizador = $volumeEntity->getNormaPaletizacao()->getUnitizador()->getId();
            $idNorma = $norma;

            $result[0]['idNorma'] = $idNorma;
            $result[0]['unidade'] = $unidadePadrao;
            $result[0]['idUnitizador'] = $idUnitizador;
            $result[0]['unitizador'] = $unitizador;
            $result[0]['qtdNorma'] = $qtdNorma;
            $result[0]['lastro'] = $lastro;
            $result[0]['camadas'] = $camadas;
            $result[0]['dscProduto'] = $produtoEn->getDescricao();
            $volumes = $volumeRepository->getProdutosVolumesByNorma($idNorma, $idProduto, $grade);
        } elseif ($tipoComercializacao == \Wms\Domain\Entity\Produto::TIPO_UNITARIO) {
            $result = $produtoRepo->getNormaPaletizacaoPadrao($idProduto, $grade);
            $idNorma = $result[0]['idNorma'];
            $volumes = $produtoRepo->getEmbalagensOrVolumesByProduto($idProduto, $grade);
        }

        if ($idNorma == null) {
            throw  new \Exception("O Produto ". $produtoEn->getDescricao() . " não possui norma de paletização");
        }
        /** @var \Wms\Domain\Entity\Armazenagem\UnitizadorRepository $uniRepo */
        $uniRepo = $this->getEntityManager()->getRepository("wms:Armazenagem\Unitizador");
        $unitizadorEn  = $uniRepo->find($result[0]['idUnitizador']);
        $statusEn      = $this->getEntityManager()->getRepository('wms:Util\Sigla')->find(\Wms\Domain\Entity\Enderecamento\Palete::STATUS_RECEBIDO);



        if ($produtoEn->getTipoComercializacao()->getId() == \Wms\Domain\Entity\Produto::TIPO_UNITARIO) {
            $tmp[0]['COD_PRODUTO_EMBALAGEM'] = $volumes[0]['COD_PRODUTO_EMBALAGEM'];
            $tmp[0]['COD_PRODUTO_VOLUME'] = NULL;
            $volumes = $tmp;
        }

        if (count($volumes) == 0) {
            throw new \Exception('Produto não possui volumes ou embalagem padrão definidas');
        }

        $recebimentoEn = $this->getEntityManager()->getRepository("wms:Recebimento")->find($idRecebimento);

        if (!isset($recebimentoEn)) {
            throw new \Exception('Recebimento não encontrado');
        }

        /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
        $notaFiscalRepo = $this->getEntityManager()->getRepository('wms:NotaFiscal');
        $getDataValidadeUltimoProduto = $notaFiscalRepo->buscaRecebimentoProduto($recebimentoEn->getId(), null, $idProduto, $grade);

        if (isset($getDataValidadeUltimoProduto) && !empty($getDataValidadeUltimoProduto)) {
            $dataValidade = $getDataValidadeUltimoProduto['dataValidade'];
        } else {
            $dataValidade = null;
        }

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo    = $this->em->getRepository('wms:Enderecamento\Palete');

        $paleteEn = $paleteRepo->salvarPaleteEntity($produtoEn, $recebimentoEn, $unitizadorEn, $statusEn, $volumes, $idNorma, $qtd, $dataValidade, 'M');

        $idPalete = $paleteEn->getId();
        $this->_em->flush();
        $this->_em->clear();

        $paleteEn = $paleteRepo->find($idPalete);

        return $paleteEn;
    }

    public function verificarCaracteristicaEnderecoAction()
    {
        $codBarraEndereco = $this->_getParam('id');
        $codEndereco = $this->_getParam('endereco');

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");
        $data = false;
        $primeiraTela = false;
        $endereco = null;

        //VALIDO PARA A PRIMEIRA TELA
        if (isset($codBarraEndereco) && !empty($codBarraEndereco)) {
            $endereco = \Wms\Util\Endereco::formatar(ColetorUtil::retiraDigitoIdentificador($codBarraEndereco));
            $primeiraTela = true;
            //VALIDO PARA CASO O USUARIO PASSE O NIVEL NA SEGUNDA TELA
        } elseif (isset($codEndereco) && !empty($codEndereco)) {
            $enderecoEn = $enderecoRepo->find($codEndereco);

            $arrEndereco = array(
                'rua' => $enderecoEn->getRua(),
                'predio' => $enderecoEn->getPredio(),
                'nivel' => $this->_getParam('nivel'),
                'apartamento' => $enderecoEn->getApartamento()
            );

            $endereco = \Wms\Util\Endereco::formatar($arrEndereco);

        }
        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoEn = $enderecoRepo->findOneBy(array('descricao' => $endereco));
        if (!empty($enderecoEn)) {
            $nivel = $enderecoEn->getNivel();
            $caracteristicaEndereco = $enderecoEn->getIdCaracteristica();

            if (($nivel != 0 && $caracteristicaEndereco == EnderecoEntity::ENDERECO_PICKING_DINAMICO) || ($primeiraTela == false && $caracteristicaEndereco == EnderecoEntity::ENDERECO_PICKING_DINAMICO))
                $data = true;
        }

        echo $this->_helper->json($data);

    }

}

