<?php

use Wms\Controller\Action,
    Wms\Util\Coletor as ColetorUtil,
    Wms\Module\Mobile\Form\ProdutoBuscar as ProdutoBuscarForm,
    Wms\Module\Mobile\Form\ProdutoQuantidade as ProdutoQuantidadeForm;

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

    public function observacoesRecebimentoAction()
    {
        $this->view->idRecebimento = $this->_getParam('id');

        $this->view->observacoes = $this->em->getRepository('wms:Recebimento\Andamento')->findBy(array('recebimento' => $this->view->idRecebimento, 'tipoAndamento' => 456));

    }

    public function finalizarAction(){
        $idOS = $this->_getParam("os");
        $idRecebimento = $this->_getParam("id");

        $this->em->beginTransaction();
        try {
            /** @var \Wms\Domain\Entity\Recebimento\DescargaRepository $descargaRepo */
            $descargaRepo = $this->em->getRepository('wms:Recebimento\Descarga');
            if ($descargaRepo->realizarDescarga($idRecebimento) === true) {
                $this->redirect('descarga', 'recebimento', 'mobile', array('recb' => $idRecebimento, 'os' => $idOS));
            }

            /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
            $recebimentoRepo = $this->em->getRepository('wms:Recebimento');

            /** @var \Wms\Domain\Entity\Recebimento $recebimentoEntity */
            $recebimentoEntity = $recebimentoRepo->find($idRecebimento);

            if (!$recebimentoEntity)
                throw new \Exception("Recebimento $idRecebimento não encontrado");

            if (!$recebimentoEntity->inOperacao())
                throw new Exception("Não pode iniciar a conferênca deste recebimento, ele está " . $recebimentoEntity->getDscStatus());

            $result = $recebimentoRepo->conferenciaColetor($idRecebimento, $idOS);

            if ($result['exception'] != null) {
                throw $result['exception'];
            }
            $this->em->flush();
            $this->em->commit();

            if ($result['concluido'] == true) {
                $this->addFlashMessage('success', $result['message']);
            } else {
                $this->addFlashMessage('error', $result['message']);
            }
        } catch (Exception $e) {
            $this->em->rollback();
            $this->addFlashMessage('error', $e->getMessage());
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
            $dataValidadeValida = $this->getRequest()->getParam('dataValidadeValida');
            $idRecebimento = $this->getRequest()->getParam('idRecebimento');
            $recebimentoRepo = $this->em->getRepository('wms:Recebimento');
            $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');
            $this->view->dataValidadeValida = $dataValidadeValida;

            $recebimentoEntity = $recebimentoRepo->find($idRecebimento);

            if (!$recebimentoEntity)
                throw new \Exception("Recebimento $idRecebimento não encontrado");

            if (!$recebimentoEntity->inOperacao())
                throw new Exception("Não pode iniciar a conferênca deste recebimento, ele está " . $recebimentoEntity->getDscStatus());

            $notaFiscalEntity = $notaFiscalRepo->findOneBy(array('recebimento' => $idRecebimento));

            if ($notaFiscalEntity) {
                $this->view->placaVeiculo   = $notaFiscalEntity->getPlaca();
                $this->view->fornecedor     = $notaFiscalEntity->getFornecedor()->getPessoa()->getNome();
            }

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
            $this->view->error = $this->getRequest()->getParam('error');
        } catch (\Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
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
        $idRecebimento = $this->getRequest()->getParam('idRecebimento');

        try {
            // carrega js da pg
            $this->view->headScript()->appendFile($this->view->baseUrl() . '/wms/resources/mobile/recebimento/produto-quantidade.js');

            $codigoBarras = $this->getRequest()->getParam('codigoBarras');

            // testa codigo de barras
            $codigoBarras = ColetorUtil::adequaCodigoBarras($codigoBarras);

            $form = new ProdutoQuantidadeForm;

            /** @var \Wms\Domain\Entity\Recebimento $recebimentoEntity */
            $recebimentoEntity = $this->em->find('wms:Recebimento', $idRecebimento);

            if (!$recebimentoEntity)
                throw new \Exception("Recebimento $idRecebimento não encontrado");

            if (!$recebimentoEntity->inOperacao())
                throw new Exception("Não pode iniciar a conferênca deste recebimento, ele está " . $recebimentoEntity->getDscStatus());

            /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
            $notaFiscalRepo = $this->em->getRepository('wms:NotaFiscal');

            $itemNF = $notaFiscalRepo->buscarItemPorCodigoBarras($idRecebimento, $codigoBarras);

            if ($itemNF == null)
                throw new \Exception('Nenhuma unidade ativa foi encontrada no recebimento com este código de barras. - ' . $codigoBarras);

            $idProduto = $itemNF['idProduto'];
            $grade = $itemNF['grade'];
            $pesoVariavel = $itemNF['possuiPesoVariavel'];

            $produtoEntity = $this->em->getRepository('wms:Produto')->findOneBy(array('id' => $idProduto, 'grade' => $grade));

            $this->view->itemNF = $itemNF;
            $form->setDefault('idNormaPaletizacao', $itemNF['idNorma']);

            $getDataValidadeUltimoProduto = $notaFiscalRepo->buscaRecebimentoProduto($idRecebimento, $codigoBarras, $idProduto, $grade);
            if (isset($getDataValidadeUltimoProduto) && !empty($getDataValidadeUltimoProduto) && !is_null($getDataValidadeUltimoProduto['dataValidade'])) {
                /**
                 * Deprecated: iconv_set_encoding(): Use of iconv.internal_encoding is deprecated in C:\wamp64\www\Imperium\wms\library\Zend\Locale\Format.php 
                    $dataValidade = new Zend_Date($getDataValidadeUltimoProduto['dataValidade']);
                    $dataValidade = $dataValidade->toString();
                 * 
                 */
                $dataValidade = date('d/m/Y H:i:s', strtotime($getDataValidadeUltimoProduto['dataValidade']));
                $this->view->dataValidade = $dataValidade;
            }

            if ($itemNF['idEmbalagem'])
                $this->_helper->viewRenderer('recebimento/embalagem-quantidade', null, true);
            elseif ($itemNF['idVolume'])
                $this->_helper->viewRenderer('recebimento/volume-quantidade', null, true);

            if ($itemNF['idTipoComercializacao'] == \Wms\Domain\Entity\Produto::TIPO_UNITARIO) {
                /** @var \Wms\Domain\Entity\Produto\NormaPaletizacaoRepository $normaRepo */
                $normaRepo = $this->em->getRepository('wms:Produto\NormaPaletizacao');
                $normasPaletizacao = $normaRepo->getNormasByProduto($idProduto, $grade);
            } else {
                $normasPaletizacao[$itemNF['idNorma']] = $itemNF["dscUnitizador"];
            }

            if ($this->getSystemParameterValue('UTILIZA_GRADE') == 'S') {
                $stringProduto = "O produto '$idProduto' grade '$grade'";
            } else {
                $stringProduto = "O produto '$idProduto' - " . $produtoEntity->getDescricao();
            }

            if (empty($normasPaletizacao))
                throw new Exception("$stringProduto não tem nenhum dado logistico cadastrado");

            $this->view->normasPaletizacao = $normasPaletizacao;

            $dscEmbFracionavelDefault = null;
            if ($itemNF['indFracionavel'] == 'S' && $itemNF['embFracDefault'] == 'S') {
                $prodEmbRepo = $this->_em->getRepository("wms:Produto\Embalagem");
                $args = [
                    "codProduto" => $idProduto,
                    "grade" => $grade,
                    "isEmbFracionavelDefault" => "N",
                    "dataInativacao" => null,
                    "isEmbExpDefault" => "N"
                ];
                $embArmazenagem = $prodEmbRepo->findBy($args, array("quantidade" => "DESC"));
                if (!empty($embArmazenagem)) {
                    $dscEmbFracionavelDefault = $embArmazenagem[0]->getDescricao();
                }
            } elseif ($itemNF['indFracionavel'] == 'S' && $itemNF['embFracDefault'] == 'N') {
                $dscEmbFracionavelDefault = $itemNF['descricaoEmbalagem'];
            }

            $this->view->dscEmbFracDefault = $dscEmbFracionavelDefault;
            $this->view->pesoVariavel = $pesoVariavel;
            $this->view->controlaLote = $itemNF['controlaLote'];
            $this->view->embFracionavelDefault = $itemNF['embFracDefault'];
            $this->view->indFracionavel = $itemNF['indFracionavel'];
            $this->view->recebimento = $recebimentoEntity;
            $form->setDefault('idRecebimento', $idRecebimento);
            $this->view->form = $form;
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            $this->redirect('ler-codigo-barras', null, null, array('idRecebimento' => $idRecebimento));
        }
    }

    public function produtoConferenciaAction()
    {
        $params = $this->getRequest()->getParams();
        extract($params);
        $dataValidadeValida = 'true';

        /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
        $recebimentoRepo = $this->em->getRepository('wms:Recebimento');
        /** @var \Wms\Domain\Entity\Recebimento\EmbalagemRepository $recebimentoEmbalagemRepository */
        $recebimentoEmbalagemRepository = $this->em->getRepository('wms:Recebimento\Embalagem');

        $this->em->beginTransaction();
        try {
            // data has been sent
            if (!$this->getRequest()->isPost())
                throw new \Exception('Escaneie o volume/embalagem novamente.');

            /** @var \Wms\Domain\Entity\Recebimento $recebimentoEn */
            $recebimentoEn = $recebimentoRepo->find($idRecebimento);

            if (!$recebimentoEn)
                throw new \Exception("Recebimento $idRecebimento não encontrado");

            if (!$recebimentoEn->inOperacao())
                throw new Exception("Não pode iniciar a conferênca deste recebimento, ele está " . $recebimentoEn->getDscStatus());

            if (!isset($qtdUnidFracionavel)) {
                $qtdUnidFracionavel = 1;
            }

            // verifica se tem ordem de servico aberto
            $retorno = $recebimentoRepo->checarOrdemServicoAberta($idRecebimento);
            $idOrdemServico = $retorno['id'];

            /** @var \Wms\Domain\Entity\Produto $produtoEn */
            $produtoEn = null;
            if (!empty($idProdutoEmbalagem)) {
                $produtoEn = $this->em->find("wms:Produto\Embalagem", $idProdutoEmbalagem)->getProduto();
            }
            elseif (!empty($idProdutoVolume)) {
                $produtoEn = $this->em->find("wms:Produto\Volume", $idProdutoVolume)->getProduto();
            }
            else {
                throw new Exception("Elemento embalagem/volume não identificado!");
            }

            $idProduto = $produtoEn->getId();
            $grade = $produtoEn->getGrade();
            $qtds = [];
            if ($produtoEn->getIndControlaLote() == 'S' && (!isset($params['lotes']) || empty($params['lotes']))) {
                throw new Exception("Nenhum lote foi definido para esta contagem!");
            } elseif ($produtoEn->getIndControlaLote() == 'N'){
                $qtds[\Wms\Domain\Entity\Produto\Lote::NCL] = [
                    "lote" => null,
                    "qtd" => (isset($isEmbFracDefault) && $isEmbFracDefault == 'S') ? (float) $qtdConferida[0] : (int) $qtdConferida[0]
                ];
            } else {
                foreach ($params["lotes"] as $i => $lote){

                    /** @var \Wms\Domain\Entity\Produto\LoteRepository $loteRepo */
                    $loteRepo = $this->em->getRepository("wms:Produto\Lote");
                    if (empty($loteRepo->getLoteRecebimento($lote, $idProduto, $grade)))
                        throw new Exception("O lote $lote não foi encontrado");

                    $qtds["$lote--$i"] = [
                        "lote" => $lote,
                        "qtd" => (isset($isEmbFracDefault) && $isEmbFracDefault == 'S') ? (float) $qtdConferida[$i] : (int) $qtdConferida[$i]
                    ];
                }
            }

            $i = 0;
            foreach ($qtds as $index) {
                $lote = $index["lote"];
                $qtdConferida = $index["qtd"];
                if ($produtoEn->getPossuiPesoVariavel() == 'S') {
                    if (empty($params['numPeso'])) {
                        $this->_helper->messenger('error', 'Informe o peso para conferência');
                        $this->redirect('ler-codigo-barras', 'recebimento', null, array('idRecebimento' => $idRecebimento));
                    } else {
                        $params['numPeso'] = str_replace(",", ".", $params['numPeso']);
                        $parametros['COD_PRODUTO'] = $produtoEn->getId();
                        $parametros['DSC_GRADE'] = $produtoEn->getGrade();
                        $qtdConferida = (float)str_replace(",", ".", $params['numPeso']);

                        $volumes = (int)$this->em->getRepository('wms:Produto\Volume')->findOneBy(array('codProduto' => $parametros['COD_PRODUTO'], 'grade' => $parametros['DSC_GRADE']));

                        if (!empty($volumes) && count($volumes) != 0) {
                            $params['numPeso'] = (float)$params['numPeso'] / count($volumes);
                        } else {
                            $params['numPeso'] = (float)$params['numPeso'];
                        }
                    }
                } else {
                    $params['numPeso'] = null;
                }

                $qtdBloqueada = 0;
                if ($produtoEn->getValidade() == "S" && $i === 0) {
                    if (!isset($params['dataValidade']) || empty($params['dataValidade'])) {
                        $this->_helper->messenger('error', 'Informe uma data de validade correta');
                        $this->redirect('ler-codigo-barras', 'recebimento', null, array('idRecebimento' => $idRecebimento));
                    }

                    $shelfLife = $produtoEn->getDiasVidaUtil();
                    $shelfLifeMax = $produtoEn->getDiasVidaUtilMax();
                    if (is_null($shelfLife) || $shelfLife == '')
                        throw new Exception("O parametro 'Dias de vencimento' do produto " . $produtoEn->getId() . " está vazio.");

                    $data = null;
                    if (strlen($params['dataValidade']) >= 8) {
                        list ($dia, $mes, $ano) = explode('/', $params['dataValidade']);
                        $ano = substr(date("Y"), 0, 2) . $ano;
                        if (checkdate((int)$mes, (int)$dia, (int)$ano))
                            $data = $dia . "/" . $mes . "/" . $ano;
                    }

                    if (empty($data)) {
                        $this->_helper->messenger('error', 'Informe uma data de validade correta');
                        $this->redirect('ler-codigo-barras', 'recebimento', null, array('idRecebimento' => $idRecebimento));
                    }
                    $dateConf = date_create_from_format('Y-m-d', "$ano-$mes-$dia");
                    $PeriodoUtil = date_create_from_format('Y-m-d', date('Y-m-d', strtotime("+$shelfLife day", strtotime(date('Y-m-d')))));
                    $PeriodoUtilMax = date_create_from_format('Y-m-d', date('Y-m-d', strtotime("+$shelfLifeMax day", strtotime(date('Y-m-d')))));
                    $objData = new Zend_Date($data);
                    if ($dateConf < $PeriodoUtil || $dateConf > $PeriodoUtilMax) {
                        if ($dateConf > $PeriodoUtilMax) {
                            $dataValidadeValida = 'false';
                            throw new \Exception('Data de validade maior que a definida no cadastro.');
                        } else if ($dateConf < $PeriodoUtil) {
                            $qtdBloqueada = $qtdConferida;
                            $qtdConferida = 0;
                            $dataValidadeValida = 'false';
                            $msgErro = 'Produto conferido com validade menor que a permitida. A autorização será obrigatória para finalização do recebimento';
                        }


                        $recebimentoEmbalagemEntities = $recebimentoEmbalagemRepository->getEmbalagemByRecebimento($idRecebimento, $produtoEn->getId(), $produtoEn->getGrade(), true, null, true);

                        foreach ($recebimentoEmbalagemEntities as $recebimentoEmbalagemEntity) {
                            list($diaComp, $mesComp, $anoComp) = explode('/', $recebimentoEmbalagemEntity->getDataValidade()->format('d/m/Y'));
                            if ($recebimentoEmbalagemEntity->getQtdConferida() > 0 && date_create_from_format('Y-m-d', "$anoComp-$mesComp-$diaComp") == $dateConf) {
                                $qtdConferida = $qtdBloqueada;
                                $qtdBloqueada = 0;
                                $dataValidadeValida = 'true';
                                break;
                            }
                        }
                    }
                    $params['dataValidade'] = $objData->toString('yyyy-MM-dd');
                } else {
                    $params['dataValidade'] = null;
                }

                // caso embalagem
                if ($this->_hasParam('idProdutoEmbalagem')) {
                    // gravo conferencia do item
                    $recebimentoRepo->gravarConferenciaItemEmbalagem($idRecebimento, $idOrdemServico, $idProdutoEmbalagem, $qtdConferida, $qtdUnidFracionavel, $idNormaPaletizacao, $params, $params['numPeso'], $qtdBloqueada, null, $lote);
                }

                // caso volume
                if ($this->_hasParam('idProdutoVolume')) {
                    $recebimentoRepo->gravarConferenciaItemVolume($idRecebimento, $idOrdemServico, $idProdutoVolume, $qtdConferida, $idNormaPaletizacao, $params, $params['numPeso'], $qtdBloqueada, null, $lote);
                }
                $i++;
            }


            if ($this->getSystemParameterValue('UTILIZA_GRADE') == 'S') {
                $stringProduto = "$idProduto - $grade.";
            } else {
                $stringProduto = "$idProduto - " . $produtoEn->getDescricao();
            }

            $mensagem = "Conferida Quantidade Embalagem do Produto $stringProduto";
            if (isset($msgErro)) {
                $mensagem = $msgErro;
            }

            $this->em->flush();
            $this->em->commit();

            $this->_helper->messenger('success', $mensagem);
            $args = ["idRecebimento" => $idRecebimento];
            $args['dataValidadeValida'] = $dataValidadeValida;

            // tudo certo, redireciono para a nova leitura
            $this->redirect('ler-codigo-barras', 'recebimento', null, $args);
        } catch (\Exception $e) {
            $this->em->rollback();
            $this->_helper->messenger('error', $e->getMessage());
            $this->redirect('ler-codigo-barras', null, null, array('idRecebimento' => $idRecebimento, 'dataValidadeValida' => $dataValidadeValida));
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
            $this->view->qtdUnidFracionavel = $params['qtdUnidFracionavel'];
            $this->view->norma = $params['idNormaPaletizacao'];
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
                $qtdUnidFracionavel = unserialize($params['qtdUnidFracionavel']);
                $norma = unserialize($params['norma']);
            } else {
                $idRecebimento = $params['idRecebimento'];
                $idOrdemServico = $params['idOrdemServico'];
                if (isset($params['idProdutoVolume']) && !empty($params['idProdutoVolume']))
                    $idProdutoVolume = $params['idProdutoVolume'];
                if (isset($params['idProdutoEmbalagem']) && !empty($params['idProdutoEmbalagem']))
                    $idProdutoEmbalagem = $params['idProdutoEmbalagem'];
                $qtdConferida = $params['qtdConferida'];
                $idNormaPaletizacao = $params['idNormaPaletizacao'];
                $params['dataValidade'] = new Zend_Date($params['dataValidade']);
                $params['dataValidade'] = $params['dataValidade']->toString('Y-MM-dd');
                $qtdUnidFracionavel = (isset($params['qtdUnidFracionavel']) and !empty($params['qtdUnidFracionavel'])) ? $params['qtdUnidFracionavel'] : null;
                $idProduto = $params['idProduto'];
                $grade = $params['grade'];
            }

            $produtoEntity = $this->em->getRepository('wms:Produto')->findOneBy(array('id' => $idProduto, 'grade' => $grade));
            if ($this->getSystemParameterValue('UTILIZA_GRADE') == 'S') {
                $stringProduto = "$idProduto - $grade.";
            } else {
                $stringProduto = "$idProduto - " . $produtoEntity->getDescricao();
            }

            if ($submit == 'semConferencia' || $submit == 'Autorizar Recebimento') {
                if ($senhaDigitada == $senhaAutorizacao) {
                    if ($params['conferenciaCega'] == true) {

                        $qtdBloqueada = $recebimentoRepo->getQuantidadeConferidaBloqueada($idRecebimento);
                        if (count($qtdBloqueada))
                            return array(
                                'message' => 'Existem itens bloqueados por validade! Não é possível finalizar',
                                'exception' => null,
                                'concluido' => false);

                        $result = $recebimentoRepo->executarConferencia($idOrdemServico, $qtdNFs, $qtdAvarias, $qtdConferidas, $norma, $qtdUnidFracionavel, $idConferente, true, $unMedida, $dataValidade);

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
                        $recebimentoRepo->gravarConferenciaItemVolume($idRecebimento, $idOrdemServico, $idProdutoVolume, $qtdConferida, $qtdUnidFracionavel, $idNormaPaletizacao, $params);
                        $this->_helper->messenger('success', 'Conferida Quantidade Volume do Produto. ' . $idProduto . ' - ' . $grade . '.');
                    } elseif (isset($idProdutoEmbalagem)) {
                        $recebimentoRepo->gravarConferenciaItemEmbalagem($idRecebimento, $idOrdemServico, $idProdutoEmbalagem, $qtdConferida, $qtdUnidFracionavel, $idNormaPaletizacao, $params);
                        $this->_helper->messenger('success', "Conferida Quantidade Embalagem do Produto " . $stringProduto);
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