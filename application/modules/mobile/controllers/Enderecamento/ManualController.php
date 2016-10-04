<?php
use Wms\Controller\Action,
Wms\Service\Recebimento as LeituraColetor,
Wms\Domain\Entity\Recebimento as RecebimentoEntity;

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
            if (isset($params['submit'])&& $params['submit'] != null) {
                if (isset($params['produto']) && trim($params['produto']) == "") {
                    throw new \Exception("Informe um produto");
                }
                if (isset($params['endereco']) && trim($params['endereco']) == "") {
                    throw new \Exception("Informe um endereço");
                }
                if (isset($params['qtd']) && trim($params['qtd']) == "") {
                    throw new \Exception("Informe uma quantidade");
                }

                unset($params['module']);
                unset($params['controller']);
                unset($params['action']);
                unset($params['submit']);

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
                $qtdRecebimentoEn = $qtdRecebimentoRepo->getQtdByRecebimento($params['id'],$codProduto,$grade);
                $sumQtdRecebimento = $qtdRecebimentoEn[0]['qtd'];

                /** @var \Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository $paleteProdutoRepo */
                $paleteProdutoRepo = $em->getRepository('wms:Enderecamento\PaleteProduto');
                $paleteProdutoEn = $paleteProdutoRepo->getQtdTotalEnderecadaByRecebimento($params['id'], $codProduto, $grade);

                if ($sumQtdRecebimento < ((((int)$params['qtd']) * $params['qtdEmbalagem']) + (int)$paleteProdutoEn[0]['qtd'])) {
                    if (isset($params['paleteGerado'])) unset($params['paleteGerado']);
                    throw new \Exception("Não é possível armazenar mais itens do que a quantidade recebida!");
                }

                $this->validarEndereco($params['endereco'], $params, 'ler-codigo-barras', 'enderecar-manual');

            } else {
                $this->addFlashMessage('info', "Informe um produto, endereço e quantidade para endereçar");
            }
        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage());
            $this->redirect('ler-codigo-barras','enderecamento_manual','mobile', array('id'=>$params['id']));
        }
    }

    public function validarEndereco($codBarraEndereco, $params, $urlOrigem, $urlDestino) {
        try{
            $LeituraColetor = new LeituraColetor();
            $endereco   = $LeituraColetor->retiraDigitoIdentificador($codBarraEndereco);

            if (!isset($endereco)) {
                throw new \Exception('Nenhum Endereço Informado');
            }

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");
            $idEndereco = $enderecoRepo->getEnderecoIdByDescricao($endereco);
            if (empty($idEndereco)) {
                throw new \Exception('Endereço não encontrado');
            }

            $idEndereco = $idEndereco[0]['COD_DEPOSITO_ENDERECO'];
            $params['endereco'] = $idEndereco;

            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->find($idEndereco);

            if ($enderecoEn->getIdCaracteristica() == $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING')
                || $enderecoEn->getIdCaracteristica() == $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING_ROTATIVO')) {
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
            $produtoEn = $this->getEntityManager()->getRepository('wms:Produto\Embalagem')->findOneBy(array('codigoBarras' => $params['produto']));
            $this->view->capacidadePicking = $produtoEn->getCapacidadePicking();

            $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");

            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->find($idEndereco);
            $this->view->rua = $enderecoEn->getRua();
            $this->view->predio = $enderecoEn->getPredio();
            $this->view->apartamento = $enderecoEn->getApartamento();
            $this->view->endereco = $enderecoEn->getDescricao();

            if (isset($params['submit'])&& $params['submit'] != null) {
                if (trim($params['nivel']) != "") {
                    $tamanhoRua = $this->getSystemParameterValue('TAMANHO_CARACT_RUA');
                    $tamanhoPredio = $this->getSystemParameterValue('TAMANHO_CARACT_PREDIO');
                    $tamanhoNivel = $this->getSystemParameterValue('TAMANHO_CARACT_NIVEL');
                    $tamanhoApartamento = $this->getSystemParameterValue('TAMANHO_CARACT_APARTAMENTO');

                    $rua         = substr("000" . $enderecoEn->getRua(), -$tamanhoRua, $tamanhoRua);
                    $predio      = substr("000" . $enderecoEn->getPredio(), -$tamanhoPredio, $tamanhoPredio);
                    $nivel       = substr("000" . $params['nivel'], -$tamanhoNivel, $tamanhoNivel);
                    $apartamento = substr("000" . $enderecoEn->getApartamento(), -$tamanhoApartamento, $tamanhoApartamento);
                    $codBarras   = $rua . $predio . $nivel . $apartamento;

                    $idEndereco = $enderecoRepo->getEnderecoIdByDescricao($codBarras);
                    if (count($idEndereco) == 0) {
                        throw  new \Exception("Nenhum Endereço Encontrado");
                    }

                    $idEndereco = $idEndereco[0]['COD_DEPOSITO_ENDERECO'];
                    $params['endereco'] = $idEndereco;

                    unset($params['module']);
                    unset($params['controller']);
                    unset($params['action']);
                    unset($params['submit']);
                    unset($params['urlDestino']);
                    unset($params['urlOrigem']);
                    unset($params['nivel']);

                    $this->redirect($urlDestino,'enderecamento_manual','mobile', $params);
                }
            }
            $this->addFlashMessage('info', "Informe um nível");

        } catch (\Exception $ex) {
            $this->addFlashMessage('error', $ex->getMessage());
            $this->redirect($urlOrigem,'enderecamento_manual','mobile', array('id'=>$params['id']));
        }
    }

    public function enderecarManualAction(){
        $params = $this->_getAllParams();
        try {
            $this->getEntityManager()->beginTransaction();
            $produto = $params['produto'];
            $codProduto = $params['codProduto'];
            $grade = $params['grade'];
            $idEndereco = $params['endereco'];
            $idRecebimento = $params['id'];
            $qtd = $params['qtd'] * $params['qtdEmbalagem'];

            /** @var \Wms\Domain\Entity\Recebimento\VQtdRecebimentoRepository $qtdRecebimentoRepo */
            $qtdRecebimentoRepo = $this->em->getRepository('wms:Recebimento\VQtdRecebimento');
            $qtdRecebimentoEn = $qtdRecebimentoRepo->getQtdByRecebimento($params['id'],$codProduto,$grade);
            $sumQtdRecebimento = $qtdRecebimentoEn[0]['qtd'];

            /** @var \Wms\Domain\Entity\Enderecamento\PaleteProdutoRepository $paleteProdutoRepo */
            $paleteProdutoRepo = $this->em->getRepository('wms:Enderecamento\PaleteProduto');
            $paleteProdutoEn = $paleteProdutoRepo->getQtdTotalEnderecadaByRecebimento($params['id'], $codProduto, $grade);

            if ($sumQtdRecebimento < ((((int)$params['qtd']) * $params['qtdEmbalagem']) + (int)$paleteProdutoEn[0]['qtd'])) {
                throw new \Exception("Não é possível armazenar mais itens do que a quantidade recebida!");
            }

            $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();

            /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
            $estoqueRepo = $this->em->getRepository('wms:Enderecamento\Estoque');
            /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
            $paleteRepo    = $this->em->getRepository('wms:Enderecamento\Palete');
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $ederecoRepo */
            $enderecoRepo    = $this->em->getRepository('wms:Deposito\Endereco');
            /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
            $produtoRepo    = $this->em->getRepository('wms:Produto');
            /** @var \Wms\Domain\Entity\Produto\DadoLogisticoRepository $dadoLogisticoRepo */
            $dadoLogisticoRepo = $this->em->getRepository('wms:Produto\DadoLogistico');
            /** @var \Wms\Domain\Entity\Produto\NormaPaletizacaoRepository $normaRepo */
            $normaRepo = $this->em->getRepository('wms:Produto\NormaPaletizacao');

            /** @var \Wms\Domain\Entity\Produto $produtoEn */
            $produtoEn = $produtoRepo->getProdutoByCodBarrasOrCodProduto($produto);
            /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
            $enderecoEn = $enderecoRepo->find($idEndereco);

            $idCaracteristicaPicking = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING');
            $idCaracteristicaPickingRotativo = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING_ROTATIVO');

            if (isset($params['capacidadePicking']) && empty($params['capacidadePicking']))
                throw new \Exception('Necessário informar a capacidade de picking para esse produto!');

            $novaCapacidadePicking = $params['capacidadePicking'];

            $embalagens = $produtoEn->getEmbalagens();
            foreach ($embalagens as $embalagemEn) {
                $dadoLogisticoEn = $dadoLogisticoRepo->findOneBy(array('embalagem' => $embalagemEn));
                if (!isset($dadoLogisticoEn) || empty($dadoLogisticoEn)) {
                    $normaRepo->gravarNormaPaletizacao($embalagemEn,$novaCapacidadePicking);
                }

                $endereco = null;
                if (!is_null($embalagemEn->getEndereco()))
                    $endereco = $embalagemEn->getEndereco()->getId();

                if ($enderecoEn->getIdCaracteristica() == $idCaracteristicaPicking && $endereco != $enderecoEn->getId() && !is_null($endereco)) {
                    throw new \Exception('O produto já está cadastrado no Picking '. $embalagemEn->getEndereco()->getDescricao());
                }
                if ($endereco != $enderecoEn->getId() && $enderecoEn->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
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

                if ($enderecoEn->getIdCaracteristica() == $idCaracteristicaPicking && $endereco != $enderecoEn->getId() && !is_null($endereco)) {
                    throw new \Exception('O produto já está cadastrado no Picking '. $volumeEn->getEndereco()->getDescricao());
                }
                if ($endereco != $enderecoEn->getId() && $enderecoEn->getIdCaracteristica() == $idCaracteristicaPickingRotativo) {
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

            $paleteEn = $this->createPalete($qtd,$produtoEn,$idRecebimento);
            $paleteRepo->alocaEnderecoPalete($paleteEn->getId(),$idEndereco);
            $paleteRepo->finalizar(array($paleteEn->getId()), $idPessoa);

            $this->addFlashMessage('success','Palete ' . $paleteEn->getId(). ' criado e endereçado com sucesso');
            $this->getEntityManager()->commit();
            $this->redirect('ler-codigo-barras','enderecamento_manual','mobile',array('id'=>$params['id']));

        } catch (\Exception $ex) {
            $this->addFlashMessage('error',$ex->getMessage());
            $this->getEntityManager()->rollback();
            $this->redirect('ler-codigo-barras','enderecamento_manual','mobile',array('id'=>$params['id']));
        }
    }

    private function createPalete($qtd, $produtoEn, $idRecebimento)
    {
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo    = $this->em->getRepository('wms:Produto');

        $idProduto = $produtoEn->getId();
        $grade = $produtoEn->getGrade();
        $result = $produtoRepo->getNormaPaletizacaoPadrao($idProduto, 'UNICA');
        $idNorma = $result[0]['idNorma'];

        if ($idNorma == null) {
            throw  new \Exception("O Produto ". $produtoEn->getDescricao() . " não possui norma de paletização");
        }
        /** @var \Wms\Domain\Entity\Armazenagem\UnitizadorRepository $uniRepo */
        $uniRepo = $this->getEntityManager()->getRepository("wms:Armazenagem\Unitizador");
        $unitizadorEn  = $uniRepo->find($result[0]['idUnitizador']);
        $statusEn      = $this->getEntityManager()->getRepository('wms:Util\Sigla')->find(\Wms\Domain\Entity\Enderecamento\Palete::STATUS_RECEBIDO);

        $volumes = $produtoRepo->getEmbalagensOrVolumesByProduto($idProduto, $grade);

        if (count($volumes) == 0) {
            throw new \Exception('Produto não possui volumes ou embalagem padrão definidas');
        }

        $recebimentoEn = $this->getEntityManager()->getRepository("wms:Recebimento")->find($idRecebimento);

        if (!isset($recebimentoEn)) {
            throw new \Exception('Recebimento não encontrado');
        }

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo    = $this->em->getRepository('wms:Enderecamento\Palete');

        $paleteEn = $paleteRepo->salvarPaleteEntity($produtoEn,$recebimentoEn,$unitizadorEn,$statusEn,$volumes, $idNorma, $qtd,null,'M');

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
        $idCaracteristicaPickingRotativo = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING_ROTATIVO');
        $LeituraColetor = new LeituraColetor();
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo   = $this->em->getRepository("wms:Deposito\Endereco");
        $data = false;
        $primeiraTela = false;
        $endereco = null;

        //VALIDO PARA A PRIMEIRA TELA
        if (isset($codBarraEndereco) && !empty($codBarraEndereco)) {
            $endereco   = $LeituraColetor->retiraDigitoIdentificador($codBarraEndereco);
            $primeiraTela = true;
        //VALIDO PARA CASO O USUARIO PASSE O NIVEL NA SEGUNDA TELA
        } elseif (isset($codEndereco) && !empty($codEndereco)) {
            $enderecoEn = $enderecoRepo->find($codEndereco);
            $tamanhoRua = $this->getSystemParameterValue('TAMANHO_CARACT_RUA');
            $tamanhoPredio = $this->getSystemParameterValue('TAMANHO_CARACT_PREDIO');
            $tamanhoNivel = $this->getSystemParameterValue('TAMANHO_CARACT_NIVEL');
            $tamanhoApartamento = $this->getSystemParameterValue('TAMANHO_CARACT_APARTAMENTO');

            $rua         = substr("000" . $enderecoEn->getRua(), -$tamanhoRua, $tamanhoRua);
            $predio      = substr("000" . $enderecoEn->getPredio(), -$tamanhoPredio, $tamanhoPredio);
            $nivel       = substr("000" . $this->_getParam('nivel'), -$tamanhoNivel, $tamanhoNivel);
            $apartamento = substr("000" . $enderecoEn->getApartamento(), -$tamanhoApartamento, $tamanhoApartamento);
            $endereco   = $rua . $predio . $nivel . $apartamento;

        }
        $enderecoEn = $enderecoRepo->getEnderecoIdByDescricao($endereco);
        $nivel = $enderecoEn[0]['NUM_NIVEL'];
        $caracteristicaEndereco = $enderecoEn[0]['COD_CARACTERISTICA_ENDERECO'];

        if (($nivel != 0 && $caracteristicaEndereco == $idCaracteristicaPickingRotativo) || ($primeiraTela == false && $caracteristicaEndereco == $idCaracteristicaPickingRotativo))
            $data = true;

        echo $this->_helper->json($data);

    }

}

