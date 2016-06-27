<?php

use Wms\Controller\Action;
use Wms\Module\Mobile\Form\ProdutoBuscar as ProdutoBuscarForm;
use Wms\Module\Mobile\Form\ProdutoQuantidade as ProdutoQuantidadeForm;

class Mobile_RecebimentoController extends Action
{

    public function descargaAction()
    {
        $operadores     = $this->_getParam('mass-id');
        $recbId         = $this->_getParam('recb');
        $osId             = $this->_getParam('os');

        if ($operadores && $recbId) {

            /** @var \Wms\Domain\Entity\Recebimento\DescargaRepository $descargaRepo */
            $descargaRepo = $this->em->getRepository('wms:Recebimento\Descarga');
            try {
                $descargaRepo->vinculaOperadores($recbId, $operadores);
                $this->_helper->messenger('success', 'Operadores vinculados ao recebimento com sucesso');
                $this->_redirect("/mobile/recebimento/finalizar/id/$recbId/os/$osId");
            } catch(Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
            }
        }

        /** @var \Wms\Domain\Entity\UsuarioRepository $UsuarioRepo */
        $UsuarioRepo                = $this->_em->getRepository('wms:Usuario');
        $this->view->operadores     = $UsuarioRepo->getUsuarioByPerfil('DESCARREGADOR RECEBI');
        $this->view->recebimento    = $recbId;
        $this->view->osId           = $osId;
    }

    public function finalizarAction(){
        $idOS = $this->_getParam("os");
        $idRecebimento = $this->_getParam("id");

        /** @var \Wms\Domain\Entity\Recebimento\DescargaRepository $descargaRepo */
        $descargaRepo = $this->em->getRepository('wms:Recebimento\Descarga');
        if ($descargaRepo->realizarDescarga($idRecebimento) === true) {
            $this->redirect('descarga','recebimento','mobile',array('recb' => $idRecebimento, 'os' => $idOS));
        }

        /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
        $recebimentoRepo = $this->em->getRepository('wms:Recebimento');

        $result = $recebimentoRepo->conferenciaColetor($idRecebimento, $idOS);

        if ($result['exception'] != null) {
            throw $result['exception'];
        }

        if ($result['concluido'] == true) {
            $this->addFlashMessage('success', $result['message']);
        } else {
            $this->addFlashMessage('error', "Existem divergencias neste recebimento. Consulte a mesa de operações para mais detalhes");
        }

        $this->_redirect('/mobile/ordem-servico/conferencia-recebimento');
    }

    /**
     *
     * @throws \Exception
     */
    public function lerCodigoBarrasAction()
    {
        try {
            $idRecebimento = $this->getRequest()->getParam('idRecebimento');
            $recebimentoRepo = $this->em->getRepository('wms:Recebimento');
            $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');

            $recebimentoEntity = $recebimentoRepo->find($idRecebimento);

            $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $idRecebimento));

            if ($notaFiscalEntity) {
                $this->view->placaVeiculo   = $notaFiscalEntity->getPlaca();
                $this->view->fornecedor     = $notaFiscalEntity->getFornecedor()->getPessoa()->getNome();
            }

            if (!$recebimentoEntity)
                throw new \Exception('Recebimento não encontrado');

            // verifica se tem ordem de servico aberto
            $retorno = $recebimentoRepo->checarOrdemServicoAberta($idRecebimento);

            if (isset($retorno['criado']) && $retorno['criado'])
                $this->_helper->messenger('info', $retorno['mensagem']);

            if(isset($retorno['finalizado']) && $retorno['finalizado']){
                $this->_helper->messenger('error', $retorno['mensagem']);
                $this->redirect('conferencia-recebimento', 'ordem-servico');
            }
            $this->view->headScript()->appendFile($this->view->baseUrl() . '/wms/resources/jquery/jquery.cycle.all.latest.js');
            if($recebimentoRepo->checarOsAnteriores($idRecebimento)){
                $this->view->listaProdutos = $recebimentoRepo->listarProdutosPorOS($idRecebimento);
            }
            $this->view->os = $retorno['id'];
            $this->view->recebimento = $recebimentoEntity;
            $this->view->idRecebimento = $idRecebimento;
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            $this->redirect('conferencia-recebimento', 'ordem-servico');
        }

        $form = new ProdutoBuscarForm;
        $form->setDefault('idRecebimento', $idRecebimento);
        $this->view->form = $form;
    }

    /**
     *
     * @throws \Exception
     */
    public function produtoQuantidadeAction()
    {
        // carrega js da pg
        $this->view->headScript()->appendFile($this->view->baseUrl() . '/wms/resources/mobile/recebimento/produto-quantidade.js');

        $idRecebimento = $this->getRequest()->getParam('idRecebimento');
        $codigoBarras = $this->getRequest()->getParam('codigoBarras');

        /** @var \Wms\Domain\Entity\Produto\VolumeRepository $produtoVolumeRepo */
        $produtoVolumeRepo = $this->getEntityManager()->getRepository('wms:Produto\Volume');
        $produtoVolumeEn = $produtoVolumeRepo->findOneBy(array('codigoBarras' => $codigoBarras));
        /** @var \Wms\Domain\Entity\Produto\VolumeRepository $produtoVolumeRepo */
        $produtoEmbalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        $produtoEmbEn = $produtoEmbalagemRepo->findOneBy(array('codigoBarras' => $codigoBarras));

        if (isset($produtoEmbEn) && !empty($produtoEmbEn)) {
            $idProduto = $produtoEmbEn->getProduto()->getId();
            $grade = $produtoEmbEn->getProduto()->getGrade();
            $pesoVariavel = $produtoEmbEn->getProduto()->getPossuiPesoVariavel();
        } else {
            $idProduto = $produtoVolumeEn->getCodProduto();
            $grade = $produtoVolumeEn->getGrade();
            $pesoVariavel = $produtoVolumeEn->getProduto()->getPossuiPesoVariavel();
        }
        $form = new ProdutoQuantidadeForm;

        try {
            $recebimentoEntity = $this->em->getReference('wms:Recebimento', $idRecebimento);
            $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');

            if (!$recebimentoEntity)
                throw new \Exception('Recebimento não encontrado');

            $recebimentoService = new \Wms\Service\Recebimento;

            // testa codigo de barras
            $codigoBarras = $recebimentoService->analisarCodigoBarras($codigoBarras);

            $itemNF = $notaFiscalRepo->buscarItemPorCodigoBarras($idRecebimento, $codigoBarras);

            if (!is_null($itemNF['dataInativacao']))
                throw new \Exception('O produto ' . $itemNF['idProduto'] . ' grade ' . $itemNF['grade'] . ' está inativo!');

            if ($itemNF == null)
                throw new \Exception('Nenhum produto encontrado no Recebimento com este Código de Barras. - ' . $codigoBarras);

            $this->view->itemNF = $itemNF;
            $form->setDefault('idNormaPaletizacao', $itemNF['idNorma']);

//            if ($produtoVolumeEn == null) {
//                $idProduto = $produtoEmbEn->getProduto()->getId();
//                $grade = $produtoEmbEn->getProduto()->getGrade();
//                $pesoVariavel = $produtoEmbEn->getProduto()->getToleranciaNominal();
//            } else {
//                $idProduto = $produtoVolumeEn->getCodProduto();
//                $grade = $produtoVolumeEn->getGrade();
//                $pesoVariavel = $produtoVolumeEn->getProduto()->getToleranciaNominal();
//            }

            $getDataValidadeUltimoProduto = $notaFiscalRepo->buscaRecebimentoProduto($idRecebimento, $codigoBarras, $idProduto, $grade);
            if (isset($getDataValidadeUltimoProduto) && !empty($getDataValidadeUltimoProduto) && !is_null($getDataValidadeUltimoProduto['dataValidade'])) {
                $dataValidade = new Zend_Date($getDataValidadeUltimoProduto['dataValidade']);
                $dataValidade = $dataValidade->toString('dd/MM/YY');
                $this->view->dataValidade = $dataValidade;
            }

            if ($itemNF['idEmbalagem'])
                $this->_helper->viewRenderer('recebimento/embalagem-quantidade', null, true);
            elseif ($itemNF['idVolume'])
                $this->_helper->viewRenderer('recebimento/volume-quantidade', null, true);
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            $this->redirect('ler-codigo-barras', null, null, array('idRecebimento' => $idRecebimento));
        }

        $normasPaletizacao = $this->em->getRepository('wms:Produto\NormaPaletizacao')->getUnitizadoresByProduto($itemNF['idProduto'],$itemNF['grade']);
        $this->view->normasPaletizacao = $normasPaletizacao;

//        if ( !empty($pesoVariavel) )
//            $pesoVariavel = "S";
//        else
//            $pesoVariavel = "N";

        $this->view->pesoVariavel = $pesoVariavel;
        $this->view->recebimento = $recebimentoEntity;
        $form->setDefault('idRecebimento', $idRecebimento);
        $this->view->form = $form;
    }

    public function produtoConferenciaAction()
    {
        $params = $this->getRequest()->getParams();
        extract($params);

        $recebimentoRepo = $this->em->getRepository('wms:Recebimento');
        $notaFiscalItemRepo = $this->em->getRepository('wms:NotaFiscal\Item');

        try {
            // data has been sent
            if (!$this->getRequest()->isPost())
                throw new \Exception('Escaneie o volume/embalagem novamente.');

            // verifica se tem ordem de servico aberto
            $retorno = $recebimentoRepo->checarOrdemServicoAberta($idRecebimento);
            $idOrdemServico = $retorno['id'];

            // item conferido
            $notaFiscalItemEntity = $notaFiscalItemRepo->find($idItem);
            $idProduto = $notaFiscalItemEntity->getProduto()->getId();
            $grade = $notaFiscalItemEntity->getGrade();
            $produtoEn = $this->getEntityManager()->getRepository("wms:Produto")->findOneBy(array('id'=>$idProduto,'grade'=>$grade));

            if ($produtoEn->getValidade() == "S") {

                if (!isset($params['dataValidade']) || empty($params['dataValidade'])){
                    $this->_helper->messenger('error', 'Informe uma data de validade correta');
                    $this->redirect('ler-codigo-barras', 'recebimento', null, array('idRecebimento' => $idRecebimento));
                }

                $shelfLife = $produtoEn->getDiasVidaUtil();
                $hoje = new Zend_Date;
                $PeriodoUtil = $hoje->addDay($shelfLife);

                $dataValida = true;
                if (strlen($params['dataValidade']) < 8) {
                    $dataValida = false;
                } else {
                    $dia = substr($params['dataValidade'],0,2);
                    if ($dia == false) $dataValida = false;
                    $mes = substr($params['dataValidade'],3,2);
                    if ($mes == false) $dataValida = false;
                    $ano = substr($params['dataValidade'],6,2);
                    if ($mes == false) $dataValida = false;

                    if ($dataValida == true) {
                        $data = $dia . "/" . $mes . "/20" . $ano;
                        if (checkdate($mes,$dia,"20".$ano) == false) $dataValida = false;
                    }
                }

                if ($dataValida == false) {
                    $this->_helper->messenger('error', 'Informe uma data de validade correta');
                    $this->redirect('ler-codigo-barras', 'recebimento', null, array('idRecebimento' => $idRecebimento));
                }

                $params['dataValidade'] = new Zend_Date($data);
                if ($params['dataValidade'] <= $PeriodoUtil) {
                    //autoriza recebimento?
                    $arrayRedirect = array(
                        'idRecebimento' => $idRecebimento,
                        'idOrdemServico' => $idOrdemServico,
                        'qtdConferida' => $qtdConferida,
                        'idNormaPaletizacao' => $idNormaPaletizacao,
                        'dataValidade' => $params['dataValidade'],
                        'idProduto' => $idProduto, 'grade' => $grade);

                    if ($this->_hasParam('idProdutoEmbalagem')) {
                        $arrayRedirect['idProdutoEmbalagem'] = $idProdutoEmbalagem;
                    }

                    if ($this->_hasParam('idProdutoVolume')) {
                        $arrayRedirect['idProdutoVolume'] = $idProdutoVolume;
                    }
                    $this->redirect('autoriza-recebimento', 'recebimento', null, $arrayRedirect );
                }
                $params['dataValidade'] = $params['dataValidade']->toString('Y-MM-dd');
            } else {
                $params['dataValidade'] = null;
            }

            if ($produtoEn->getPossuiPesoVariavel() == 'S'){
                if (empty($params['numPeso'])) {
                    $this->_helper->messenger('error', 'Informe o peso para conferência');
                    $this->redirect('ler-codigo-barras', 'recebimento', null, array('idRecebimento' => $idRecebimento));
                } else {
                    $quantidade = (int) $qtdConferida;
                    $pesoDigitado = str_replace(",",".",$params['numPeso']);
                    $params['numPeso'] = str_replace(",",".",$params['numPeso']);
                    $parametros['COD_PRODUTO'] = $produtoEn->getId();
                    $parametros['DSC_GRADE'] = $produtoEn->getGrade();
                    $tolerancia = str_replace(",",".",$produtoEn->getToleranciaNominal());

                    $pesoProduto = $this->em->getRepository('wms:Produto')->getPesoProduto($parametros);
                    $volumes = (int) $this->em->getRepository('wms:Produto\Volume')->findOneBy(array('codProduto' => $parametros['COD_PRODUTO'], 'grade' => $parametros['DSC_GRADE']));

                    if (!empty($volumes) && count($volumes) != 0){
                        $peso = (float) $pesoProduto[0]['NUM_PESO'] / count($volumes);
                    } else {
                        $peso = (float) $pesoProduto[0]['NUM_PESO'];
                    }

                    $pesoUnitarioMargemS = (float)  ( $pesoDigitado/$quantidade ) + $tolerancia;
                    $pesoUnitarioMargemI = (float)  ( $pesoDigitado/$quantidade ) - $tolerancia;

                    if (!($peso <= $pesoUnitarioMargemS && $peso >= $pesoUnitarioMargemI)){
                        $this->_helper->messenger('error', 'O peso informado não confere com a tolerância permitida');
                        $this->redirect('ler-codigo-barras', 'recebimento', null, array('idRecebimento' => $idRecebimento));
                    } else {
                        if ( !empty($volumes) && count($volumes)!=0 ){
                            $params['numPeso'] = (float)$params['numPeso'] / count($volumes);
                        } else {
                            $params['numPeso'] = (float)$params['numPeso'];
                        }
                    }
                }
            } else {
                $params['numPeso'] = null;
            }

            // caso embalagem
            if ($this->_hasParam('idProdutoEmbalagem')) {
                // gravo conferencia do item
                $recebimentoRepo->gravarConferenciaItemEmbalagem($idRecebimento, $idOrdemServico, $idProdutoEmbalagem, $qtdConferida, $idNormaPaletizacao, $params, $params['numPeso']);
                $this->_helper->messenger('success', 'Conferida Quantidade Embalagem do Produto. ' . $idProduto . ' - ' . $grade . '.');
            }

            // caso volume
            if ($this->_hasParam('idProdutoVolume')) {
                $recebimentoRepo->gravarConferenciaItemVolume($idRecebimento, $idOrdemServico, $idProdutoVolume, $qtdConferida, $idNormaPaletizacao, $params, $params['numPeso']);
                $this->_helper->messenger('success', 'Conferida Quantidade Volume do Produto. ' . $idProduto . ' - ' . $grade . '.');
            }

            // tudo certo, redireciono para a nova leitura
            $this->redirect('ler-codigo-barras', 'recebimento', null, array('idRecebimento' => $idRecebimento));
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            $this->redirect('ler-codigo-barras', null, null, array('idRecebimento' => $idRecebimento));
        }
    }

    //modal para autorização de recebimento
    public function autorizaRecebimentoAction()
    {
        $request = $this->getRequest();
        $params = $this->_getAllParams();
        /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
        $recebimentoRepo = $this->em->getRepository('wms:Recebimento');

        if (isset($params['conferenciaCega'])) {
            $this->view->idOrdemServico = $params['idOrdemServico'];
            $this->view->qtdNFs = $params['qtdNFs'];
            $this->view->qtdAvarias = $params['qtdAvarias'];
            $this->view->qtdConferidas = $params['qtdConferidas'];
            $this->view->idConferente = $params['idConferente'];
            $this->view->unMedida = $params['unMedida'];
            $this->view->dataValidade = $params['dataValidade'];
            $this->view->conferenciaCega = $params['conferenciaCega'];
        }
        if ($request->isPost()) {
            $senhaDigitada = $params['senhaConfirmacao'];
            $senhaAutorizacao = $this->getSystemParameterValue('SENHA_AUTORIZAR_DIVERGENCIA');
            $submit = $params['btnFinalizar'];

            if ($params['conferenciaCega'] == true) {
                $idOrdemServico = unserialize($params['idOrdemServico']);
                $qtdNFs = unserialize($params['qtdNFs']);
                $qtdAvarias = unserialize($params['qtdAvarias']);
                $qtdConferidas = unserialize($params['qtdConferidas']);
                $idConferente = unserialize($params['idConferente']);
                $unMedida = unserialize($params['unMedida']);
                $dataValidade = unserialize($params['dataValidade']);
            } else {
                $idRecebimento = $params['idRecebimento'];
                $idOrdemServico = $params['idOrdemServico'];
                $idProdutoVolume = $params['idProdutoVolume'];
                $idProdutoEmbalagem = $params['idProdutoEmbalagem'];
                $qtdConferida = $params['qtdConferida'];
                $idNormaPaletizacao = $params['idNormaPaletizacao'];
                $params['dataValidade'] = new Zend_Date($params['dataValidade']);
                $params['dataValidade'] = $params['dataValidade']->toString('Y-MM-dd');
                $idProduto = $params['idProduto'];
                $grade = $params['grade'];
            }
            if ($submit == 'semConferencia') {
                if ($senhaDigitada == $senhaAutorizacao) {
                    if ($params['conferenciaCega'] == true) {
                        $result = $recebimentoRepo->executarConferencia($idOrdemServico, $qtdNFs, $qtdAvarias, $qtdConferidas, $idConferente, true, $unMedida, $dataValidade);

                        if ($result['exception'] != null) {
                            throw $result['exception'];
                        }
                        if ($result['message'] != null) {
                            $this->addFlashMessage('success', $result['message']);
                        }
                        if ($result['concluido'] == true) {
                            $this->redirect('index','recebimento','web');
                        } else {
                            $this->redirect('divergencia','recebimento','web',array('id' => $idOrdemServico));
                        }
                    }
                    // gravo conferencia do item
                    if (isset($idProdutoVolume)) {
                        $recebimentoRepo->gravarConferenciaItemVolume($idRecebimento, $idOrdemServico, $idProdutoVolume, $qtdConferida, $idNormaPaletizacao, $params);
                        $this->_helper->messenger('success', 'Conferida Quantidade Volume do Produto. ' . $idProduto . ' - ' . $grade . '.');
                    } elseif (isset($idProdutoEmbalagem)) {
                        $recebimentoRepo->gravarConferenciaItemEmbalagem($idRecebimento, $idOrdemServico, $idProdutoEmbalagem, $qtdConferida, $idNormaPaletizacao, $params);
                        $this->_helper->messenger('success', 'Conferida Quantidade Embalagem do Produto. ' . $idProduto . ' - ' . $grade . '.');
                    }
                    $this->redirect('ler-codigo-barras', 'recebimento', null, array('idRecebimento' => $idRecebimento));
                } else {
                    $this->addFlashMessage('error', 'Senha informada não é válida');
                    if ($params['conferenciaCega'] == true) {
                        $this->redirect('conferencia','recebimento','web',array('idOrdemServico' => $idOrdemServico));
                    } else {
                        $this->redirect('ler-codigo-barras', 'recebimento', null, array('idRecebimento' => $idRecebimento));
                    }
                }
            }
        }
    }

}

