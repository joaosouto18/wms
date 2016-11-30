<?php
use Wms\Controller\Action,
    Wms\Domain\Entity\Expedicao\EtiquetaSeparacao,
    Wms\Module\Mobile\Form\SenhaLiberacao,
    Wms\Service\Coletor as LeituraColetor,
    Wms\Module\Expedicao\Printer\EtiquetaSeparacao as Etiqueta,
    Wms\Domain\Entity\Expedicao;

class Mobile_ExpedicaoController extends Action
{

    protected $bloquearOs = null;

    public function indexAction()
    {
        $idCentral = $this->_getParam('idCentral');
        $this->setIdCentral($idCentral);
    }

    public function iniciarExpedicaoAction()
    {
        $expedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
        $expedicaoRepo->getExpedicaoByCliente();
    }

    public function definirOperacaoAction()
    {
        $LeituraColetor = new LeituraColetor();
        $codBarras = $LeituraColetor->retiraDigitoIdentificador($this->_getParam('codigoBarras'));
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoQuebraRepository $mapaSeparacaoQuebraRepo */
        $mapaSeparacaoQuebraRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoQuebra');
        $mapaSeparacaoQuebraEn = $mapaSeparacaoQuebraRepo->findOneBy(array('mapaSeparacao' => $codBarras));
        if (isset($mapaSeparacaoQuebraEn) && !empty($mapaSeparacaoQuebraEn)) {
            if ($mapaSeparacaoQuebraEn->getTipoQuebra() == 'T') {
                $this->_redirect('mobile/expedicao/confirma-clientes/codigoBarras/'.$codBarras);
            }
        }
        $this->_redirect('mobile/expedicao/confirmar-operacao/codigoBarras/'.$this->_getParam('codigoBarras'));
    }

    public function confirmaClientesAction()
    {
        $this->view->codigoBarras = $idMapaSeparacao = $this->_getParam('codigoBarras');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $this->view->clientes = $clientes = $mapaSeparacaoRepo->getClientesByConferencia($idMapaSeparacao);

        $mapaSeparacaoEn = $mapaSeparacaoRepo->find($idMapaSeparacao);
        $idExpedicao = $mapaSeparacaoEn->getExpedicao()->getId();
        $this->view->idExpedicao = $idExpedicao;
    }

    public function confirmarOperacaoAction()
    {
        $this->view->codBarras = $codBarras = $this->_getParam('codigoBarras');
        if (isset($codBarras) and ($codBarras != null) and ($codBarras != "")) {
            try {
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
                $this->addFlashMessage('error',$e->getMessage());
                $this->_redirect('mobile/expedicao/index');
            }
        } else {
            $this->addFlashMessage('info','informe um código de barras');
            $this->_redirect('mobile/expedicao/index');
        }
    }

    public function lerEmbaladosMapaAction()
    {
        $this->view->idEmbalado = $idEmbalado = $this->_getParam('embalado');
        $submit = $this->_getParam('submit');
        $LeituraColetor = new LeituraColetor();

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        try {
            $conferir = 'Conferir';
            if (isset($submit) || isset($conferir)) {
                $idEmbalado = $LeituraColetor->retiraDigitoIdentificador($idEmbalado);
                $mapaSeparacaoEmbaladoRepo->conferirVolumeEmbalado($idEmbalado);
                $this->addFlashMessage('success',"Volume embalado $idEmbalado conferido com sucesso!");
            }
        } catch (\Exception $e) {
            $this->addFlashMessage('error',$e->getMessage());
        }

    }

    public function lerProdutoMapaAction() {

        $idMapa = $this->_getParam("idMapa");
        $idVolume = $this->_getParam("idVolume");
        $idExpedicao = $this->_getParam("idExpedicao");
        $qtd = $this->_getParam("qtd");
        $codBarras = $this->_getParam("codigoBarras");
        $codPessoa = $this->_getParam('cliente');

        $sessao = new \Zend_Session_Namespace('coletor');
        $central = $sessao->centralSelecionada;

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findBy(array('mapaSeparacao' => $idMapa, 'pessoa' => $codPessoa), array('id' => 'DESC'));

        $statusMapaEmbalado = false;
        if (isset($codPessoa) && !empty($codPessoa)) {
            if (count($mapaSeparacaoEmbaladoEn) <= 0) {
                $mapaSeparacaoEmbaladoRepo->save($idMapa,$codPessoa);
            } elseif ($mapaSeparacaoEmbaladoEn[0]->getStatus()->getId() == Expedicao\MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FINALIZADO) {
                $mapaSeparacaoEmbaladoRepo->save($idMapa,$codPessoa,$mapaSeparacaoEmbaladoEn[0]);
            }

            if (isset($codPessoa) && !empty($codPessoa) && isset($idMapa) && !empty($idMapa)) {
                $mapaSeparacaoEmbEntity = $mapaSeparacaoEmbaladoRepo->findOneBy(array('mapaSeparacao' => $idMapa, 'pessoa' => $codPessoa, 'status' => Expedicao\MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO));
                if (isset($mapaSeparacaoEmbEntity) && !empty($mapaSeparacaoEmbEntity)) {
                    $statusMapaEmbalado = true;
                }
            }
        }

        $idModeloSeparacao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');
        $dscVolume = "";
        $this->view->idVolume = $idVolume;
        $this->view->idMapa = $idMapa;
        $this->view->idExpedicao = $idExpedicao;
        $this->view->central = $central;
        $this->view->idPessoa = $codPessoa;
        $this->view->mapaSeparacaoEmbalado = $statusMapaEmbalado;

        $Expedicao = new \Wms\Coletor\Expedicao($this->getRequest(), $this->em);
        if ( ($Expedicao->validacaoExpedicao() == false) || ($Expedicao->osLiberada() == false)) {
            //BLOQUEIA COLETOR
        }

        $volumePatrimonioRepo  = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio');
        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
        $modeloSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\ModeloSeparacao');
        $produtoEmbalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        $produtoVolumeRepo = $this->getEntityManager()->getRepository('wms:Produto\Volume');

        $produtosMapa = $mapaSeparacaoRepo->validaConferencia($idExpedicao, false, $idMapa);
        if (count($produtosMapa) > 0) {
            $this->view->headScript()->appendFile($this->view->baseUrl() . '/wms/resources/jquery/jquery.cycle.all.latest.js');
            $this->view->produtosMapa = $produtosMapa;
        }


        $volumePatrimonioEn = null;
        if ((isset($idVolume)) && ($idVolume != null)) {
            $volumePatrimonioEn = $volumePatrimonioRepo->find($idVolume);
            if (!empty($volumePatrimonioEn))
                $dscVolume = $volumePatrimonioEn->getId() . ' - ' . $volumePatrimonioEn->getDescricao();
        }

        $modeloSeparacaoEn = $modeloSeparacaoRepo->find($idModeloSeparacao);
        $mapaEn = $mapaSeparacaoRepo->find($idMapa);

        if (isset($codBarras) and ($codBarras != null) and ($codBarras != "") && isset($idMapa) && !empty($idMapa)) {
            try {
                $codBarrasProcessado = (float) $codBarras;

                $tipoProvavelCodBarras = 'embalagem';
                $embalagens = null;

                $produtoEmbalagenEn = $produtoEmbalagemRepo->findOneBy(array('codigoBarras' => $codBarras));
                $produtoVolumeEn = $produtoVolumeRepo->findOneBy(array('codigoBarras' => $codBarras));
                if ((isset($produtoEmbalagenEn) && !empty($produtoEmbalagenEn)) || (isset($produtoVolumeEn) && !empty($produtoVolumeEn))) {
                    $tipoProvavelCodBarras = 'embalagem';
                } else if ((strlen($codBarrasProcessado) > 2) && ((substr($codBarrasProcessado, 0, 2)) == "13")) {
                    if (empty($volumePatrimonioEn)) {
                        $tipoProvavelCodBarras = 'volume';
                    } else {
                        $produtoRepo = $this->getEntityManager()->getRepository('wms:Produto');
                        $embalagens = $produtoRepo->getEmbalagensByCodBarras($codBarras);
                        /** @var Expedicao\VolumePatrimonio $novoVolumeEn */
                        $novoVolumeEn = $volumePatrimonioRepo->find($codBarrasProcessado);

                        if (empty($embalagens) && !empty($novoVolumeEn)) {
                            throw new \Exception("Já existe um volume patrimonio, feche a caixa " . $novoVolumeEn->getId() . " antes de abrir um novo volume.");
                        } else if (empty($embalagens) && empty($novoVolumeEn)) {
                            throw new \Exception("Código de barras inválido para volume-patrimonio tanto quanto embalagens.");
                        }
                    }
                }

                if ($tipoProvavelCodBarras === 'volume') {
                    $idVolume = $codBarras;
                    $volumePatrimonioEn = $volumePatrimonioRepo->find($idVolume);
                    if (empty($volumePatrimonioEn)) {
                        $this->addFlashMessage('error',"Nenhum volume-patrimônio foi encontrado com o código $idVolume");
                        $this->_redirect("/mobile/expedicao/ler-produto-mapa/idMapa/$idMapa/idExpedicao/$idExpedicao/cliente/$codPessoa");
                    }

                    $dscVolume = $volumePatrimonioEn->getId() . ' - ' . $volumePatrimonioEn->getDescricao();
                    /** @var Expedicao\ExpedicaoVolumePatrimonioRepository $expVolumePatrimonioRepo */
                    $expVolumePatrimonioRepo = $this->em->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');

                    $codQuebra = 0;
                    if ($modeloSeparacaoEn->getTipoQuebraVolume() == 'C') {
                        $mapaSeparacaoQuebraRepo = $this->em->getRepository('wms:Expedicao\MapaSeparacaoQuebra');
                        $mapaSeparacaoEn = $mapaSeparacaoQuebraRepo->findBy(array('mapaSeparacao' => $idMapa, 'tipoQuebra' => 'C'));
                        if (isset($mapaSeparacaoEn) && !empty($mapaSeparacaoEn))
                            $codQuebra = $mapaSeparacaoEn[0]->getCodQuebra();
                    }
                    $expVolumePatrimonioRepo->vinculaExpedicaoVolume($idVolume, $idExpedicao, $codQuebra);

                    $this->view->idVolume = $codBarras;
                    $this->addFlashMessage('info', 'Volume ' . $codBarrasProcessado . ' vinculada a expedição');

                } else if ($tipoProvavelCodBarras === 'embalagem') {

                    if (empty($embalagens)) {
                        $produtoRepo = $this->getEntityManager()->getRepository('wms:Produto');
                        $embalagens = $produtoRepo->getEmbalagensByCodBarras($codBarras);
                    }
                    $embalagemEn = $embalagens['embalagem'];
                    $volumeEn = $embalagens['volume'];

                    $resultado = $mapaSeparacaoRepo->validaProdutoMapa($codBarras,$embalagemEn,$volumeEn,$mapaEn,$modeloSeparacaoEn,$volumePatrimonioEn,$codPessoa);
                    if ($resultado['return'] == false) {
                        throw new \Exception($resultado['message']);
                    }
                    $idMapa = $resultado['idMapa'];
                    $mapaEn = $mapaSeparacaoRepo->find($idMapa);

                    if (isset($qtd) && ($qtd != null)) {
                        $mapaSeparacaoRepo->adicionaQtdConferidaMapa($embalagemEn,$volumeEn,$mapaEn,$volumePatrimonioEn,$qtd);
                        $this->addFlashMessage('success', "Quantidade Conferida com sucesso");
                    } else{
                        $this->_redirect('mobile/expedicao/informa-qtd-mapa/idMapa/' . $idMapa . '/idExpedicao/' . $idExpedicao . '/codBarras/' . $codBarras . "/idVolume/" . $idVolume . '/cliente/' . $codPessoa);
                    }
                }
            } catch (\Exception $e) {
                $this->addFlashMessage('error',$e->getMessage());
            }
        }

        $this->view->dscVolume = $dscVolume;
        $this->view->exibeQtd = false;
        if ((isset($idVolume)) && ($idVolume != null)) {
            if ($modeloSeparacaoEn->getTipoConferenciaEmbalado() == "I") {
                $this->view->exibeQtd = true;
            }
        } else {
            if ($modeloSeparacaoEn->getTipoConferenciaNaoEmbalado() == "I") {
                $this->view->exibeQtd = true;
            }
        }
    }

    public function fechaVolumePatrimonioMapaAction(){
        $idMapa = $this->_getParam('idMapa');
        $idExpedicao = $this->_getParam('idExpedicao');
        $idVolume = $this->_getParam('idVolume');

        $expVolumePatrimonioRepo = $this->em->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');

        $params = "";

        try {
            $expVolumePatrimonioRepo->fecharCaixa($idExpedicao, $idVolume);
            $linkImpressao = '<a href="' . $this->view->url(array('controller' => 'expedicao', 'action' => 'imprime-volume-patrimonio', 'idExpedicao' => $idExpedicao, 'volume' => $idVolume)) . '" target="_blank" ><img style="vertical-align: middle" src="' . $this->view->baseUrl('img/icons/page_white_acrobat.png') . '" alt="#" /> Clique aqui para Imprimir</a>';
            $mensagem = "Volume $idVolume fechado com sucesso - $linkImpressao";

            $this->addFlashMessage('success', $mensagem );
        } catch (Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->_redirect('mobile/expedicao/ler-produto-mapa/idMapa/' . $idMapa . "/idExpedicao/". $idExpedicao . $params . "/idVolume/" );
    }

    public function fechaMapaEmbaladoAction()
    {
        $idMapa = $this->_getParam('idMapa');
        $idPessoa = $this->_getParam('cliente');
        $idExpedicao = $this->_getParam('idExpedicao');
        $existeItensPendentes = true;

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');

        try {
            $this->getEntityManager()->beginTransaction();
            $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->fecharMapaSeparacaoEmbalado($idMapa,$idPessoa,$mapaSeparacaoEmbaladoRepo);
            $qtdPendenteConferencia = $mapaSeparacaoEmbaladoRepo->getProdutosConferidosByCliente($idMapa,$idPessoa);
            if (count($qtdPendenteConferencia) <= 0)
                $existeItensPendentes = false;

            $this->getEntityManager()->commit();
            if (isset($mapaSeparacaoEmbaladoEn) && !empty($mapaSeparacaoEmbaladoEn)) {
                $mapaSeparacaoEmbaladoRepo->imprimirVolumeEmbalado($mapaSeparacaoEmbaladoEn,$existeItensPendentes);
            }

        } catch (Exception $e) {
            $this->getEntityManager()->rollback();
            $this->_helper->messenger('error', $e->getMessage());
        }
        if ($existeItensPendentes === false)
            $this->_redirect('mobile/expedicao/ler-produto-mapa/idMapa/' . $idMapa . '/idExpedicao/' . $idExpedicao . '/cliente/' . $idPessoa);
        else
            $this->_redirect('mobile/expedicao/confirma-clientes/codigoBarras/' . $idMapa);

    }

    public function imprimeVolumePatrimonioAction()
    {
        $idExpedicao = $this->_getParam('idExpedicao');
        $volume = $this->_getParam('volume');
        $parametroEtiquetaVolume = $this->getSystemParameterValue('MODELO_ETIQUETA_VOLUME');

        $modeloSeparacaoId = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');
        $modeloSeparacaoEn = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao")->find($modeloSeparacaoId);

        /** @var Expedicao\ExpedicaoVolumePatrimonioRepository $expVolumePatrimonioRepo */
        $expVolumePatrimonioRepo = $this->em->getRepository('wms:Expedicao\ExpedicaoVolumePatrimonio');
        /** @var Expedicao\ExpedicaoVolumePatrimonio $expVolumePatrimonioEn */
        $expVolumePatrimonioEn = $expVolumePatrimonioRepo->findOneBy(array('volumePatrimonio' => $volume, 'expedicao' => $idExpedicao));

        if (empty($expVolumePatrimonioEn))
            throw new Exception("Não foi encontrado o volume $volume na expedição $expVolumePatrimonioEn");

        $codCliente = $expVolumePatrimonioEn->getTipoVolume();
        $clienteRepo = $this->em->getRepository('wms:Pessoa\Papel\Cliente');
        $clienteEn = $clienteRepo->findBy(array('codClienteExterno' => $codCliente));

        $dscVolume = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio')->find($volume)->getDescricao();

        /** @var Expedicao $expedicaoEn */
        $expedicaoEn = $this->em->find('wms:Expedicao', $idExpedicao);

        /** @var \Wms\Domain\Entity\Pessoa $pessoaEmpresa */
        $pessoaEmpresa = $this->em->find('wms:Pessoa',1);

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

        $dataInicio = (!empty($expedicaoEn))?$expedicaoEn->getDataInicio():null;
        $emissor = (!empty($pessoaEmpresa))?$pessoaEmpresa->getNome():null;

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
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaVolume("P", 'mm', array(110, 62,5));
                    $gerarEtiqueta->imprimirExpedicaoModelo2($fields);
                    break;
                case 3:
                    $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaVolume("P", 'mm', array(110, 75));
                    $gerarEtiqueta->imprimirExpedicaoModelo3($fields, false);
                    break;
            }

        }
    }

    public function informaQtdMapaAction()
    {
        $idVolume = $this->_getParam('idVolume');
        $idMapa = $this->_getParam('idMapa');
        $codBarras = $this->_getParam('codBarras');
        $qtd = $this->_getParam('qtd');
        $idExpedicao = $this->_getParam('idExpedicao');
        $codPessoa = $this->_getParam('cliente');

        $embalagens = $this->getEntityManager()->getRepository("wms:Produto\Embalagem")->findBy(array('codigoBarras'=>$codBarras));
        $embalagemEntity = $embalagens[0];
        $this->view->codProduto = $embalagemEntity->getProduto()->getId();
        $this->view->grade = $embalagemEntity->getProduto()->getGrade();
        $this->view->descricao = $embalagemEntity->getProduto()->getDescricao();
        $this->view->embalagem = $embalagemEntity->getDescricao() . "(" . $embalagemEntity->getQuantidade() . ")";
        $this->view->fator = $embalagemEntity->getQuantidade();
        $this->view->idVolume = $idVolume;
        $this->view->codBarras = $codBarras;
        $this->view->idMapa = $idMapa;
        $this->view->idExpedicao = $idExpedicao;
        $this->view->idPessoa = $codPessoa;
        $this->view->urlVoltar = '/mobile/expedicao/ler-produto-mapa/idMapa/'.$idMapa.'/idExpedicao/'.$idExpedicao.'/cliente/'.$codPessoa;

        if (isset($qtd) && ($qtd > 0)) {
            try {
                $volumePatrimonioRepo  = $this->getEntityManager()->getRepository('wms:Expedicao\VolumePatrimonio');
                /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
                $mapaSeparacaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao');
                /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
                $produtoRepo = $this->getEntityManager()->getRepository('wms:Produto');

                $embalagens = $produtoRepo->getEmbalagensByCodBarras($codBarras);
                $embalagemEn = $embalagens['embalagem'];
                $volumeEn = $embalagens['volume'];

                $volumePatrimonioEn = null;
                if ((isset($idVolume)) && ($idVolume != null)) {
                    $volumePatrimonioEn = $volumePatrimonioRepo->find($idVolume);
                }
                $mapaEn = $mapaSeparacaoRepo->find($idMapa);

                $mapaSeparacaoRepo->adicionaQtdConferidaMapa($embalagemEn,$volumeEn,$mapaEn,$volumePatrimonioEn,$qtd,$codPessoa);
                $listaQtdProdutosConferidos = $mapaSeparacaoRepo->verificaConferenciaProduto($idMapa,$embalagemEn,$volumeEn);
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

                $this->addFlashMessage('info','Produto conferido com sucesso');

                if ($todosProdutosConferidos == true)
                    $this->addFlashMessage('success', 'Todos os Produtos ' . $embalagemEn->getProduto()->getId() .' - '. $embalagemEn->getProduto()->getGrade() . ' foram conferidos com sucesso!');

                if ($todoMapaConferido == true)
                    $this->addFlashMessage('success', 'Todo o Mapa foi conferido com sucesso!');

                $this->_redirect('mobile/expedicao/ler-produto-mapa/idMapa/' . $idMapa . "/idExpedicao/". $idExpedicao . "/idVolume/" . $idVolume . '/cliente/' . $codPessoa);

            } catch (\Exception $e) {
                $this->addFlashMessage('error',$e->getMessage());
            }

        } else {
            $this->addFlashMessage('info','Informe uma Quantidade');
        }
    }

    public function tipoConferenciaAction()
    {
        $idExpedicao = $this->_getParam('idExpedicao',null);
        $placa = $this->_getParam('placa',null);
        if ($placa != null) {
            $url = '/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/'.$idExpedicao.'/placa/'.$placa;
            $urlNEmbalado = '/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/tipo-conferencia/naoembalado/placa/'.$placa;
            $urlEmbalado = '/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/'.$idExpedicao;
        } else {
            $urlNEmbalado = '/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/tipo-conferencia/naoembalado';
            $url = '/mobile/volume-patrimonio/ler-codigo-barra-volume/idExpedicao/'.$idExpedicao;
            $urlEmbalado = '/mobile/volume-patrimonio/carrega-tipo/idExpedicao/'.$idExpedicao;
        }
        $menu = array(
            1 => array(
                'url' => $url.'/box/1',
                'label' => 'CONF. VOLUME',
            ),
            2 => array(
                'url' => $urlNEmbalado,
                'label' => 'CONF. NÃO EMBALADO',
            ),
            3 => array (
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

    public function finalizarAction()
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo  = $this->em->getRepository('wms:Expedicao');
        $sessao = new \Zend_Session_Namespace('coletor');
        $request = $this->getRequest();
        $idExpedicao      = $request->getParam('idExpedicao');
        $idMapa           = $request->getParam('idMapa');
        $central          = $sessao->centralSelecionada;
        $mapa             = $request->getParam('mapa', "N");

        $modeloSeparacaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\ModeloSeparacao");

        $idModeloSeparacao = $this->getSystemParameterValue('MODELO_SEPARACAO_PADRAO');
        $modeloSeparacaoEn = $modeloSeparacaoRepo->find($idModeloSeparacao);
        $quebraColetor = $modeloSeparacaoEn->getUtilizaQuebraColetor();

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoRepository $mapaSeparacaoRepo */
        $mapaSeparacaoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\MapaSeparacao");

        $todosMapasConferidos = true;
        if ($quebraColetor == 'S') {
            $resultMapa = $mapaSeparacaoRepo->conferenciaMapa($idMapa);
            if ($resultMapa === true) {
                $this->addFlashMessage('success', 'Mapa de Separação Finalizado com sucesso!');
                $mapasExpedicao = $mapaSeparacaoRepo->findBy(array('codExpedicao' => $idExpedicao));
                foreach ($mapasExpedicao as $mapaExpedicao) {
                    $statusMapa = $mapaExpedicao->getCodStatus();
                    if (isset($statusMapa) && !empty($statusMapa)) {
                        if ($statusMapa != EtiquetaSeparacao::STATUS_CONFERIDO) {
                            $todosMapasConferidos = false;
                            break;
                        }
                    }
                }
            }
        }

        if ($todosMapasConferidos === true) {
            $result = $ExpedicaoRepo->finalizarExpedicao($idExpedicao, $central, true, 'C', $idMapa);
            if (is_string($result)) {
                $this->addFlashMessage('error', $result);
                if ($mapa == 'S') {
                    $this->_redirect("mobile/expedicao/index/idCentral/$central");
                }
                $this->redirect('conferencia-expedicao', 'ordem-servico','mobile', array('idCentral' => $central));
            } else if ($result==0) {
                $this->addFlashMessage('success', 'Primeira Conferência finalizada com sucesso');
            } else {
                $this->addFlashMessage('success', 'Conferência finalizada com sucesso');
            }

            if ($this->getSystemParameterValue('VINCULA_EQUIPE_CARREGAMENTO') == 'S') {
                $this->redirect('carregamento', 'expedicao','mobile', array('idExpedicao' => $idExpedicao));
            }
        }

        $this->_redirect('mobile/expedicao/index/idCentral/'.$central);

    }

    public function selecionaPlacaAction()
    {
        $idExpedicao    = $this->getRequest()->getParam('idExpedicao');
        $sessaoColetor = new \Zend_Session_Namespace('coletor');
        $sessaoColetor->parcialmenteFinalizado = true;

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo  = $this->em->getRepository('wms:Expedicao');
        $placas = $expedicaoRepo->getPlacasByExpedicaoCentral($idExpedicao);

        $this->view->placas = $placas;
    }

    protected function validacaoEtiqueta($codigoBarras)
    {
        $this->bloquearOs();
        $idExpedicao        = $this->getRequest()->getParam('idExpedicao');
        $placa              = $this->getRequest()->getParam('placa', null);
        $tipoConferencia    = $this->getRequest()->getParam('tipo-conferencia', null);
        $idTipoVolume       = $this->getRequest()->getParam('idTipoVolume', null);
        $volume             = $this->getRequest()->getParam('volume', null);
        $sessao             = new \Zend_Session_Namespace('coletor');
        $idCentral          = $sessao->centralSelecionada;

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo  = $this->em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $etiqueta = $etiquetaRepo->getEtiquetaByExpedicaoAndId($codigoBarras);
        if (count($etiqueta) == 0) {

            $msg = 'Etiqueta '.$codigoBarras.' não encontrada';
            $this->gravaAndamentoExpedicao($msg,$idExpedicao,$codigoBarras, null);

            if ($this->bloquearOs=='S') {
                $this->bloqueioOs($idExpedicao, $msg, false);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml("error",$msg,$this->createUrlMobile());
                } else {
                    $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
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
                $msg='Etiqueta de reentrega'.$codigoBarras.' pertence a expedicao ' . $etiqueta[0]['codExpedicao'];
                $this->gravaAndamentoExpedicao($msg,$idExpedicao,$codigoBarras, null);

                if ($this->bloquearOs=='S') {
                    $this->bloqueioOs($idExpedicao, $msg, false);
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
            $msg='Etiqueta '.$codigoBarras.' pertence a expedicao ' . $etiqueta[0]['codExpedicao'];
            $this->gravaAndamentoExpedicao($msg,$idExpedicao,$codigoBarras,null);

            if ($this->bloquearOs=='S') {
                $this->bloqueioOs($idExpedicao, $msg, false);

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
                $msg='Produtos embalados devem ser vinculados a um patrimônio';
                $this->gravaAndamentoExpedicao($msg,$idExpedicao,$codigoBarras,null);
                if ($this->bloquearOs=='S'){
                    $this->createXml('error',$msg);
                } else {
                    $this->createXml("error",$msg);
                    die();
                }
            }

        //Verifico se a etiqueta pertence a carga selecionada
        if  (!is_null($idTipoVolume) && !empty($idTipoVolume)) {
            if ($idTipoVolume != $etiqueta[0]['codCargaExterno']) {
                $msg='Etiqueta '.$codigoBarras.' não pertence a carga selecionada - Carga Correta:' . $etiqueta[0]['codCargaExterno'];
                $this->gravaAndamentoExpedicao($msg,$idExpedicao,$codigoBarras,null);

                if ($this->bloquearOs=='S'){
                    $this->bloqueioOs($idExpedicao, $msg, false);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml('error', $msg, $this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                }  else {
                    $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                    die();
                }
                return false;
            }
        }

        //Etiqueta pertence a central Selecionada e a placa selecionada
        if  (!is_null($placa) && !empty($placa)) {
            if ($etiqueta[0]['pontoTransbordo'] != $idCentral) {
                $msg='Etiqueta não pertence a central ' . $idCentral;
                $this->gravaAndamentoExpedicao($msg,$idExpedicao,$codigoBarras,null);

                if ($this->bloquearOs=='S'){
                    $this->bloqueioOs($idExpedicao, $msg, false);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml('error', $msg, $this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                }  else {
                    $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                    die();
                }
                return false;
            }
            if ($etiqueta[0]['placaCarga'] != $placa) {
                $msg = 'Etiqueta não pertence a placa ' . $placa;

                $this->gravaAndamentoExpedicao($msg,$idExpedicao,$codigoBarras,null);
                $this->bloqueioOs($idExpedicao, $msg, false);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('error', 'Etiqueta não pertence a placa ' . $placa, $this->createUrlMobile());
                } else {
                    $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                    die();
                }
                return false;
            }
        } else {
            if ($etiqueta[0]['codEstoque'] != $idCentral) {
                $this->gravaAndamentoExpedicao($msg,$idExpedicao,$codigoBarras,null);
                $this->bloqueioOs($idExpedicao, 'Etiqueta não pertence a central ' . $idCentral, false);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('error', 'Etiqueta não pertence a central ' . $idCentral, $this->createUrlMobile());
                } else {
                    $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                    die();
                }
                return false;
            }
        }

        return $etiqueta;
    }

    public function validaStatusReentrega ($etiqueta) {

        $etiquetaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaSeparacao");
        $nfSaidaPedidoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\NotaFiscalSaidaPedido");
        $esReentregaRepo = $this->getEntityManager()->getRepository("wms:Expedicao\EtiquetaSeparacaoReentrega");

        if ($this->getSystemParameterValue('CONFERE_RECEBIMENTO_REENTREGA') == 'S'){
            $etiquetaEn = $etiquetaRepo->findOneBy(array('id'=>$etiqueta[0]['codBarras']));
            $pedido = $etiquetaEn->getPedido();
            $nfSaidaPedidoPedido = $nfSaidaPedidoRepo->findBy(array('codPedido'=>$pedido->getId()));
            foreach ($nfSaidaPedidoPedido as $nfSaida) {
                $nfSaidaEn= $nfSaida->getNotaFiscalSaida();
                $statusNf =$nfSaidaEn->getStatus()->getId();
                if ($statusNf != Expedicao\NotaFiscalSaida::DEVOLVIDO_PARA_REENTREGA) {
                    return array('result'=>false,'msg'=> "Nota Fiscal de reentrega" . $nfSaidaEn->getNumeroNf() . "/". $nfSaidaEn->getSerieNf() . " ainda não foi recebida");
                }
            }
        }

        $esReentregaEn = $esReentregaRepo->findOneBy(array('codEtiquetaSeparacao'=>$etiqueta[0]['codBarras'],
                                                           'codReentrega'=>$etiqueta[0]['codReentrega']));

        if ($esReentregaEn->getCodStatus() != EtiquetaSeparacao::STATUS_PENDENTE_REENTREGA ) {
            return array('result'=>false,'msg'=> "Etiqueta de Separação de Reentrega" . $etiqueta[0]['codBarras'] . " já foi conferida");
        }

        $siglaEn = $this->getEntityManager()->getRepository('wms:Util\Sigla')->findOneBy(array('id'=>EtiquetaSeparacao::STATUS_CONFERIDO));

        $esReentregaEn->setStatus($siglaEn);
        $esReentregaEn->setCodStatus($siglaEn->getId());
        $this->getEntityManager()->persist($esReentregaEn);
        $this->getEntityManager()->flush();

        return array('result'=>true,'msg'=> "Etiqueta de Conferida com sucesso");
    }

    public function validaStatusEtiqueta($idExpedicao, $status, $sessaoColetor,$etiqueta=null)
    {
        $this->bloquearOs();
        $tipoConferencia    = $this->getRequest()->getParam('tipo-conferencia', null);
        $idTipoVolume       = $this->getRequest()->getParam('idTipoVolume', null);


        $obrigaRealizarRecebimento = $sessaoColetor->ObrigaBiparEtiquetaProduto;
        $placa = $this->getRequest()->getParam('placa',null);

        switch ($status)
        {
            case EtiquetaSeparacao::STATUS_PENDENTE_IMPRESSAO:
            case EtiquetaSeparacao::STATUS_PENDENTE_CORTE:
            case EtiquetaSeparacao::STATUS_CORTADO:
            case EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO;
                return false;
                break;
            case EtiquetaSeparacao::STATUS_CONFERIDO:
                if ($sessaoColetor->parcialmenteFinalizado == false) {

                    $verificaReconferencia = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'RECONFERENCIA_EXPEDICAO'))->getValor();

                    if ($verificaReconferencia=='S'){
                        $expedEntity=$this->_em->getReference('wms:Expedicao',$idExpedicao);
                        $statusExped=$expedEntity->getStatus()->getId();

                        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaConferenciaRepository $etiquetaConfRepo */
                        $etiquetaConfRepo  = $this->em->getRepository('wms:Expedicao\EtiquetaConferencia');

                        if ( $statusExped==Expedicao::STATUS_PRIMEIRA_CONFERENCIA){

                            $resultado=$etiquetaConfRepo->getEtiquetaByCodBarras($idExpedicao,$etiqueta);

                            if ($resultado[0]['codStatus']==Expedicao::STATUS_PRIMEIRA_CONFERENCIA)
                                return false;
                        } else if ( $statusExped==Expedicao::STATUS_SEGUNDA_CONFERENCIA){

                            $resultado=$etiquetaConfRepo->getEtiquetaByCodBarras($idExpedicao,$etiqueta);

                            if ($resultado[0]['codStatus']==Expedicao::STATUS_SEGUNDA_CONFERENCIA)
                                return false;
                        }

                    } else {
                        return false;
                    }
                } else {
                    if ($obrigaRealizarRecebimento == 'S') {
                        $msg = 'Recebimento de transbordo da expedição ' . $idExpedicao . ' não concluido';
                        $this->gravaAndamentoExpedicao($msg,$idExpedicao, $etiqueta , null);
                        $this->bloqueioOs($idExpedicao, 'Recebimento de transbordo da expedição ' . $idExpedicao . ' não concluido', false);
                        if ($this->_request->isXmlHttpRequest()) {
                            $this->createXml('error', 'Recebimento de transbordo da expedição ' . $idExpedicao . ' não concluido', $this->createUrlMobile());
                        } else {
                            $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
                            die();

                        }
                        return false;
                    }
                }
                break;
        }
        return true;
    }

    public function extraiCodigoBarras($etiquetas)
    {
        $codBarras = "";
        foreach ($etiquetas as $etiqueta) {
            $codBarras = $codBarras . '-' . $etiqueta['codBarrasProduto'];
        }
        return $codBarras;
    }

    public function geraArrayCodigoBarras ($value)
    {
        $result = explode('-', $value);
        unset($result[0]);
        return $result;
    }

    protected function bloqueioOs($idExpedicao, $motivo, $render = true)
    {
        $this->bloquearOs();
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo  = $this->em->getRepository('wms:Expedicao');
        $osEntity = $expedicaoRepo->verificaOSUsuario($idExpedicao);
        $osEntity[0]->setBloqueio($motivo);
        $this->_em->persist($osEntity[0]);
        $this->_em->flush();

        //$this->gravaAndamentoExpedicao($motivo,$idExpedicao);
        $this->_helper->messenger('error', $motivo);

        if ($render == true) {
            $form = new SenhaLiberacao();
            $form->setDefault('idExpedicao', $idExpedicao);
            $this->view->form = $form;
            $this->render('bloqueio');
        }
    }

    protected function gravaAndamentoExpedicao ($motivo, $idExpedicao, $codEtiquetaSeparacao = null, $codBarrasProduto = null)
    {
        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
        $andamentoRepo->save($motivo, $idExpedicao, false, true, $codEtiquetaSeparacao, $codBarrasProduto);
    }

    protected function desbloqueioOs($idExpedicao, $motivo)
    {
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo  = $this->em->getRepository('wms:Expedicao');
        $osEntity = $expedicaoRepo->verificaOSUsuario($idExpedicao);
        $osEntity[0]->setBloqueio(NULL);
        $this->_em->persist($osEntity[0]);
        $this->_em->flush();

        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');

        $andamentoRepo->save($motivo, $idExpedicao);
        $this->_helper->messenger('success', $motivo);
    }


    public function liberarOsAction()
    {
        $request     = $this->getRequest();
        $idExpedicao = $request->getParam('idExpedicao');
        $placa = $this->getRequest()->getParam('placa', null);
        $volume = $this->getRequest()->getParam('volume', null);
        $tipoConferencia = $this->getRequest()->getParam('tipo-conferencia', null);
        $idTipoVolume = $this->getRequest()->getParam('idTipoVolume', null);

        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
        $EtiquetaRepo   = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
        if ($request->isPost()) {
            $senhaDigitada    = $request->getParam('senha');

            if ($EtiquetaRepo->checkAutorizacao($senhaDigitada)) {
                $this->desbloqueioOs($idExpedicao, 'Ordem de serviço liberada');
                $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa, 'tipo-conferencia' => $tipoConferencia, 'volume' => $volume, 'idTipoVolume' => $idTipoVolume));
            } else {
                $this->addFlashMessage('error', 'Senha informada não é válida');
            }
        }

        $form = new SenhaLiberacao();
        $form->setDefault('idExpedicao', $idExpedicao);
        $this->view->form = $form;
        $this->render('bloqueio');

    }

    public function confirmaConferenciaAction()
    {
        $idExpedicao    = $this->getRequest()->getParam('idExpedicao');
        $idEtiqueta     = $this->getRequest()->getParam('idEtiqueta');
        $produto        = $this->getRequest()->getParam('produto');
        $placa = $this->getRequest()->getParam('placa',null);

        $this->confereEtiqueta($idEtiqueta);

        /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $andamentoRepo */
        $andamentoRepo  = $this->_em->getRepository('wms:Expedicao\Andamento');
        $andamentoRepo->save('Botão confirmar conferência '.$produto, $idExpedicao);

        $this->addFlashMessage('success', 'Produto conferido com sucesso');
        $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
    }

    /**
     * @param $idEtiqueta
     */
    protected function confereEtiqueta($idEtiqueta, $codStatus , $volume = null,$idExpedicao=null)
    {
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
                $expedEntity = $this->_em->getReference('wms:Expedicao',$idExpedicao);
                $statusExped = $expedEntity->getStatus()->getId();

                if ($statusExped == Expedicao::STATUS_PRIMEIRA_CONFERENCIA ){
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

    public function buscarEtiquetasAction()
    {
        $this->bloquearOs();
        $idTipoVolume       = $this->getRequest()->getParam('idTipoVolume', null);

        $sessaoColetor = new \Zend_Session_Namespace('coletor');
        $idExpedicao = $this->getRequest()->getParam('idExpedicao');
        $etiquetaSeparacao = $this->getRequest()->getParam('etiquetaSeparacao');
        $LeituraColetor = new LeituraColetor();
        $etiquetaSeparacao = $LeituraColetor->retiraDigitoIdentificador($etiquetaSeparacao);
        $placa = $this->getRequest()->getParam('placa',null);
        $tipoConferencia = $this->getRequest()->getParam('tipo-conferencia', null);
        $volume = $this->getRequest()->getParam('volume', null);

        $etiqueta = $this->validacaoEtiqueta($etiquetaSeparacao);

        if ($etiqueta == false) {
            $msg = "";
            if ($this->bloquearOs=='S'){
                return false;
            }
        }


//        VERIFICA SE O PRODUTO PERTENCE A ETIQUETA CORRETA
        $etiquetaProduto = $this->getRequest()->getParam('etiquetaProduto');
        if (isset($etiquetaProduto)) {
            $arraycodBarrasProduto = $this->geraArrayCodigoBarras($this->extraiCodigoBarras($etiqueta));
            $etiquetaProduto   = $LeituraColetor->adequaCodigoBarras($etiquetaProduto, true);

            if (!in_array($etiquetaProduto, $arraycodBarrasProduto)) {
                $msg='Produto '. $etiqueta[0]['codProduto'] . ' - ' . $etiqueta[0]['produto'] . ' - ' . $etiqueta[0]['grade'] .' ref. Etq. Sep. ' . $etiquetaSeparacao . ' não confere com a etiqueta do fabricante ' . $etiquetaProduto;
                $this->gravaAndamentoExpedicao($msg,$idExpedicao, $etiquetaSeparacao, $etiquetaProduto);
                //$this->_helper->messenger('info', $msg);

                if ($this->bloquearOs=='S'){
                    $this->bloqueioOs($idExpedicao, $msg, false);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml("error",$msg,$this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                } else {
                    $this->createXml("error",$msg);
                    die();
                }
                return false;
            }
        }


        if ($etiqueta[0]['reentregaExpedicao'] == null) {
            $return = $this->validaStatusEtiqueta ($idExpedicao, $etiqueta[0]['codStatus'], $sessaoColetor, $etiquetaSeparacao);
        } else {
            $return = $this->validaStatusReentrega($etiqueta);
            if ($return['result'] == false) {
                $msg= $return['msg'];
                $this->gravaAndamentoExpedicao($msg,$idExpedicao, $etiquetaSeparacao,null);
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml("error", $msg);
                } else {
                    $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
                return false;
            } else {
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('success', 'Etiqueta conferida com sucesso');
                } else {
                    $this->addFlashMessage('success', 'Etiqueta conferida com sucesso');
                    $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
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

            $msg=$mensagem;
            $this->gravaAndamentoExpedicao($msg,$idExpedicao, $etiquetaSeparacao,null);
            if ($this->_request->isXmlHttpRequest()) {
                $this->createXml("error", $msg);
            } else {
                $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
            }

            return false;
        }

        if ($sessaoColetor->parcialmenteFinalizado == true) {
            $obrigaBiparEtiqueta = $sessaoColetor->RecebimentoTransbordoObrigatorio;
            if ($obrigaBiparEtiqueta == 'N') {
                $this->confereEtiqueta($etiquetaSeparacao,$etiqueta[0]['codStatus'], $volume, $idExpedicao);
                $this->addFlashMessage('success', 'Produto conferido com sucesso');
                if ($this->_request->isXmlHttpRequest()) {
                    $this->createXml('success', 'Produto conferido com sucesso');
                } else {
                    $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                }
            }
        }

        $etiquetaProduto = $this->getRequest()->getParam('etiquetaProduto');
        if (isset($etiquetaProduto)) {
            $arraycodBarrasProduto = $this->geraArrayCodigoBarras($this->extraiCodigoBarras($etiqueta));
            $etiquetaProduto   = $LeituraColetor->adequaCodigoBarras($etiquetaProduto, true);

            if (!in_array($etiquetaProduto, $arraycodBarrasProduto)) {
                $msg='Produto '. $etiqueta[0]['codProduto'] . ' - ' . $etiqueta[0]['produto'] . ' - ' . $etiqueta[0]['grade'] .' ref. Etq. Sep. ' . $etiquetaSeparacao . ' não confere com a etiqueta do fabricante ' . $etiquetaProduto;
                $this->gravaAndamentoExpedicao($msg,$idExpedicao, $etiquetaSeparacao, $etiquetaProduto);
                //$this->_helper->messenger('info', $msg);

                if ($this->bloquearOs=='S'){
                    $this->bloqueioOs($idExpedicao, $msg, false);
                    if ($this->_request->isXmlHttpRequest()) {
                        $this->createXml("error",$msg,$this->createUrlMobile());
                    } else {
                        $this->redirect('liberar-os', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
                        die();
                    }
                } else {
                    $this->createXml("error",$msg);
                    die();
                }
                return false;
            }
        }

        if (($etiqueta[0]['embalado'] == 'S') && (is_null($volume))) {
            $msg = "A etiqueta " . $etiquetaSeparacao . " precisa de um volume informado pois é Embalado";
            $this->gravaAndamentoExpedicao($msg,$idExpedicao, $etiquetaSeparacao, null);
            $this->createXml("error",$msg,'/mobile/expedicao/ler-codigo-barras/idExpedicao/'.$idExpedicao.'/placa/'.$placa.'/bloqueiaOS/1/tipo-conferencia/'.$tipoConferencia.'/idTipoVolume/'.$idTipoVolume."/msg/".$msg);
        }

        $this->confereEtiqueta($etiquetaSeparacao,$etiqueta[0]['codStatus'], $volume, $idExpedicao);

        if ($this->_request->isXmlHttpRequest()) {
            $this->createXml('success', 'Etiqueta conferida com sucesso');
        } else {

            $this->addFlashMessage('success', 'Etiqueta conferida com sucesso');
            $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $idExpedicao, 'placa' => $placa));
        }

    }

    public function lerCodigoBarrasAction()
    {
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
            }else {
                $this->view->segundaConferencia = "N";
            }

            $url="/volume".$this->_getParam('volume', null)."/volume".$this->_getParam('volume', null)."/placa".$this->_getParam('placa', null)."/bloqueiaOS".$this->_getParam('bloqueiaOS', null);

            if ( ($Expedicao->validacaoExpedicao() == false) || ( $Expedicao->osLiberada() == false)) {
                $this->mensagemColetor($Expedicao,$url);
            }

            if ($Expedicao->possuiEmbalado() == true) {
                $this->_forward('tipo-conferencia','expedicao','mobile', array('placa' => $Expedicao->getPlaca()));
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
    public function mensagemColetor($Expedicao)
    {
        $this->_helper->messenger($Expedicao->getStatus(), $Expedicao->getMessage());
        if ($this->_request->isXmlHttpRequest()) {
            $this->createXml($Expedicao->getRetorno(), $Expedicao->getMessage(), $Expedicao->getRedirect());
        } else {
            $this->_redirect($Expedicao->getRedirect());
        }
    }

    public function finalizadoAction()
    {
        $idExpedicao = $this->_getParam('idExpedicao');
        $placa      = $this->_getParam('placa');

        $sessaoColetor = new \Zend_Session_Namespace('coletor');
        $obrigaBiparEtiqueta = $sessaoColetor->RecebimentoTransbordoObrigatorio;
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');

        if ($obrigaBiparEtiqueta == 'S') {
            $conferido = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao,EtiquetaSeparacao::STATUS_CONFERIDO, "Array", $placa);
            if (count($conferido) > 0) {
                $result = $conferido;
            } else {
                $result = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao,EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO, "Array", $placa);
            }
        } else {
            $result = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao,EtiquetaSeparacao::STATUS_CONFERIDO, "Array", $placa);
        }

        if (count($result) > 0) {
            $this->createXml('error', 'Faltam '.count($result).' produtos a serem conferidos');
        } else {
            $this->createXml('success','Todos os produtos já foram recebidos');
        }
    }

    public function setIdCentral($idCentral = null)
    {
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

    public function bloquearOs()
    {
        $this->bloquearOs = $this->getSystemParameterValue('BLOQUEIO_OS');

        return $this->bloquearOs;
    }

    public function expedicaoCarregamentoAction()
    {

    }

    public function carregamentoAction()
    {
        //OBTER OS PARAMETROS
        $leituraColetor          = new LeituraColetor();
        $operadores              = $this->_getParam('mass-id');
        $this->view->idExpedicao = $idExpedicao = $this->_getParam('idExpedicao');
        $this->view->operacao    = $this->_getParam('operacao');

        //OBTER OS REPOSITORIOS
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo           = $this->em->getRepository('wms:Expedicao');
        /** @var \Wms\Domain\Entity\UsuarioRepository $UsuarioRepo */
        $UsuarioRepo             = $this->_em->getRepository('wms:Usuario');
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo            = $this->em->getRepository('wms:Expedicao\EtiquetaSeparacao');

        $codBarras               = $leituraColetor->retiraDigitoIdentificador($this->_getParam('codBarras'));
        $etiquetaEn              = $etiquetaRepo->findOneBy(array('id' => $codBarras));
        $entityExpedicao         = $expedicaoRepo->findOneBy(array('id' => $idExpedicao));

        if (!$entityExpedicao) {
            $this->addFlashMessage('error', 'Expedição não encontrada!');
            $this->redirect('expedicao-carregamento', 'expedicao', 'mobile');
        }
        $placa = null;

        if (!$etiquetaEn) {
            $this->view->operadores     = $UsuarioRepo->getUsuarioByPerfil(0, $this->getSystemParameterValue("PERFIL_EQUIPE_CARREGAMENTO"));
            /** @var \Wms\Domain\Entity\Expedicao\EquipeCarregamentoRepository $carregamentoRepo */
            $this->view->equipe         = $equipe = $this->em->getRepository('wms:Expedicao\EquipeCarregamento');
        } else {
            //VERIFICA QUAL O STATUS DA ETIQUETA E EXIBE A EQUIPE CORRETA
            switch ($etiquetaEn->getStatus()->getId()) {
                case EtiquetaSeparacao::STATUS_RECEBIDO_TRANSBORDO;
                    $this->view->operadores     = $UsuarioRepo->getUsuarioByPerfil(0, $this->getSystemParameterValue("PERFIL_EQUIPE_RECEBIMENTO_TRANSBORDO"));
                    /** @var \Wms\Domain\Entity\Recebimento\EquipeRecebimentoTransbordoRepository $equipeRecebTransbRepo */
                    $this->view->equipe         = $equipe = $this->em->getRepository('wms:Recebimento\EquipeRecebimentoTransbordo');
                    break;
                case EtiquetaSeparacao::STATUS_EXPEDIDO_TRANSBORDO;
                    $this->view->operadores     = $UsuarioRepo->getUsuarioByPerfil(0, $this->getSystemParameterValue("PERFIL_EQUIPE_EXPEDICAO_TRANSBORDO"));
                    /** @var \Wms\Domain\Entity\Expedicao\EquipeExpedicaoTransbordoRepository $equipe */
                    $this->view->equipe         = $equipe = $this->em->getRepository("wms:Expedicao\EquipeExpedicaoTransbordo");
                    $placa                      = str_replace('-','',$this->_getParam('placa'));
                    break;
                default:
                    $this->view->operadores     = $UsuarioRepo->getUsuarioByPerfil(0, $this->getSystemParameterValue("PERFIL_EQUIPE_CARREGAMENTO"));
                    /** @var \Wms\Domain\Entity\Expedicao\EquipeCarregamentoRepository $carregamentoRepo */
                    $this->view->equipe         = $equipe = $this->em->getRepository('wms:Expedicao\EquipeCarregamento');
                    break;
            }
        }

        if ($operadores && $idExpedicao) {

            try {
                $equipe->vinculaOperadores($idExpedicao,$operadores,$placa);
                $this->_helper->messenger('success', 'Operadores vinculados a expedicao com sucesso');
                $this->_redirect('mobile');
            } catch(Exception $e) {
                $this->addFlashMessage('error', $e->getMessage());
            }
        }

        /** @var \Wms\Domain\Entity\UsuarioRepository $UsuarioRepo */
        $UsuarioRepo                = $this->_em->getRepository('wms:Usuario');
        $this->view->operadores     = $UsuarioRepo->getUsuarioByPerfil('AUXILIAR EXPEDICAO');
        $this->view->idExpedicao    = $idExpedicao;
    }

    public function bloquearEtiquetaInexistenteAjaxAction()
    {
        $this->view->form = $form = new \Wms\Module\Mobile\Form\BloquearEtiqueta();

        $values = $form->getParams();

        if ($values) {
            $params = $this->_getAllParams();
            if ($this->getSystemParameterValue('SENHA_FINALIZAR_EXPEDICAO') == $params['senha']) {
                $this->redirect('ler-codigo-barras', 'expedicao','mobile', array('idExpedicao' => $params['idExpedicao']));
            } else {
                $this->_helper->messenger('error', 'Senha Incorreta!');
                $this->redirect('bloquear-etiqueta-inexistente-ajax', 'expedicao','mobile', array('idExpedicao' => $params['idExpedicao']));
            }

        }

    }

    public function vincularLacreAction()
    {
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
                $expedicaoVolumePatrimonioEn = $expedicaoVolumePatrimonioRepo->findBy(array('volumePatrimonio' => $volumePatrimonioId), array('id' => 'DESC'),1);

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

}

