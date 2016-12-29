<?php

/**
 * Created by PhpStorm.
 * User: Rodrigo
 * Date: 25/01/2016
 * Time: 09:28
 */

use Wms\Module\Web\Controller\Action;
use Wms\Module\Importacao\Form\Index as IndexForm;
use Wms\Util\WmsCache;

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
        $this->statusProgress["exception"] = null;
        $this->_helper->json(array('result' => $errstr));

    }

    private function setCaseImportacao($tabelaDestino, $em, $repositorios, $elements){

        $importacaoService = new \Wms\Service\Importacao();

        /** @var \Wms\Domain\Entity\PessoaJuridicaRepository $pJuridicaRepo */
        $pJuridicaRepo = $repositorios['pjRepo'];

        /** @var \Wms\Domain\Entity\PessoaFisicaRepository $pFisicaRepo */
        $pFisicaRepo = $repositorios['pfRepo'];

        /** @var \Wms\Domain\Entity\Pessoa\Papel\FornecedorRepository $fornecedorRepo */
        $fornecedorRepo = $repositorios['fornecedorRepo'];

        /** @var \Wms\Domain\Entity\CodigoFornecedor\ReferenciaRepository $referenciaRepo */
        $referenciaRepo = $repositorios['referenciaRepo'];

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $repositorios['produtoRepo'];

        /** @var \Wms\Domain\Entity\Produto\DadoLogisticoRepository $dadoLogisticoRepo */
        $dadoLogisticoRepo = $repositorios['dadoLogisticoRepo'];
        
        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $repositorios['embalagemRepo'];

        /** @var \Wms\Domain\Entity\Produto\NormaPaletizacaoRepository $normaPaletizacaoRepo */
        $normaPaletizacaoRepo = $repositorios['normaPaletizacaoRepo'];

        /** @var \Wms\Domain\Entity\Expedicao\CargaRepository $cargaRepo */
        $cargaRepo = $repositorios['cargaRepo'];

        /** @var \Wms\Domain\Entity\Produto\ClasseRepository $classeRepo */
        $classeRepo = $repositorios['classeRepo'];

        /** @var \Wms\Domain\Entity\Pessoa\Papel\ClienteRepository $clienteRepo */
        $clienteRepo = $repositorios['clienteRepo'];

        $arrRegistro = $elements['arrRegistro'];
        $arrErroRows = $elements['arrErroRows'];
        $countFlush = $elements['countFlush'];
        $linha = $elements['linha'];
        $checkArray = $elements['checkArray'];
        $numPedido = $elements['numPedido'];

        try {

            switch ($tabelaDestino) {
                case 'produto':
                    $registro = http_build_query($arrRegistro, '', ' ');
                    if (!in_array($registro, $checkArray)) {
                        array_push($checkArray, $registro);
                    } else {
                        $arrErroRows[$linha] = "Produto repetido: " . $registro;
                        break;
                    }
                    $criterio = array('id' => $arrRegistro['id'],
                        'grade' => $arrRegistro['grade']

                    );
                    $check = $produtoRepo->findBy($criterio);

                    if (!empty($check)){
                        $arrErroRows[$linha] = "Este produto já foi cadastrado " . $registro;
                        break;
                    }
                    $result = $importacaoService->saveProduto($em, $arrRegistro, $repositorios);
                    if (is_string($result)) {
                        $arrErroRows['exception'] = $result;
                    } else {
                        $countFlush++;
                    }
                    break;
                case 'fabricante':
                    $result = $importacaoService->saveFabricante($em, $arrRegistro['id'], $arrRegistro['nome'], $repositorios);
                    if (is_string($result)) {
                        $arrErroRows['exception'] = $result;
                    } else {
                        $countFlush++;
                    }
                    break;
                case 'classe':
                    if (!in_array($arrRegistro['id'], $checkArray)) {
                        array_push($checkArray, $arrRegistro['id']);
                    } else {
                        $arrErroRows[$linha] = "Classe repetida" . $arrRegistro['nome'];
                        break;
                    }
                    $result = $importacaoService->saveClasse($arrRegistro['id'], $arrRegistro['nome'], (isset($arrRegistro['idPai'])) ? $arrRegistro['idPai'] : null, $repositorios);
                    if (is_string($result)) {
                        $arrErroRows['exception'] = $result;
                    } else {
                        $countFlush++;
                    }
                    break;
                case 'embalagem':

                    if (!empty($arrRegistro['endereco'])) {
                        $arrRegistro['endereco'] = str_replace(",", ".", $arrRegistro['endereco']);
                        $endereco = explode(".", $arrRegistro['endereco']);

                        foreach ($endereco as $element) {
                            if (strlen($element) < 1) {
                                $arrErroRows[$linha] = "Embalagem sem picking - CodProduto: " . $arrRegistro['codProduto'];
                            }
                        }
                    }

                    $embalagemEntity = null;
                    if ($arrRegistro['codigoBarras'] != "") {
                        $codigoBarras = \Wms\Util\CodigoBarras::formatarCodigoEAN128Embalagem($arrRegistro['codigoBarras']);
                        $embalagemEntity = $embalagemRepo->findOneBy(array(
                            'codigoBarras' => $codigoBarras
                        ));
                    } else {
                        $arrRegistro['CBInterno'] = 'S';
                        $embalagemEntity = $embalagemRepo->findOneBy(array(
                            'codProduto' => $arrRegistro['codProduto'],
                            'grade' => $arrRegistro['grade'],
                            'quantidade' => $arrRegistro['quantidade']
                        ));
                    }
                    if (!empty($embalagemEntity)){
                        $arrErroRows[$linha] = "Código de barras já cadastrado " . $arrRegistro['codigoBarras'];
                        break;
                    };
                    $result = $importacaoService->saveEmbalagens($em, $arrRegistro, $repositorios);
                    if (is_string($result)) {
                        $arrErroRows['exception'] = $result;
                    } else {
                        $countFlush++;
                    }
                    break;
                case 'fornecedor';
                    $cpf_cnpjFormatado = \Core\Util\String::retirarMaskCpfCnpj($arrRegistro['cpf_cnpj']);
                    
                    if (strlen($cpf_cnpjFormatado) == 11) {
                        $arrErroRows[$linha] = 'Proibido importar fornecedor por CPF ' . $arrRegistro['cpf_cnpj'];
                        break;
                    } else if (strlen($cpf_cnpjFormatado) == 14) {
                        $arrRegistro['tipoPessoa'] = "J";
                    } else {
                        $arrErroRows[$linha] = 'CNPJ fora do padrão: ' . $arrRegistro['cpf_cnpj'];
                        break;
                    }
                    
                    if (!in_array($arrRegistro['cpf_cnpj'], $checkArray)) {
                        array_push($checkArray, $arrRegistro['cpf_cnpj']);
                    } else {
                        if ($arrRegistro['tipoPessoa'] == "J")
                            $arrErroRows[$linha] = 'CNPJ repetido: ' . $arrRegistro['cpf_cnpj'];

                        if ($arrRegistro['tipoPessoa'] == "F")
                            $arrErroRows[$linha] = 'CPF repetido: ' . $arrRegistro['cpf_cnpj'];

                        break;
                    }

                    /** @var \Wms\Domain\Entity\Pessoa\Papel\Fornecedor $fornecedor */
                    $fornecedor = $fornecedorRepo->findOneBy(array('idExterno' => $arrRegistro['idExterno']));
                    if (!empty($fornecedor)){
                        $nome = $fornecedor->getPessoa()->getNome();
                        $nomFantasia = $fornecedor->getPessoa()->getNomeFantasia();
                        $nom = (!empty($nomFantasia)) ? $nomFantasia : $nome;
                        $arrErroRows[$linha] = "O fornecedor $nom já está cadastrado com o código $arrRegistro[idExterno]";
                        break;
                    }

                    if($arrRegistro['tipoPessoa'] == 'J') {
                        /** @var \Wms\Domain\Entity\Pessoa\Juridica $entityPessoa */
                        $entityPessoa = $pJuridicaRepo->findOneBy(array('cnpj' => $cpf_cnpjFormatado));

                        if ($entityPessoa) {
                            $fornecedor = $fornecedorRepo->findBy(array("id" => $entityPessoa->getId()));
                            if ($fornecedor) {
                                $arrErroRows[$linha] = 'Fornecedor já cadastrado: ' . $arrRegistro['nome'];
                                break;
                            } else {
                                $result = $importacaoService->savePessoaEmFornecedor($em, $entityPessoa, $arrRegistro['idExterno']);
                                if (is_string($result)) {
                                    $arrErroRows['exception'] = $result;
                                } else {
                                    $countFlush++;
                                }
                                break;
                            }
                        }
                    } else if ($arrRegistro['tipoPessoa'] == 'F') {
                        /** @var \Wms\Domain\Entity\Pessoa\Fisica $entityPessoa */
                        $entityPessoa = $pFisicaRepo->findOneBy(array('cpf' => $cpf_cnpjFormatado));

                        if ($entityPessoa) {
                            $fornecedor = $fornecedorRepo->findBy(array("id" => $entityPessoa->getId()));
                            if ($fornecedor) {
                                $arrErroRows[$linha] = 'Fornecedor já cadastrado: ' . $arrRegistro['nome'];
                                break;
                            } else {
                                $result = $importacaoService->savePessoaEmFornecedor($em, $entityPessoa, $arrRegistro['idExterno']);
                                if (is_string($result)) {
                                    $arrErroRows['exception'] = $result;
                                } else {
                                    $countFlush++;
                                }
                                break;
                            }
                        }
                    }

                    $result = $importacaoService->saveFornecedor($em, $arrRegistro, false);
                    if (is_string($result)) {
                        $arrErroRows['exception'] = $result;
                    } else {
                        $countFlush++;
                    }
                    break;
                case 'cliente':
                    $cpf_cnpjFormatado = \Core\Util\String::retirarMaskCpfCnpj($arrRegistro['cpf_cnpj']);
                    if (strlen($cpf_cnpjFormatado) == 11) {
                        $arrRegistro['tipoPessoa'] = "F";
                    } else if (strlen($cpf_cnpjFormatado) == 14) {
                        $arrRegistro['tipoPessoa'] = "J";
                    } else {
                        $arrErroRows[$linha] = "CNPJ ou CPF fora do padrão: " . $arrRegistro['cpf_cnpj'];
                        break;
                    }
                    if (!in_array($arrRegistro['cpf_cnpj'], $checkArray)) {
                        array_push($checkArray, $arrRegistro['cpf_cnpj']);
                    } else {
                        if ($arrRegistro['tipoPessoa'] == "J")
                            $arrErroRows[$linha] = "CNPJ repetido: " . $arrRegistro['cpf_cnpj'];

                        if ($arrRegistro['tipoPessoa'] == "F")
                            $arrErroRows[$linha] = "CPF repetido: " . $arrRegistro['cpf_cnpj'];
                        break;
                    }

                    if ($arrRegistro['tipoPessoa'] == 'J') {
                        $entityPessoa = $pJuridicaRepo->findOneBy(array('cnpj' => $cpf_cnpjFormatado));
                        if ($entityPessoa) {
                            $cliente = $clienteRepo->findBy(array("id" => $entityPessoa->getId()));
                            if ($cliente) {
                                $arrErroRows[$linha] = "Cliente já cadastrado: " . $arrRegistro['nome'];
                                break;
                            } else {
                                $result = $importacaoService->savePessoaEmCliente($em, $entityPessoa, $arrRegistro['codClienteExterno']);
                                if (is_string($result)) {
                                    $arrErroRows['exception'] = $result;
                                } else {
                                    $countFlush++;
                                }
                                break;
                            }
                        }
                    } else if ($arrRegistro['tipoPessoa'] == 'F') {
                        $entityPessoa = $pFisicaRepo->findOneBy(array('cpf' => $cpf_cnpjFormatado));
                        if ($entityPessoa) {
                            $cliente = $clienteRepo->findBy(array("id" => $entityPessoa->getId()));
                            if ($cliente) {
                                $arrErroRows[$linha] = "Cliente já cadastrado: " . $arrRegistro['nome'];
                                break;
                            } else {
                                $result = $importacaoService->savePessoaEmCliente($em, $entityPessoa, $arrRegistro['codClienteExterno']);
                                if (is_string($result)) {
                                    $arrErroRows['exception'] = $result;
                                } else {
                                    $countFlush++;
                                }
                                break;
                            }
                        }
                    }

                    $result = $importacaoService->saveCliente($em, $arrRegistro);
                    if (is_string($result)) {
                        $arrErroRows['exception'] = $result;
                    } else {
                        $countFlush++;
                    }
                    break;
                case 'referencia':
                    $registro = $arrRegistro['dscReferencia'] . " - CodInterno: " . $arrRegistro['codProduto'] . ' - CNPJ: ' . $arrRegistro['cnpj'];

                    if (!in_array($registro, $checkArray)) {
                        array_push($checkArray, $registro);
                    } else {
                        $arrErroRows[$linha] = "Referência repetida: " . $registro;
                        break;
                    }

                    $cnpj = \Core\Util\String::retirarMaskCpfCnpj($arrRegistro['cnpj']);
                    $arrRegistro['fornecedor'] = $fornecedorRepo->getFornecedorByCNPJ($cnpj);
                    if (empty($arrRegistro['fornecedor'])) {
                        $arrErroRows[$linha] = "Nenhum fornecedor encontrado com o CNPJ: " . $arrRegistro['cnpj'];
                        break;
                    }
                    unset($arrRegistro['cnpj']);

                    /** @var \Wms\Domain\Entity\Produto $prodEntity */
                    $prodEntity = $produtoRepo->findOneBy(array('id' => $arrRegistro['codProduto'], 'grade' => $arrRegistro['grade']));
                    if (empty($prodEntity)) {
                        $arrErroRows[$linha] = "Nenhum produto de código: " . $arrRegistro['codProduto'] . ' e grade: ' . $arrRegistro['grade'];
                        break;
                    }

                    unset($arrRegistro['codProduto']);
                    unset($arrRegistro['grade']);

                    $arrRegistro['idProduto'] = $prodEntity->getIdProduto();
                    $criteria = array(
                        'idProduto' => $prodEntity->getIdProduto(),
                        'fornecedor' => $arrRegistro['fornecedor']->getPessoa(),
                        'dscReferencia' => $arrRegistro['dscReferencia']
                    );

                    $refeEntity = $referenciaRepo->findOneBy($criteria);

                    if (empty($refeEntity)) {
                        $result = $importacaoService->saveReferenciaProduto($em, $arrRegistro);
                        if (!is_string($result)) {
                            $countFlush++;
                        } else {
                            $arrErroRows['exception'] = $result;
                        }
                    } else {
                        $arrErroRows[$linha] = 'Referencia já registrada: ' . $arrRegistro['dscReferencia'];
                    }
                    break;
                case 'pedido':
                    $registro = http_build_query($arrRegistro, '', ' ');
                    if (!in_array($registro, $checkArray)) {
                        array_push($checkArray, $registro);
                    } else {
                        $arrErroRows[$linha] = "Pedido repetido: " . $registro;
                        break;
                    }
                    $arrRepo = array(
                        'pJuridicaRepo' => $pJuridicaRepo,
                        'pFisicaRepo' => $pFisicaRepo,
                        'clienteRepo' => $clienteRepo
                    );
                    $result = $importacaoService->savePedido($em, $arrRegistro, $arrRepo);
                    if (is_string($result)) {
                        $arrErroRows['exception'] = $result;
                    } else {
                        $countFlush++;
                    }

                    break;
                case 'pedidoProduto':
                    $registro = http_build_query($arrRegistro, '', ' ');
                    if (!in_array($registro, $checkArray)) {
                        array_push($checkArray, $registro);
                    } else {
                        $arrErroRows[$linha] = "Produto repetido neste pedido: " . $registro;
                        break;
                    }

                    $result = $importacaoService->savePedidoProduto($em, $arrRegistro, false);
                    if (is_string($result)) {
                        $arrErroRows['exception'] = $result;
                    } else {
                        $countFlush++;
                    }
                    break;
                case 'normaPaletizacao':
                    $registro = http_build_query($arrRegistro, '', ' ');
                    if (!in_array($registro, $checkArray)) {
                        array_push($checkArray, $registro);
                    } else {
                        $arrErroRows[$linha] = "Norma repetida: " . $registro;
                        break;
                    }

                    $check = $normaPaletizacaoRepo->findBy($arrRegistro);

                    if (!empty($check)){
                        $arrErroRows[$linha] = "Esta norma já foi cadastrada " . $registro;
                        break;
                    }
                    $result = $importacaoService->saveNormaPaletizacao($em, $arrRegistro);
                    if (is_string($result)) {
                        $arrErroRows['exception'] = $result;
                    } else {
                        $countFlush++;
                    }
                    break;
                case 'dadoLogistico':
                    $registro = http_build_query($arrRegistro, '', ' ');
                    if (!in_array($registro, $checkArray)) {
                        array_push($checkArray, $registro);
                    } else {
                        $arrErroRows[$linha] = "Dado logistico repetido: " . $registro;
                        break;
                    }
                    $criterioEmb = array(
                        'codProduto' => $arrRegistro['codProduto'],
                        'grade' => $arrRegistro['grade']
                    );

                    if (isset($arrRegistro['codigoBarras']) && !empty($arrRegistro['codigoBarras'])){
                        $criterioEmb['codigoBarras'] = $arrRegistro['codigoBarras'];
                    } else {
                        $criterioEmb['quantidade'] = $arrRegistro['quantidade'];
                    }

                    unset($arrRegistro['codProduto']);
                    unset($arrRegistro['grade']);
                    unset($arrRegistro['quantidade']);
                    unset($arrRegistro['codigoBarras']);

                    $arrRegistro['embalagem'] = $embalagemRepo->findOneBy($criterioEmb);

                    $criterioNorma = array(
                        "numLastro" => $arrRegistro["numLastro"],
                        "numCamadas" => $arrRegistro["numCamadas"],
                        "numPeso" => $arrRegistro["numPeso"],
                        "numNorma" => $arrRegistro["numNorma"],
                        "unitizador" => $arrRegistro["unitizador"],
                        "isPadrao" => $arrRegistro["isPadrao"],
                    );
                    unset($arrRegistro['numLastro']);
                    unset($arrRegistro['numCamadas']);
                    unset($arrRegistro['numPeso']);
                    unset($arrRegistro['numNorma']);
                    unset($arrRegistro['unitizador']);
                    unset($arrRegistro['isPadrao']);
                    $arrRegistro['normaPaletizacao'] = $normaPaletizacaoRepo->findOneBy($criterioNorma);

                    $check = $dadoLogisticoRepo->findBy($arrRegistro);

                    if (!empty($check)){
                        $arrErroRows[$linha] = "Esta dado logistico já foi cadastrado " . $registro;
                        break;
                    }
                    $result = $importacaoService->saveDadoLogistico($em, $arrRegistro);
                    if (is_string($result)) {
                        $arrErroRows['exception'] = $result;
                    } else {
                        $countFlush++;
                    }
                    break;
                case 'endereco':
                    $arrRegistro['endereco'] = str_replace(",", ".", $arrRegistro['endereco']);
                    $endereco = explode(".", $arrRegistro['endereco']);
                    $stsEndereço = true;
                    foreach ($endereco as $element) {
                        if (strlen($element) < 1) {
                            $arrErroRows[$linha] = "Endereço incompleto";
                            $stsEndereço = false;
                        }
                    }
                    if ($stsEndereço) {
                        $result = $importacaoService->saveEndereco($em, $arrRegistro);
                        if (is_string($result)) {
                            $arrErroRows['exception'] = $result;
                        } else {
                            $countFlush++;
                        }
                    }
                    break;
                case "carga":
                    $registro = http_build_query($arrRegistro, '', ' ');

                    if (!in_array($registro, $checkArray)) {
                        array_push($checkArray, $registro);
                    } else {
                        $arrErroRows[$linha] = "Dados de carga repetidos: " . $registro;
                        break;
                    }
                    
                    $codTipoCarga = $arrRegistro['codTipoCarga'];
                    unset($arrRegistro['codTipoCarga']);
                    $check = $cargaRepo->findBy($arrRegistro);
                    
                    $arrRegistro['codTipoCarga'] = $codTipoCarga;
                    if (empty($check)){
                        $result = $importacaoService->saveCarga($em, $arrRegistro);
                        if (is_string($result)) {
                            $arrErroRows['exception'] = $result;
                        } else {
                            $countFlush++;
                        }
                    } else {
                        $arrErroRows[$linha] = "Esta carga já foi cadastrada " . $registro;
                    }
                    break;
                default:
                    break;
            }
        }catch(Exception $e){
            $arrErroRows['exception'] = $e->getMessage();
        }

        $result = array(
            'numPedido' => $numPedido,
            'checkArray' => $checkArray,
            'arrErroRows' => $arrErroRows,
            'countFlush' => $countFlush,
            'linha' => $linha
        );

        return $result;
    }
    
    public function iniciarAjaxAction(){

        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {

            set_error_handler(array($this, 'custom_warning_handler'));
            ini_set('memory_limit', '-1');
            ini_set('max_execution_time', 3000);

            $dir = $this->getSystemParameterValue("DIRETORIO_IMPORTACAO");

            $produtoRepo = $em->getRepository('wms:Produto');
            $enderecoRepo = $em->getRepository("wms:Deposito\Endereco");
            $fabricanteRepo = $em->getRepository('wms:Fabricante');
            $classeRepo = $em->getRepository('wms:Produto\Classe');
            $embalagemRepo = $em->getRepository('wms:Produto\Embalagem');
            $camposRepo = $em->getRepository('wms:Importacao\Campos');
            $pJuridicaRepo = $em->getRepository('wms:Pessoa\Juridica');
            $pFisicaRepo = $em->getRepository('wms:Pessoa\Fisica');
            $fornecedorRepo = $em->getRepository('wms:Pessoa\Papel\Fornecedor');
            $referenciaRepo = $em->getRepository('wms:CodigoFornecedor\Referencia');
            $normaPaletizacaoRepo = $em->getRepository('wms:Produto\NormaPaletizacao');
            $dadoLogisticoRepo = $em->getRepository('wms:Produto\DadoLogistico');
            $cargaRepo  = $em->getRepository('wms:Expedicao\Carga');
            $impArquivoRepo = $em->getRepository('wms:Importacao\Arquivo');
            $clienteRepo = $em->getRepository('wms:Pessoa\Papel\Cliente');

            $repositorios = array(
                'produtoRepo' => $produtoRepo,
                'enderecoRepo' => $enderecoRepo,
                'fabricanteRepo' => $fabricanteRepo,
                'classeRepo' => $classeRepo,
                'embalagemRepo' => $embalagemRepo,
                'pjRepo' => $pJuridicaRepo,
                'pfRepo' => $pFisicaRepo,
                'fornecedorRepo' => $fornecedorRepo,
                'referenciaRepo' => $referenciaRepo,
                'normaPaletizacaoRepo' => $normaPaletizacaoRepo,
                'dadoLogisticoRepo' => $dadoLogisticoRepo,
                'cargaRepo' => $cargaRepo,
                'clienteRepo' => $clienteRepo,
            );

            $files = explode('-', $this->getRequest()->getParam('files'));

            $arquivos = $impArquivoRepo->findBy(array('tabelaDestino' => $files, 'ativo' => 'S'), array('sequencia' => 'ASC'));

            $dtUltImp = null;
            /** @var \Wms\Domain\Entity\Importacao\Arquivo $arquivo */
            foreach ($arquivos as $arquivo){
                $dtCheck = $arquivo->getUltimaImportacao();
                if (empty($dtUltImp)){
                    $dtUltImp = $dtCheck;
                } else if ($dtUltImp < $dtCheck) {
                    $dtUltImp = $dtCheck;
                }
            }

            $arrErros = array();
            $countFlush = 0;

            $tLinhas = null;
            $tColunas = null;
            $objExcel = null;
            $arquivoAtual = null;

            $config = array('updateMethodName' => 'Zend_ProgressBar_Update');
            $adapter = new Zend_ProgressBar_Adapter_JsPush($config);

            $this->statusProgress["tArquivo"] = count($arquivos);
            $this->progressBar = new Zend_ProgressBar($adapter, 0, $this->statusProgress["tArquivo"]);

            /**
             * @var  $key
             * @var \Wms\Domain\Entity\Importacao\Arquivo $arquivo
             */
            foreach ($arquivos as $key => $arquivo) {
                $this->statusProgress["iArquivo"] = $key + 1;

                $file = $arquivo->getNomeArquivo();
                $archive = $dir . DIRECTORY_SEPARATOR . $file;
                $camposArquivo = $camposRepo->findBy(array('arquivo' => $arquivo->getId()));
                $cabecalho = $arquivo->getCabecalho();
                $tabelaDestino = $arquivo->getTabelaDestino();

                $arrErroRows = array();
                $numPedido = null;
                $checkArray = array();

                $exp = explode(".", $file);
                $extencao = end($exp);

                if ($extencao == "xls") {
                    if ($file != $arquivoAtual) {
                        require_once PHPEXCEL_PATH . DIRECTORY_SEPARATOR . "PHPExcel.php";

                        //Obj de leitura xls
                        $objReader = new PHPExcel_Reader_Excel5();
                        $objReader->setReadDataOnly(true);
                        $objExcel = $objReader->load($archive);

                        //Total de colunas
                        $cols = $objExcel->setActiveSheetIndex(0)->getHighestColumn();
                        $tColunas = PHPExcel_Cell::columnIndexFromString($cols);

                        //Total de linhas
                        $tLinhas = $objExcel->setActiveSheetIndex(0)->getHighestRow();
                        $arquivoAtual = $file;
                    }

                    if (ucfirst($cabecalho) == 'S') {
                        $this->statusProgress["tLinha"] = $tLinhas - 1;
                    } else {
                        $this->statusProgress["tLinha"] = $tLinhas;
                    }

                    for ($linha = 1; $linha <= $tLinhas; $linha++) {

                        if (ucfirst($cabecalho) == 'S' && $linha == 1) {
                            /** @var \Wms\Domain\Entity\Importacao\Campos $campo */
                            /*foreach ($camposArquivo as $campo){
                                $coluna = $campo->getPosicaoTxt();
                                if (!empty($coluna)){
//                                    if (empty($objExcel->getActiveSheet()->getCellByColumnAndRow($coluna, $linha)->getFormattedValue())){
//                                        throw new Exception("O cabeçalho não está na primeira linha ou não está conforme a configuração necessária");
//                                    }
                                }
                            }*/
                            continue;
                        }

                        if (ucfirst($cabecalho) == 'S') {
                            $this->statusProgress["iLinha"] = $linha - 1;
                        } else {
                            $this->statusProgress["iLinha"] = $linha;
                        }



                        $arrRegistro = array();

                        /** @var \Wms\Domain\Entity\Importacao\Campos $campo */
                        foreach ($camposArquivo as $campo) {
                            $coluna = $campo->getPosicaoTxt();
                            if (($coluna == null) || ($tColunas - 1 < $coluna)) {
                                $valorCampo = trim($campo->getValorPadrao());
                            } else {
                                $valorCampo = trim($objExcel->getActiveSheet()->getCellByColumnAndRow($coluna, $linha)->getFormattedValue());
                                $valorCampo = utf8_encode($valorCampo);
                                if (empty($valorCampo)) {
                                    if ($campo->getPreenchObrigatorio() === "n") {
                                        $valorCampo = trim($campo->getValorPadrao());
                                    } else {
                                        $arrErroRows[$linha] = "Campo: " . $campo->getNomeCampo() . " - não pode ser nulo.";
                                        break;
                                    }
                                }
                            }

                            if ($campo->getTamanhoInicio() != "") {
                                $valorCampo = substr($valorCampo, $campo->getTamanhoInicio(), $campo->getTamanhoFim());
                            }

                            $arrRegistro[$campo->getNomeCampo()] = utf8_decode($valorCampo);
                        }

                        $elements = array(
                            'numPedido' => $numPedido,
                            'checkArray' => $checkArray,
                            'arrRegistro' => $arrRegistro,
                            'arrErroRows' => $arrErroRows,
                            'countFlush' => $countFlush,
                            'linha' => $linha
                        );

                        $resultSetCase = $this->setCaseImportacao($tabelaDestino, $em, $repositorios, $elements);

                        $arrErroRows = $resultSetCase['arrErroRows'];
                        $countFlush = $resultSetCase['countFlush'];
                        $checkArray = $resultSetCase['checkArray'];
                        $numPedido = $resultSetCase['numPedido'];

                        if (isset($arrErroRows['exception']) && !empty($arrErroRows['exception'])) {
                            $this->statusProgress['exception'] = $arrErroRows['exception'];
                            echo '<script type="text/javascript">parent.Zend_ProgressBar_Update({"current":0,"max":1,"percent":0,"timeTaken":2,"timeRemaining":null,"text": ' . Zend_Json::encode($this->statusProgress) .'});</script><br />';
                            $this->progressBar->finish();
                            $em->rollback();
                            exit;
                        }

                        $this->progressBar->update(null, $this->statusProgress);

                        if ($countFlush >= 100) {
                            $countFlush = 0;
                            $em->flush();
                            $em->clear();
                        }
                    }
                } elseif ($extencao == "csv") {

                    $handle = fopen($archive, "r");

                    $caracterQuebra = $arquivo->getCaracterQuebra();
                    
                    $this->statusProgress["tLinha"] = count(file($archive)) - 1;

                    $i = 0;

                    while ($linha = fgets($handle)) {
                        $linha = utf8_encode($linha);
                        $i = $i + 1;
                        if (ucfirst($cabecalho) == 'S') {
                            if ($i == 1) {
                                continue;
                            }
                        }

                        $this->statusProgress["iLinha"] = $i - 1;
                        if ($caracterQuebra == "") {
                            $conteudoArquivo = array(0 => $linha);
                        } else {
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
                                            $arrErroRows[$i] = "Campo: " . $campo->getNomeCampo() . " - não pode ser nulo.";
                                            break;
                                        }
                                    }
                                }

                                if ($campo->getTamanhoInicio() != "") {
                                    $valorCampo = substr($valorCampo, $campo->getTamanhoInicio(), $campo->getTamanhoFim());
                                }
                                $arrRegistro[$campo->getNomeCampo()] = $valorCampo;
                            }

                            $elements = array(
                                'numPedido' => $numPedido,
                                'checkArray' => $checkArray,
                                'arrRegistro' => $arrRegistro,
                                'arrErroRows' => $arrErroRows,
                                'countFlush' => $countFlush,
                                'linha' => $i
                            );

                            $resultSetCase = $this->setCaseImportacao($tabelaDestino, $em, $repositorios, $elements);

                            $arrErroRows = $resultSetCase['arrErroRows'];
                            $countFlush = $resultSetCase['countFlush'];
                            $checkArray = $resultSetCase['checkArray'];
                            $numPedido = $resultSetCase['numPedido'];

                        } else {
                            continue;
                        }

                        if (isset($arrErroRows['exception']) && !empty($arrErroRows['exception'])) {
                            $this->statusProgress['exception'] = $arrErroRows['exception'];
                            $this->progressBar->update(null, $this->statusProgress);
                            $this->progressBar->finish();
                            $em->rollback();
                            echo Zend_Json::encode(array('result' => 'Exception'));
                            exit;
                        }

                        $this->progressBar->update(null, $this->statusProgress);

                        if ($countFlush >= 40) {
                            $countFlush = 0;
                            $em->flush();
                        }
                    }
                }

                $arquivo->setUltimaImportacao(new DateTime());
                $em->merge($arquivo);
                $em->flush();

                if (count($arrErroRows) > 0) {
                    $arrErros[$file] = $arrErroRows;
                }
            }

            if (count($arrErros) > 0) {
                $this->statusProgress["error"] = $arrErros;
            };
            $this->progressBar->update(null, $this->statusProgress);
            $this->progressBar->finish();
            $em->commit();
            echo Zend_Json::encode(array('result' => 'ok'));
            exit;

        } catch (\Exception $e) {
            $em->rollback();
            $this->statusProgress['exception'] = array($e->getMessage());
            echo '<script type="text/javascript">parent.Zend_ProgressBar_Update({"current":0,"max":1,"percent":0,"timeTaken":2,"timeRemaining":null,"text": ' . Zend_Json::encode($this->statusProgress) .'});</script><br />';
            $this->progressBar->finish();
            exit;
        }
    }

    public function alterarStatusAction()
    {
        try {
            $id = $this->getRequest()->getParam('id');

            $impArquivoService = new \Wms\Service\ImportacaoArquivo($this->getEntityManager());
            $impArquivoService->alterarStatus($id);

            $this->addFlashMessage('success', 'Arquivo de importação alterado com sucesso.');
        }catch (Exception $e){
            $this->addFlashMessage('error', $e->getMessage());
        }
        $this->redirect('configuracao-importacao');
    }

    public function listaCamposImportacaoAction()
    {
        $id = $this->getRequest()->getParam('id');
        $grid = new \Wms\Module\Importacao\Grid\ListaCamposImportacao();
        $this->view->grid = $grid->init($id)->render();
    }

    public function configuracaoImportacaoAction()
    {
        $grid = new \Wms\Module\Importacao\Grid\ConfiguracaoImportacao();
        $this->view->grid = $grid->init()->render();
    }

    public function editarCampoImportacaoAction()
    {
        if ($this->getRequest()->isPost()){
            
        } else {
            $idCampo = $this->getRequest()->getParam('id');
            $form = new \Wms\Module\Importacao\Form\EditarCamposImportacao();
            $campo = $this->em->getRepository('wms:Importacao\Campos')->find($idCampo);
            $this->view->form = $form;
            $this->view->campo = $campo;
        }
    }

    public function indexAction()
    {
        $arquivos = $this->getEntityManager()->getRepository('wms:Importacao\Arquivo')->findBy(array('ativo' => 'S'), array('ultimaImportacao' => 'DESC'));

        $dtUltImp = null;

        if (!empty($arquivos)) {

            $dtUltImp = reset($arquivos)->getUltimaImportacao();

            function order($a, $b)
            {
                if ($a->getSequencia() == $b->getSequencia())
                    return 0;
                return ($a->getSequencia() < $b->getSequencia()) ? -1 : 1;
            }

            usort($arquivos, 'order');
        }

        $this->view->arquivos = $arquivos;
        $this->view->ultData = ($dtUltImp)?$dtUltImp->format('d/m/Y'):'S/ Registros';

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