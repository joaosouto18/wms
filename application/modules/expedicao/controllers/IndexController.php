<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Grid\Expedicao as ExpedicaoGrid,
    Wms\Domain\Entity\Expedicao,
    Wms\Module\Web\Form\Subform\FiltroExpedicaoMercadoria,
    Wms\Module\Web\Grid\Expedicao\PesoCargas as PesoCargasGrid;

class Expedicao_IndexController  extends Action
{

    public function importAjaxAction()
    {
        //DIRETORIO DOS ARQUIVOS
        $dir = 'C:\desenvolvimento\wms\docs\importcsv';
        //LEITURA DE ARQUIVOS COMO ARRAY
        $files = scandir($dir);

        //LEITURA DE ARQUIVOS
        foreach ($files as $file) {
            $handle = $dir.'/\/'.$file;

            //DEFINIÇÃO DE ARQUIVO E METODO ADEQUADO PARA LEITURA DE DADOS
            switch ($file) {
                case 'expedicao.csv':
                    $this->importExpedicao($handle);
                    break;
                case 'fabricante.csv':
                    $this->importFabricante($handle);
                    break;
                case 'filial.csv':
//                    $this->importFilial($handle);
                    break;
                case 'fornecedor.csv':
                    $this->importFornecedor($handle);
                    break;
                case 'notaFiscal.csv':
                    $this->importNotaFiscal($handle);
                    break;
                case 'produto.csv':
                    $this->importProduto($handle);
                    break;
            }
        }
    }

    private function importNotaFiscal($handle)
    {
        $em = $this->getEntityManager();
        $importacao = new \Wms\Service\Importacao();

        $handle = fopen($handle, "r");
        $caracterQuebra = ';';

        try {
            $array = array();
            $count = 0;
            $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                $registro = array_combine($cabecalho, $data);
                $array['numeroNota'] = $registro['NUMERO_NOTA'];
                $array['serie'] = $registro['SERIE'];
                $array['dataEmissao'] = $registro['DATA_EMISSAO'];
                $array['placa'] = $registro['PLACA_VEICULO'];
                $array['codFornecedorExterno'] = $registro['COD_FORNECEDOR'];
                $array['itens'][$count]['idProduto'] = $registro['COD_PRODUTO'];
                $array['itens'][$count]['grade'] = $registro['GRADE'];
                $array['itens'][$count]['quantidade'] = $registro['QUANTIDADE'];
                $count++;
            }
            $importacao->saveNotaFiscal($em, $array['codFornecedorExterno'], $array['numeroNota'], $array['serie'], $array['dataEmissao'], $array['placa'], $array['itens'], 'N', null);
            fclose($handle);
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    private function importExpedicao($handle)
    {
        $em = $this->getEntityManager();
        $importacao = new \Wms\Service\Importacao();

        $handle = fopen($handle, "r");
        $caracterQuebra = ';';

        try {
            $array = array();
            $count = 0;
            $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {

                $registro = array_combine($cabecalho, $data);
                $array['data'] = $registro['DATA'];
                $array['codCliente'] = $registro['COD_CLIENTE'];
                $array['nomeCliente'] = $registro['NOME_CLIENTE'];
                $array['placaExpedicao'] = $registro['PLACA_VEICULO'];
                $array['placaCarga'] = $registro['PLACA_VEICULO'];
                $array['codCargaExterno'] = $registro['COD_CARGA'];
                $array['codTipoCarga'] = $registro['TIPO_CARGA'];
                $array['centralEntrega'] = $registro['CENTRAL_ENTREGA'];
                $array['codPedido'] = $registro['COD_PEDIDO'];
                $array['tipoPedido'] = $registro['TIPO_PEDIDO'];
                $array['linhaEntrega'] = $registro['LINHA_ENTREGA'];
                $array['itinerario'] = $registro['ITINERARIO'];
                $array['itens'][$count]['codProduto'] = $registro['COD_PRODUTO'];
                $array['itens'][$count]['grade'] = $registro['GRADE'];
                $array['itens'][$count]['quantidade'] = $registro['QUANTIDADE'];
                $count++;
            }
            $array['idExpedicao'] = $importacao->saveExpedicao($em, $array['placaExpedicao']);
            $array['carga'] = $importacao->saveCarga($em, $array);
            $array['pedido'] = $importacao->savePedido($em, $array);
            foreach ($array['itens'] as $item) {
                $item['pedido'] = $array['pedido'];
                $importacao->savePedidoProduto($em, $item);
            }

            fclose($handle);
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    private function importFabricante($handle)
    {
        $em = $this->getEntityManager();
        $importacao = new \Wms\Service\Importacao();

        $handle = fopen($handle, "r");
        $caracterQuebra = ';';

        try {
            $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                $registro = array_combine($cabecalho, $data);

                $idFabricante = $registro['COD_FABRICANTE'];
                $nome = $registro['FABRICANTE'];
                $importacao->saveFabricante($em, $idFabricante, $nome);
            }
            fclose($handle);
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    private function importFornecedor($handle)
    {
        $em = $this->getEntityManager();
        $fornecedorRepo = $em->getRepository('wms:Pessoa\Papel\Fornecedor');
        $ClienteRepo    = $em->getRepository('wms:Pessoa\Papel\Cliente');

        $handle = fopen($handle, "r");
        $caracterQuebra = ';';

        try {
            $em->beginTransaction();
            $array = array();
            $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                $registro = array_combine($cabecalho, $data);

                $array['codFornecedor'] = $registro['COD_FORNECEDOR'];
                $array['nome'] = $registro['NOME_FORNECEDOR'];
                $array['tipoPessoa'] = $registro['TIPO_PESSOA'];
                $array['cpf_cnpj'] = $registro['CPF_CNPJ'];
                $array['logradouro'] = $registro['RUA'];
                $array['numero'] = $registro['NUMERO'];
                $array['complemento'] = $registro['COMPLEMENTO'];
                $array['bairro'] = $registro['BAIRRO'];
                $array['cidade'] = $registro['CIDADE'];
                $array['uf'] = $registro['ESTADO'];
                $array['referencia'] = $registro['REFERENCIA'];
                $array['email'] = $registro['EMAIL'];
                $array['telefone'] = $registro['TELEFONE'];
                $array['observacao'] = $registro['OBSERVACAO'];

                $entityFornecedor = $fornecedorRepo->findOneBy(array('idExterno' => $array['codFornecedor']));
                if ($entityFornecedor == null) {
                    switch ($array['tipoPessoa']) {
                        case 'PJ':
                            $cliente['pessoa']['tipo'] = 'J';

                            $PessoaJuridicaRepo    = $em->getRepository('wms:Pessoa\Juridica');
                            $entityPessoa = $PessoaJuridicaRepo->findOneBy(array('cnpj' => str_replace(array(".", "-", "/"), "",$array['cpf_cnpj'])));
                            if ($entityPessoa) {
                                break;
                            }

                            $cliente['pessoa']['juridica']['dataAbertura'] = null;
                            $cliente['pessoa']['juridica']['cnpj'] = $array['cpf_cnpj'];
                            $cliente['pessoa']['juridica']['idTipoOrganizacao'] = null;
                            $cliente['pessoa']['juridica']['idRamoAtividade'] = null;
                            $cliente['pessoa']['juridica']['nome'] = $array['nome'];
                            break;
                        case 'PF':

                            $PessoaFisicaRepo    = $em->getRepository('wms:Pessoa\Fisica');
                            $entityPessoa       = $PessoaFisicaRepo->findOneBy(array('cpf' => str_replace(array(".", "-", "/"), "",$array['cpf_cnpj'])));
                            if ($entityPessoa) {
                                break;
                            }

                            $cliente['pessoa']['tipo']              = 'F';
                            $cliente['pessoa']['fisica']['cpf']     = $array['cpf_cnpj'];
                            $cliente['pessoa']['fisica']['nome']    = $array['nome'];
                            break;
                    }

                    $SiglaRepo      = $em->getRepository('wms:Util\Sigla');
                    $entitySigla    = $SiglaRepo->findOneBy(array('referencia' => $array['uf']));

                    $array['cep'] = (isset($array['cep']) && !empty($array['cep']) ? $array['cep'] : '');
                    $cliente['enderecos'][0]['acao'] = 'incluir';
                    $cliente['enderecos'][0]['idTipo'] = \Wms\Domain\Entity\Pessoa\Endereco\Tipo::ENTREGA;

                    if (isset($array['complemento']))
                        $cliente['enderecos'][0]['complemento'] = $array['complemento'];
                    if (isset($array['logradouro']))
                        $cliente['enderecos'][0]['descricao'] = $array['logradouro'];
                    if (isset($array['referencia']))
                        $cliente['enderecos'][0]['pontoReferencia'] = $array['referencia'];
                    if (isset($array['bairro']))
                        $cliente['enderecos'][0]['bairro'] = $array['bairro'];
                    if (isset($array['cidade']))
                        $cliente['enderecos'][0]['localidade'] = $array['cidade'];
                    if (isset($array['numero']))
                        $cliente['enderecos'][0]['numero'] = $array['numero'];
                    if (isset($array['cep']))
                        $cliente['enderecos'][0]['cep'] = $array['cep'];
                    if (isset($entitySigla))
                        $cliente['enderecos'][0]['idUf'] = $entitySigla->getId();

                    $fornecedor = new \Wms\Domain\Entity\Pessoa\Papel\Fornecedor();
                    if ($entityPessoa == null) {
                        $entityPessoa = $ClienteRepo->persistirAtor($fornecedor, $cliente, false);
                    } else {
                        $fornecedor->setPessoa($entityPessoa);
                    }
                    $fornecedor->setId($entityPessoa->getId());
                    $fornecedor->setIdExterno($array['codFornecedor']);

                    $em->persist($fornecedor);
                }
            }

            $em->flush();
            $em->commit();
            fclose($handle);
        } catch (\Exception $e) {
            $em->rollback();
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    private function importProduto($handle)
    {
        $em = $this->getEntityManager();

        $importacao = new \Wms\Service\Importacao();
        $handle = fopen($handle, "r");
        $caracterQuebra = ';';

        try {
            $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            $em->beginTransaction();
            $produtos = array();
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                $registro = array_combine($cabecalho, $data);

                $produtos['codProduto'] = $registro['COD_PRODUTO'];
                $produtos['descricao'] = $registro['DESCRICAO'];
                $produtos['grade'] = $registro['GRADE'];
                $produtos['referencia'] = $registro['REFERENCIA'];
                $produtos['tipoComercializacao'] = $registro['COD_TIPO_COMERCIALIZACAO'];
                $produtos['classe'] = $registro['COD_CLASSE'];
                $produtos['fabricante'] = $registro['NOME_FABRICANTE'];
                $produtos['linhaSeparacao'] = $registro['COD_LINHA_SEPARACAO'];
                $produtos['codBarras'] = $registro['COD_BARRAS'];
                $produtos['numVolumes'] = $registro['NUM_VOLUMES'];
                $produtos['diasVidaUtil'] = $registro['DIAS_VIDA_UTIL'];
                $produtos['validade'] = $registro['POSSUI_VALIDADE'];
                $produtos['enderecoReferencia'] = $registro['COD_ENDERECO_REF_END_AUTO'];
                $produtos['embalagens'][0]['descricaoEmbalagem'] = $registro['DESCRICAO_EMBALAGEM'];
                $produtos['embalagens'][0]['qtdEmbalagem'] = $registro['QTD_EMBALAGEM'];
                $produtos['embalagens'][0]['indPadrao'] = $registro['IND_PADRAO'];
                $produtos['embalagens'][0]['codigoBarras'] = $registro['CODIGO_BARRAS'];
                $produtos['embalagens'][0]['cbInterno'] = $registro['CB_INTERNO'];
                $produtos['embalagens'][0]['imprimirCb'] = $registro['IMPRIMIR_CB'];
                $produtos['embalagens'][0]['embalado'] = $registro['EMBALADO'];
                $produtos['embalagens'][0]['capacidadePicking'] = $registro['CAPACIDADE_PICKING'];
                $produtos['embalagens'][0]['pontoReposicao'] = 0;
                $produtos['embalagens'][0]['acao'] = 'incluir';

                $produtos['volumes'][0]['descricaoVolume'] = $registro['DESCRICAO_VOLUME'];
                $produtos['volumes'][0]['codigoBarras'] = $registro['CODIGO_BARRAS'];
                $produtos['volumes'][0]['sequenciaVolume'] = $registro['CODIGO_SEQUENCIAL_VOLUME'];
                $produtos['volumes'][0]['peso'] = $registro['PESO'];
                $produtos['volumes'][0]['normaPaletizacao'] = $registro['NORMA_PALETIZACAO'];
                $produtos['volumes'][0]['cbInterno'] = $registro['CB_INTERNO'];
                $produtos['volumes'][0]['imprimirCb'] = $registro['IMPRIMIR_CB'];
                $produtos['volumes'][0]['altura'] = $registro['ALTURA'];
                $produtos['volumes'][0]['largura'] = $registro['LARGURA'];
                $produtos['volumes'][0]['profundidade'] = $registro['PROFUNDIDADE'];
                $produtos['volumes'][0]['cubagem'] = $registro['CUBAGEM'];
                $produtos['volumes'][0]['capacidadePicking'] = $registro['CAPACIDADE_PICKING'];

                $importacao->saveProduto($em, $produtos);
            }
            $em->flush();
            $em->commit();
            fclose($handle);
        } catch (\Exception $e) {
            $em->rollback();
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    private function importFilial($handle)
    {
        $em = $this->getEntityManager();

        $importacao = new \Wms\Service\Importacao();
        $handle = fopen($handle, "r");
        $caracterQuebra = ';';

        try {
            $em->beginTransaction();
            $filial = array();
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                if ($data[0] == 'NOME FILIAL')
                    continue;

                $filial['nome'] = $data[0];
                $filial['ativo'] = $data[1];
                $filial['codExterno'] = $data[2];
                $filial['recebimentoTransbordo'] = $data[3];
                $filial['leituraEtiqueta'] = $data[4];
                $filial['utilizaRessuprimento'] = $data[5];

                $importacao->saveFilial($em, $filial);
            }
            $em->flush();
            $em->commit();
            fclose($handle);
        } catch (\Exception $e) {
            $em->rollback();
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    public function indexAction()
    {
        $form = new FiltroExpedicaoMercadoria();
        $this->view->form = $form;
        $params = $this->_getAllParams();

        $s1 = new Zend_Session_Namespace('sessionAction');
        $s1->setExpirationSeconds(900, 'action');
        $s1->action=$params;

        $s = new Zend_Session_Namespace('sessionUrl');
        $s->setExpirationSeconds(900, 'url');
        $s->url=$params;

        ini_set('max_execution_time', 3000);

        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        $dataI1 = new \DateTime;

        if ( !empty($params) ) {

            if ( !empty($params['idExpedicao']) ||  !empty($params['codCargaExterno']) ){
                $idExpedicao=null;
                $idCarga=null;

                if (!empty($params['idExpedicao']) )
                    $idExpedicao=$params['idExpedicao'];


                if (!empty($params['codCargaExterno']) )
                    $idCarga=$params['codCargaExterno'];

                $params=array();
                $params['idExpedicao']=$idExpedicao;
                $params['codCargaExterno']=$idCarga;
            } else {
                if ( empty($params['dataInicial1']) ){
                    $params['dataInicial1']=$dataI1->format('d/m/Y');
                }
            }
            if ( !empty($params['control']) )
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

        $form->populate($params);

        $Grid = new ExpedicaoGrid();
        $this->view->grid = $Grid->init($params)
            ->render();

        $this->view->refresh = true;
        ini_set('max_execution_time', 30);

    }

    public function agruparcargasAction()
    {
        $id = $this->_getParam('id');
        $this->view->id = $id;

        if ( $this->getRequest()->getParam('idExpedicaoNova')!='' ){
            try {
                $idNova = $this->getRequest()->getParam('idExpedicaoNova');

                if ($idNova == null)
                    throw new \Exception('Você precisa informar a nova Expedição');

                if ($this->getRequest()->isPost() ) {

                    $idAntiga = $this->getRequest()->getParam('idExpedicao');

                    $reservaEstoqueExpedicao = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoqueExpedicao")->findBy(array('expedicao'=>$idAntiga));
                    if (count($reservaEstoqueExpedicao) >0) {
                        throw new \Exception('Não é possivel agrupar essa expedição pois ela já possui reservas de Estoque');
                    }

                    /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
                    $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
                    /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $AndamentoRepo */
                    $AndamentoRepo   = $this->_em->getRepository('wms:Expedicao\Andamento');

                    $novaExpedicaoEn = $this->_em->getReference('wms:Expedicao', $idNova);
                    $antigaExpedicaoEn = $this->_em->getReference('wms:Expedicao', $idAntiga);

                    $cargas=$ExpedicaoRepo->getCargas($idAntiga);

                    foreach ($cargas as $c){
                        $codCarga=$c->getId();
                        $entityCarga = $this->_em->getReference('wms:Expedicao\Carga', $codCarga);
                        $entityCarga->setExpedicao($novaExpedicaoEn);
                        $this->_em->persist($entityCarga);
                        $AndamentoRepo->save("Carga ". $c->getCodCargaExterno(). " transferida pelo agrupamento de cargas", $idNova);
                    }
                    $this->_em->flush();
                    $this->_helper->messenger('success', 'Cargas migradas para a expedição '.$idNova.' com sucesso.');
                    return $this->redirect('index');
                }
            } catch (\Exception $e) {
                $this->_helper->messenger('error', $e->getMessage());
            }
        }
    }

    public function consultarpesoAction()
    {
        $id = $this->_getParam('id');

        $parametros['id']=$id;
        $parametros['agrup']='carga';

        $GridPeso = new PesoCargasGrid();
        $this->view->gridPeso = $GridPeso->init($parametros)
            ->render();

        $parametros['agrup']='expedicao';
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $pesos=$ExpedicaoRepo->getPesos($parametros);

        $this->view->totalExpedicao=$pesos;
    }

    public function desagruparcargaAction ()
    {
        $params = $this->_getAllParams();

        if (isset($params['placa']) && !empty($params['placa'])) {
            $idCarga = $this->_getParam('COD_CARGA');
            $placa = $params['placa'];

            /** @var \Wms\Domain\Entity\Expedicao\AndamentoRepository $AndamentoRepo */
            $AndamentoRepo   = $this->_em->getRepository('wms:Expedicao\Andamento');
            /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $EtiquetaRepo */
            $EtiquetaRepo      = $this->_em->getRepository('wms:Expedicao\EtiquetaSeparacao');
            /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
            $ExpedicaoRepo      = $this->_em->getRepository('wms:Expedicao');
            /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $CargaRepo */
            $CargaRepo      = $this->_em->getRepository('wms:Expedicao\Carga');

            try {
                /** @var \Wms\Domain\Entity\Expedicao\Carga $cargaEn */
                $cargaEn = $CargaRepo->findOneBy(array('id'=>$idCarga));

                /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $pedidoRepo */
                $pedidoRepo = $this->getEntityManager()->getRepository("wms:Expedicao\Pedido");
                $pedidos = $pedidoRepo->findBy(array('codCarga'=>$cargaEn->getId()));

                /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoPedidoRepository $ondaPedidoRepo */
                $ondaPedidoRepo = $this->getEntityManager()->getRepository('wms:Ressuprimento\OndaRessuprimentoPedido');
                foreach ($pedidos as $pedidoEn) {
                    $ondaPedidoEn = $ondaPedidoRepo->findBy(array('pedido' => $pedidoEn->getId()));

                    if ($pedidoEn->getIndEtiquetaMapaGerado() == 'S') {
                        throw new \Exception('Carga não pode ser desagrupada, existem etiquetas/Mapas gerados!');
                    } else if (count($ondaPedidoEn) > 0) {
                        throw new \Exception('Carga não pode ser desagrupada, existe ressuprimento gerado!');
                    }
                }

                $countCortadas = $EtiquetaRepo->countByStatus(Expedicao\EtiquetaSeparacao::STATUS_CORTADO, $cargaEn->getExpedicao() ,null,null,$idCarga);
                $countTotal = $EtiquetaRepo->countByStatus(null, $cargaEn->getExpedicao(),null,null,$idCarga);

                if ($countTotal != $countCortadas) {
                    throw new \Exception('A Carga '. $cargaEn->getCodCargaExterno(). ' possui etiquetas que não foram cortadas e não pode ser removida da expedição');
                }

                $cargas=$ExpedicaoRepo->getCargas($cargaEn->getCodExpedicao());
                if (count($cargas) <= 1) {
                    throw new \Exception('A Expedição não pode ficar sem cargas');
                }
                $AndamentoRepo->save("Carga " . $cargaEn->getCodCargaExterno() . " retirada da expedição atraves do desagrupamento de cargas", $cargaEn->getCodExpedicao());
                $expedicaoAntiga = $cargaEn->getCodExpedicao();
                $expedicaoEn = $ExpedicaoRepo->save($placa);
                $cargaEn->setExpedicao($expedicaoEn);
                $cargaEn->setSequencia(1);
                $cargaEn->setPlacaCarga($placa);
                $this->_em->persist($cargaEn);

                foreach ($pedidos as $pedido) {
                    $pedidoRepo->removeReservaEstoque($pedido->getId());
                }

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
            $this->redirect("index",'index','expedicao');
        } elseif (isset($params['salvar']) && empty($params['placa'])) {
            $this->_helper->messenger('error', 'É necessário digitar uma placa');
            $this->redirect("index",'index','expedicao');
        }
    }

    public function semEstoqueReportAction(){
        $idExpedicao = $this->_getParam('id');
        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $result = $ExpedicaoRepo->getProdutosSemEstoqueByExpedicao($idExpedicao);
        $this->exportPDF($result,'semEstoque.pdf','Produtos sem estoque na expedição','L');
    }

    public function imprimirAction(){
        $idExpedicao = $this->_getParam('id');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $result = $ExpedicaoRepo->getVolumesExpedicaoByExpedicao($idExpedicao);

        foreach ($result as $key => $resultado) {
            if ($key + 1 == count($result)) {
                $result[$key + 1]['VOLUME'] = null;
                $result[$key + 1]['DESCRIÇÃO'] = null;
                $result[$key + 1]['ITINERÁRIO'] = null;
                $result[$key + 1]['CLIENTE'] = 'TOTAL DE CAIXAS FECHADAS';
                $result[$key + 1]['QTD_CAIXA'] = $result[$key]['QTD_CAIXA'];
            }
            $result[$key]['QTD_CAIXA'] = null;
        }

        $this->exportPDF($result,'volume-patrimonio','Relatório de Volumes Patrimônio da Expedição '.$idExpedicao,'L');
    }

    public function declaracaoAjaxAction(){
        $idExpedicao = $this->_getParam('id');

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $ExpedicaoRepo */
        $ExpedicaoRepo   = $this->_em->getRepository('wms:Expedicao');
        $result = $ExpedicaoRepo->getVolumesExpedicaoByExpedicao($idExpedicao);

        $declaracaoReport = new \Wms\Module\Expedicao\Report\VolumePatrimonio();
        $declaracaoReport->imprimir($result);
    }

    public function equipeCarregamentoAction()
    {
        $form = new \Wms\Module\Expedicao\Form\EquipeCarregamento();
        $this->view->form = $form;

        $params = $this->_getAllParams();
        $grid = new \Wms\Module\Expedicao\Grid\EquipeCarregamento();
        $this->view->grid = $grid->init($params)
            ->render();
    }

    public function acertarReservaEstoqueAjaxAction()
    {
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
        var_dump('sucesso!');exit;
    }


}