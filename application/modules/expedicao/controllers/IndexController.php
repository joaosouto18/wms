<?php

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
        $parametroPedidos = $this->getSystemParameterValue('COD_INTEGRACAO_PEDIDOS');
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

        //INTEGRAR CARGAS NO MOMENTO Q ENTRAR NA TELA DE EXPEDICAO
        if (isset($parametroPedidos) && !empty($parametroPedidos)) {
            $explodeIntegracoes = explode(',', $parametroPedidos);

            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntegracaoRepository */
            $acaoIntegracaoRepository = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
            foreach ($explodeIntegracoes as $codIntegracao) {
                $acaoIntegracaoEntity = $acaoIntegracaoRepository->find($codIntegracao);
                $acaoIntegracaoRepository->processaAcao($acaoIntegracaoEntity);
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

            if (!empty($params['idExpedicao']) || !empty($params['codCargaExterno'])) {
                $idExpedicao = null;
                $idCarga = null;

                if (!empty($params['idExpedicao']))
                    $idExpedicao = $params['idExpedicao'];


                if (!empty($params['codCargaExterno']))
                    $idCarga = $params['codCargaExterno'];

                $params = array();
                $params['idExpedicao'] = $idExpedicao;
                $params['codCargaExterno'] = $idCarga;
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
            $dataI2 = new \DateTime;
//            $dataI1->sub(new DateInterval('P01D'));

            $params = array(
                'dataInicial1' => $dataI1->format('d/m/Y'),
                'dataInicial2' => $dataI2->format('d/m/Y')
            );
            unset($params['control']);
        }

        $params['usaDeclaracaoVP'] = $this->getSystemParameterValue('USA_DECLARACAO_DE_VOLUME_PATRIMONIO');

        $form->populate($params);

        $Grid = new ExpedicaoGrid();
        $this->view->grid = $Grid->init($params)
                ->render();


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

    public function desagruparcargaAction() {
        $params = $this->_getAllParams();

        if (isset($params['placa']) && !empty($params['placa'])) {
            $idCarga = $this->_getParam('COD_CARGA');
            $placa = $params['placa'];

            /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $AndamentoRepo */
            $AndamentoRepo = $this->_em->getRepository('wms:Expedicao\Andamento');
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
            $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
            /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
            $ExpedicaoRepo = $this->_em->getRepository('wms:Expedicao');
            /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $CargaRepo */
            $CargaRepo = $this->_em->getRepository('wms:Expedicao\Carga');

            try {
                /** @var \Wms\Domain\Entity\Expedicao\Carga $cargaEn */
                $cargaEn = $CargaRepo->findOneBy(array('id' => $idCarga));

                /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
                $pedidoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\Pedido");
                $pedidos = $pedidoRepo->findBy(array('codCarga' => $cargaEn->getId()));

                /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoPedidoRepository $ondaPedidoRepo */
                $ondaPedidoRepo = $this->getEntityManager()->getRepository('wms:Ressuprimento\OndaRessuprimentoPedido');
                foreach ($pedidos as $pedidoEn) {
                    //$ondaPedidoEn = $ondaPedidoRepo->findBy(array('pedido' => $pedidoEn->getId()));

                    if ($pedidoEn->getIndEtiquetaMapaGerado() == 'S') {
                        throw new \Exception('Carga não pode ser desagrupada, existem etiquetas/Mapas gerados!');
                        //} else if (count($ondaPedidoEn) > 0) {
                        //    throw new \Exception('Carga não pode ser desagrupada, existe ressuprimento gerado!');
                    }
                }

                $countCortadas = $EtiquetaRepo->countByStatus(Expedicao\EtiquetaSeparacao::STATUS_CORTADO, $cargaEn->getExpedicao(), null, null, $idCarga);
                $countTotal = $EtiquetaRepo->countByStatus(null, $cargaEn->getExpedicao(), null, null, $idCarga);

                if ($countTotal != $countCortadas) {
                    throw new \Exception('A Carga ' . $cargaEn->getCodCargaExterno() . ' possui etiquetas que não foram cortadas e não pode ser removida da expedição');
                }

                $cargas = $ExpedicaoRepo->getCargas($cargaEn->getCodExpedicao());
                if (count($cargas) <= 1) {
                    throw new \Exception('A Expedição não pode ficar sem cargas');
                }

                foreach ($pedidos as $pedido) {
                    $pedidoRepo->removeReservaEstoque($pedido->getId());
                }

                $AndamentoRepo->save("Carga " . $cargaEn->getCodCargaExterno() . " retirada da expedição atraves do desagrupamento de cargas", $cargaEn->getCodExpedicao());
                $expedicaoAntiga = $cargaEn->getCodExpedicao();
                $expedicaoEn = $ExpedicaoRepo->save($placa);
                $cargaEn->setExpedicao($expedicaoEn);
                $cargaEn->setSequencia(1);
                $cargaEn->setPlacaCarga($placa);
                $this->_em->persist($cargaEn);


                if ($countCortadas > 0) {
                    $expedicaoEn->setStatus(EXPEDICAO::STATUS_CANCELADO);
                    $this->_em->persist($expedicaoEn);
                    $AndamentoRepo->save("Etiquetas da carga " . $cargaEn->getCodCargaExterno() . " canceladas na expedição " . $expedicaoAntiga, $expedicaoEn->getId());
                }

                $this->_em->flush();
                $this->_helper->messenger('Foi criado uma nova expedição código ' . $expedicaoEn->getId() . " com a carga selecionada");
            } catch (\Exception $e) {
                $this->_helper->messenger('error', $e->getMessage());
            }
            $this->redirect("index", 'index', 'expedicao');
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
                    'label' => 'Última Separação',
                    'cssClass' => 'btn updateSeparacao',
                    'style' => 'margin-top: 15px; margin-right: 10px ;  height: 20px;'
                ),
                array(
                    'label' => 'Salvar',
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
        $form = new \Wms\Module\Produtividade\Form\EquipeSeparacao();
        $params = $this->_getAllParams();
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

                        $equipeSeparacaoEn = $equipeSeparacaoRepo->getIntervaloEtiquetaUsuario($usuarioEn);

                        //SALVA OS DADOS NA TABELA EQUIPE_SEPARACAO
                        $inicial = 0;
                        $final = 0;
                        $menorIntervalo = 0;
                        if (is_array($equipeSeparacaoEn) && count($equipeSeparacaoEn) > 0) {
                            foreach ($equipeSeparacaoEn as $intervalo) {

                                if ($inicial != 0) {
                                    $iteracao = $intervalo['etiquetaInicial'] - $final;
                                    if ($iteracao > 1) {
                                        $equipeSeparacaoRepo->save($final + 1, $intervalo['etiquetaInicial'] - 1, $usuarioEn, false);
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
                                $equipeSeparacaoRepo->save($etiquetaInicial, $menorIntervalo - 1, $usuarioEn, false);
                            }
                            if ($etiquetaFinal > $final) {
                                $equipeSeparacaoRepo->save($final + 1, $etiquetaFinal, $usuarioEn, false);
                            }
                            $this->getEntityManager()->flush();
                        } else {
                            $equipeSeparacaoRepo->save($etiquetaInicial, $etiquetaFinal, $usuarioEn);
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

                        $apontamentoMapaEn = $apontamentoMapaRepo->findOneBy(array('codUsuario' => $usuarioEn->getId(), 'mapaSeparacao' => $mapaSeparacaoEn));
                        if (!isset($apontamentoMapaEn) || empty($apontamentoMapaEn))
                            $apontamentoMapaRepo->save($mapaSeparacaoEn, $usuarioEn->getId());
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

    public function conferenteApontamentoSeparacaoAjaxAction() {
        $params = $this->_getAllParams();
        $cpf = str_replace(array('.', '-'), '', $params['cpf']);

        /** @var \Wms\Domain\Entity\UsuarioRepository $usuarioRepo */
        $usuarioRepo = $this->getEntityManager()->getRepository('wms:Usuario');
        $result = $usuarioRepo->getPessoaByCpf($cpf);

        if (!empty($result)) {
            $response = array('result' => 'Ok', 'pessoa' => $result[0]['NOM_PESSOA']);
        } else {
            $response = array('result' => 'Error', 'msg' => "Nenhum conferente encontrado com este CPF");
        }

        $this->_helper->json($response);
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

        $pdf = new \Wms\Module\Expedicao\Printer\ProdutosCarregamento();
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

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepository */
        $expedicaoRepository = $this->getEntityManager()->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $expedicaoAndamentoRepository */
        $expedicaoAndamentoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\Andamento');
        /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepository */
        $pedidoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\Pedido');
        /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $cargaRepository */
        $cargaRepository = $this->getEntityManager()->getRepository('wms:Expedicao\Carga');
        $NotaFiscalSaidaRepository = $this->getEntityManager()->getRepository('wms:Expedicao\NotaFiscalSaida');
        $ReentregaRepository = $this->getEntityManager()->getRepository('wms:Expedicao\Reentrega');
        $cargaEntities = $expedicaoRepository->getCargas($idExpedicao);

        $idCargas = null;
        foreach ($cargaEntities as $key => $cargaEntity) {
            if (count($cargaEntities) > $key + 1) {
                $idCargas .= $cargaEntity->getCodCargaExterno() . ',';
            } else {
                $idCargas .= $cargaEntity->getCodCargaExterno();
            }
            $pedidoEntities = $cargaRepository->getPedidos($cargaEntity->getId());
            foreach ($pedidoEntities as $rowPedido) {
                $pedidoEntity = $pedidoRepository->find($rowPedido->getId());
                $pedidoRepository->removeReservaEstoque($rowPedido->getId());
                $pedidoRepository->remove($pedidoEntity, true);
            }
            $ReentregaRepository->removeReentrega($cargaEntity->getId());
            $NotaFiscalSaidaRepository->atualizaStatusNota($cargaEntity->getCodCargaExterno());
            $cargaRepository->removeCarga($cargaEntity->getId());
        }

        //GERA ETIQUETA MAPA ERP
        if ($this->getSystemParameterValue('IND_INFORMA_ERP_ETQ_MAPAS_IMPRESSOS_INTEGRACAO') == 'S') {
            $idIntegracao = $this->getSystemParameterValue('ID_INTEGRACAO_CANCELA_CARGA_ERP');

            /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
            $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');
            $acaoEn = $acaoIntRepo->find($idIntegracao);
            $options[] = (!is_null($idCargas)) ? $idCargas : null;

            $result = $acaoIntRepo->processaAcao($acaoEn, $options, 'E', "P", null, 612);
            if (!$result === true) {
                throw new \Wms\Util\WMS_Exception($result);
            }
        }

        $expedicaoEntity = $expedicaoRepository->find($idExpedicao);
        $expedicaoRepository->alteraStatus($expedicaoEntity, Expedicao::STATUS_CANCELADO);
        $expedicaoAndamentoRepository->save("cargas $idCargas removidas", $idExpedicao);
        $this->addFlashMessage('success', "cargas $idCargas da expedicao $idExpedicao removidas com sucesso");
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
                $possuiConferenciaConcluida = false;
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
        $form->populate($data);
        $this->view->recarregar = $this->_getParam("recarregar");
        $this->view->pessoa = $this->_getParam("pessoa");
        $this->view->form = $form;
    }

    public function confirmarClienteAjaxAction() {
        $mapaSeparacaoQuebraRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoQuebra');
        $mapaSeparacaoQuebraEn = $mapaSeparacaoQuebraRepo->findOneBy(array('mapaSeparacao' => ColetorUtil::retiraDigitoIdentificador($this->_getParam('codigoBarrasMapa'))));

        if (!empty($mapaSeparacaoQuebraEn) && $mapaSeparacaoQuebraEn->getTipoQuebra() == Expedicao\MapaSeparacaoQuebra::QUEBRA_CARRINHO) {
            $this->view->idMapa = $idMapaSeparacao = ColetorUtil::retiraDigitoIdentificador($this->_getParam('codigoBarrasMapa'));
            /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
            $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
            $clientes = $mapaSeparacaoRepo->getClientesByConferencia($idMapaSeparacao);
            foreach ($clientes as $key => $cliente) {
                $numeroCaixas = explode(',', $cliente['NUM_CAIXA_PC_INI']);
                $caixaAnterior = null;
                $arrCaixas = array();
                foreach ($numeroCaixas as $caixa) {
                    if ($caixa != $caixaAnterior)
                        $arrCaixas[] = $caixa;
                    $caixaAnterior = $caixa;
                }
                $clientes[$key]['NUM_CAIXA_PC_INI'] = implode('; ', $arrCaixas);
            }

            if (empty($clientes)) {
                $mapaSeparacaoQuebraRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoQuebra');
                $mapaSeparacaoQuebraEn = $mapaSeparacaoQuebraRepo->findOneBy(array('mapaSeparacao' => ColetorUtil::retiraDigitoIdentificador($this->_getParam('codigoBarrasMapa'))));
                if (!empty($mapaSeparacaoQuebraEn) && $mapaSeparacaoQuebraEn->getTipoQuebra() == Expedicao\MapaSeparacaoQuebra::QUEBRA_CARRINHO) {
                    $clientes = 'finalizar';
                }
            }
            $this->view->clientes = $clientes;
            $mapaSeparacaoEn = $mapaSeparacaoRepo->find($idMapaSeparacao);
            $idExpedicao = $mapaSeparacaoEn->getExpedicao()->getId();
            $this->view->idExpedicao = $idExpedicao;
            $this->view->codMapa = $idMapaSeparacao;
        }
    }

    public function carregaMapaAjaxAction() {
        $codBarras = ColetorUtil::retiraDigitoIdentificador($this->_getParam('codigoBarrasMapa'));
        $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
        try {
            $operacao = $expedicaoRepo->getUrlMobileByCodBarras($this->_getParam('codigoBarrasMapa'));
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
            $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
            if (empty($codPessoa)) {
                /** EXIBE OS PRODUTOS FALTANTES DE CONFERENCIA PARA O MAPA  */
                $this->view->produtos = $mapaSeparacaoRepo->validaConferencia($operacao['expedicao'], true, $codBarras, 'D');
            } else {
                /** EXIBE OS PRODUTOS FALTANTES DE CONFERENCIA PARA O MAPA DE EMBALADOS */
                $this->view->produtos = $mapaSeparacaoRepo->getProdutosConferidosByClientes($codBarras, $codPessoa);
                $this->view->produtosConferidos = $mapaSeparacaoRepo->getProdutosConferidosTotalByClientes($codBarras, $codPessoa);
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

            $idModeloSeparacao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');
            $modeloSeparacaoEn = $modeloSeparacaoRepo->find($idModeloSeparacao);

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

            $this->view->tipoDefaultEmbalado = $modeloSeparacaoEn->getTipoDefaultEmbalado();
            $this->view->utilizaQuebra = $modeloSeparacaoEn->getUtilizaQuebraColetor();
            $this->view->utilizaVolumePatrimonio = $modeloSeparacaoEn->getUtilizaVolumePatrimonio();
            $this->view->tipoQuebraVolume = $modeloSeparacaoEn->getTipoQuebraVolume();
            $this->view->idVolume = $idVolume;
            $this->view->idMapa = $idMapa;
            $this->view->idExpedicao = $idExpedicao;
            $this->view->central = $central;
            $this->view->idPessoa = $codPessoa;
            $this->view->separacaoEmbalado = (empty($codPessoa)) ? false : true;
            $this->view->dscVolume = $dscVolume;
            $this->view->confereQtd = $confereQtd;
        } catch (\Exception $e) {
            $this->view->erro = $e->getMessage();
        }
    }

}
