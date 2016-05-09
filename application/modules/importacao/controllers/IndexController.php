<?php

/**
 * Created by PhpStorm.
 * User: Rodrigo
 * Date: 25/01/2016
 * Time: 09:28
 */

use Wms\Module\Web\Controller\Action;
use Wms\Module\Importacao\Form\Index as IndexForm;

class Importacao_IndexController extends Action
{
    private $progressBar = null;
    private $statusProgress = array(
        'tArquivo' => 0,
        'iArquivo' => 0,
        'tLinha' => 0,
        'iLinha' => 0,
        "error" => null,
        "exception" => null
    );

    public function custom_warning_handler($errno, $errstr) {
        $this->statusProgress["exception"] = $errstr;
        $this->progressBar->update(null, $this->statusProgress);
        $this->_helper->json(array('result' => $errstr));

    }
    public function iniciarAjaxAction()
    {
        try{
            set_error_handler(array($this,'custom_warning_handler'));
            ini_set('memory_limit', '-1');
            ini_set('max_execution_time', 3000);
            $em = $this->getEntityManager();

            $dir = $this->getSystemParameterValue("DIRETORIO_IMPORTACAO");
            $importacaoService = new \Wms\Service\Importacao();

            $produtoRepo    = $em->getRepository('wms:Produto');
            $enderecoRepo   = $em->getRepository("wms:Deposito\Endereco");
            $fabricanteRepo = $em->getRepository('wms:Fabricante');
            $classeRepo     = $em->getRepository('wms:Produto\Classe');
            $embalagemRepo  = $em->getRepository('wms:Produto\Embalagem');
            $camposRepo     = $em->getRepository('wms:Importacao\Campos');

            $repositorios = array('produtoRepo'    => $produtoRepo,
                                  'enderecoRepo'   => $enderecoRepo,
                                  'fabricanteRepo' => $fabricanteRepo,
                                  'classeRepo'     => $classeRepo,
                                  'embalagemRepo'  => $embalagemRepo,);

            $arquivos = $em->getRepository('wms:Importacao\Arquivo')->findBy(array('ativo'=>'S'),array('sequencia' => 'ASC'));
            $arrErros = array();
            $countFlush = 0;

            $config = array('updateMethodName' => 'Zend_ProgressBar_Update');
            $adapter = new Zend_ProgressBar_Adapter_JsPush($config);

            $this->statusProgress["tArquivo"] = count($arquivos);
            $this->progressBar = new Zend_ProgressBar($adapter, 0, $this->statusProgress["tArquivo"]);

            foreach ($arquivos as $key => $arquivo) {
                $this->statusProgress["iArquivo"] = $key + 1;

                $file = $arquivo->getNomeArquivo();
                $caracterQuebra = $arquivo->getCaracterQuebra();
                $cabecalho = $arquivo->getCabecalho();
                $tabelaDestino = $arquivo->getTabelaDestino();

                $archive = $dir . DIRECTORY_SEPARATOR . $file;
                $handle = fopen($archive, "r");
                $camposArquivo = $camposRepo->findBy(array('arquivo' => $arquivo->getId()));

                $i = 0;
                $arrErroRows = array();
                $this->statusProgress["tLinha"] = count(file($archive)) - 1;

                // Variável apenas para comparação do número do pedido atual com o anterior na importação de pedidos
                $numPedido = null;

                while($linha = fgets($handle)) {
                    $i = $i+1;
                    if (ucfirst($cabecalho) == 'S') {
                        if ($i == 1) {
                            continue;
                        }
                    }

                    $this->statusProgress["iLinha"] = $i-1;
                    if ($caracterQuebra == "") {
                        $conteudoArquivo = array(0=>$linha);
                    }   else {
                        $conteudoArquivo = explode($caracterQuebra, $linha);
                    }

                    if (count(array_filter($conteudoArquivo)) > 1) {
                        $arrRegistro = array();
                        /** @var \Wms\Domain\Entity\Importacao\Campos $campo */
                        foreach ($camposArquivo as $campo) {
                            if (($campo->getPosicaoTxt() == null) || (count($conteudoArquivo) - 1 < $campo->getPosicaoTxt())) {
                                $valorCampo = trim($campo->getValorPadrao());
                            } else {
                                $valorCampo = trim($conteudoArquivo[$campo->getPosicaoTxt()]);

                                if ($valorCampo == "") {
                                    if ($campo->getPreenchObrigatorio() === "n") {
                                        $valorCampo = trim($campo->getValorPadrao());
                                    } else {
                                        $arrErroRows[$i] = $conteudoArquivo;
                                        break;
                                    }
                                }
                            }

                            if ($campo->getTamanhoInicio() != "") {
                                $valorCampo = substr($valorCampo, $campo->getTamanhoInicio(), $campo->getTamanhoFim());
                            }
                            $arrRegistro[$campo->getNomeCampo()] = $valorCampo;
                        }

                        switch ($tabelaDestino) {
                            case 'produto':
                                $importacaoService->saveProduto($em, $arrRegistro, $repositorios);
                                $countFlush++;
                                break;
                            case 'fabricante':
                                $importacaoService->saveFabricante($em, $arrRegistro['id'], $arrRegistro['nome'], $repositorios);
                                $countFlush++;
                                break;
                            case 'classe':
                                $importacaoService->saveClasse($em, $arrRegistro['id'], $arrRegistro['nome'], (isset($arrRegistro['idPai'])) ? $arrRegistro['idPai'] : null, $repositorios);
                                $countFlush++;
                                break;
                            case 'embalagem':
                                $importacaoService->saveEmbalagens($em, $arrRegistro, $repositorios);
                                $countFlush++;
                                break;
                            case 'pessoa':
                                $importacaoService->savePessoa($em, $arrRegistro);
                                $countFlush++;
                                break;
                            case 'cliente':
                                $importacaoService->saveCliente($em, $arrRegistro);
                                $countFlush++;
                                break;
                            case 'carga':
                                $importacaoService->saveCarga($em, $arrRegistro);
                                $countFlush++;
                                break;
                            case 'pedido':
                                if ($arrRegistro['codPedido'] !== $numPedido) {
                                    $numPedido = $arrRegistro['codPedido'];
                                    $importacaoService->savePedido($em, $arrRegistro);
                                    $countFlush++;
                                    break;
                                }
                                break;
                            case 'pedidoProduto':
                                $importacaoService->savePedidoProduto($em, $arrRegistro, false);
                                $countFlush++;
                                break;
                            case 'dadoLogistico':
                                $importacaoService->saveDadoLogistico($em, $arrRegistro);
                                $countFlush++;
                                break;
                            case 'endereco':
                                $importacaoService->saveEndereco($em, $arrRegistro);
                                $countFlush++;
                                break;
                            default:
                                break;
                        }
                    } else {
                        continue;
                    }

                    if ($countFlush >= 1){
                        $countFlush = 0;
                        $em->flush();
                        $em->clear();
                    }

                    $this->progressBar->update(null,$this->statusProgress);
                }

                $em->flush();

                if (count($arrErroRows) > 0) {
                    $arrErros[$file] = $arrErroRows;
                }
            }

            if (count($arrErros) > 0){
                $this->statusProgress["error"] = $arrErros;
                $this->progressBar->update(null, $this->statusProgress);
                $this->_helper->json(array('result' => "Ocorreram Falhas na importação"));
            };
            $this->progressBar->update(null,$this->statusProgress);
            $this->progressBar->finish();
            $this->_helper->json(array('result' => "Importação concluída com sucesso"));
        } catch (\Exception $e) {
            $this->_helper->json(array('result' => $e->getMessage()));
        } catch (Exception $e2) {
            $this->_helper->json(array('result' => $e2->getMessage()));
        }


    }

    public function indexAction()
    {
        $form = new IndexForm();
        $this->view->form = $form;

        /*$form = new IndexForm();
        $this->view->form = $form;
        $params = $this->_getAllParams();
        unset($params['module']);
        unset($params['controller']);
        unset($params['action']);
        if (isset($params) && !empty($params)) {
            //DIRETORIO DOS ARQUIVOS
            $dir = $params['localArmazenamento']; // 'C:\desenvolvimento\wms\docs\importcsv';
            //LEITURA DE ARQUIVOS COMO ARRAY
            $files = scandir($dir);

            try {
                //LEITURA DE ARQUIVOS
                foreach ($files as $file) {
                    $handle = $dir.'/\/'.$file;

                    //DEFINI��O DE ARQUIVO E METODO ADEQUADO PARA LEITURA DE DADOS
                    switch ($file) {
                        case 'expedicao.csv':
                            $this->importExpedicao($handle, $params, 'csv');
                            break;
                        case 'expedicao.txt':
                            $this->importExpedicao($handle, $params, 'txt');
                            break;
                        case 'fabricante.csv':
                            $this->importFabricante($handle, $params, 'csv');
                            break;
                        case 'fabricante.txt':
                            $this->importFabricante($handle, $params, 'txt');
                            break;
                        case 'filial.csv':
                            $this->importFilial($handle, $params, 'csv');
                            break;
                        case 'filial.txt':
                            $this->importFilial($handle, $params, 'txt');
                            break;
                        case 'fornecedor.csv':
                            $this->importFornecedor($handle, $params, 'csv');
                            break;
                        case 'fornecedor.txt':
                            $this->importFornecedor($handle, $params, 'txt');
                            break;
                        case 'notaFiscal.csv':
                            $this->importNotaFiscal($handle, $params, 'csv');
                            break;
                        case 'notaFiscal.txt':
                            $this->importNotaFiscal($handle, $params, 'txt');
                            break;
                        case 'produto.csv':
                            $this->importProduto($handle, $params, 'csv');
                            break;
                        case 'produto.txt':
                            $this->importProduto($handle, $params, 'txt');
                            break;
                    }
                }
                $this->_helper->messenger('success', 'Todos arquivos importados com sucesso.');
                return $this->redirect('index');
            } catch (\Exception $e) {
                $this->_helper->messenger('error', $e->getMessage());
            }
        }*/
    }

    private function importNotaFiscal($handle, $params, $tipoArquivo)
    {
        $em = $this->getEntityManager();
        $importacao = new \Wms\Service\Importacao();

        $handle = fopen($handle, "r");
        $caracterQuebra = $params['caracterQuebra'];

        try {
            $array = array();
            $count = 0;
            if ($tipoArquivo == 'csv') {
                $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            } elseif ($tipoArquivo == 'txt') {
                $cabecalho = fgets($handle, 1000);
                $cabecalho = explode($caracterQuebra, $cabecalho);
            } else {
                throw new \Exception("Formato de arquivo nao suportado.");
            }
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                foreach ($cabecalho as $key => $titulo) {
                    $cabecalho[$key] = trim($titulo);
                }
                foreach ($data as $key => $dados) {
                    $data[$key] = trim($dados);
                }
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

    private function importExpedicao($handle, $params, $tipoArquivo)
    {
        $em = $this->getEntityManager();
        $importacao = new \Wms\Service\Importacao();

        $handle = fopen($handle, "r");
        $caracterQuebra = $params['caracterQuebra'];

        try {
            $array = array();
            $count = 0;
            if ($tipoArquivo == 'csv') {
                $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            } elseif ($tipoArquivo == 'txt') {
                $cabecalho = fgets($handle, 1000);
                $cabecalho = explode($caracterQuebra, $cabecalho);
            } else {
                throw new \Exception("Formato de arquivo nao suportado.");
            }
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                foreach ($cabecalho as $key => $titulo) {
                    $cabecalho[$key] = trim($titulo);
                }
                foreach ($data as $key => $dados) {
                    $data[$key] = trim($dados);
                }
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
                $importacao->savePedidoProduto($em, $item, true);
            }

            fclose($handle);
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }
    }

    private function importFabricante($handle, $params, $tipoArquivo)
    {
        $em = $this->getEntityManager();
        $importacao = new \Wms\Service\Importacao();

        $handle = fopen($handle, "r");
        $caracterQuebra = $params['caracterQuebra'];

        try {
            if ($tipoArquivo == 'csv') {
                $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            } elseif ($tipoArquivo == 'txt') {
                $cabecalho = fgets($handle, 1000);
                $cabecalho = explode($caracterQuebra, $cabecalho);
            } else {
                throw new \Exception("Formato de arquivo nao suportado.");
            }
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                foreach ($cabecalho as $key => $titulo) {
                    $cabecalho[$key] = trim($titulo);
                }
                foreach ($data as $key => $dados) {
                    $data[$key] = trim($dados);
                }
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

    private function importFornecedor($handle, $params, $tipoArquivo)
    {
        $em = $this->getEntityManager();
        $fornecedorRepo = $em->getRepository('wms:Pessoa\Papel\Fornecedor');
        $ClienteRepo    = $em->getRepository('wms:Pessoa\Papel\Cliente');

        $handle = fopen($handle, "r");
        $caracterQuebra = $params['caracterQuebra'];

        try {
            $em->beginTransaction();
            $array = array();
            if ($tipoArquivo == 'csv') {
                $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            } elseif ($tipoArquivo == 'txt') {
                $cabecalho = fgets($handle, 1000);
                $cabecalho = explode($caracterQuebra, $cabecalho);
            } else {
                throw new \Exception("Formato de arquivo nao suportado.");
            }
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                foreach ($cabecalho as $key => $titulo) {
                    $cabecalho[$key] = trim($titulo);
                }
                foreach ($data as $key => $dados) {
                    $data[$key] = trim($dados);
                }
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

    private function importProduto($handle, $params, $tipoArquivo)
    {
        $em = $this->getEntityManager();

        $importacao = new \Wms\Service\Importacao();
        $handle = fopen($handle, "r");
        $caracterQuebra = $params['caracterQuebra'];

        try {
            if ($tipoArquivo == 'csv') {
                $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            } elseif ($tipoArquivo == 'txt') {
                $cabecalho = fgets($handle, 1000);
                $cabecalho = explode($caracterQuebra, $cabecalho);
            } else {
                throw new \Exception("Formato de arquivo nao suportado.");
            }

            $em->beginTransaction();
            $produtos = array();
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                foreach ($cabecalho as $key => $titulo) {
                    $cabecalho[$key] = trim($titulo);
                }
                foreach ($data as $key => $dados) {
                    $data[$key] = trim($dados);
                }
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
                $produtos['embalagens'][0]['codigoBarras'] = $registro['CODIGO_BARRAS_EMBALAGEM'];
                $produtos['embalagens'][0]['cbInterno'] = $registro['CB_INTERNO_EMBALAGEM'];
                $produtos['embalagens'][0]['imprimirCb'] = $registro['IMPRIMIR_CB_EMBALAGEM'];
                $produtos['embalagens'][0]['embalado'] = $registro['EMBALADO'];
                $produtos['embalagens'][0]['capacidadePicking'] = $registro['CAPACIDADE_PICKING_EMBALAGEM'];
                $produtos['embalagens'][0]['pontoReposicao'] = 0;
                $produtos['embalagens'][0]['acao'] = 'incluir';

                $produtos['volumes'][0]['descricaoVolume'] = $registro['DESCRICAO_VOLUME'];
                $produtos['volumes'][0]['codigoBarras'] = $registro['CODIGO_BARRAS_VOLUME'];
                $produtos['volumes'][0]['sequenciaVolume'] = $registro['CODIGO_SEQUENCIAL_VOLUME'];
                $produtos['volumes'][0]['peso'] = $registro['PESO'];
                $produtos['volumes'][0]['normaPaletizacao'] = $registro['NORMA_PALETIZACAO'];
                $produtos['volumes'][0]['cbInterno'] = $registro['CB_INTERNO_VOLUME'];
                $produtos['volumes'][0]['imprimirCb'] = $registro['IMPRIMIR_CB_VOLUME'];
                $produtos['volumes'][0]['altura'] = $registro['ALTURA'];
                $produtos['volumes'][0]['largura'] = $registro['LARGURA'];
                $produtos['volumes'][0]['profundidade'] = $registro['PROFUNDIDADE'];
                $produtos['volumes'][0]['cubagem'] = $registro['CUBAGEM'];
                $produtos['volumes'][0]['capacidadePicking'] = $registro['CAPACIDADE_PICKING_VOLUME'];

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

    private function importFilial($handle, $params, $tipoArquivo)
    {
        $em = $this->getEntityManager();

        $importacao = new \Wms\Service\Importacao();
        $handle = fopen($handle, "r");
        $caracterQuebra = $params['caracterQuebra'];

        try {
            if ($tipoArquivo == 'csv') {
                $cabecalho = fgetcsv($handle,0,$caracterQuebra);
            } elseif ($tipoArquivo == 'txt') {
                $cabecalho = fgets($handle, 1000);
                $cabecalho = explode($caracterQuebra, $cabecalho);
            } else {
                throw new \Exception("Formato de arquivo nao suportado.");
            }
            $em->beginTransaction();
            $filial = array();
            while (($data = fgetcsv($handle, 1000, $caracterQuebra)) !== FALSE or ($data = fgets($handle, 1000)) !== FALSE) {
                foreach ($cabecalho as $key => $titulo) {
                    $cabecalho[$key] = trim($titulo);
                }
                foreach ($data as $key => $dados) {
                    $data[$key] = trim($dados);
                }
                $registro = array_combine($cabecalho, $data);

                $filial['pessoa']['juridica']['idExterno'] = $registro['COD_FILIAL_INTEGRACAO'];
                $filial['pessoa']['juridica']['codExterno'] = $registro['COD_EXTERNO'];
                $filial['pessoa']['juridica']['indLeitEtqProdTransbObg'] = $registro['LEITURA_ETIQUETA'];
                $filial['pessoa']['juridica']['indRessuprimento'] = $registro['UTILIZA_RESSUPRIMENTO'];
                $filial['pessoa']['juridica']['indRecTransbObg'] = $registro['RECEBIMENTO_TRANSBORDO'];
                $filial['pessoa']['juridica']['isAtivo'] = $registro['IND_ATIVO'];

                $filial['pessoa']['tipo'] = 'J';
                $filial['pessoa']['juridica']['dataAbertura'] = $registro['DATA_ABERTURA'];
                $filial['pessoa']['juridica']['cnpj'] = $registro['CNPJ'];
                $filial['pessoa']['juridica']['nome'] = $registro['NOME_EMPRESA'];
                $filial['pessoa']['juridica']['idTipoOrganizacao'] = null;
                $filial['pessoa']['juridica']['idRamoAtividade'] = null;

                $filial['acao'] = 'incluir';

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

}