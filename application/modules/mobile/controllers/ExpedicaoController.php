<?php

use Wms\Controller\Action,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao,
    Wms\Module\Mobile\Form\SenhaLiberacao,
    Wms\Util\Coletor as ColetorUtil,
    \Wms\Util\Endereco as EnderecoUtil,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Domain\Entity\Atividade as AtividadeEntity,
    Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\Expedicao\CaixaEmbalado;
use Wms\Domain\Entity\Expedicao\MapaSeparacaoProdutoRepository;

class Mobile_ExpedicaoController extends Action {

    protected $bloquearOs = null;

    public function indexAction() {
        $idCentral = $this->_getParam('idCentral');
        $this->setIdCentral($idCentral);
    }

    public function iniciarExpedicaoAction() {
        $expedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
        $expedicaoRepo->getExpedicaoByCliente();
    }

    public function definirOperacaoAction()
    {
        $codBarras = $this->_getParam('codigoBarras');

        $volumePatrimonioRepo = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio');
        $volumePatrimonioEn = $volumePatrimonioRepo->find($codBarras);
        if (empty($volumePatrimonioEn)) {
            $codBarras = ColetorUtil::retiraDigitoIdentificador($codBarras);
        }

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoQuebraRepository $mapaSeparacaoQuebraRepo */
        $mapaSeparacaoQuebraRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoQuebra');

        switch (substr($codBarras,0,2)) {
            case 12:
                $mapaSeparacaoQuebraEn = $mapaSeparacaoQuebraRepo->findOneBy(array('mapaSeparacao' => $codBarras, 'tipoQuebra' => Expedicao\MapaSeparacaoQuebra::QUEBRA_CARRINHO));
                if (!empty($mapaSeparacaoQuebraEn)) {
                    $this->_redirect("mobile/expedicao/confirma-clientes/codigoBarras/$codBarras");
                } else {
                    $this->_redirect("mobile/expedicao/confirmar-operacao/codigoBarras/$codBarras");
                }
                break;
            default:
                $this->_redirect("mobile/expedicao/confirmar-operacao/codigoBarras/$codBarras");
                break;
        }
    }

    public function confirmaClientesAction() {
        $this->view->idMapa = $idMapaSeparacao = $this->_getParam('codigoBarras');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');

        $this->view->clientes = $mapaSeparacaoRepo->getClientesByConferencia($idMapaSeparacao);

        $mapaSeparacaoEn = $mapaSeparacaoRepo->find($idMapaSeparacao);
        $idExpedicao = $mapaSeparacaoEn->getExpedicao()->getId();
        $this->view->idExpedicao = $idExpedicao;
    }

    public function confirmarOperacaoAction() {
        $this->view->codBarras = $codBarras = $this->_getParam('codigoBarras');
        if (isset($codBarras) and ( $codBarras != null) and ( $codBarras != "")) {
            try {
                /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
                $expedicaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao");
                $operacao = $expedicaoRepo->getUrlMobileByCodBarras($codBarras);
                $this->view->operacao = $operacao['operacao'];
                if (isset($operacao['placa'])) {
                    $this->view->placa = $operacao['placa'];
                }
                if (isset($operacao['carga'])) {
                    $this->view->carga = $operacao['carga'];
                }
                if (isset($operacao['parcialmenteFinalizado'])) {
                    $sessaoColetor = new \Zend_Session_Namespace('coletor');
                    if ($operacao['parcialmenteFinalizado'] == true) {
                        $sessaoColetor->parcialmenteFinalizado = true;
                    } else {
                        $sessaoColetor->parcialmenteFinalizado = false;
                    }
                }

                $this->view->expedicao = $operacao['expedicao'];
                $this->view->url = $operacao['url'];
            } catch (\Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
                $this->_redirect('mobile/expedicao/index');
            }
        } else {
            $this->addFlashMessage('info', 'informe um código de barras');
            $this->_redirect('mobile/expedicao/index');
        }
    }

    public function lerEmbaladosMapaAction() {
        $this->view->idEmbalado = $idEmbalado = $this->_getParam('embalado');
        $this->view->idExpedicao = $idExpedicao = $this->_getParam('expedicao');
        $this->view->idMapa = $idMapa = $this->_getParam('idMapa');

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        try {
            if ($this->getRequest()->isPost()) {
                $idEmbalado = \Wms\Util\Coletor::retiraDigitoIdentificador($idEmbalado);
                $mapaSeparacaoEmbaladoRepo->conferirVolumeEmbalado($idEmbalado, $idExpedicao, $idMapa);
                $this->addFlashMessage('success', "Volume embalado $idEmbalado conferido com sucesso!");
            }
        } catch (\Exception $e) {
            $this->view->error = true;
            $this->addFlashMessage('error', $e->getMessage());
        }
    }

    public function lerProdutoMapaAction() {

        $produtosMapa = array();
        $this->view->headScript()->appendFile($this->view->baseUrl() . '/wms/resources/jquery/jquery.cycle.all.latest.js');
        $confereQtd = false;
        try {
            $idMapa = $this->_getParam("idMapa");
            $idVolume = $this->_getParam("idVolume");
            $idExpedicao = $this->_getParam("idExpedicao");
            $codPessoa = $this->_getParam('cliente', null);
            $sessao = new \Zend_Session_Namespace('coletor');
            $central = $sessao->centralSelecionada;
            $sessao->bloquearOs = $this->bloquearOs();

            $Expedicao = new \Wms\Coletor\Expedicao($this->getRequest(), $this->em);
            $Expedicao->validacaoExpedicao();
            if ($this->bloquearOs()) {
                if (!$Expedicao->osLiberada()) {
                    $this->addFlashMessage('error', $Expedicao->getMessage());
                    $this->addFlashMessage('warning', $Expedicao->getOs()->getBloqueio());
                    $this->_redirect($Expedicao->getRedirect());
                }
            }
            $volumePatrimonioRepo = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio');
            /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
            $modeloSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ModeloSeparacao');

            /** @var Expedicao\MapaSeparacaoProdutoRepository $mapaSepProdRepo */
            $mapaSepProdRepo = $this->em->getRepository('wms:Expedicao\MapaSeparacaoProduto');
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
            $conferenciaNaoEmbalado = $modeloSeparacaoEn->getTipoConferenciaNaoEmbalado();
            $conferenciaEmbalado = $modeloSeparacaoEn->getTipoConferenciaEmbalado();

            if (!empty($mapaQuebraEn) && $mapaQuebraEn->getTipoQuebra() == Expedicao\MapaSeparacaoQuebra::QUEBRA_CARRINHO) {
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
            $this->view->tipoDefaultEmbalado = $modeloSeparacaoEn->getTipoDefaultEmbalado();
            $this->view->utilizaQuebra = $modeloSeparacaoEn->getUtilizaQuebraColetor();
            $this->view->utilizaVolumePatrimonio = $modeloSeparacaoEn->getUtilizaVolumePatrimonio();
            $this->view->agrupContEtiquetas = $modeloSeparacaoEn->getAgrupContEtiquetas();
            $this->view->tipoQuebraVolume = $modeloSeparacaoEn->getTipoQuebraVolume();
            $this->view->arrCodBarras = $mapaSepProdRepo->getCodBarrasAtivosByMapa($idExpedicao, $idMapa, ($modeloSeparacaoEn->getUtilizaQuebraColetor() == 'S' ));
            $this->view->idVolume = $idVolume;
            $this->view->idMapa = $idMapa;
            $this->view->idExpedicao = $idExpedicao;
            $this->view->central = $central;
            $this->view->idPessoa = $codPessoa;
            $this->view->separacaoEmbalado = (empty($codPessoa)) ? false : true;
            $this->view->dscVolume = $dscVolume;
            $this->view->confereQtd = $confereQtd;
        } catch (\Exception $e) {
            if ($confereQtd == true) {
                $vetRetorno = array('retorno' => array('resposta' => 'error', 'message' => $e->getMessage()), 'dados' => $produtosMapa);
                $this->_helper->json($vetRetorno);
            } else {
                $this->addFlashMessage('error', $e->getMessage());
            }
        }
    }

    public function confereProdutoAjaxAction() {
        $idMapa = $this->_getParam("idMapa");
        $qtd = $this->_getParam("qtd");
        $codBarras = $this->_getParam("codigoBarras");
        $lote = $this->_getParam("lote");
        $codPessoa = $this->_getParam('cliente');
        $idExpedicao = $this->_getParam("idExpedicao");
        $idVolume = $this->_getParam("idVolume");
        $checkout = $this->_getParam("chekcout");

        $sessao = new \Zend_Session_Namespace('coletor');

        if ($sessao->bloquearOs == 'S') {
            $Expedicao = new \Wms\Coletor\Expedicao($this->getRequest(), $this->em);
            $Expedicao->validacaoExpedicao();
            if ($sessao->bloquearOs == 'S' && !$Expedicao->osLiberada()) {
                $response = [
                    'resposta' => 'bloqued_os',
                    'errorMsg' => '',
                    'warningMsg' => $Expedicao->getOs()->getBloqueio()
                ];
                if ($checkout== 'S') {
                    $form = new SenhaLiberacao();
                    $form->setDefault('idExpedicao', $idExpedicao);
                    $response['blockOsForm'] = $form->render();
                }

                $vetRetorno = array('retorno' => $response);
                $this->_helper->json($vetRetorno);
            }
        }

        if($checkout == 'chekcout'){
            $chekcout = true;
            $cpfEmbalador = $this->_getParam("cpfEmbalador");
            $cpfEmbalador = str_replace(array('.', '-'), '', $cpfEmbalador);
        }else{
            $chekcout = false;
            $cpfEmbalador = Zend_Auth::getInstance()->getIdentity()->getPessoa()->getCPF(false);
        }

        if ($codPessoa == "") {
            $codPessoa = null;
        }
        $msg['msg'] = "";
        $volume = "";
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $volumePatrimonioRepo = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio');
        $mapaSeparacaoQuebraRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoQuebra');

        $paramsModeloSeparacao = array(
            'tipoDefaultEmbalado' => $this->_getParam("tipoDefaultEmbalado"),
            'utilizaQuebra' => $this->_getParam("utilizaQuebra"),
            'utilizaVolumePatrimonio' => $this->_getParam("utilizaVolumePatrimonio")
        );

        if (!empty($codBarras) && !empty($idMapa)) {
            try {
                $codBarrasProcessado = intval($codBarras);

                /** @var Expedicao\VolumePatrimonio $volumePatrimonioEn */
                $volumePatrimonioEn = null;
                if ((strlen($codBarrasProcessado) > 2) && ((substr($codBarrasProcessado, 0, 2)) == "13")) {
                    $tipoProvavelCodBarras = 'volume';
                    $volumePatrimonioEn = $volumePatrimonioRepo->find($codBarrasProcessado);
                    if (empty($volumePatrimonioEn)) {
                        $tipoProvavelCodBarras = 'produto';
                    }
                } else {
                    $tipoProvavelCodBarras = 'produto';
                }

                if ($tipoProvavelCodBarras === 'volume' || !empty($volumePatrimonioEn)) {
                    if ((!empty($idVolume)) && ($idVolume != null)) {
                        $volumePatrimonioEnById = $volumePatrimonioRepo->find($idVolume);
                        if (empty($volumePatrimonioEnById)) {
                            throw new \Exception("Nenhum volume-patrimônio foi encontrado com o código $idVolume");
                        } elseif (!empty($volumePatrimonioEnById) && !empty($novoVolumePatrimonio)) {
                            throw new \Exception("Já existe um volume patrimonio aberto, feche a caixa " . $volumePatrimonioEn->getId() . " antes de abrir um novo volume.");
                        }
                    } elseif (empty($idVolume) && !empty($volumePatrimonioEn)) {
                        $idVolume = $volumePatrimonioEn->getId();
                    }

                    /** @var Expedicao\ExpedicaoVolumePatrimonioRepository $expVolumePatrimonioRepo */
                    $expVolumePatrimonioRepo = $this->em->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');

                    $codQuebra = 0;
                    if ($this->_getParam("tipoQuebraVolume") == Expedicao\ModeloSeparacao::QUEBRA_VOLUME_CLIENTE) {
                        $mapaSeparacaoEn = $mapaSeparacaoQuebraRepo->findBy(array('mapaSeparacao' => $idMapa, 'tipoQuebra' => Expedicao\MapaSeparacaoQuebra::QUEBRA_CLIENTE));
                        if (isset($mapaSeparacaoEn) && !empty($mapaSeparacaoEn))
                            $codQuebra = $mapaSeparacaoEn[0]->getCodQuebra();
                    }
                    $expVolumePatrimonioRepo->vinculaExpedicaoVolume($idVolume, $idExpedicao, $codQuebra);

                    $this->view->idVolume = $idVolume;
                    $msg['msg'] = "Volume $codBarrasProcessado vinculada a expedição";
                    $volume = ['idVolume' => $volumePatrimonioEn->getId(), 'dscVolume' => $volumePatrimonioEn->getDescricao()];
                    $msg['produto'] = "";
                }
                else if ($tipoProvavelCodBarras === 'produto') {
                    $codBarras = ColetorUtil::adequaCodigoBarras($codBarras, true);

                    if (!empty($idVolume)) {
                        $volumePatrimonioEn = $volumePatrimonioRepo->find($idVolume);
                        $volume = ['idVolume' => $volumePatrimonioEn->getId(), 'dscVolume' => $volumePatrimonioEn->getDescricao()];
                    }
                    $result = $mapaSeparacaoRepo->confereMapaProduto($paramsModeloSeparacao, $idExpedicao, $idMapa, $codBarras, $qtd, $volumePatrimonioEn, $cpfEmbalador, $codPessoa, null, $chekcout, $lote);
                    if(isset($result['checkout'])){
                        $msg['msg'] = 'checkout';
                        $msg['produto'] = $result['produto'];
                    }else{
                        $msg['msg'] = 'Quantidade conferida com sucesso';
                        $msg['produto'] = $result['produto'];
                    }

                }
            } catch (\Exception $e) {
                /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
                $motivo = $e->getMessage();
                if ($sessao->bloquearOs == 'S') {
                    $motivo = "OS bloqueada: $motivo";
                    $this->bloqueioOs($idExpedicao, $motivo, \Wms\Domain\Entity\OrdemServico::BLOCK_MAPA);
                    $response = [
                        'resposta' => 'bloqued_os',
                        'errorMsg' => "OS bloqueada:",
                        'warningMsg' => $e->getMessage()
                    ];
                    if ($checkout== 'S') {
                        $form = new SenhaLiberacao();
                        $form->setDefault('idExpedicao', $idExpedicao);
                        $response['blockOsForm'] = $form->render();
                    }

                    $vetRetorno = array('retorno' => $response);
                } else {
                    $vetRetorno = array('retorno' => array('resposta' => 'error', 'message' => $e->getMessage(), 'produto' => '', 'volumePatrimonio' => ''));
                }
                $andamentoRepo = $this->_em->getRepository('wms:Expedicao\Andamento');
                $andamentoRepo->save($motivo, $idExpedicao, false, true, null, $codBarras, false, $idMapa);
                $this->_helper->json($vetRetorno);
            }
        }

        $vetRetorno = array('retorno' => array('resposta' => 'success', 'message' => $msg['msg'], 'produto' => $msg['produto'], 'volumePatrimonio' => $volume));
        $this->_helper->json($vetRetorno);
    }

    public function getProdutosConferirAction() {
        $idMapa = $this->_getParam("idMapa");
        $idExpedicao = $this->_getParam("idExpedicao");
        $codPessoa = $this->_getParam("cliente");
        $produtosClientes = array();
        $produtosMapa = array();

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');

        if (empty($codPessoa)) {
            /** EXIBE OS PRODUTOS FALTANTES DE CONFERENCIA PARA O MAPA  */
            $produtosMapa = $mapaSeparacaoRepo->validaConferencia($idExpedicao, true, $idMapa, 'D');
        } else {
            /** EXIBE OS PRODUTOS FALTANTES DE CONFERENCIA PARA O MAPA DE EMBALADOS */
            $produtosClientes = $mapaSeparacaoRepo->getProdutosConferidosByClientes($idMapa, $codPessoa);
        }

        $this->_helper->json(array('resposta' => 'success', 'dados' => $produtosMapa, 'dadosClientes' => $produtosClientes));
    }

    public function consultaProdutoAction() {
        $codigoBarras = ColetorUtil::adequaCodigoBarras($this->_getParam('codigoBarras'));

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $info = $produtoRepo->getEmbalagemByCodBarras($codigoBarras);

        if (empty($info)) {
            $this->addFlashMessage('error', 'Nenhum produto encontrado para o código de barras ' . $codigoBarras);
            $this->redirect("index", 'consulta-produto');
        }
        $dadosProduto = array(
            'codProduto' => $info[0]['idProduto'],
            'grade' => $info[0]['grade'],
            'descricao' => $info[0]['descricao'],
            'quantidade' => $info[0]['quantidadeEmbalagem'],
            'descricaoEmbalagem' => $info[0]['descricaoEmbalagem']
        );

        $this->_helper->json(array('status' => 'ok', 'result' => $dadosProduto));
    }

    public function fechaVolumePatrimonioMapaAction() {
        $idMapa = $this->_getParam('idMapa');
        $idExpedicao = $this->_getParam('idExpedicao');
        $idVolume = $this->_getParam('idVolume');

        $expVolumePatrimonioRepo = $this->em->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');

        $params = "";

        try {
            $expVolumePatrimonioRepo->fecharCaixa($idExpedicao, $idVolume);
            $linkImpressao = '<a href="' . $this->view->url(array('controller' => 'expedicao', 'action' => 'imprime-volume-patrimonio', 'idExpedicao' => $idExpedicao, 'volume' => $idVolume)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Clique aqui para Imprimir</a>';
            $mensagem = "Volume $idVolume fechado com sucesso - $linkImpressao";

            $this->addFlashMessage('success', $mensagem);
        } catch (Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->_redirect('mobile/expedicao/ler-produto-mapa/idMapa/' . $idMapa . "/idExpedicao/" . $idExpedicao . $params . "/idVolume/");
    }

    public function fechaMapaEmbaladoAction() {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 3000);
        $idMapa = $this->_getParam('idMapa');
        $idPessoa = $this->_getParam('cliente');
        $idExpedicao = $this->_getParam('idExpedicao');
        $checkout = $this->_getParam('checkout');
        $cpfEmbalador = $this->_getParam('cpfEmbalador');
        $nVols = $this->_getParam('nVols');

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        $mapaSeparacaoConferenciaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoConferencia');
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $osRepo */

        /** @var Expedicao\ModeloSeparacao $modeloSeparacaoEn */
        $modeloSeparacaoEn = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao")->getModeloSeparacao($idExpedicao);
        $agrupaEtiquetas = ($modeloSeparacaoEn->getAgrupContEtiquetas() == 'S');
        $fechaEmbaladosNoFinal = ($modeloSeparacaoEn->getCriarVolsFinalCheckout() == 'S');
        $usaCaixaPadrao = ($modeloSeparacaoEn->getUsaCaixaPadrao() == 'S');
        $tipoAgrupSeqVols = $modeloSeparacaoEn->getTipoAgroupSeqEtiquetas();

        $qtdPendenteConferencia = $mapaSeparacaoEmbaladoRepo->getProdutosConferidosByCliente($idMapa, $idPessoa);

        $returnToCliente = (!empty($qtdPendenteConferencia));
	    $isBeginTransaction = false;
        try {

            /** @var Expedicao\MapaSeparacaoEmbalado $mapaSeparacaoEmbaladoEn */
            $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findOneBy(array('mapaSeparacao' => $idMapa, 'pessoa' => $idPessoa, 'status' => Expedicao\MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO));

            $checkAgrupamento = function ($mapaSeparacaoConferencias = null) use ($mapaSeparacaoEmbaladoRepo, $idMapa, $idPessoa, $idExpedicao, $qtdPendenteConferencia, $mapaSeparacaoEmbaladoEn, $usaCaixaPadrao, $tipoAgrupSeqVols) {

                if (empty($mapaSeparacaoConferencias) && !empty($qtdPendenteConferencia)) {
                    throw new Exception("Não é possível fechar volume sem produtos conferidos!");
                }

                $preCountVolCliente = 0;

                if ($usaCaixaPadrao) {
                    /** @var CaixaEmbalado $caixaEn */
                    $caixaEn = $this->getEntityManager()->getRepository('wms:Expedicao\CaixaEmbalado')->findOneBy(['isAtiva' => true, 'isDefault' => true]);
                    if (empty($caixaEn)) throw new \Exception("O parâmetro de agrupamento de etiquetas está habilitado, para isso é obrigatório o cadastro de uma caixa de embalado padrão e que esteja ativa!");

                    /** @var MapaSeparacaoProdutoRepository $mapaSeparacaoProdutoRepo */
                    $mapaSeparacaoProdutoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoProduto');
                    $arrElements = $mapaSeparacaoProdutoRepo->getMaximosConsolidadoByCliente($idExpedicao);
                    $preCountVolCliente = CaixaEmbalado::calculaExpedicao($caixaEn, $arrElements, $idPessoa);
                }

                $countVolsEmbCliente = count($mapaSeparacaoEmbaladoRepo->findBy(['mapaSeparacao' => $idMapa, "pessoa" => $idPessoa]));
                $countVolsEmb = count($mapaSeparacaoEmbaladoRepo->findBy(['mapaSeparacao' => $idMapa]));

                if ($countVolsEmbCliente == $preCountVolCliente && !empty($qtdPendenteConferencia)) {
                    throw new Exception("Pelo calculo pré definido de volumes, este volume não pode ser fechado, pois ainda existem itens à serem conferidos deste cliente");
                } elseif ($countVolsEmbCliente == $preCountVolCliente && empty($qtdPendenteConferencia) && empty($mapaSeparacaoEmbaladoEn)) {
                    throw new Exception("O último volume já foi fechado e teve sua etiqueta gerada, para reimprimir vá nas opções desta expedição na tela 'Expedição Mercadorias'");
                } elseif ($countVolsEmbCliente == $preCountVolCliente && empty($qtdPendenteConferencia)) {
                    throw new Exception("Todos os volumes pré calculados para este cliente já foram fechados!");
                }

                /** @var Expedicao\VEtiquetaSeparacaoRepository $vEtiquetaSepRepo */
                $vEtiquetaSepRepo = $this->_em->getRepository("wms:Expedicao\VEtiquetaSeparacao");
                $countEtiquetasCliente = $vEtiquetaSepRepo->getCountEtiquetasByCliente($idExpedicao);

                $totalEtqtCliente = (!empty($countEtiquetasCliente[$idPessoa])) ? $countEtiquetasCliente[$idPessoa] : 0;
                $posEntrega = $totalEtqtCliente + $countVolsEmbCliente;
                $totalEntrega = $preCountVolCliente + $totalEtqtCliente;

                $isLast = ($usaCaixaPadrao)? ($countVolsEmbCliente == $preCountVolCliente) : empty($qtdPendenteConferencia);

                $posVolume = 0;

                switch ($tipoAgrupSeqVols) {
                    case Expedicao\ModeloSeparacao::TIPO_AGROUP_VOLS_EXPEDICAO:
                        $posVolume = $countVolsEmb + count($vEtiquetaSepRepo->findBy(['codExpedicao' => $idExpedicao]));
                        break;
                    case Expedicao\ModeloSeparacao::TIPO_AGROUP_VOLS_CLIENTE:
                        $posVolume = $countVolsEmbCliente + $totalEtqtCliente;
                        break;
                }

                return [$posVolume, $isLast, $posEntrega, $totalEntrega];
            };

            /**
             * @param $mapaSeparacaoEmbaladoEn Expedicao\MapaSeparacaoEmbalado
             * @param $posVolume
             * @param $posEntrega
             * @param $totalEntrega
             */
            $fechaEmbalado = function ($mapaSeparacaoEmbaladoEn, $posVolume = null, $posEntrega = null, $totalEntrega = null) use ($mapaSeparacaoEmbaladoRepo){
                /** @var \Wms\Domain\Entity\OrdemServicoRepository $osRepo */
                $osRepo = $this->getEntityManager()->getRepository('wms:OrdemServico');
                $mapaSeparacaoEmbaladoRepo->fecharMapaSeparacaoEmbalado($mapaSeparacaoEmbaladoEn, $posVolume, $posEntrega, $totalEntrega);
                $os = $mapaSeparacaoEmbaladoEn->getOs();
                $osRepo->finalizar($os->getId(), "Fechamento de Volume Embalado", $os);
            };

            /**
             * @param $idMapa
             * @param $idPessoa
             * @param int|null $posVolume
             * @param Expedicao\MapaSeparacaoEmbalado|null $lastEmbalado
             * @param int|null $posEntrega
             * @param int|null $totalEntrega
             * @return Expedicao\MapaSeparacaoEmbalado
             */
            $criarEmbaladoFechado = function ($idMapa, $idPessoa, $posVolume = null, $lastEmbalado = null, $posEntrega = null, $totalEntrega = null) use ($mapaSeparacaoEmbaladoRepo, $fechaEmbalado, $idExpedicao, $cpfEmbalador){
                $osEmbalamento = $mapaSeparacaoEmbaladoRepo->getOsEmbalagem($cpfEmbalador, $idExpedicao, true);
                $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->save($idMapa, $idPessoa, $osEmbalamento, $lastEmbalado, true);
                $fechaEmbalado($mapaSeparacaoEmbaladoEn, $posVolume, $posEntrega, $totalEntrega);
                return $mapaSeparacaoEmbaladoEn;
            };

            $isLast = false;
            $this->getEntityManager()->beginTransaction();
            $isBeginTransaction = true;
            if (!empty($mapaSeparacaoEmbaladoEn)) {
                $mapaSeparacaoConferencias = $mapaSeparacaoConferenciaRepo->findBy(array('mapaSeparacaoEmbalado' => $mapaSeparacaoEmbaladoEn));
                if (empty($mapaSeparacaoConferencias) && !$agrupaEtiquetas) {
                    $this->addFlashMessage('error', 'Não é possível imprimir etiqueta sem produtos conferidos!');
                    if ($checkout == 1) {
                        $this->_redirect('expedicao/index/checkout-expedicao/codigoBarrasMapa/' . $idMapa . '0/erro/1');
                    } else {
                        $this->_redirect('mobile/expedicao/ler-produto-mapa/idMapa/' . $idMapa . '/idExpedicao/' . $idExpedicao . '/cliente/' . $idPessoa);
                    }
                }

                $posVolume =  null;
                $posEntrega =  null;
                $totalEntrega =  null;
                if ($agrupaEtiquetas && !$fechaEmbaladosNoFinal) {
                    list($posVolume, $isLast, $posEntrega, $totalEntrega) = $checkAgrupamento($mapaSeparacaoConferencias);
                }
                $fechaEmbalado($mapaSeparacaoEmbaladoEn, $posVolume, $posEntrega, $totalEntrega);

                if (!$agrupaEtiquetas && $fechaEmbaladosNoFinal) {
                    if (empty($nVols)) throw new Exception("O número de volumes à serem criados não foi definido");
                    else {
                        if (!empty($qtdPendenteConferencia)) throw new Exception("O modelo de separação exige que confira todos os produtos antes de fechar os volumes");
                        $nVols -= 1; // Decrementa o volume original para criar apenas os demais
                        for ($i = 0; $i < $nVols; $i++) {
                            $mapaSeparacaoEmbaladoEn = $criarEmbaladoFechado($idMapa, $idPessoa, null, $mapaSeparacaoEmbaladoEn);
                        }
                        $isLast = true;
                    }
                }
            } elseif ($agrupaEtiquetas && $usaCaixaPadrao && empty($qtdPendenteConferencia)) {
                list($posVolume, $isLast, $posEntrega, $totalEntrega) = $checkAgrupamento();
                if (!empty($posVolume)) {
                    $posVolume += 1;
                    $posEntrega += 1;
                    $embalados = $mapaSeparacaoEmbaladoRepo->findBy(['mapaSeparacao' => $idMapa, 'pessoa' => $idPessoa], ['sequencia' => 'DESC']);
                    $mapaSeparacaoEmbaladoEn = $criarEmbaladoFechado($idMapa, $idPessoa, $posVolume, $embalados[0], $posEntrega, $totalEntrega);
                }
            } else {
                throw new Exception("Não há volume para ser fechado");
            }
            $this->getEntityManager()->commit();

            $mapaSeparacaoEmbaladoRepo->imprimirVolumeEmbalado($mapaSeparacaoEmbaladoEn, $idPessoa, $fechaEmbaladosNoFinal, !($fechaEmbaladosNoFinal || $agrupaEtiquetas), $isLast);
        } catch (Exception $e) {
            if ($isBeginTransaction) $this->getEntityManager()->rollback();
            $this->_helper->messenger('error', $e->getMessage());
            if (!$returnToCliente) {
                if ($checkout == 1) {
                    $this->_redirect('expedicao/index/checkout-expedicao/codigoBarrasMapa/' . $idMapa . '0/recarregar/1');
                } else {
                    $this->_redirect('mobile/expedicao/confirma-clientes/codigoBarras/' . $idMapa);
                }
            } else {
                if ($checkout == 1) {
                    $this->_redirect('expedicao/index/checkout-expedicao/codigoBarrasMapa/' . $idMapa . '0/recarregar/1/pessoa/' . $idPessoa);
                } else {
                    $this->_redirect('mobile/expedicao/ler-produto-mapa/idMapa/' . $idMapa . '/idExpedicao/' . $idExpedicao . '/cliente/' . $idPessoa);
                }
            }
        }
    }

    public function imprimeVolumePatrimonioAction() {
        $idExpedicao = $this->_getParam('idExpedicao');
        $volume = $this->_getParam('volume');
        $parametroEtiquetaVolume = $this->getSystemParameterValue('MODELO_ETIQUETA_VOLUME');
        /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepository */
        $modeloSeparacaoRepository = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao");

        //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
        $modeloSeparacaoEn = $modeloSeparacaoRepository->getModeloSeparacao($idExpedicao);

        /** @var Expedicao\ExpedicaoVolumePatrimonioRepository $expVolumePatrimonioRepo */
        $expVolumePatrimonioRepo = $this->em->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');
        /** @var Expedicao\ExpedicaoVolumePatrimonio $expVolumePatrimonioEn */
        $expVolumePatrimonioEn = $expVolumePatrimonioRepo->findOneBy(array('volumePatrimonio' => $volume, 'expedicao' => $idExpedicao));

        if (empty($expVolumePatrimonioEn))
            throw new Exception("Não foi encontrado o volume $volume na expedição $expVolumePatrimonioEn");

        $codCliente = $expVolumePatrimonioEn->getTipoVolume();
        $clienteRepo = $this->em->getRepository('wms:Pessoa\Papel\Cliente');
        $clienteEn = $clienteRepo->findBy(array('codExterno' => $codCliente));

        $dscVolume = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio')->find($volume)->getDescricao();

        /** @var Expedicao $expedicaoEn */
        $expedicaoEn = $this->em->find('wms:Expedicao', $idExpedicao);

        /** @var \Wms\Domain\Entity\Pessoa $pessoaEmpresa */
        $pessoaEmpresa = $this->em->find('wms:Pessoa', 1);

        /** @var \Wms\Domain\Entity\Pessoa $pessoaCliente */
        $pessoaCliente = $clienteEn[0]->getPessoa();

        $codPessoa = $pessoaCliente->getNome();
        $cargas = $expVolumePatrimonioEn->getExpedicao()->getCarga();
        $pedido = $cargas[0]->getPedido();
        $idPedido = $pedido[0]->getId();

        /** @var \Doctrine\ORM\PersistentCollection $enderecoCollection */
        $enderecoCollection = $pessoaCliente->getEnderecos();
        $endereco = $enderecoCollection->first();

        $produtos = $expVolumePatrimonioRepo->getProdutosVolumeByMapa($idExpedicao, $volume);

        $dataInicio = (!empty($expedicaoEn)) ? $expedicaoEn->getDataInicio() : null;
        $emissor = (!empty($pessoaEmpresa)) ? $pessoaEmpresa->getNome() : null;

        $localidade = null;
        $estado = null;
        if (!empty($endereco)) {
            $localidade = $endereco->getLocalidade();
            $estado = $endereco->getUf()->getReferencia();
        }

        $sequencia = $expVolumePatrimonioEn->getSequencia();

        if ($modeloSeparacaoEn->getImprimeEtiquetaVolume() == 'S') {

            $fields = array();
            $fields['expedicao'] = $idExpedicao;
            $fields['volume'] = $volume;
            $fields['dataInicio'] = $dataInicio;
            $fields['emissor'] = $emissor;
            $fields['localidade'] = $localidade;
            $fields['estado'] = $estado;
            $fields['descricao'] = $dscVolume;
            $fields['quebra'] = $codPessoa;
            $fields['pedido'] = $idPedido;
            $fields['produtos'] = $produtos;
            if (!empty($sequencia))
                $fields['sequencia'] = $expVolumePatrimonioEn->getSequencia();


            switch ($parametroEtiquetaVolume) {
                case 1:
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaVolume("P", 'mm', array(110, 50));
                    $gerarEtiqueta->imprimirExpedicaoModelo1($fields, false);
                    break;
                case 2:
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaVolume("P", 'mm', array(110, 62, 5));
                    $gerarEtiqueta->imprimirExpedicaoModelo2($fields);
                    break;
                case 3:
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaVolume("P", 'mm', array(110, 75));
                    $gerarEtiqueta->imprimirExpedicaoModelo3($fields, false);
                    break;
            }
        }
    }

    public function informaQtdMapaAction() {
        $idVolume = $this->_getParam('idVolume');
        $idMapa = $this->_getParam('idMapa');
        $idEmbVol = $this->_getParam('idEmbVol');
        $tipo = $this->_getParam('tipo');
        $qtd = $this->_getParam('qtd');
        $idExpedicao = $this->_getParam('idExpedicao');
        $codPessoa = $this->_getParam('cliente');

        /** @var \Wms\Domain\Entity\Produto\Embalagem|\Wms\Domain\Entity\Produto\Volume $embVolEnt */
        $embVolEnt = $this->getEntityManager()->find('wms:Produto\\' . $tipo, $idEmbVol);

        $produto = $embVolEnt->getProduto();

        $this->view->codProduto = $produto->getId();
        $this->view->grade = $produto->getGrade();
        $this->view->descricao = $produto->getDescricao();
        $this->view->embalagem = $embVolEnt->getDescricao() . (is_a($embVolEnt, '\Wms\Domain\Entity\Produto\Embalagem')) ? "(" . $embVolEnt->getQuantidade() . ")" : "";
        $this->view->fator = (is_a($embVolEnt, "\Wms\Domain\Entity\Produto\Embalagem")) ? "(" . $embVolEnt->getQuantidade() . ")" : 1;
        $this->view->idVolume = $idVolume;
        $this->view->idEmbVol = $idEmbVol;
        $this->view->tipo = $tipo;
        $this->view->codBarras = $embVolEnt->getCodigoBarras();
        $this->view->idMapa = $idMapa;
        $this->view->idExpedicao = $idExpedicao;
        $this->view->idPessoa = $codPessoa;
        $this->view->urlVoltar = '/mobile/expedicao/ler-produto-mapa/idMapa/' . $idMapa . '/idExpedicao/' . $idExpedicao . '/cliente/' . $codPessoa;

        if (!empty($qtd)) {
            try {
                $volumePatrimonioRepo = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio');
                /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
                $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
                /** @var \Wms\Domain\Entity\Expedicao\ModeloSeparacaoRepository $modeloSeparacaoRepository */
                $modeloSeparacaoRepository = $this->getEntityManager()->getRepository('wms:Expedicao\ModeloSeparacao');

                //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
                $modeloSeparacaoEn = $modeloSeparacaoRepository->getModeloSeparacao($idExpedicao);

                $paramsModelo = ['agrupContEtiquetas' => $modeloSeparacaoEn->getAgrupContEtiquetas()];

                $embalagemEn = null;
                $volumeEn = null;
                if (!empty($embVolEnt) and $tipo === 'Embalagem') {
                    $embalagemEn = $embVolEnt;
                } elseif (!empty($embVolEnt) and $tipo === 'Volume') {
                    $volumeEn = $embVolEnt;
                }

                $volumePatrimonioEn = null;
                if ((isset($idVolume)) && ($idVolume != null)) {
                    $volumePatrimonioEn = $volumePatrimonioRepo->find($idVolume);
                }
                $mapaEn = $mapaSeparacaoRepo->find($idMapa);

                /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
                $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
                $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findBy(array('mapaSeparacao' => $idMapa, 'pessoa' => $codPessoa), array('id' => 'DESC'));

                if (isset($codPessoa) && !empty($codPessoa)) {
                    if (count($mapaSeparacaoEmbaladoEn) <= 0) {
                        $mapaSeparacaoEmbaladoRepo->save($idMapa, $codPessoa);
                    } elseif ($mapaSeparacaoEmbaladoEn[0]->getStatus()->getId() == Expedicao\MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FINALIZADO) {
                        $mapaSeparacaoEmbaladoRepo->save($idMapa, $codPessoa, $mapaSeparacaoEmbaladoEn[0]);
                    }
                }

                $mapaSeparacaoRepo->adicionaQtdConferidaMapa($embalagemEn, $volumeEn, $mapaEn, $volumePatrimonioEn, $qtd, $codPessoa);
                $listaQtdProdutosConferidos = $mapaSeparacaoRepo->verificaConferenciaProduto($idMapa, $embalagemEn, $volumeEn);
                $listaProdutosNãoConferidosMapa = $mapaSeparacaoRepo->verificaConferenciaMapa($idMapa);
                $todosProdutosConferidos = true;
                $todoMapaConferido = true;

                foreach ($listaQtdProdutosConferidos as $qtdProdutoConferido) {
                    if ($qtdProdutoConferido['QTD_PRODUTO_CONFERIR'] != 0) {
                        $todosProdutosConferidos = false;
                        break;
                    }
                }
                foreach ($listaProdutosNãoConferidosMapa as $produtoNaoConferidoMapa) {
                    if ($produtoNaoConferidoMapa['QTD_PRODUTO_CONFERIR'] != 0) {
                        $todoMapaConferido = false;
                        break;
                    }
                }

                $this->addFlashMessage('success', "Quantidade Conferida com sucesso");

                if ($todosProdutosConferidos == true)
                    $this->addFlashMessage('success', 'Todos os Produtos ' . $embalagemEn->getProduto()->getId() . ' - ' . $embalagemEn->getProduto()->getGrade() . ' foram conferidos com sucesso!');

                if ($todoMapaConferido == true)
                    $this->addFlashMessage('success', 'Todo o Mapa foi conferido com sucesso!');

                $this->_redirect('mobile/expedicao/ler-produto-mapa/idMapa/' . $idMapa . "/idExpedicao/" . $idExpedicao . "/idVolume/" . $idVolume . '/cliente/' . $codPessoa);
            } catch (\Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
            }
        } else {
            $this->addFlashMessage('info', 'Informe uma Quantidade');
        }
    }

    public function tipoConferenciaAction() {
        $idExpedicao = $this->_getParam('idExpedicao', null);
        $placa = $this->_getParam('placa', null);
        if ($placa != null) {
            $url = '/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/' . $idExpedicao . '/placa/' . $placa;
            $urlNEmbalado = '/mobile/expedicao/ler-codigo-barras/idExpedicao/' . $idExpedicao . '/tipo-conferencia/naoembalado/placa/' . $placa;
            $urlEmbalado = '/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/' . $idExpedicao;
        } else {
            $urlNEmbalado = '/mobile/expedicao/ler-codigo-barras/idExpedicao/' . $idExpedicao . '/tipo-conferencia/naoembalado';
            $url = '/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/' . $idExpedicao;
            $urlEmbalado = '/mobile/volume-patrimonio/carrega-tipo/idExpedicao/' . $idExpedicao;
        }
        $menu = array(
            1 => array(
                'url' => $url . '/box/1',
                'label' => 'CONF. VOLUME',
            ),
            2 => array(
                'url' => $urlNEmbalado,
                'label' => 'CONF. NÃO EMBALADO',
            ),
            3 => array(
                'url' => $urlEmbalado,
                'label' => 'CONF. EMBALADO',
            ),
        );

        if ($placa != null) {
            unset($menu[3]);
        }

        $this->view->menu = $menu;
        $this->renderScript('menu.phtml');
    }

    public function divergenciaAction() {
        $request = $this->getRequest();
        $idExpedicao = $request->getParam('idExpedicao');
        $idMapa = $request->getParam('idMapa');

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacao");
        $produtosMapa = $mapaSeparacaoRepo->validaConferencia($idExpedicao, false, $idMapa, 'D');
        $this->view->produtos = $produtosMapa;
    }

    public function finalizarAction() {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo = $this->em->getRepository('wms:Expedicao');
        $request = $this->getRequest();
        $idExpedicao = $request->getParam('idExpedicao');
        $idMapa = $request->getParam('idMapa');
        $mapa = $request->getParam('mapa', "N");
        $checkout = $this->_getParam('checkout');

        if (empty($checkout)) {
            $sessao = new \Zend_Session_Namespace('coletor');
            $central = $sessao->centralSelecionada;
        } else {
            $central = $this->_getParam('central');
        }

        $modeloSeparacaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao");

        //OBTEM O MODELO DE SEPARACAO VINCULADO A EXPEDICAO
        $modeloSeparacaoEn = $modeloSeparacaoRepo->getModeloSeparacao($idExpedicao);
        $quebraColetor = $modeloSeparacaoEn->getUtilizaQuebraColetor();

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacao");

        if ($quebraColetor == 'S') {
            if (isset($idMapa) && !empty($idMapa)) {
                $result = $ExpedicaoRepo->finalizarExpedicao($idExpedicao, $central, true, 'C', $idMapa);
                $mapaEn = $mapaSeparacaoRepo->findOneBy(array('id' => $idMapa));
                if ($mapaEn->getCodStatus() == EtiquetaSeparacao::STATUS_CONFERIDO) {
                    $this->addFlashMessage('success', "Mapa de Separação $idMapa Finalizado com sucesso!");
                }
            } else {
                $result = $ExpedicaoRepo->finalizarExpedicao($idExpedicao, $central, true, 'C', $idMapa);
            }
        } else {
            $result = $ExpedicaoRepo->finalizarExpedicao($idExpedicao, $central, true, 'C');
        }

        /** @var \Wms\Domain\Entity\Expedicao $expedicaoEn */
        $expedicaoEn  = $this->getEntityManager()->getRepository('wms:Expedicao')->findOneBy(array('id'=>$idExpedicao));
        if ($expedicaoEn->getCodStatus() == \Wms\Domain\Entity\Expedicao::STATUS_EM_FINALIZACAO) {
            $statusConferencia = $this->getEntityManager()->getRepository('wms:Util\Sigla')->findOneBy(array('id' => Expedicao::STATUS_EM_CONFERENCIA));
            $expedicaoEn->setStatus($statusConferencia);
            $expedicaoEn->setCodStatus(\Wms\Domain\Entity\Expedicao::STATUS_EM_CONFERENCIA);
            $this->getEntityManager()->persist($expedicaoEn);
            $this->getEntityManager()->flush();
        }

        if (is_string($result)) {

            if (substr($result, 0, 46) == "Existem produtos para serem Conferidos no mapa") {
                $linkImpressao = '<a href="' . $this->view->url(array('controller' => 'expedicao', 'action' => 'divergencia', 'idExpedicao' => $idExpedicao, 'idMapa' => $idMapa)) . '" target="_self" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Ver Divergencias</a>';
                $result = "$result - $linkImpressao";
            }

            $this->addFlashMessage('error', $result);
            if ($mapa == 'S') {

                if ($checkout == 1) {
                    $this->_redirect('expedicao/index/checkout-expedicao');
                } else {
                    $this->_redirect("mobile/expedicao/index/idCentral/$central");
                }
            }
            $this->redirect('conferencia-expedicao', 'ordem-servico', 'mobile', array('idCentral' => $central));
        } else if ($result === 0) {
            $this->addFlashMessage('success', 'Primeira Conferência finalizada com sucesso');
        } else {
            $this->addFlashMessage('success', 'Conferência finalizada com sucesso');
        }

        if ($this->getSystemParameterValue('VINCULA_EQUIPE_CARREGAMENTO') == 'S') {
            $this->redirect('carregamento', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao));
        }
        if ($checkout == 1) {
            $this->_redirect('expedicao/index/checkout-expedicao');
        } else {
            $this->_redirect('mobile/expedicao/index/idCentral/' . $central);
        }
    }

    public function selecionaPlacaAction() {
        $idExpedicao = $this->getRequest()->getParam('idExpedicao');
        $sessaoColetor = new \Zend_Session_Namespace('coletor');
        $sessaoColetor->parcialmenteFinalizado = true;

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->em->getRepository('wms:Expedicao');
        $placas = $expedicaoRepo->getPlacasByExpedicaoCentral($idExpedicao);

        $this->view->placas = $placas;
    }

    protected function validacaoEtiqueta($codigoBarras) {
        $this->bloquearOs();
        $idExpedicao = $this->getRequest()->getParam('idExpedicao');
        $placa = $this->getRequest()->getParam('placa', null);
        $tipoConferencia = $this->getRequest()->getParam('tipo-conferencia', null);
        $idTipoVolume = $this->getRequest()->getParam('idTipoVolume', null);
        $volume = $this->getRequest()->getParam('volume', null);
        $sessao = new \Zend_Session_Namespace('coletor');
        $idCentral = $sessao->centralSelecionada;

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $etiqueta = $etiquetaRepo->getEtiquetaByExpedicaoAndId($codigoBarras);
        if (count($etiqueta) == 0) {

            $msg = 'Etiqueta ' . $codigoBarras . ' não encontrada';
            $this->gravaAndamentoExpedicao($msg, $idExpedicao, $codigoBarras, null);

            if ($this->bloquearOs == 'S') {
                $this->bloqueioOs($idExpedicao, $msg, \Wms\Domain\Entity\OrdemServico::BLOCK_ETIQ);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml("error", $msg, $this->createUrlMobile());
                } else {
                    $this->redirect('liberar-os', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                    die();
                }
            } else {
                $this->createXml("error", $msg);
                die();
            }

            return false;
        }

        if ($etiqueta[0]['reentregaExpedicao'] != null) {
            if ($etiqueta[0]['reentregaExpedicao'] != $idExpedicao) {
                $msg = 'Etiqueta de reentrega' . $codigoBarras . ' pertence a expedicao ' . $etiqueta[0]['codExpedicao'];
                $this->gravaAndamentoExpedicao($msg, $idExpedicao, $codigoBarras, null);

                if ($this->bloquearOs == 'S') {
                    $this->bloqueioOs($idExpedicao, $msg, \Wms\Domain\Entity\OrdemServico::BLOCK_ETIQ);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml('error', $msg, $this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                } else {
                    $this->createXml("error", $msg);
                    die();
                }
                return false;
            } else {
                return $etiqueta;
            }
        }

        if ($etiqueta[0]['codExpedicao'] != $idExpedicao) {
            $msg = 'Etiqueta ' . $codigoBarras . ' pertence a expedicao ' . $etiqueta[0]['codExpedicao'];
            $this->gravaAndamentoExpedicao($msg, $idExpedicao, $codigoBarras, null);

            if ($this->bloquearOs == 'S') {
                $this->bloqueioOs($idExpedicao, $msg, \Wms\Domain\Entity\OrdemServico::BLOCK_ETIQ);

                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('error', $msg, $this->createUrlMobile());
                } else {
                    $this->redirect('liberar-os', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                    die();
                }
            } else {
                $this->createXml("error", $msg);
                die();
            }
            return false;
        }


        //Se o tipo de conferencia for nao embalado, nao se pode bipar produtos que devem ser embalados
        if ($tipoConferencia == 'naoembalado' && $etiqueta[0]['embalado'] == 'S') {
            $msg = 'Produtos embalados devem ser vinculados a um patrimônio';
            $this->gravaAndamentoExpedicao($msg, $idExpedicao, $codigoBarras, null);
            if ($this->bloquearOs == 'S') {
                $this->createXml('error', $msg);
            } else {
                $this->createXml("error", $msg);
                die();
            }
        }

        //Verifico se a etiqueta pertence a carga selecionada
        if (!is_null($idTipoVolume) && !empty($idTipoVolume)) {
            if ($idTipoVolume != $etiqueta[0]['codCargaExterno']) {
                $msg = 'Etiqueta ' . $codigoBarras . ' não pertence a carga selecionada - Carga Correta:' . $etiqueta[0]['codCargaExterno'];
                $this->gravaAndamentoExpedicao($msg, $idExpedicao, $codigoBarras, null);

                if ($this->bloquearOs == 'S') {
                    $this->bloqueioOs($idExpedicao, $msg, \Wms\Domain\Entity\OrdemServico::BLOCK_ETIQ);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml('error', $msg, $this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                } else {
                    $this->createXml("error", $msg, '/mobile/expedicao/ler-codigo-barras/idExpedicao/' . $idExpedicao . '/placa/' . $placa . '/bloqueiaOS/1/tipo-conferencia/' . $tipoConferencia . '/idTipoVolume/' . $idTipoVolume . "/msg/" . $msg);
                    die();
                }
                return false;
            }
        }

        //Etiqueta pertence a central Selecionada e a placa selecionada
        if (!is_null($placa) && !empty($placa)) {
            if ($etiqueta[0]['pontoTransbordo'] != $idCentral) {
                $msg = 'Etiqueta não pertence a central ' . $idCentral;
                $this->gravaAndamentoExpedicao($msg, $idExpedicao, $codigoBarras, null);

                if ($this->bloquearOs == 'S') {
                    $this->bloqueioOs($idExpedicao, $msg, \Wms\Domain\Entity\OrdemServico::BLOCK_ETIQ);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml('error', $msg, $this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                } else {
                    $this->createXml("error", $msg, '/mobile/expedicao/ler-codigo-barras/idExpedicao/' . $idExpedicao . '/placa/' . $placa . '/bloqueiaOS/1/tipo-conferencia/' . $tipoConferencia . '/idTipoVolume/' . $idTipoVolume . "/msg/" . $msg);
                    die();
                }
                return false;
            }
            if ($etiqueta[0]['placaCarga'] != $placa) {
                $msg = 'Etiqueta não pertence a placa ' . $placa;

                $this->gravaAndamentoExpedicao($msg, $idExpedicao, $codigoBarras, null);
                $this->bloqueioOs($idExpedicao, $msg, \Wms\Domain\Entity\OrdemServico::BLOCK_ETIQ);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('error', 'Etiqueta não pertence a placa ' . $placa, $this->createUrlMobile());
                } else {
                    $this->createXml("error", $msg, '/mobile/expedicao/ler-codigo-barras/idExpedicao/' . $idExpedicao . '/placa/' . $placa . '/bloqueiaOS/1/tipo-conferencia/' . $tipoConferencia . '/idTipoVolume/' . $idTipoVolume . "/msg/" . $msg);
                    die();
                }
                return false;
            }
        } else {
            if ($etiqueta[0]['codEstoque'] != $idCentral) {
                $msg = 'Etiqueta não pertence a central ' . $idCentral;
                $this->gravaAndamentoExpedicao($msg, $idExpedicao, $codigoBarras, null);
                $this->bloqueioOs($idExpedicao, 'Etiqueta não pertence a central ' . $idCentral, \Wms\Domain\Entity\OrdemServico::BLOCK_ETIQ);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('error', 'Etiqueta não pertence a central ' . $idCentral, $this->createUrlMobile());
                } else {
                    $this->createXml("error", $msg, '/mobile/expedicao/ler-codigo-barras/idExpedicao/' . $idExpedicao . '/placa/' . $placa . '/bloqueiaOS/1/tipo-conferencia/' . $tipoConferencia . '/idTipoVolume/' . $idTipoVolume . "/msg/" . $msg);
                    die();
                }
                return false;
            }
        }

        return $etiqueta;
    }

    public function validaStatusReentrega($etiqueta) {

        $etiquetaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaSeparacao");
        $nfSaidaPedidoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\NotaFiscalSaidaPedido");
        $esReentregaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaSeparacaoReentrega");

        $esReentregaEn = $esReentregaRepo->findOneBy(array('codEtiquetaSeparacao' => $etiqueta[0]['codBarras'],
            'codReentrega' => $etiqueta[0]['codReentrega']));

        if ($this->getSystemParameterValue('CONFERE_RECEBIMENTO_REENTREGA') == 'S') {

            $reentregaEn = $esReentregaEn->getReentrega();
            $nfSaidaEn = $reentregaEn->getNotaFiscalSaida();
            $statusNf = $nfSaidaEn->getStatus()->getId();
            if ($statusNf != Expedicao\NotaFiscalSaida::DEVOLVIDO_PARA_REENTREGA) {
                return array('result' => false, 'msg' => "Nota Fiscal de reentrega" . $nfSaidaEn->getNumeroNf() . "/" . $nfSaidaEn->getSerieNf() . " ainda não foi recebida");
            }
        }


        if ($esReentregaEn->getCodStatus() != EtiquetaSeparacao::STATUS_PENDENTE_REENTREGA) {
            return array('result' => false, 'msg' => "Etiqueta de Separação de Reentrega" . $etiqueta[0]['codBarras'] . " já foi conferida");
        }

        $siglaEn = $this->getEntityManager()->getRepository('wms:Util\Sigla')->findOneBy(array('id' => EtiquetaSeparacao::STATUS_CONFERIDO));

        $esReentregaEn->setStatus($siglaEn);
        $esReentregaEn->setCodStatus($siglaEn->getId());
        $this->getEntityManager()->persist($esReentregaEn);
        $this->getEntityManager()->flush();

        return array('result' => true, 'msg' => "Etiqueta de Conferida com sucesso");
    }

    public function validaStatusEtiqueta($idExpedicao, $status, $sessaoColetor, $etiqueta = null) {
        $this->bloquearOs();
        $tipoConferencia = $this->getRequest()->getParam('tipo-conferencia', null);
        $idTipoVolume = $this->getRequest()->getParam('idTipoVolume', null);


        $obrigaRealizarRecebimento = $sessaoColetor->ObrigaBiparEtiquetaProduto;
        $placa = $this->getRequest()->getParam('placa', null);

        switch ($status) {
            case EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO:
            case EtiquetaSeparacao::STATUS_PENDENTE_CORTE:
            case EtiquetaSeparacao::STATUS_CORTADO:
            case EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO;
                return false;
                break;
            case EtiquetaSeparacao::STATUS_CONFERIDO:
                if ($sessaoColetor->parcialmenteFinalizado == false) {

                    $verificaReconferencia = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'RECONFERENCIA_EXPEDICAO'))->getValor();

                    if ($verificaReconferencia == 'S') {
                        $expedEntity = $this->_em->getReference('wms:Expedicao', $idExpedicao);
                        $statusExped = $expedEntity->getStatus()->getId();

                        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaConferenciaRepository $etiquetaConfRepo */
                        $etiquetaConfRepo = $this->em->getRepository('wms:Expedicao\EtiquetaConferencia');

                        if ($statusExped == Expedicao::STATUS_PRIMEIRA_CONFERENCIA) {

                            $resultado = $etiquetaConfRepo->getEtiquetaByCodBarras($idExpedicao, $etiqueta);

                            if ($resultado[0]['codStatus'] == Expedicao::STATUS_PRIMEIRA_CONFERENCIA)
                                return false;
                        } else if ($statusExped == Expedicao::STATUS_SEGUNDA_CONFERENCIA) {

                            $resultado = $etiquetaConfRepo->getEtiquetaByCodBarras($idExpedicao, $etiqueta);

                            if ($resultado[0]['codStatus'] == Expedicao::STATUS_SEGUNDA_CONFERENCIA)
                                return false;
                        }
                    } else {
                        return false;
                    }
                } else {
                    if ($obrigaRealizarRecebimento == 'S') {
                        $msg = 'Recebimento de transbordo da expedição ' . $idExpedicao . ' não concluido';
                        $this->gravaAndamentoExpedicao($msg, $idExpedicao, $etiqueta, null);
                        $this->bloqueioOs($idExpedicao, 'Recebimento de transbordo da expedição ' . $idExpedicao . ' não concluido', \Wms\Domain\Entity\OrdemServico::BLOCK_ETIQ);
                        if ($this->_request->isXmlHttpRequest()) {
                            $this->createXml('error', 'Recebimento de transbordo da expedição ' . $idExpedicao . ' não concluido', $this->createUrlMobile());
                        } else {
                            $this->createXml("error", $msg, '/mobile/expedicao/ler-codigo-barras/idExpedicao/' . $idExpedicao . '/placa/' . $placa . '/bloqueiaOS/1/tipo-conferencia/' . $tipoConferencia . '/idTipoVolume/' . $idTipoVolume . "/msg/" . $msg);
                            die();
                        }
                        return false;
                    }
                }
                break;
        }
        return true;
    }

    public function extraiCodigoBarras($etiquetas) {
        $codBarras = "";
        foreach ($etiquetas as $etiqueta) {
            $codBarras = $codBarras . '-' . trim($etiqueta['codBarrasProduto']);
        }
        return $codBarras;
    }

    public function geraArrayCodigoBarras($value) {
        $result = explode('-', $value);
        unset($result[0]);
        return $result;
    }

    protected function bloqueioOs($idExpedicao, $motivo, $bloqDe) {
        if ($this->_em->isOpen() == false) {
            $this->_em = $this->_em->create($this->_em->getConnection(),$this->_em->getConfiguration());
        }
        $this->bloquearOs();
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->em->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\OrdemServico[] $osEntity */
        $osEntity = $expedicaoRepo->verificaOSUsuario($idExpedicao);
        $osEntity[0]->setBloqueio($motivo);
        $osEntity[0]->setBloqDe($bloqDe);
        $this->_em->persist($osEntity[0]);
        $this->_em->flush();
        $this->view->isOldBrowserVersion = $this->getOldBrowserVersion();

        //$this->gravaAndamentoExpedicao($motivo,$idExpedicao);
        $this->_helper->messenger('error', $motivo);
    }

    protected function gravaAndamentoExpedicao($motivo, $idExpedicao, $codEtiquetaSeparacao = null, $codBarrasProduto = null) {
        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo = $this->_em->getRepository('wms:Expedicao\Andamento');
        $andamentoRepo->save($motivo, $idExpedicao, false, true, $codEtiquetaSeparacao, $codBarrasProduto);
    }

    protected function desbloqueioOs($idExpedicao, $motivo) {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->em->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\OrdemServico[] $osEntity */
        $osEntity = $expedicaoRepo->verificaOSUsuario($idExpedicao);
        $osEntity[0]->setBloqueio(NULL);
        $osEntity[0]->setBloqDe(NULL);
        $this->_em->persist($osEntity[0]);
        $this->_em->flush();

        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo = $this->_em->getRepository('wms:Expedicao\Andamento');

        $andamentoRepo->save($motivo, $idExpedicao);
        $this->_helper->messenger('success', $motivo);
        return $osEntity[0];
    }

    public function liberarOsAction() {
        $request = $this->getRequest();
        $idExpedicao = $request->getParam('idExpedicao');
        $placa = $this->getRequest()->getParam('placa', null);
        $volume = $this->getRequest()->getParam('volume', null);
        $tipoConferencia = $this->getRequest()->getParam('tipo-conferencia', null);
        $idTipoVolume = $this->getRequest()->getParam('idTipoVolume', null);
        $checkout = $this->getRequest()->getParam("checkout", false);
        $this->view->isOldBrowserVersion = $this->getOldBrowserVersion();
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $response = [];
        if ($request->isPost()) {
            $senhaDigitada = $request->getParam('senha');

            if ($EtiquetaRepo->checkAutorizacao($senhaDigitada)) {
                $os = $this->desbloqueioOs($idExpedicao, 'Ordem de serviço liberada');
                if ($os->bloqueioEtiqueta()) {
                    $this->redirect('ler-codigo-barras', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa, 'tipo-conferencia' => $tipoConferencia, 'volume' => $volume, 'idTipoVolume' => $idTipoVolume));
                } elseif ($checkout){
                     $response = ['status' => 'ok'];
                } else {
                    $this->redirect("index");
                }
            } else {
                if ($checkout) {
                    $response = [
                        'status' => 'error',
                        'msg' => 'Senha informada não é válida'
                    ];
                } else {
                    $this->addFlashMessage('error', 'Senha informada não é válida');
                }
            }
        }

        if ($checkout) {
            $vetRetorno = array('retorno' => $response);
            $this->_helper->json($vetRetorno);
        }

        $form = new SenhaLiberacao();
        $form->setDefault('idExpedicao', $idExpedicao);
        $this->view->form = $form;
        $this->render('bloqueio');
    }

    /**
     * @param $idEtiqueta
     */
    protected function confereEtiqueta($idEtiqueta, $codStatus, $volume = null, $idExpedicao = null) {
        $sessao = new \Zend_Session_Namespace('coletor');

        $date = new \DateTime();
        $date = $date->format('Y-m-d H:i:s');

        if ($codStatus == EtiquetaSeparacao::STATUS_ETIQUETA_GERADA) {
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
            $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
            $etiquetaRepo->incrementaQtdAtentidaOuCortada($idEtiqueta, 'atendida');
        }

        if (isset($sessao->parcialmenteFinalizado) && $sessao->parcialmenteFinalizado == true) {
            $q1 = $this->_em->createQuery('update wms:Expedicao\EtiquetaSeparacao es set es.status = :status, es.codOSTransbordo = :osID , es.dataConferenciaTransbordo = :dataConferencia, es.volumePatrimonio = :volumePatrimonio where es.id = :idEtiqueta');
            $q1->setParameter('status', EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO);
            $q1->setParameter('dataConferencia', $date);
        } else {
            $verificaReconferencia = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'RECONFERENCIA_EXPEDICAO'))->getValor();

            if ($verificaReconferencia == 'S') {
                $expedEntity = $this->_em->getReference('wms:Expedicao', $idExpedicao);
                $statusExped = $expedEntity->getStatus()->getId();

                if ($statusExped == Expedicao::STATUS_PRIMEIRA_CONFERENCIA) {
                    $q2 = $this->_em->createQuery('update wms:Expedicao\EtiquetaConferencia es set es.status = :status, es.codOsPrimeiraConferencia = :osID , es.dataConferencia = :dataConferencia, es.volumePatrimonio = :volumePatrimonio where es.codEtiquetaSeparacao = :idEtiqueta');
                    $q2->setParameter('status', EtiquetaSeparacao::STATUS_PRIMEIRA_CONFERENCIA);
                    $q2->setParameter('dataConferencia', $date);

                    $q1 = $this->_em->createQuery('update wms:Expedicao\EtiquetaSeparacao es set es.status = :status, es.codOS = :osID , es.dataConferencia = :dataConferencia, es.volumePatrimonio = :volumePatrimonio where es.id = :idEtiqueta');
                    $q1->setParameter('dataConferencia', $date);
                } else {
                    $q2 = $this->_em->createQuery('update wms:Expedicao\EtiquetaConferencia es set es.status = :status, es.codOsSegundaConferencia = :osID , es.dataReconferencia = :dataReconferencia, es.volumePatrimonio = :volumePatrimonio where es.codEtiquetaSeparacao = :idEtiqueta');
                    $q2->setParameter('status', EtiquetaSeparacao::STATUS_SEGUNDA_CONFERENCIA);
                    $q2->setParameter('dataReconferencia', $date);

                    $q1 = $this->_em->createQuery('update wms:Expedicao\EtiquetaSeparacao es set es.status = :status, es.codOS = :osID , es.volumePatrimonio = :volumePatrimonio where es.id = :idEtiqueta');
                }

                $q2->setParameter('osID', $sessao->osID);
                $q2->setParameter('idEtiqueta', $idEtiqueta);
                $q2->setParameter('volumePatrimonio', $volume);
                $q2->execute();
            } else {
                $q1 = $this->_em->createQuery('update wms:Expedicao\EtiquetaSeparacao es set es.status = :status, es.codOS = :osID , es.dataConferencia = :dataConferencia, es.volumePatrimonio = :volumePatrimonio where es.id = :idEtiqueta');
                $q1->setParameter('dataConferencia', $date);
            }

            $q1->setParameter('status', EtiquetaSeparacao::STATUS_CONFERIDO);
        }

        $q1->setParameter('osID', $sessao->osID);
        $q1->setParameter('idEtiqueta', $idEtiqueta);
        $q1->setParameter('volumePatrimonio', $volume);
        $q1->execute();
    }

    public function verificaEtiquetaValidaAjaxAction() {
        $this->bloquearOs();

        $etiquetaSeparacao = ColetorUtil::retiraDigitoIdentificador($this->getRequest()->getParam('etiquetaSeparacao'));
        $etiqueta = $this->validacaoEtiqueta($etiquetaSeparacao);

        //VERIFICA SE O PRODUTO PERTENCE A ETIQUETA CORRETA
        $etiquetaProduto = $this->getRequest()->getParam('etiquetaProduto');
        if (isset($etiquetaProduto)) {
            $arraycodBarrasProduto = $this->geraArrayCodigoBarras($this->extraiCodigoBarras($etiqueta));
            $etiquetaProduto = ColetorUtil::adequaCodigoBarras($etiquetaProduto, true);

            if (!in_array($etiquetaProduto, $arraycodBarrasProduto)) {
                $msg = 'Produto ' . $etiqueta[0]['codProduto'] . ' - ' . $etiqueta[0]['produto'] . ' - ' . $etiqueta[0]['grade'] . ' ref. Etq. Sep. ' . $etiquetaSeparacao . ' não confere com a etiqueta do fabricante ' . $etiquetaProduto;

                if ($this->bloquearOs != 'S') {
                    $vetRetorno = array('retorno' => array('resposta' => 'error', 'message' => $msg));
                    $this->_helper->json($vetRetorno);
                    die;
                }
                return false;
            }
        }
    }

    public function buscarEtiquetasAction() {
        $this->bloquearOs();
        $idTipoVolume = $this->getRequest()->getParam('idTipoVolume', null);

        $sessaoColetor = new \Zend_Session_Namespace('coletor');
        $idExpedicao = $this->getRequest()->getParam('idExpedicao');
        $etiquetaSeparacao = $this->getRequest()->getParam('etiquetaSeparacao');
        $etiquetaSeparacao = ColetorUtil::retiraDigitoIdentificador($etiquetaSeparacao);
        $placa = $this->getRequest()->getParam('placa', null);
        $tipoConferencia = $this->getRequest()->getParam('tipo-conferencia', null);
        $volume = $this->getRequest()->getParam('volume', null);

        $etiqueta = $this->validacaoEtiqueta($etiquetaSeparacao);

        if ($etiqueta == false) {
            $msg = "";
            if ($this->bloquearOs == 'S') {
                return false;
            }
        }


//        VERIFICA SE O PRODUTO PERTENCE A ETIQUETA CORRETA
        $etiquetaProduto = $this->getRequest()->getParam('etiquetaProduto');
        if (isset($etiquetaProduto)) {
            $arraycodBarrasProduto = $this->geraArrayCodigoBarras($this->extraiCodigoBarras($etiqueta));
            $etiquetaProduto = ColetorUtil::adequaCodigoBarras($etiquetaProduto, true);

            if (!in_array($etiquetaProduto, $arraycodBarrasProduto)) {
                $msg = 'Produto ' . $etiqueta[0]['codProduto'] . ' - ' . $etiqueta[0]['produto'] . ' - ' . $etiqueta[0]['grade'] . ' ref. Etq. Sep. ' . $etiquetaSeparacao . ' não confere com a etiqueta do fabricante ' . $etiquetaProduto;
                $this->gravaAndamentoExpedicao($msg, $idExpedicao, $etiquetaSeparacao, $etiquetaProduto);
                //$this->_helper->messenger('info', $msg);

                if ($this->bloquearOs == 'S') {
                    $this->bloqueioOs($idExpedicao, $msg, \Wms\Domain\Entity\OrdemServico::BLOCK_ETIQ);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml("error", $msg, $this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                } else {
                    $this->createXml("error", $msg);
                    die();
                }
                return false;
            }
        }


        if ($etiqueta[0]['reentregaExpedicao'] == null) {
            $return = $this->validaStatusEtiqueta($idExpedicao, $etiqueta[0]['codStatus'], $sessaoColetor, $etiquetaSeparacao);
        } else {
            $return = $this->validaStatusReentrega($etiqueta);
            if ($return['result'] == false) {
                $msg = $return['msg'];
                $this->gravaAndamentoExpedicao($msg, $idExpedicao, $etiquetaSeparacao, null);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml("error", $msg);
                } else {
                    $this->redirect('ler-codigo-barras', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
                return false;
            } else {
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('success', 'Etiqueta conferida com sucesso');
                } else {
                    $this->addFlashMessage('success', 'Etiqueta conferida com sucesso');
                    $this->redirect('ler-codigo-barras', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
                return true;
            }
        }

        if ($return == false) {
            switch ($etiqueta[0]['codStatus']) {
                case EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO:
                    $this->_helper->messenger('info', 'Etiqueta pendente de impressão');
                    $mensagem = 'Etiqueta pendente de impressão';
                case EtiquetaSeparacao::STATUS_PENDENTE_CORTE:
                    $this->_helper->messenger('info', 'Etiqueta pendente de corte');
                    $mensagem = 'Etiqueta pendente de corte';
                case EtiquetaSeparacao::STATUS_CORTADO:
                    $this->_helper->messenger('info', 'Etiqueta cortada');
                    $mensagem = 'Etiqueta cortada';
                case EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO;
                    $this->_helper->messenger('info', 'Etiqueta de transbordo já conferida');
                    $mensagem = 'Etiqueta de transbordo já conferida';
                case EtiquetaSeparacao::STATUS_CONFERIDO:
                    $this->_helper->messenger('info', 'Etiqueta já conferida');
                    $mensagem = 'Etiqueta já conferida';
            }

            $msg = $mensagem;
            $this->gravaAndamentoExpedicao($msg, $idExpedicao, $etiquetaSeparacao, null);
            if ($this->_request->isXmlHttpRequest()) {
                $this->createXml("error", $msg);
            } else {
                $this->redirect('ler-codigo-barras', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
            }

            return false;
        }

        if ($sessaoColetor->parcialmenteFinalizado == true) {
            $obrigaBiparEtiqueta = $sessaoColetor->RecebimentoTransbordoObrigatorio;
            if ($obrigaBiparEtiqueta == 'N') {
                $this->confereEtiqueta($etiquetaSeparacao, $etiqueta[0]['codStatus'], $volume, $idExpedicao);
                $this->addFlashMessage('success', 'Produto conferido com sucesso');
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('success', 'Produto conferido com sucesso');
                } else {
                    $this->redirect('ler-codigo-barras', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
            }
        }

        $etiquetaProduto = $this->getRequest()->getParam('etiquetaProduto');
        if (isset($etiquetaProduto)) {
            $arraycodBarrasProduto = $this->geraArrayCodigoBarras($this->extraiCodigoBarras($etiqueta));
            $etiquetaProduto = ColetorUtil::adequaCodigoBarras($etiquetaProduto, true);

            if (!in_array($etiquetaProduto, $arraycodBarrasProduto)) {
                $msg = 'Produto ' . $etiqueta[0]['codProduto'] . ' - ' . $etiqueta[0]['produto'] . ' - ' . $etiqueta[0]['grade'] . ' ref. Etq. Sep. ' . $etiquetaSeparacao . ' não confere com a etiqueta do fabricante ' . $etiquetaProduto;
                $this->gravaAndamentoExpedicao($msg, $idExpedicao, $etiquetaSeparacao, $etiquetaProduto);
                //$this->_helper->messenger('info', $msg);

                if ($this->bloquearOs == 'S') {
                    $this->bloqueioOs($idExpedicao, $msg, \Wms\Domain\Entity\OrdemServico::BLOCK_ETIQ);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml("error", $msg, $this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                } else {
                    $this->createXml("error", $msg);
                    die();
                }
                return false;
            }
        }

        if (($etiqueta[0]['embalado'] == 'S') && (is_null($volume))) {
            $msg = "A etiqueta " . $etiquetaSeparacao . " precisa de um volume informado pois é Embalado";
            $this->gravaAndamentoExpedicao($msg, $idExpedicao, $etiquetaSeparacao, null);
            $this->createXml("error", $msg, '/mobile/expedicao/ler-codigo-barras/idExpedicao/' . $idExpedicao . '/placa/' . $placa . '/bloqueiaOS/1/tipo-conferencia/' . $tipoConferencia . '/idTipoVolume/' . $idTipoVolume . "/msg/" . $msg);
        }

        $this->confereEtiqueta($etiquetaSeparacao, $etiqueta[0]['codStatus'], $volume, $idExpedicao);

        if ($this->_request->isXmlHttpRequest()) {
            $this->createXml('success', 'Etiqueta conferida com sucesso');
        } else {

            $this->addFlashMessage('success', 'Etiqueta conferida com sucesso');
            $this->redirect('ler-codigo-barras', 'expedicao', 'mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
        }
    }

    public function lerCodigoBarrasAction() {
        try {
            $Expedicao = new \Wms\Coletor\Expedicao($this->getRequest(), $this->em);
            $Expedicao->setLayout();

            $this->view->volume = $this->_getParam('volume', null);
            $this->view->idTipoVolume = $this->_getParam('idTipoVolume', null);
            $this->view->mensagem = $this->_getParam('msg', null);
            $this->view->placa = $Expedicao->getPlaca();
            $this->view->idExpedicao = $Expedicao->getIdExpedicao();

            $expedicaoEn = $this->getEntityManager()->getRepository("wms:Expedicao")->find($Expedicao->getIdExpedicao());
            if ($expedicaoEn->getStatus()->getId() == Expedicao::STATUS_SEGUNDA_CONFERENCIA) {
                $this->view->segundaConferencia = "S";
            } else {
                $this->view->segundaConferencia = "N";
            }

            $url = "/volume" . $this->_getParam('volume', null) . "/volume" . $this->_getParam('volume', null) . "/placa" . $this->_getParam('placa', null) . "/bloqueiaOS" . $this->_getParam('bloqueiaOS', null);

            if (($Expedicao->validacaoExpedicao() == false) || ( $Expedicao->osLiberada() == false)) {
                $this->mensagemColetor($Expedicao, $url);
            }

            if ($Expedicao->possuiEmbalado() == true) {
                $this->_forward('tipo-conferencia', 'expedicao', 'mobile', array('placa' => $Expedicao->getPlaca()));
            }
            $acao = "Expedição:";
            $sessaoColetor = new \Zend_Session_Namespace('coletor');
            if ($sessaoColetor->parcialmenteFinalizado == true) {
                $acao = "Expedição de Transbordo:";
            }
            $this->view->acao = $acao;
            $this->view->volume = $this->_getParam('volume', null);
            $this->view->idTipoVolume = $this->_getParam('idTipoVolume', null);
            $this->view->placa = $Expedicao->getPlaca();
            $this->view->idExpedicao = $Expedicao->getIdExpedicao();
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
            if ($this->_request->isXmlHttpRequest()) {
                $this->createXml('error', $e->getMessage(), "/mobile/ordem-servico/conferencia-expedicao");
            } else {
                $this->redirect('conferencia-expedicao', 'ordem-servico');
            }
        }
    }

    /**
     * @param $Expedicao
     */
    public function mensagemColetor($Expedicao) {
        $this->_helper->messenger($Expedicao->getStatus(), $Expedicao->getMessage());
        if ($this->_request->isXmlHttpRequest()) {
            $this->createXml($Expedicao->getRetorno(), $Expedicao->getMessage(), $Expedicao->getRedirect());
        } else {
            $this->_redirect($Expedicao->getRedirect());
        }
    }

    public function finalizadoAction() {
        $idExpedicao = $this->_getParam('idExpedicao');
        $placa = $this->_getParam('placa');

        $sessaoColetor = new \Zend_Session_Namespace('coletor');
        $obrigaBiparEtiqueta = $sessaoColetor->RecebimentoTransbordoObrigatorio;
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');

        if ($obrigaBiparEtiqueta == 'S') {
            $conferido = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao, EtiquetaSeparacao::STATUS_CONFERIDO, "Array", $placa);
            if (count($conferido) > 0) {
                $result = $conferido;
            } else {
                $result = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao, EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO, "Array", $placa);
            }
        } else {
            $result = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao, EtiquetaSeparacao::STATUS_CONFERIDO, "Array", $placa);
        }

        if (count($result) > 0) {
            $this->createXml('error', 'Faltam ' . count($result) . ' produtos a serem conferidos');
        } else {
            $this->createXml('success', 'Todos os produtos já foram recebidos');
        }
    }

    public function setIdCentral($idCentral = null) {
        $sessaoColetor = new \Zend_Session_Namespace('coletor');
        $sessaoColetor->parcialmenteFinalizado = false;

        if (empty($idCentral)) {
            $sessao = new \Zend_Session_Namespace('deposito');
            $idCentral = $sessao->centraisPermitidas;
            $sessaoColetor->centralSelecionada = $idCentral[0];
        } else {
            $sessaoColetor->centralSelecionada = $idCentral;
        }

        /** @var \Wms\Domain\Entity\Filial $filialEn */
        $filialRepo = $this->em->getRepository('wms:Filial');
        $filialEn = $filialRepo->findOneBy(array('codExterno' => $idCentral));

        if ($filialEn) {
            $sessaoColetor->ObrigaBiparEtiquetaProduto = $filialEn->getIndLeitEtqProdTransbObg();
            $sessaoColetor->RecebimentoTransbordoObrigatorio = $filialEn->getIndRecTransbObg();
            return $idCentral;
        }
        return $idCentral;
    }

    public function bloquearOs() {
        $this->bloquearOs = $this->getSystemParameterValue('BLOQUEIO_OS');

        return $this->bloquearOs;
    }

    public function expedicaoCarregamentoAction() {
        
    }

    public function carregamentoAction() {
        //OBTER OS PARAMETROS
        $operadores = $this->_getParam('mass-id');
        $this->view->idExpedicao = $idExpedicao = $this->_getParam('idExpedicao');
        $this->view->operacao = $this->_getParam('operacao');

        $btnPressionado = $this->_getParam('submit');
        $dthFinal = false;
        if ($btnPressionado == 'Finalizar') {
            $dthFinal = true;
        }

        //OBTER OS REPOSITORIOS
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->em->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\UsuarioRepository $UsuarioRepo */
        $UsuarioRepo = $this->_em->getRepository('wms:Usuario');
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $codBarras = ColetorUtil::retiraDigitoIdentificador($this->_getParam('codBarras'));
        $etiquetaEn = $etiquetaRepo->findOneBy(array('id' => $codBarras));
        $entityExpedicao = $expedicaoRepo->findOneBy(array('id' => $idExpedicao));

        if (!$entityExpedicao) {
            $this->addFlashMessage('error', 'Expedição não encontrada!');
            $this->redirect('expedicao-carregamento', 'expedicao', 'mobile');
        }
        $placa = null;

        if (!$etiquetaEn) {
            $this->view->operadores = $UsuarioRepo->getUsuarioByPerfil(0, $this->getSystemParameterValue("PERFIL_EQUIPE_CARREGAMENTO"));
            /** @var \Wms\Domain\Entity\Expedicao\EquipeCarregamentoRepository $carregamentoRepo */
            $this->view->equipe = $equipe = $this->em->getRepository('wms:Expedicao\EquipeCarregamento');
            $equipeCarregamentoEntitty = $equipe->findOneBy(array('expedicao' => $idExpedicao));
            if (($equipeCarregamentoEntitty != null) && (!is_null($equipeCarregamentoEntitty->getDataFim()))) {
                $this->_helper->messenger('error', 'Expedição já possui equipe de carregamento vinculada');
                $this->_redirect('mobile');
            }
        } else {
            //VERIFICA QUAL O STATUS DA ETIQUETA E EXIBE A EQUIPE CORRETA
            switch ($etiquetaEn->getStatus()->getId()) {
                case EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO;
                    $this->view->operadores = $UsuarioRepo->getUsuarioByPerfil(0, $this->getSystemParameterValue("PERFIL_EQUIPE_RECEBIMENTO_TRANSBORDO"));
                    /** @var \Wms\Domain\Entity\Recebimento\EquipeRecebimentoTransbordoRepository $equipeRecebTransbRepo */
                    $this->view->equipe = $equipe = $this->em->getRepository('wms:Recebimento\EquipeRecebimentoTransbordo');
                    break;
                case EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO;
                    $this->view->operadores = $UsuarioRepo->getUsuarioByPerfil(0, $this->getSystemParameterValue("PERFIL_EQUIPE_EXPEDICAO_TRANSBORDO"));
                    /** @var \Wms\Domain\Entity\Expedicao\EquipeExpedicaoTransbordoRepository $equipe */
                    $this->view->equipe = $equipe = $this->em->getRepository("wms:Expedicao\EquipeExpedicaoTransbordo");
                    $placa = str_replace('-', '', $this->_getParam('placa'));
                    break;
                default:
                    $this->view->operadores = $UsuarioRepo->getUsuarioByPerfil(0, $this->getSystemParameterValue("PERFIL_EQUIPE_CARREGAMENTO"));
                    /** @var \Wms\Domain\Entity\Expedicao\EquipeCarregamentoRepository $carregamentoRepo */
                    $this->view->equipe = $equipe = $this->em->getRepository('wms:Expedicao\EquipeCarregamento');
                    $equipeCarregamentoEntitty = $equipe->findOneBy(array('expedicao' => $idExpedicao, 'dataFim' => !null));
                    if (!is_null($equipeCarregamentoEntitty->getDataFim())) {
                        $this->_helper->messenger('error', 'Expedição já possui equipe de carregamento vinculada');
                        $this->_redirect('mobile');
                    }
                    break;
            }
        }

        if ($operadores && $idExpedicao) {

            try {
                $equipe->vinculaOperadores($idExpedicao, $operadores, $placa, $dthFinal);
                $this->_helper->messenger('success', 'Operadores vinculados a expedicao com sucesso');
                $this->_redirect('mobile');
            } catch (Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
            }
        }

        $this->view->idExpedicao = $idExpedicao;
    }

    public function bloquearEtiquetaInexistenteAjaxAction() {
        $this->view->form = $form = new \Wms\Module\Mobile\Form\BloquearEtiqueta();

        $values = $form->getParams();

        if ($values) {
            $params = $this->_getAllParams();
            if ($this->getSystemParameterValue('SENHA_FINALIZAR_EXPEDICAO') == $params['senha']) {
                $this->redirect('ler-codigo-barras', 'expedicao', 'mobile', array('idExpedicao' => $params['idExpedicao']));
            } else {
                $this->_helper->messenger('error', 'Senha Incorreta!');
                $this->redirect('bloquear-etiqueta-inexistente-ajax', 'expedicao', 'mobile', array('idExpedicao' => $params['idExpedicao']));
            }
        }
    }

    public function vincularLacreAction() {
        /** @var \Wms\Domain\Entity\Expedicao\ExpedicaoVolumePatrimonioRepository $expedicaoVolumePatrimonioRepo */
        $expedicaoVolumePatrimonioRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');
        $volumePatrimonioId = $this->_getParam('id');
        $params = $this->_getAllParams();

        if ($this->_getParam('submit')) {
            $result = $expedicaoVolumePatrimonioRepo->vinculaLacre($params);
            if ($result) {
                $this->_helper->messenger('success', 'Lacre Salvo com sucesso!');
                $this->redirect('vincular-lacre', 'expedicao', 'mobile');
            }
        }

        if ($volumePatrimonioId) {
            try {
                $expedicaoVolumePatrimonioEn = $expedicaoVolumePatrimonioRepo->findBy(array('volumePatrimonio' => $volumePatrimonioId), array('id' => 'DESC'), 1);

                if (!isset($expedicaoVolumePatrimonioEn) || empty($expedicaoVolumePatrimonioEn))
                    throw new \Exception('Volume Patrimônio nao encontrado!');

                $expedicao = $expedicaoVolumePatrimonioEn[0]->getExpedicao()->getId();
                $dataFechamento = $expedicaoVolumePatrimonioEn[0]->getDataFechamento()->format('d/m/Y');
                $id = $expedicaoVolumePatrimonioEn[0]->getId();

                echo $this->_helper->json(array('id' => $id, 'expedicao' => $expedicao, 'data' => $dataFechamento));
            } catch (\Exception $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }

    public function separacaoAjaxAction(){
        $mapa = $this->_getParam('mapa');
        $pedido = $this->_getParam('pedido');
        $idExpedicao = $this->_getParam('expedicao');
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');

        if(empty($mapa)) {
            $this->view->mapas = $mapaSeparacaoRepo->findMapasSeparar($pedido);
            $this->view->mapa = null;
            $this->view->pedido = $pedido;
        }else {
            $ordemServicoRepo = $this->_em->getRepository('wms:OrdemServico');
            $idPessoa = (isset($idPessoa)) ? $idPessoa : \Zend_Auth::getInstance()->getIdentity()->getId();
            $osEn = $ordemServicoRepo->findOneBy(array('atividade' => AtividadeEntity::SEPARACAO, 'formaConferencia' => OrdemServicoEntity::COLETOR,
                'expedicao' => $idExpedicao, 'pessoa' => $idPessoa, 'dataFinal' => null));
            if (empty($osEn)) {
                $codOs = $ordemServicoRepo->save(new OrdemServicoEntity, array(
                    'identificacao' => array(
                        'tipoOrdem' => 'expedicao',
                        'idExpedicao' => $idExpedicao,
                        'idAtividade' => AtividadeEntity::SEPARACAO,
                        'formaConferencia' => OrdemServicoEntity::COLETOR,
                    ),
                ), true, "Id");
                $apontamentoMapaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ApontamentoMapa');
                $apontamentoMapaRepo->save($mapaSeparacaoRepo->find($mapa), $idPessoa);
                $this->view->codOs = $codOs;
            }else{
                $this->view->codOs = $osEn->getId();
            }
            $this->view->mapa = $mapa;
            $this->view->pedido = $pedido;
            $this->view->idExpedicao = $idExpedicao;
            $this->view->enderecos = $mapaSeparacaoRepo->findEnderecosMapa($mapa);
        }
    }

    public function getProdutosEndAjaxAction() {
        $codigoBarras = $this->_getParam('codigoBarras');
        $codMapa = $this->_getParam('codMapa');
        $this->view->idExpedicao = $this->_getParam('idExpedicao');
        $this->view->codOs = $this->_getParam('codOs');
        $this->view->mapa = $codMapa;
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        try{
            if (!empty($codigoBarras)) {
                $codigoBarras = ColetorUtil::retiraDigitoIdentificador($codigoBarras);
                $endereco = EnderecoUtil::formatar($codigoBarras);
                $this->view->endereco = $endereco;
                $produtos = $mapaSeparacaoRepo->getProdutosMapaEndereco($endereco, $codMapa);
                if (!empty($produtos)) {
                    $this->view->produtos = $produtos;
                    $this->view->codDepositoEndereco = $produtos[0]['COD_DEPOSITO_ENDERECO'];
                }else{
                    $this->view->error = "Endereço já conferido ou não pertence ao mapa";
                    $this->view->enderecos = $mapaSeparacaoRepo->findEnderecosMapa($codMapa);
                }
            }else{
                $this->view->error = "Endereço inválido";
                $this->view->enderecos = $mapaSeparacaoRepo->findEnderecosMapa($codMapa);
            }
        } catch (\Exception $e) {
            $this->view->error = $e->getMessage();
            $this->view->enderecos = $mapaSeparacaoRepo->findEnderecosMapa($codMapa);
        }
    }

    public function separaProdutoAjaxAction(){
        $codigoBarras = $this->_getParam('codigoBarrasProd');
        $codMapaSeparacao = $this->_getParam('codMapaSeparacao');
        $codOs = $this->_getParam('codOs');
        $endereco = $this->_getParam('endereco');
        $codDepositoEndereco = $this->_getParam('codDepositoEndereco');
        $qtdSeparar = $this->_getParam('qtdSeparar');
        $lote = $this->_getParam('lote');
        $this->view->idExpedicao = $this->_getParam('idExpedicao');
        $this->view->codMapa = $codMapaSeparacao;
        try{
            $separacaomapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\SeparacaoMapaSeparacao');
            $separacaomapaSeparacaoRepo->separaProduto($codigoBarras, $codMapaSeparacao, $codOs, $codDepositoEndereco, $qtdSeparar, $lote);
        } catch (\Exception $e) {
            $this->view->error = $e->getMessage();
        }
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $this->view->produtos = $mapaSeparacaoRepo->getProdutosMapaEndereco($endereco, $codMapaSeparacao);
    }

    public function finalizaMapaAjaxAction(){
        $codMapa = $this->_getParam('codMapa');
        $codOs = $this->_getParam('codOs');
        $idExpedicao = $this->_getParam('idExpedicao');
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $ordemServicoRepo = $this->_em->getRepository('wms:OrdemServico');
        $apontamentoMapaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ApontamentoMapa');
        $idPessoa = (isset($idPessoa)) ? $idPessoa : \Zend_Auth::getInstance()->getIdentity()->getId();

        $apontamentoMapa = $apontamentoMapaRepo->findBy(array('mapaSeparacao' => $mapaSeparacaoRepo->find($codMapa)));
        foreach ($apontamentoMapa as $apontamentoMapaEn){
            $apontamentoMapaRepo->update($apontamentoMapaEn);
        }

        $whereOs = array(
            'idExpedicao' => $idExpedicao,
            'atividade' => AtividadeEntity::SEPARACAO,
            'formaConferencia' => OrdemServicoEntity::COLETOR,
        );
        $Os = $ordemServicoRepo->findBy($whereOs);
        foreach ($Os as $osEn){
            $ordemServicoRepo->finalizar($osEn->getId(), 'Separação Coletor');
        }
        $mapaSeparacaoRepo->finalizaMapaAjax($codMapa);
        $this->_helper->json(array('resposta' => 'success'));
    }

    public function getEmbalagemCodAjaxAction(){
        $codigoBarrasProd = $this->_getParam('codigoBarrasProd');
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEntityManager()->getRepository("wms:Produto");
        $info = $produtoRepo->getEmbalagemByCodBarras($codigoBarrasProd);
        if (!empty($info)) {
            $this->_helper->json(array('resposta' => 'success', 'dados' => $info[0]));
        } else {
            $this->_helper->json(array('resposta' => 'error', 'msg' => "Nenhum produto encontrado com esse código de barras $codigoBarrasProd"));
        }
    }

}
