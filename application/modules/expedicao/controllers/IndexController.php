<?php

use Wms\Domain\Entity\Expedicao\CaixaEmbalado;
use Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbalado;
use Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository;
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Expedicao as ExpedicaoGrid,
    Wms\Service\Coletor as LeituraColetor,
    Wms\Domain\Entity\Expedicao,
    Wms\Module\Web\Form\Subform\FiltroExpedicaoMercadoria,
    Wms\Module\Web\Grid\Expedicao\PesoCargas as PesoCargasGrid,
    Wms\Util\Coletor as ColetorUtil,
    Wms\Service\Expedicao as EquipeExpedicao;
use \Wms\Module\Web\Page;

class Expedicao_IndexController extends Action {

    public function indexAction() {
        $em = $this->getEntityManager();
        $parametroPedidos = $this->getSystemParameterValue('COD_INTEGRACAO_PEDIDOS');
        $parametroPedidosTelaExpedicao = $this->getSystemParameterValue('COD_INTEGRACAO_PEDIDOS_TELA_EXP');
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Importar Pedidos ERP',
                    'cssClass' => 'btnSave',
                    'urlParams' => array(
                        'module' => 'importacao',
                        'controller' => 'gerenciamento',
                        'action' => 'index',
                        'id' => $parametroPedidos
                    ),
                    'tag' => 'a'
                )
            )
        ));

        $form = new FiltroExpedicaoMercadoria();
        $this->view->form = $form;
        $params = $this->_getAllParams();

        /** @var \Wms\Domain\Entity\Expedicao\TriggerCancelamentoCargaRepository $triggerCancelamentoCargaRepository */
        $triggerCancelamentoCargaRepository = $em->getRepository('wms:Expedicao\TriggerCancelamentoCarga');
        /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $cargaRepository */
        $cargaRepository = $em->getRepository('wms:Expedicao\Carga');
        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepository */
        $pedidoRepository = $em->getRepository('wms:Expedicao\Pedido');
        /** @var \Wms\Domain\Entity\Expedicao\ReentregaRepository $ReentregaRepository */
        $ReentregaRepository = $em->getRepository('wms:Expedicao\Reentrega');
        /** @var \Wms\Domain\Entity\Expedicao\NotaFiscalSaidaRepository $NotaFiscalSaidaRepository */
        $NotaFiscalSaidaRepository = $em->getRepository('wms:Expedicao\NotaFiscalSaida');
        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $expedicaoAndamentoRepository */
        $expedicaoAndamentoRepository = $em->getRepository('wms:Expedicao\Andamento');
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepository */
        $expedicaoRepository = $em->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
        /** @var \Wms\Domain\Entity\Integracao\ConexaoIntegracaoRepository $conexaoRepo */
        $conexaoRepo = $this->_em->getRepository('wms:Integracao\ConexaoIntegracao');

            //CANCELAR CARGAS NO WMS JA CANCELADAS NO ERP
        if ($this->getSystemParameterValue('REPLICAR_CANCELAMENTO_CARGA') == 'S') {
            $acaoEn = $acaoIntRepo->find(24);
            $cargasCanceladasEntities = [];
            try{
                $cargasCanceladasEntities = $acaoIntRepo->processaAcao($acaoEn, null, 'L');
            } catch (Exception $e) {
                $link = '<a href="/integracao/index/integracao-error-ajax" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Imprimir Relatório</a>';
                $this->addFlashMessage("info","Houve algum erro na integração automática de cancelamento de expedição! " . $link);
            }

            foreach ($cargasCanceladasEntities as $cargaCanceladaEntity) {

                /*
                 * Seta como cancelada as cargas na tabela TR_PEDIDO antes que possam ser listadas pela integração de pedidos
                 */
                $explodeIntegracoes = explode(',', $parametroPedidosTelaExpedicao);
                /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntegracaoRepository */
                $acaoIntegracaoRepository = $em->getRepository('wms:Integracao\AcaoIntegracao');
                foreach ($explodeIntegracoes as $codIntegracao) {
                    $acaoPedidoEntity = $acaoIntegracaoRepository->find($codIntegracao);
                    if (!is_null($acaoPedidoEntity->getTabelaReferencia())) {
                        $observacao = "Carga " . $cargaCanceladaEntity['COD_CARGA_EXTERNO']. " cancelada pelo ERP";

                        $query = " UPDATE " . $acaoPedidoEntity->getTabelaReferencia() . "
                                      SET IND_PROCESSADO = 'C', DSC_OBSERVACAO_INTEGRACAO = '$observacao'
                                    WHERE CARGA = " . $cargaCanceladaEntity['COD_CARGA_EXTERNO'] . "
                                      AND (IND_PROCESSADO IS NULL OR IND_PROCESSADO = 'N') ";

                        $update = true;
                        $conexaoEn = $acaoPedidoEntity->getConexao();
                        $conexaoRepo->runQuery($query, $conexaoEn, $update);
                        $em->flush();
                    }
                }

                $cargaEntity = $cargaRepository->findOneBy(array('codCargaExterno' => $cargaCanceladaEntity['COD_CARGA_EXTERNO']));
                if(!empty($cargaEntity)) {
                    /** @var Expedicao $expedicao */
                    $expedicao = $cargaEntity->getExpedicao();
                    if ($expedicao->getCodStatus() == Expedicao::STATUS_FINALIZADO || $expedicao->getIndProcessando() == 'S') {
                        $expedicaoAndamentoRepository->save('Tentativa de cancelamento da carga ' . $cargaEntity->getCodCargaExterno() . ', porém não cancelada', $cargaEntity->getCodExpedicao(), false, false);
                        continue;
                    }
                }
                if (!$cargaEntity && $cargaCanceladaEntity) {
                    $query = "UPDATE " . $acaoEn->getTabelaReferencia() . " SET IND_PROCESSADO = 'S', DTH_PROCESSAMENTO = SYSDATE WHERE ID IN ($cargaCanceladaEntity[ID]) AND (IND_PROCESSADO IS NULL OR IND_PROCESSADO = 'N')";
                    $update = true;
                    $conexaoEn = $acaoEn->getConexao();
                    $conexaoRepo->runQuery($query, $conexaoEn, $update);
                    $em->flush();
                }
                if ($cargaEntity != null) {
                    $pedidosEn = $pedidoRepository->findBy(['codCarga' => $cargaEntity->getId()]);
                    foreach ($pedidosEn as $pedidoEntity) {
                        $pedidoRepository->removeReservaEstoque($pedidoEntity->getId(), false);
                        $pedidoRepository->remove($pedidoEntity, false);
                    }

                    $ReentregaRepository->removeReentrega($cargaEntity->getId());
                    $NotaFiscalSaidaRepository->atualizaStatusNota($cargaEntity->getCodCargaExterno());
                    $cargaRepository->removeCarga($cargaEntity->getId());

                    $cargasByExpedicao = $cargaRepository->findOneBy(array('codExpedicao' => $cargaEntity->getCodExpedicao()));
                    if (!$cargasByExpedicao)
                        $expedicaoRepository->alteraStatus($cargaEntity->getExpedicao(), Expedicao::STATUS_CANCELADO);

                    $expedicaoAndamentoRepository->save('carga ' . $cargaEntity->getCodCargaExterno() . ' removida', $cargaEntity->getCodExpedicao(), false, false);

                    if ($cargaCanceladaEntity) {
                        $query = "UPDATE " . $acaoEn->getTabelaReferencia() . " SET IND_PROCESSADO = 'S', DTH_PROCESSAMENTO = SYSDATE WHERE ID IN ($cargaCanceladaEntity[ID]) AND (IND_PROCESSADO IS NULL OR IND_PROCESSADO = 'N')";
                        $update = true;
                        $conexaoEn = $acaoEn->getConexao();
                        $conexaoRepo->runQuery($query, $conexaoEn, $update);
                    }
                    $em->flush();
                }
            }
        }

        //INTEGRAR CARGAS NO MOMENTO Q ENTRAR NA TELA DE EXPEDICAO
        if (isset($parametroPedidosTelaExpedicao) && !empty($parametroPedidosTelaExpedicao)) {
            $explodeIntegracoes = explode(',', $parametroPedidosTelaExpedicao);

            try {
                /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntegracaoRepository */
                $acaoIntegracaoRepository = $em->getRepository('wms:Integracao\AcaoIntegracao');
                foreach ($explodeIntegracoes as $codIntegracao) {
                    $acaoIntegracaoEntity = $acaoIntegracaoRepository->find($codIntegracao);
                    $acaoIntegracaoRepository->processaAcao($acaoIntegracaoEntity, null, 'E', 'P', null, \Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltro::DATA_ESPECIFICA);
                }
            } catch (Exception $e) {
                $link = '<a href="/integracao/index/integracao-error-ajax" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Imprimir Relatório</a>';
                $this->addFlashMessage("info","Houve algum erro na integração automática de expedição! " . $link);
            }
        }

        $s1 = new Zend_Session_Namespace('sessionAction');
        $s1->setExpirationSeconds(900, 'action');
        $s1->action = $params;

        $s = new Zend_Session_Namespace('sessionUrl');
        $s->setExpirationSeconds(900, 'url');
        $s->url = $params;

        ini_set('max_execution_time', 3000);

        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        $dataI1 = new \DateTime;

        if (!empty($params)) {

            if (!empty($params['idExpedicao']) || !empty($params['codCargaExterno']) || !empty($params['pedido'])) {
                $idExpedicao = null;
                $idCarga = null;
                $pedido = null;

                if (!empty($params['idExpedicao']))
                    $idExpedicao = $params['idExpedicao'];


                if (!empty($params['codCargaExterno']))
                    $idCarga = $params['codCargaExterno'];

                if (!empty($params['pedido']))
                    $pedido = $params['pedido'];

                $params = array();
                $params['idExpedicao'] = $idExpedicao;
                $params['codCargaExterno'] = $idCarga;
                $params['pedido'] = $pedido;
            } else {
                if (empty($params['dataInicial1'])) {
                    $params['dataInicial1'] = $dataI1->format('d/m/Y');
                }
            }
            if (!empty($params['control']))
                $this->view->control = $params['control'];


            unset($params['control']);
        } else {
            $dataI1 = new \DateTime;

            $params = array(
                'dataInicial1' => $dataI1->format('d/m/Y'),
                'dataInicial2' => $dataI1->format('d/m/Y')
            );
            unset($params['control']);
        }

        $params['usaDeclaracaoVP'] = $this->getSystemParameterValue('USA_DECLARACAO_DE_VOLUME_PATRIMONIO');
        $form->populate($params);

        $Grid = new ExpedicaoGrid();
        $this->view->grid = $Grid->init($params)->render();

        if ($this->getSystemParameterValue('REFRESH_INDEX_EXPEDICAO') == 'S') {
            $this->view->refresh = true;
        }

        ini_set('max_execution_time', 30);
    }

    public function agruparcargasAction() {
        $id = $this->_getParam('id');
        $this->view->id = $id;

        if ($this->getRequest()->getParam('idExpedicaoNova') != '') {
            try {
                $idNova = $this->getRequest()->getParam('idExpedicaoNova');

                if ($idNova == null)
                    throw new \Exception('Você precisa informar a nova Expedição');

                if ($this->getRequest()->isPost()) {

                    $idAntiga = $this->getRequest()->getParam('idExpedicao');

                    $reservaEstoqueExpedicao = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueExpedicao")->findBy(array('expedicao' => $idAntiga));
                    if (count($reservaEstoqueExpedicao) > 0) {
                        throw new \Exception('Não é possivel agrupar essa expedição pois ela já possui reservas de Estoque');
                    }

                    /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
                    $ExpedicaoRepo = $this->_em->getRepository('wms:Expedicao');
                    /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $AndamentoRepo */
                    $AndamentoRepo = $this->_em->getRepository('wms:Expedicao\Andamento');

                    $novaExpedicaoEn = $this->_em->getReference('wms:Expedicao', $idNova);
                    $antigaExpedicaoEn = $this->_em->getReference('wms:Expedicao', $idAntiga);

                    $cargas = $ExpedicaoRepo->getCargas($idAntiga);

                    foreach ($cargas as $c) {
                        $codCarga = $c->getId();
                        $entityCarga = $this->_em->getReference('wms:Expedicao\Carga', $codCarga);
                        $entityCarga->setExpedicao($novaExpedicaoEn);
                        $this->_em->persist($entityCarga);
                        $AndamentoRepo->save("Carga " . $c->getCodCargaExterno() . " transferida pelo agrupamento de cargas", $idNova);
                    }
                    $this->_em->flush();
                    $this->_helper->messenger('success', 'Cargas migradas para a expedição ' . $idNova . ' com sucesso.');
                    return $this->redirect('index');
                }
            } catch (\Exception $e) {
                $this->_helper->messenger('error', $e->getMessage());
            }
        }
    }

    public function consultarpesoAction() {
        $id = $this->_getParam('id');

        $parametros['id'] = $id;
        $parametros['agrup'] = 'carga';

        $GridPeso = new PesoCargasGrid();
        $this->view->gridPeso = $GridPeso->init($parametros)
                ->render();

        $parametros['agrup'] = 'expedicao';
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->_em->getRepository('wms:Expedicao');
        $pesos = $ExpedicaoRepo->getPesos($parametros);

        $this->view->totalExpedicao = $pesos;
    }

    public function desagruparcargaAction()
    {
        $params = $this->_getAllParams();
        if (isset($params['placa']) && !empty($params['placa'])) {
            $idCarga = $this->_getParam('COD_CARGA');
            $placa = $params['placa'];
            $idExpedicao = $params['id'];
            /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $AndamentoRepo */
            $AndamentoRepo = $this->_em->getRepository('wms:Expedicao\Andamento');
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
            $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
            /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
            $ExpedicaoRepo = $this->_em->getRepository('wms:Expedicao');
            /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $CargaRepo */
            $CargaRepo = $this->_em->getRepository('wms:Expedicao\Carga');
            /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepository */
            $mapaSeparacaoRepository = $this->_em->getRepository('wms:Expedicao\MapaSeparacao');
            try {
                /** @var \Wms\Domain\Entity\Expedicao\Carga $cargaEn */
                $cargaEn = $CargaRepo->findOneBy(array('id' => $idCarga));

                $mapaSeparacaoCargas = $ExpedicaoRepo->getMapaSeparacaoCargasByExpedicao($idExpedicao, $idCarga,2);

                if (count($mapaSeparacaoCargas) >0) {
                    throw new \Exception('Carga não pode ser desagrupada, existem etiquetas/Mapas gerados para duas cargas distintas!');
                }

                $mapasSeparacaoExcluir = $ExpedicaoRepo->getMapaSeparacaoCargasByExpedicao($idExpedicao, $idCarga);
                foreach ($mapasSeparacaoExcluir as $mapaSeparacao) {
                    $mapaSeparacaoEn = $mapaSeparacaoRepository->find($mapaSeparacao['COD_MAPA_SEPARACAO']);
                    $this->_em->remove($mapaSeparacaoEn);
                }

                $countCortadas = $EtiquetaRepo->countByStatus(Expedicao\EtiquetaSeparacao::STATUS_CORTADO, $cargaEn->getExpedicao(), null, null, $idCarga);
                $countTotal = $EtiquetaRepo->countByStatus(null, $cargaEn->getExpedicao(), null, null, $idCarga);

                if ($countTotal >0) {
                    if ($countTotal != $countCortadas) {
                        throw new \Exception('Não é permitido desagrupar cargas que possuem etiquetas em operação');
                    } else {
                        throw new \Exception('Não é permitido desagrupar cargas que possuem todas as etiquetas cortadas');
                    }
                }

                $cargas = $ExpedicaoRepo->getCargas($cargaEn->getCodExpedicao());
                if (count($cargas) <= 1) {
                    throw new \Exception('A Expedição não pode ficar sem cargas');
                }

                /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
                $pedidoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\Pedido");
                $pedidos = $pedidoRepo->findBy(array('codCarga' => $cargaEn->getId()));
                foreach ($pedidos as $pedido) {
                    $pedidoRepo->removeReservaEstoque($pedido->getId(), false);
                    $pedido->setIndEtiquetaMapaGerado('N');
                    $this->_em->persist($pedido);
                }
                $AndamentoRepo->save("Carga " . $cargaEn->getCodCargaExterno() . " retirada da expedição atraves do desagrupamento de cargas", $cargaEn->getCodExpedicao());
                $expedicaoAntiga = $cargaEn->getCodExpedicao();
                $expedicaoEn = $ExpedicaoRepo->save($placa);
                $cargaEn->setExpedicao($expedicaoEn);
                $cargaEn->setSequencia(1);
                $cargaEn->setPlacaCarga($placa);
                $cargaEn->setPlacaExpedicao($placa);
                $this->_em->persist($cargaEn);
                if ($countCortadas > 0) {
                    $expedicaoEn->getCodStatus(EXPEDICAO::STATUS_CANCELADO);
                    $this->_em->persist($expedicaoEn);
                    $AndamentoRepo->save("Etiquetas da carga " . $cargaEn->getCodCargaExterno() . " canceladas na expedição " . $expedicaoAntiga, $expedicaoEn->getId());
                }
                $this->_em->flush();
                $this->addFlashMessage('success','Foi criado uma nova expedição com a carga desagrupada');
                $this->redirect("index", 'index', 'expedicao');
            } catch (\Exception $e) {
                $this->_helper->messenger('error', $e->getMessage());
                $this->redirect("index", 'index', 'expedicao');
            }

        } elseif (isset($params['salvar']) && empty($params['placa'])) {
            $this->_helper->messenger('error', 'É necessário digitar uma placa');
            $this->redirect("index", 'index', 'expedicao');
        }
    }

    public function semEstoqueReportAction() {
        $idExpedicao = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->_em->getRepository('wms:Expedicao');
        $result = $ExpedicaoRepo->getProdutosSemEstoqueByExpedicao($idExpedicao);
        $this->exportPDF($result, 'semEstoque.pdf', 'Produtos sem estoque na expedição', 'L');
    }

    public function imprimirAction() {
        $idExpedicao = $this->_getParam('id');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->_em->getRepository('wms:Expedicao');
        $result = $ExpedicaoRepo->getVolumesExpedicaoByExpedicao($idExpedicao);

        $this->exportPDF($result, 'volume-patrimonio', 'Relatório de Volumes Patrimônio da Expedição ' . $idExpedicao, 'L');
    }

    public function detalharPesoAjaxAction() {
        $idCarga = $this->_getParam('COD_CARGA');

        $cargaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Carga');
        $peso = $cargaRepo->getDetalhesPeso($idCarga);
        $this->exportPDF($peso, "Peso.pdf", "Detalhamento da Carga", "L");
    }

    public function declaracaoAjaxAction() {
        $idExpedicao = $this->_getParam('id');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->_em->getRepository('wms:Expedicao');
        $result = $ExpedicaoRepo->getVolumesExpedicaoByExpedicao($idExpedicao);

        $declaracaoReport = new \Wms\Module\Expedicao\Report\VolumePatrimonio();
        $declaracaoReport->imprimir($result);
    }

    public function apontamentoSeparacaoAction() {
        //adding default buttons to the page
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar',
                    'cssClass' => 'btnBack',
                    'onclick' => 'window.history.back()',
                    'urlParams' => array(
                        'action' => 'index',
                        'id' => null
                    ),
                ),
                array(
                    'label' => 'Limpar',
                    'cssClass' => 'btn limpar',
                    'style' => 'margin-top: 15px; margin-right: 10px ;  height: 20px;'
                ),array(
                    'label' => 'Fechar Mapa',
                        'cssClass' => 'btn updateSeparacao',
                    'style' => 'margin-top: 15px; margin-right: 10px ;  height: 20px;'
                ),
                array(
                    'label' => 'Efetivar Apontamento ',
                    'cssClass' => 'btn save',
                    'style' => 'margin-top: 15px; margin-right: 10px ;  height: 20px;'
                )
            )
        ));
        $pessoaFisicaRepo = $this->getEntityManager()->getRepository('wms:Pessoa\Fisica');
        /** @var \Wms\Domain\Entity\Expedicao\ApontamentoMapaRepository $apontamentoMapaRepo */
        $apontamentoMapaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ApontamentoMapa');
        /** @var \Wms\Domain\Entity\Expedicao\EquipeSeparacaoRepository $equipeSeparacaoRepo */
        $equipeSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EquipeSeparacao');
        $this->view->qtdFuncMapa = $this->getSystemParameterValue('MAX_PRODUTIVIDADE_MAPA');
        $numFunc = $equipeSeparacaoRepo->getUltimoApontamento();
        if (empty($numFunc)) {
            $func = 1;
        } else {
            $func = $numFunc['NUM_FUNC'];
        }

        $form = new \Wms\Module\Produtividade\Form\EquipeSeparacao();
        $params = $this->_getAllParams();
        $this->view->qtdFunc = $func;
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        try {
            if (isset($params['data']) && !empty($params['data'])) {
                $data = $params['data'];
                foreach ($data as $params) {
                    if ($params['tipo'] == 'Etiquetas') {
                        //FORMATA OS DADOS RECEBIDOS
                        $cpf = str_replace(array('.', '-'), '', $params['cpf']);
                        $etiquetas = explode('-', $params['etiquetas']);
                        $etiquetaInicial = trim($etiquetas[0]);
                        $etiquetaFinal = trim($etiquetas[1]);
                        $numFunc = $params['func'];

                        //ENCONTRA O USUARIO DIGITADO
                        /** @var Expedicao\EquipeSeparacao $usuarioEn */
                        $usuarioEn = $pessoaFisicaRepo->findOneBy(array('cpf' => $cpf));
                        //VERIFICA O USUARIO
                        if (is_null($usuarioEn))
                            throw new \Exception("Conferente $cpf não encontrado!");
                        //VERIFICA AS ETIQUETAS
                        if (is_null($etiquetaFinal))
                            $etiquetaFinal = $etiquetaInicial;

                        if (is_null($etiquetaInicial))
                            $etiquetaInicial = $etiquetaFinal;

                        $equipeSeparacaoEn = null;//$equipeSeparacaoRepo->getIntervaloEtiquetaUsuario($usuarioEn);

                        //SALVA OS DADOS NA TABELA EQUIPE_SEPARACAO
                        $inicial = 0;
                        $final = 0;
                        $menorIntervalo = 0;
                        if (is_array($equipeSeparacaoEn) && count($equipeSeparacaoEn) > 0) {
                            foreach ($equipeSeparacaoEn as $intervalo) {

                                if ($inicial != 0) {
                                    $iteracao = $intervalo['etiquetaInicial'] - $final;
                                    if ($iteracao > 1) {
                                        $equipeSeparacaoRepo->save($final + 1, $intervalo['etiquetaInicial'] - 1, $usuarioEn, $numFunc,false);
                                    }
                                } else {
                                    $menorIntervalo = $intervalo['etiquetaInicial'];
                                }
                                $inicial = $intervalo['etiquetaInicial'];
                                $final = $intervalo['etiquetaFinal'];
                                if ($intervalo['etiquetaFinal'] < $etiquetaInicial) {
                                    $final = $etiquetaInicial - 1;
                                }
                            }

                            if ($etiquetaInicial < $menorIntervalo) {
                                $equipeSeparacaoRepo->save($etiquetaInicial, $menorIntervalo - 1, $usuarioEn,$numFunc, false);
                            }
                            if ($etiquetaFinal > $final) {
                                $equipeSeparacaoRepo->save($final + 1, $etiquetaFinal, $usuarioEn, $numFunc, false);
                            }
                            $this->getEntityManager()->flush();
                        } else {
                            $equipeSeparacaoRepo->save($etiquetaInicial, $etiquetaFinal, $usuarioEn, $numFunc);
                        }
                    } elseif ($params['tipo'] == 'Mapa') {
                        $cpf = str_replace(array('.', '-'), '', $params['cpf']);
                        //ENCONTRA O USUARIO DIGITADO
                        $usuarioEn = $pessoaFisicaRepo->findOneBy(array('cpf' => $cpf));
                        //VERIFICA O USUARIO
                        if (is_null($usuarioEn))
                            throw new \Exception("Conferente $cpf não encontrado!");

                        $codMapaSeparacao = $params['mapa'];
                        $mapaSeparacaoEn = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao')->find($codMapaSeparacao);

                        if (is_null($mapaSeparacaoEn))
                            throw new \Exception("Mapa de Separação $codMapaSeparacao não encontrado!");

                        if ($mapaSeparacaoEn->getStatus()->getId() != 523)
                            throw new \Exception("Mapa de Separação $codMapaSeparacao não está aberto!");

                        $apontamentoMapaEn = $apontamentoMapaRepo->findOneBy(array('codUsuario' => $usuarioEn->getId(), 'mapaSeparacao' => $mapaSeparacaoEn));
                        if (!isset($apontamentoMapaEn) || empty($apontamentoMapaEn)) {
                            $apontamentoMapaRepo->save($mapaSeparacaoEn, $usuarioEn->getId());
                        }
                    }
                }
                $this->_helper->json(array('result' => 'Ok'));
                exit;
            }
        } catch (\Exception $e) {
            $this->_helper->json(array('result' => 'Error', 'msg' => $e->getMessage()));
        }

        $this->view->form = $form;
    }

    public function fechaConferenciaAjaxAction() {
        $params = $this->_getAllParams();
        $cpf = str_replace(array('.', '-'), '', $params['cpf']);
        $codMapaSeparacao = $params['mapa'];

        $pessoaFisicaRepo = $this->getEntityManager()->getRepository('wms:Pessoa\Fisica');
        /** @var \Wms\Domain\Entity\Expedicao\ApontamentoMapaRepository $apontamentoMapaRepo */
        $apontamentoMapaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ApontamentoMapa');

        $usuarioEn = $pessoaFisicaRepo->findOneBy(array('cpf' => $cpf));

        if (empty($usuarioEn)) {
            $response = array('result' => 'Error', 'msg' => "Nenhum conferente encontrado com este CPF");
            $this->_helper->json($response);
        }

        $mapaSeparacaoEn = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao')->find($codMapaSeparacao);
        if (is_null($mapaSeparacaoEn)) {
            $response = array('result' => 'Error', 'msg' => "Mapa de Separação $codMapaSeparacao não encontrado!");
            $this->_helper->json($response);
        }

        $apontamentoMapaEn = $apontamentoMapaRepo->findOneBy(array('codUsuario' => $usuarioEn->getId(), 'mapaSeparacao' => $mapaSeparacaoEn));
        if (isset($apontamentoMapaEn) && !empty($apontamentoMapaEn)) {
            $result = $apontamentoMapaRepo->update($apontamentoMapaEn);

            if ($result == true) {
                $response = array('result' => 'success', 'msg' => "Apontamento finalizado com sucesso!");
                $this->_helper->json($response);
            }
        } else {
            $response = array('result' => 'Error', 'msg' => "Apontamento não cadastrado anteriormente!");
            $this->_helper->json($response);
        }
    }

    public function verificaEtiquetaValidaAjaxAction(){

        $etiqueta = $this->_getParam('etiqueta');
        $verificaExpedicao = $this->_getParam('expedicao');
        $etiquetaInicial = $this->_getParam('etiquetaInicial');
        $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $etiquetaEn = $EtiquetaRepo->find($etiqueta);

        if(empty($etiquetaEn)){
            $response = array('result' => 'Error', 'msg' => 'Etiqueta invalida');
        }else{
            if($verificaExpedicao == 1){
                $equipeSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EquipeSeparacao');
                $etiquetaInicial = trim($etiquetaInicial);
                $etiquetaFinal = trim($etiqueta);
                $expedicaoIni = $equipeSeparacaoRepo->getExpedicao($etiquetaInicial);
                $expedicaoFim = $equipeSeparacaoRepo->getExpedicao($etiquetaFinal);
                if($expedicaoIni['COD_EXPEDICAO'] != $expedicaoFim['COD_EXPEDICAO']){
                    $response = array('result' => 'Error', 'msg' => 'Etiquetas não pertencem a mesma expedição.');
                }else{
                    $response = array('result' => 'Ok');
                }
            }else{
                $response = array('result' => 'Ok');
            }
        }
        $this->_helper->json($response);
    }

    public function conferenteApontamentoSeparacaoAjaxAction() {
        $params = $this->_getAllParams();
        $cpf = str_replace(array('.', '-'), '', $params['cpf']);
        $codMapa = 0;
        $erro = '';
        $pendenteFechamento = 'N';

        /** @var \Wms\Domain\Entity\UsuarioRepository $usuarioRepo */
        $usuarioRepo = $this->getEntityManager()->getRepository('wms:Usuario');
        $pessoaFisicaRepo = $this->getEntityManager()->getRepository('wms:Pessoa\Fisica');
        $equipeSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EquipeSeparacao');
        if (isset($params['etiquetaInicial'])) {
            //FORMATA OS DADOS RECEBIDOS

            $etiquetaInicial = trim($params['etiquetaInicial']);
            $etiquetaFinal = trim($params['etiquetaFinal']);
            $expedicaoIni = $equipeSeparacaoRepo->getExpedicao($etiquetaInicial);
            $expedicaoFim = $equipeSeparacaoRepo->getExpedicao($etiquetaFinal);
            if($expedicaoIni['COD_EXPEDICAO'] != $expedicaoFim['COD_EXPEDICAO']){
                $erro = 'Etiquetas não pertencem a mesma expedição.';
            }
            //ENCONTRA O USUARIO DIGITADO
            /** @var Expedicao\EquipeSeparacao $usuarioEn */
            $usuarioEn = $pessoaFisicaRepo->findOneBy(array('cpf' => $cpf));
            //VERIFICA O USUARIO
            if (is_null($usuarioEn)) {
                $erro = 'Nenhum conferente encontrado com este CPF';
            } else {
                $usuario = $usuarioRepo->getPessoaByCpf($cpf);
            }
            //VERIFICA AS ETIQUETAS
            if (is_null($etiquetaFinal))
                $etiquetaFinal = $etiquetaInicial;

            if (is_null($etiquetaInicial))
                $etiquetaInicial = $etiquetaFinal;


            //SALVA OS DADOS NA TABELA EQUIPE_SEPARACAO
            $inicial = 0;
            $final = 0;
            $menorIntervalo = 0;
            $salvar = false;
            if (empty($erro)) {
                $equipeSeparacaoEn = $equipeSeparacaoRepo->getIntervaloEtiquetaUsuario($usuarioEn);
                $salvar = true;
                if (is_array($equipeSeparacaoEn) && count($equipeSeparacaoEn) > 0) {
                    foreach ($equipeSeparacaoEn as $intervalo) {
                        if($etiquetaInicial >= $intervalo['etiquetaInicial'] && $etiquetaInicial <= $intervalo['etiquetaFinal']){
                            $erro = "Intervalo já bipado para ".$usuario[0]['NOM_PESSOA'];
                            $salvar = false;
                        }
                        if($etiquetaFinal >= $intervalo['etiquetaInicial'] && $etiquetaFinal <= $intervalo['etiquetaFinal']){
                            $erro = "Intervalo já bipado para ".$usuario[0]['NOM_PESSOA'];
                            $salvar = false;
                        }
                        if($etiquetaInicial <= $intervalo['etiquetaInicial'] && $etiquetaFinal >= $intervalo['etiquetaFinal']){
                            $erro = "Intervalo já bipado para ".$usuario[0]['NOM_PESSOA'];
                            $salvar = false;
                        }
                    }
                } else {
                    $salvar = true;
                }
            }
        }else{
            $erro = '';
            $usuario = $usuarioRepo->getPessoaByCpf($cpf);
            $salvar = true;
            if(!empty($usuario)) {
                if (isset($params['mapa'])) {
                    $apontamentoMapaRepository = $this->getEntityManager()->getRepository('wms:Expedicao\ApontamentoMapa');
                    if ($params['mapa'] == 'false') {
                        $mapa = $apontamentoMapaRepository->getMapaAbertoUsuario($usuario[0]['COD_PESSOA']);
                        if (!empty($mapa)) {
                            $codMapa = $mapa[0]['COD_MAPA_SEPARACAO'];
                        }
                    } else {
                        $mapa = ColetorUtil::retiraDigitoIdentificador($params['mapa']);
                        $qtdMax = $this->getSystemParameterValue('MAX_PRODUTIVIDADE_MAPA');
                        $qtdMapa = $apontamentoMapaRepository->getQtdApontamentoMapa($mapa);
                        if ($qtdMapa['QTD'] >= $qtdMax) {

                            if ($apontamentoMapaRepository->verificaApontamentoMapaUsuarioPendenteFechamento($mapa,$usuario[0]['COD_PESSOA'])) {
                                $pendenteFechamento = 'S';
                            } else {
                                $erro = 'Quantidade máxima de funcionários já vinculadas a esse mapa';
                                $salvar = false;
                            }

                        }
                    }
                    /** @var Expedicao\MapaSeparacao $mapaEn */
                    $mapaEn = $this->em->find("wms:Expedicao\MapaSeparacao", $mapa);
                    $expedicaoIni['COD_EXPEDICAO'] = $mapaEn->getCodExpedicao();
                }
            }else{
                $erro = 'Nenhum conferente encontrado com este CPF';
                $salvar = false;
            }

        }


        if (empty($erro) && $salvar == true) {
            $response = array('result' => 'Ok', 'pessoa' => $usuario[0]['NOM_PESSOA'], 'mapa' => $codMapa, 'expedicao' => $expedicaoIni['COD_EXPEDICAO'], 'dth_vinculo' => date('d/m/Y'), 'pendenteFechamento' => $pendenteFechamento);
        } elseif($salvar == false && empty($erro)) {
            $response = array('result' => 'Error', 'msg' => "Intervalo já bipado para ".$usuario[0]['NOM_PESSOA']);
        }else{
            $response = array('result' => 'Error', 'msg' => $erro);
        }

        $this->_helper->json($response);
    }

    public function buscaApontamentoSeparacaoAjaxAction(){
        $params = $this->_getAllParams();
        $etiqueta = ColetorUtil::retiraDigitoIdentificador($params['etiquetas']['etiquetaBusca']);
        $cpf = str_replace(array('.', '-'), '', $params['etiquetas']['cpfBusca']);
        $dataInicio = $params['etiquetas']['dataInicial'];
        $dataFim = $params['etiquetas']['dataFinal'];
        $expedicao = $params['etiquetas']['expedicao'];
        $equipeSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EquipeSeparacao');
        $result = $equipeSeparacaoRepo->getApontamentosProdutividade($cpf, $dataInicio, $dataFim, $etiqueta,$expedicao);
        $this->_helper->json(array('dados' => $result));
    }

    public function apagaApontamentoSeparacaoAction()
    {
        $params = $this->_getAllParams();
        $equipeSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EquipeSeparacao');
        if ($params['id'] > 0) {
            $this->_em->remove($equipeSeparacaoRepo->find($params['id']));
            $this->_em->flush();
        }
        $this->_helper->json(array());
    }

    public function equipeCarregamentoAction() {
        $form = new \Wms\Module\Expedicao\Form\EquipeCarregamento();
        $this->view->form = $form;

        $params = $this->_getAllParams();
        $grid = new \Wms\Module\Expedicao\Grid\EquipeCarregamento();
        $this->view->grid = $grid->init($params)
                ->render();
    }

    public function relatorioCodigoBarrasProdutosAction() {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000);
        $idExpedicao = $this->_getParam('id', 0);
        $gerarEtiqueta = new \Wms\Module\Web\Report\Produto\EtiquetaCodigoBarras();
        $gerarEtiqueta->init($idExpedicao);
    }

    public function acertarReservaEstoqueAjaxAction() {
        set_time_limit(0);
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueExpedicaoRepository $reservaEstoqueExpedicaoRepo */
        $reservaEstoqueExpedicaoRepo = $this->_em->getRepository('wms:Ressuprimento\ReservaEstoqueExpedicao');
        $reservaEstoqueExpedicao = $reservaEstoqueExpedicaoRepo->findBy(array('pedido' => null));

        foreach ($reservaEstoqueExpedicao as $reservaEstoqueExpedicaoEn) {
            $idExpedicao = $reservaEstoqueExpedicaoEn->getExpedicao()->getId();
            $idReservaEstoque = $reservaEstoqueExpedicaoEn->getReservaEstoque()->getId();
            $sql = "SELECT P.COD_PEDIDO FROM PEDIDO P
                    INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                    INNER JOIN CARGA C ON P.COD_CARGA = C.COD_CARGA
                    INNER JOIN EXPEDICAO E ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                    INNER JOIN RESERVA_ESTOQUE_EXPEDICAO REE ON REE.COD_EXPEDICAO = E.COD_EXPEDICAO
                    INNER JOIN RESERVA_ESTOQUE RE ON REE.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                    INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE AND REP.COD_PRODUTO = PP.COD_PRODUTO AND REP.DSC_GRADE = PP.DSC_GRADE
                    WHERE E.COD_EXPEDICAO = $idExpedicao
                    AND RE.COD_RESERVA_ESTOQUE = $idReservaEstoque";

            $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
            $codPedido = $result[0]['COD_PEDIDO'];

            /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
            $pedidoRepo = $this->_em->getRepository("wms:Expedicao\Pedido");
            $pedidoEn = $pedidoRepo->findOneBy(array('id' => $codPedido));

            $reservaEstoqueExpedicaoEn->setPedido($pedidoEn);
            $this->_em->persist($reservaEstoqueExpedicaoEn);
            $this->_em->flush();
        }
        var_dump('sucesso!');
        exit;
    }

    public function correcaoAjaxAction() {
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        $produtoRepo = $this->getEntityManager()->getRepository('wms:Produto');
        $embalagemEn = $produtoRepo->findAll();

        $count = 0;
        foreach ($embalagemEn as $embalagem) {
            $produtoId = $embalagem->getId();
            $grade = $embalagem->getGrade();
            $embalagensProduto = $embalagemRepo->findBy(array('codProduto' => $produtoId, 'grade' => $grade), array('pontoReposicao' => 'DESC'));
            foreach ($embalagensProduto as $key => $embalagemProduto) {
                if ($embalagensProduto[0]->getPontoReposicao() > $embalagemProduto->getPontoReposicao()) {
                    $count += $count;
                    $embalagemProduto->setPontoReposicao($embalagensProduto[0]->getPontoReposicao());
                    $this->getEntityManager()->persist($embalagemProduto);
                    $this->getEntityManager()->flush($embalagemProduto);
                }
            }
        }

        var_dump($count . ' Produtos Inseridos!');
        exit;
    }

    public function relatorioProdutosConferidosAjaxAction() {
        $idExpedicao = $this->_getParam('id');
        $idLinhaSeparacao = $this->_getParam('idLinhaSeparacao');

        $modeloRelatorioCarregamento = $this->getSystemParameterValue('MODELO_RELATORIO_CARREGAMENTO');

        if ($modeloRelatorioCarregamento == 1) {
            $pdf = new \Wms\Module\Expedicao\Printer\ProdutosCarregamento();
        } else {
            $pdf = new \Wms\Module\Expedicao\Printer\ProdutosCarregamento_modelo2();
        }

        $pdf->imprimir($idExpedicao, $idLinhaSeparacao);
    }

    public function relatorioProdutosClientesConferidosAjaxAction() {
        $idExpedicao = $this->_getParam('id');
        $idLinhaSeparacao = $this->_getParam('idLinhaSeparacao');

        $pdf = new \Wms\Module\Expedicao\Printer\ProdutosClienteCarregamento();
        $pdf->imprimir($idExpedicao, $idLinhaSeparacao);
    }

    public function cancelarExpedicaoAjaxAction() {
        $idExpedicao = $this->_getParam('id', 0);

        try{
            $this->em->beginTransaction();
            /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepository */
            $expedicaoRepository = $this->getEntityManager()->getRepository('wms:Expedicao');
            $expedicaoRepository->cancelarExpedicao($idExpedicao);

            $this->em->commit();
            $this->addFlashMessage('success', "cargas da expedicao $idExpedicao removidas com sucesso");
        } catch (Exception $e) {
            $this->addFlashMessage('error', "Houve uma falha ao cancelar a expedição $idExpedicao: " . $e->getMessage());
        }
        $this->_redirect('expedicao');
    }

    public function relatoriosCarregamentoAjaxAction() {
        try {
            $form = new \Wms\Module\Expedicao\Form\RelatoriosCarregamento();
            $idExpedicao = $this->_getParam('id');

            $em = $this->getEntityManager();
            $linhasSeparacao = $em->getRepository('wms:Armazenagem\LinhaSeparacao')
                    ->getLinhaSeparacaoByConferenciaExpedicao($idExpedicao);

            $possuiConferenciaConcluida = true;
            if (!isset($linhasSeparacao) || empty($linhasSeparacao)) {
//                $possuiConferenciaConcluida = false;
            }

            $form->start($linhasSeparacao);
            $this->view->form = $form;
            $this->view->idExpedicao = $idExpedicao;
            $this->view->possuiConferenciaConcluida = $possuiConferenciaConcluida;
        } catch (\Wms\Util\WMS_Exception $e) {
            $this->_helper->json(array('status' => 'error', 'msg' => $e->getMessage()));
        }
    }

    public function checkoutExpedicaoAction() {
        $form = new \Wms\Module\Expedicao\Form\CheckoutExpedicao();
        $form->init();
        $data = $this->_getAllParams();
        if (empty($data['cpfEmbalador'])) {
            $userId = \Zend_Auth::getInstance()->getIdentity()->getId();
            /** @var \Wms\Domain\Entity\Pessoa\Fisica $pf */
            $pf = $this->_em->find("wms:Pessoa\Fisica", $userId);
            $data['cpfEmbalador'] = $pf->getCPF(false);
        }
        $form->populate($data);
        $this->view->recarregar = $this->_getParam("recarregar");
        $this->view->pessoa = $this->_getParam("pessoa");
        $this->view->form = $form;
    }

    public function confirmarClienteAjaxAction() {
        $mapaSeparacaoQuebraRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoQuebra');
        $idMapaSeparacao = ColetorUtil::retiraDigitoIdentificador($this->_getParam('codigoBarrasMapa'));
        /** @var Expedicao\MapaSeparacaoQuebra $mapaSeparacaoQuebraEn */
        $mapaSeparacaoQuebraEn = $mapaSeparacaoQuebraRepo->findOneBy(array('mapaSeparacao' => $idMapaSeparacao, 'tipoQuebra' => Expedicao\MapaSeparacaoQuebra::QUEBRA_CARRINHO));

        if (!empty($mapaSeparacaoQuebraEn)) {
            $this->view->idMapa = $idMapaSeparacao;
            /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
            $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');

            $mapaSeparacaoEn = $mapaSeparacaoQuebraEn->getMapaSeparacao();
            $idExpedicao = $mapaSeparacaoEn->getExpedicao()->getId();
            /** @var Expedicao\ModeloSeparacao $modeloSeparacaoEn */
            $modeloSeparacaoEn = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao")->getModeloSeparacao($idExpedicao);

            $agrupaVolumes = ($modeloSeparacaoEn->getAgrupContEtiquetas() == 'S');
            $clientes = $mapaSeparacaoRepo->getClientesByConferencia($idMapaSeparacao, $agrupaVolumes);

            if ($agrupaVolumes && $modeloSeparacaoEn->getUsaCaixaPadrao() == 'S') {
                /** @var CaixaEmbalado $caixaEn */
                $caixaEn = $this->getEntityManager()->getRepository('wms:Expedicao\CaixaEmbalado')->findOneBy(['isAtiva' => true, 'isDefault' => true]);

                /** @var MapaSeparacaoProdutoRepository $mapaSeparacaoProdutoRepo */
                $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto');
                $arrElements = $mapaSeparacaoProdutoRepo->getMaximosConsolidadoByCliente($idExpedicao);

                /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
                $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');

                foreach ($clientes as $key => $cliente) {
                    $preCountVolCliente = CaixaEmbalado::calculaExpedicao($caixaEn, $arrElements, $cliente['COD_PESSOA']);
                    $volumes = count($mapaSeparacaoEmbaladoRepo->findBy(['mapaSeparacao' => $idMapaSeparacao, "pessoa" => $cliente['COD_PESSOA'], "status" => MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FINALIZADO]));
                    if ($volumes == $preCountVolCliente) {
                        unset($clientes[$key]);
                    }
                }
            }

            if (empty($clientes)) {
                $clientes = 'finalizar';
            }

            $cargaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\Carga');
            $this->view->clientes = $clientes;
            $cargas = $cargaRepo->findBy(array('expedicao' => $idExpedicao));
            $strCargas = ' ';
            foreach ($cargas as $cargasEnt){
                if($strCargas != ' '){
                    $strCargas .= ' - ';
                }
                $strCargas .= $cargasEnt->getCodCargaExterno();
            }
            $this->view->cargas = $strCargas;
            $this->view->idExpedicao = $idExpedicao;
            $this->view->codMapa = $idMapaSeparacao;
        }
    }

    public function getCountVolumesConsolidadoAjaxAction()
    {
        $idMapa = $this->_getParam('idMapa');
        $codPessoa = $this->_getParam('cliente');
        $mapaSepEmbClienteRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        $volsFechados = count($mapaSepEmbClienteRepo->findBy(['mapaSeparacao' => $idMapa, "pessoa" => $codPessoa]));
        $this->_helper->json($volsFechados);
    }

    public function carregaMapaAjaxAction() {
        $codBarras = ColetorUtil::retiraDigitoIdentificador($this->_getParam('codigoBarrasMapa'));
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository $mapaSepProdRepo */
        $mapaSepProdRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto');

        try {
            $operacao = $expedicaoRepo->getUrlMobileByCodBarras($codBarras);
            $codPessoa = $this->_getParam('cod_pessoa');
            $this->view->operacao = $operacao['operacao'];
            if (isset($operacao['placa'])) {
                $this->view->placa = $operacao['placa'];
            }
            if (isset($operacao['carga'])) {
                $this->view->carga = $operacao['carga'];
            }

            $this->view->expedicao = $operacao['expedicao'];
            $this->view->codMapa = $codBarras;

            $sessao = new \Zend_Session_Namespace('coletor');
            $central = $sessao->centralSelecionada;
            $this->view->separacaoEmbalado = (empty($codPessoa)) ? false : true;

            $Expedicao = new \Wms\Coletor\Expedicao($this->getRequest(), $this->em);
            $Expedicao->validacaoExpedicao();
            $Expedicao->osLiberada();

            /** @var Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
            $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
            if (empty($codPessoa)) {
                /** EXIBE OS PRODUTOS FALTANTES DE CONFERENCIA PARA O MAPA  */
                $this->view->produtos = $mapaSeparacaoRepo->validaConferencia($operacao['expedicao'], true, $codBarras, 'D');
            } else {
                /** EXIBE OS PRODUTOS FALTANTES DE CONFERENCIA PARA O MAPA DE EMBALADOS */
                $produtos = $mapaSeparacaoRepo->getProdutosConferidosByClientes($codBarras, $codPessoa);
                $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
                foreach ($produtos as $key => $value) {
                    if ($value['QUANTIDADE'] > 0) {
                        $vetSeparar = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['QUANTIDADE']);
                        $produtos[$key]['QUANTIDADE'] = implode(' - ', $vetSeparar);
                    } else {
                        $value['QUANTIDADE'] = 0;
                    }
                }
                $produtosConferidos = $mapaSeparacaoRepo->getProdutosConferidosTotalByClientes($codBarras, $codPessoa);

                if (!empty($produtosConferidos)) {
                    foreach ($produtosConferidos as $key => $value) {
                        if ($value['QUANTIDADE'] > 0) {
                            $vetSeparar = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['QUANTIDADE']);
                            $produtosConferidos[$key]['QUANTIDADE'] = implode(' - ', $vetSeparar);
                        } else {
                            $value['QUANTIDADE'] = 0;
                        }
                        if ($value['QTD_CONFERIDA'] > 0) {
                            $vetSeparar = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['QTD_CONFERIDA']);
                            $produtosConferidos[$key]['QTD_CONFERIDA_EMB'] = implode(' - ', $vetSeparar);
                        } else {
                            $value['QTD_CONFERIDA_EMB'] = 0;
                        }
                    }
                }
                $this->view->produtos = $produtos;
                $this->view->produtosConferidos = $produtosConferidos;
            }
            $idMapa = $codBarras;
            $idVolume = $this->_getParam("idVolume");
            $idExpedicao = $this->_getParam("idExpedicao");

            $resultado = $expedicaoRepo->criarOrdemServico($idExpedicao);
            $sessao->osID = $resultado['id'];
            $volumePatrimonioRepo = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio');
            /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
            $modeloSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ModeloSeparacao');
            $mapaSeparacaoQuebraRepo = $this->em->getRepository('wms:Expedicao\MapaSeparacaoQuebra');

            $dscVolume = "";
            $volumePatrimonioEn = null;
            if (!empty($idVolume)) {
                $volumePatrimonioEn = $volumePatrimonioRepo->find($idVolume);
                if (!empty($volumePatrimonioEn))
                    $dscVolume = $volumePatrimonioEn->getId() . ' - ' . $volumePatrimonioEn->getDescricao();
            }

            //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
            /** @var Expedicao\ModeloSeparacao $modeloSeparacaoEn */
            $modeloSeparacaoEn = $modeloSeparacaoRepo->getModeloSeparacao($idExpedicao);

            /** VERIFICA E CONFERE DE ACORDO COM O PARAMETRO DE TIPO DE CONFERENCIA PARA EMBALADOS E NAO EMBALADOS */
            $mapaQuebraEn = $mapaSeparacaoQuebraRepo->findOneBy(array('mapaSeparacao' => $idMapa));
            $confereQtd = false;
            $conferenciaNaoEmbalado = $modeloSeparacaoEn->getTipoConferenciaNaoEmbalado();
            $conferenciaEmbalado = $modeloSeparacaoEn->getTipoConferenciaEmbalado();

            if ($mapaQuebraEn->getTipoQuebra() == Expedicao\MapaSeparacaoQuebra::QUEBRA_CARRINHO) {
                if ($conferenciaEmbalado == Expedicao\ModeloSeparacao::CONFERENCIA_ITEM_A_ITEM) {
                    $confereQtd = true;
                }
            } else {
                if ($conferenciaNaoEmbalado == Expedicao\ModeloSeparacao::CONFERENCIA_ITEM_A_ITEM) {
                    $confereQtd = true;
                }
            }

            list($temLote, $lotesCodBarras) = $mapaSepProdRepo->getCodBarrasByLoteMapa($idMapa);
            $this->view->temLote = $temLote;
            $this->view->lotesCodBarras = json_encode($lotesCodBarras);
            $this->view->desconsideraZeroEsquerda = (self::getSystemParameterValue('DESCONSIDERA_ZERO_ESQUERDA') == 'S');
            $this->view->tipoDefaultEmbalado = $modeloSeparacaoEn->getTipoDefaultEmbalado();
            $this->view->utilizaQuebra = $modeloSeparacaoEn->getUtilizaQuebraColetor();
            $this->view->utilizaVolumePatrimonio = $modeloSeparacaoEn->getUtilizaVolumePatrimonio();
            $this->view->tipoQuebraVolume = $modeloSeparacaoEn->getTipoQuebraVolume();
            $this->view->criarVolsFinalCheckout = $modeloSeparacaoEn->getCriarVolsFinalCheckout();
            $this->view->idVolume = $idVolume;
            $this->view->idMapa = $idMapa;
            $this->view->idExpedicao = $idExpedicao;
            $this->view->central = $central;
            $this->view->idPessoa = $codPessoa;
            $this->view->separacaoEmbalado = (empty($codPessoa)) ? false : true;
            $this->view->dscVolume = $dscVolume;
            $this->view->confereQtd = $confereQtd;

            $this->view->agrupaVolumes = ($modeloSeparacaoEn->getAgrupContEtiquetas() == 'S');
            $this->view->usaCaixaPadrao = ($modeloSeparacaoEn->getUsaCaixaPadrao() == 'S');
            if ($this->view->agrupaVolumes && $this->view->usaCaixaPadrao) {
                $mapaSepEmbClienteRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
                $volsCriados = count($mapaSepEmbClienteRepo->findBy(['mapaSeparacao' => $idMapa, "pessoa" => $codPessoa]));
                /** @var CaixaEmbalado $caixaEn */
                $caixaEn = $this->getEntityManager()->getRepository('wms:Expedicao\CaixaEmbalado')->findOneBy(['isAtiva' => true, 'isDefault' => true]);

                $arrElements = $mapaSepProdRepo->getMaximosConsolidadoByCliente($idExpedicao);

                $this->view->MaxVolCliente = CaixaEmbalado::calculaExpedicao($caixaEn, $arrElements, $codPessoa);
                $this->view->indexVol = $volsCriados;
            }

        } catch (\Exception $e) {
            $this->view->erro = $e->getMessage();
        }
    }

    public function relatorioVolumeEmbaladoAjaxAction()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000);
        $idExpedicao = $this->_getParam('id',0);
        $relatorioEmbalados = new \Wms\Module\Expedicao\Report\RelatorioEtiquetaEmbalados();
        $relatorioEmbalados->imprimirExpedicaoModelo($idExpedicao);
    }

    public function relatorioReimpressaoEtiquetaAction()
    {
        $form = new \Wms\Module\Expedicao\Form\RelatorioReimpressaoEtiqueta();
        $grid = new \Wms\Module\Expedicao\Grid\RelatorioReimpressaoEtiqueta();

        $result = [];
        if ($this->getRequest()->isPost()) {
            $params = $this->getRequest()->getParams();

            $result = $this->_em->getRepository("wms:Expedicao\VEtiquetaSeparacao")->getMotivoReimpressao($params);

            if (empty($result)) {
                $this->addFlashMessage("info", "Nenhuma etiqueta foi reimpressa com essas características!");
            }

            if (!empty($params['pdf']) && !empty($result)) {
                $pdf = new \Wms\Module\Web\Report\Generico("L");
                $pdf->init($result, "Motivo de Reimpressão de Etiqueta", "Relatório de Motivo de Reimpressão de Etiqueta");
            } else {
                $form->setDefaults($params);
            }
        }

        $this->view->form = $form;

        $grid->init($result);
        $this->view->grid = $grid;
    }

}
