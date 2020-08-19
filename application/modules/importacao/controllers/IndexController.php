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
        "exception" => null,
        "object" => "",
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

                    $embalagemEntity = null;
                    if ($arrRegistro['codigoBarras'] != "") {
                        $codigoBarras = \Wms\Util\CodigoBarras::formatarCodigoEAN128Embalagem($arrRegistro['codigoBarras']);
                        $embalagemEntity = $embalagemRepo->findOneBy(array('codigoBarras' => $codigoBarras));
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

                    if (!empty($arrRegistro['endereco'])) {
                        $arrQtdDigitos = \Wms\Util\Endereco::getQtdDigitos();
                        if (isset($arrRegistro['parseTo']) && !empty($arrRegistro['parseTo'])) {
                            $parser = explode(";",$arrRegistro['parseTo']);

                            $enderecoUnknow = explode(".",str_replace(",", ".", $arrRegistro['endereco']));
                            $enderecoParsed = array();
                            foreach ($parser as $index) {
                                $enderecoParsed[] = $enderecoUnknow[$index - 1];
                            }
                            $arrRegistro['endereco'] = implode(".", $enderecoParsed);
                        }
                        $endereco = \Wms\Util\Endereco::formatar($arrRegistro['endereco'], null, $arrQtdDigitos);
                        $arrRegistro['endereco'] = $endereco;
                        $arrDados = \Wms\Util\Endereco::separar($endereco, $arrQtdDigitos);

                        $enderecoEn = $em->getRepository('wms:Deposito\Endereco')->findOneBy($arrDados);

                        if (empty($enderecoEn)) {
                            $arrErroRows[$linha] = "Endereço $arrRegistro[endereco] do produto $arrRegistro[codProduto] não foi encontrado";
                        }

                        $arrRegistro['enderecoEn'] = $enderecoEn;
                    }

                    $result = $importacaoService->saveEmbalagens($em, $arrRegistro, $repositorios);
                    if (is_string($result)) {
                        $arrErroRows['exception'] = $result;
                    } else {
                        $countFlush++;
                    }
                    break;
                case 'fornecedor';

                    if (isset($arrRegistro['verificador']) and $arrRegistro['verificador'] == 'N') {
                        break;
                    }

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
                    $fornecedor = $fornecedorRepo->findOneBy(array('codExterno' => $arrRegistro['idExterno']));
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
                    $arrQtdDigitos = \Wms\Util\Endereco::getQtdDigitos();
                    if (isset($arrRegistro['parseTo']) && !empty($arrRegistro['parseTo'])) {
                        $parser = explode(";",$arrRegistro['parseTo']);
                        $enderecoUnknow = explode(".",str_replace(",", ".", $arrRegistro['endereco']));
                        $enderecoParsed = array();
                        foreach ($parser as $index) {
                            $enderecoParsed[] = $enderecoUnknow[$index - 1];
                        }
                        $arrRegistro['endereco'] = implode(".", $enderecoParsed);
                    }
                    $arrRegistro['endereco'] = \Wms\Util\Endereco::formatar($arrRegistro['endereco'], null, $arrQtdDigitos);
                    $arrEndereco = \Wms\Util\Endereco::separar($arrRegistro['endereco'], $arrQtdDigitos);
                    $arrRegistro = array_merge($arrRegistro, $arrEndereco);

                    $entity = $em->getRepository('wms:Deposito\Endereco')->findOneBy($arrEndereco);

                    if (empty($entity)) {
                        $result = $importacaoService->saveEndereco($em, $arrRegistro);
                        if (is_string($result)) {
                            $arrErroRows['exception'] = $result;
                        } else {
                            $countFlush++;
                        }
                    } else {
                        $arrErroRows[$linha] = "Este endereço já foi cadastrado " . $endereco;
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

                case "inventarioProduto":

                    $inventarioEn = $arrRegistro['inventarioEn'];
                    unset($arrRegistro['inventarioEn']);

                    $registro = http_build_query($arrRegistro, '', ' ');

                    if (!in_array($registro, $checkArray)) {
                        array_push($checkArray, $registro);
                    } else {
                        $arrErroRows[$linha] = "Item repetido no inventario: " . $registro;
                        break;
                    }

                    $arrRegistro['inventarioEn'] = $inventarioEn;

                    $result = $importacaoService->saveInventarioProduto($em, $arrRegistro, $repositorios);
                    if (is_string($result)) {
                        $arrErroRows['exception'] = $result;
                    } else {
                        $countFlush++;
                    }
                    break;
                case 'estoqueErp':
                    try {
                        if ($arrRegistro['ESTOQUE_DISPONIVEL'] > 0) {

                            $valorEstoque = array_change_key_case($arrRegistro, CASE_UPPER);
                            $codProduto = (int) $arrRegistro['COD_PRODUTO'];
                            $grade = $arrRegistro['GRADE'];

                            if (isset($valorEstoque['GRADE'])) {
                                $grade = utf8_encode($valorEstoque['GRADE']);
                            }

                            $produtoEn = $produtoRepo->findOneBy(array('id' => $codProduto, 'grade' => $grade));
                            if ($produtoEn != null) {
                                $estoqueErp = new \Wms\Domain\Entity\Enderecamento\EstoqueErp();
                                $estoqueErp->setProduto($produtoEn);
                                $estoqueErp->setCodProduto($codProduto);
                                $estoqueErp->setGrade($grade);
                                $estoqueErp->setEstoqueDisponivel(str_replace(',', '.', $arrRegistro['ESTOQUE_DISPONIVEL']));
                                $estoqueErp->setEstoqueAvaria(str_replace(',', '.', $arrRegistro['ESTOQUE_AVARIA']));
                                $estoqueErp->setEstoqueGerencial(str_replace(',', '.', $arrRegistro['ESTOQUE_GERENCIAL']));
                                $estoqueErp->setFatorUnVenda(str_replace(',', '.', $arrRegistro['FATOR_UNIDADE_VENDA']));
                                $estoqueErp->setUnVenda($arrRegistro['DSC_UNIDADE']);
                                $estoqueErp->setVlrEstoqueTotal(str_replace(',', '.', $arrRegistro['VALOR_ESTOQUE']));
                                $estoqueErp->setVlrEstoqueUnitario(str_replace(',', '.', $arrRegistro['CUSTO_UNITARIO']));
                                $this->_em->persist($estoqueErp);
                            }
                            $countFlush++;
                        }
                    }catch (Exception $e){
                        $arrErroRows['exception'] = $e->getMessage();
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
            $volumeRepo = $em->getRepository('wms:Produto\Volume');
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
            $invEnderecoRepo = $this->_em->getRepository('wms:Inventario\Endereco');
            $invEndProdRepo = $this->_em->getRepository('wms:Inventario\EnderecoProduto');
            $inventarioRepo = $this->_em->getRepository('wms:Inventario');
            $vSaldoCompletoRepo = $this->_em->getRepository('wms:Enderecamento\VSaldoCompleto');

            $repositorios = array(
                'produtoRepo' => $produtoRepo,
                'enderecoRepo' => $enderecoRepo,
                'fabricanteRepo' => $fabricanteRepo,
                'classeRepo' => $classeRepo,
                'embalagemRepo' => $embalagemRepo,
                'volumeRepo' => $volumeRepo,
                'pjRepo' => $pJuridicaRepo,
                'pfRepo' => $pFisicaRepo,
                'fornecedorRepo' => $fornecedorRepo,
                'referenciaRepo' => $referenciaRepo,
                'normaPaletizacaoRepo' => $normaPaletizacaoRepo,
                'dadoLogisticoRepo' => $dadoLogisticoRepo,
                'cargaRepo' => $cargaRepo,
                'clienteRepo' => $clienteRepo,
                'invEnderecoRepo' => $invEnderecoRepo,
                'invEndProdRepo' => $invEndProdRepo,
                'vSaldoCompletoRepo' => $vSaldoCompletoRepo
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
                $this->statusProgress['object'] = $arquivo->getNomeInput();

                $inventarioEn = null;
                if ($tabelaDestino === "inventarioProduto") {
                    $inventarioEn = $inventarioRepo->save();
                }

                if ($tabelaDestino === 'estoqueErp') {
                    $query = $this->_em->createQuery("DELETE FROM wms:Enderecamento\EstoqueErp");
                    $query->execute();
                }

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
                            continue;
                        }

                        if (ucfirst($cabecalho) == 'S') {
                            $this->statusProgress["iLinha"] = $linha - 1;
                        } else {
                            $this->statusProgress["iLinha"] = $linha;
                        }

                        $arrRegistro = array();

                        if (!empty($inventarioEn)) {
                            $arrRegistro['inventarioEn'] = $inventarioEn;
                        }

                        /** @var \Wms\Domain\Entity\Importacao\Campos $campo */
                        foreach ($camposArquivo as $campo) {
                            $coluna = $campo->getPosicaoTxt();
                            if (($coluna == null) || ($tColunas - 1 < $coluna)) {
                                $valorCampo = trim($campo->getValorPadrao());
                            } else {
                                $valorCampo = $objExcel->getActiveSheet()->getCellByColumnAndRow($coluna, $linha)->getFormattedValue();
                                if ((strtolower($campo->getNomeCampo()) != "cpf_cnpj")
                                    or (strtolower($campo->getNomeCampo()) != "cpf")
                                    or (strtolower($campo->getNomeCampo()) != "cnpj"))
                                {
                                    $valorCampo = trim($valorCampo);
                                }

                                $valorCampo = utf8_encode($valorCampo);
                                if (empty($valorCampo)) {
                                    if ($campo->getPreenchObrigatorio() === "n") {
                                        $valorCampo = trim($campo->getValorPadrao());
                                    } else {
                                        throw new Exception("Campo: " . $campo->getNomeCampo() . " - não pode ser nulo.");
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

                        if ($countFlush >= 1) {
                            $countFlush = 0;
//                            $em->flush();
//                            $em->clear();
                        }
                    }
                } else {

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
            $this->statusProgress["success"] = true;
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
        $this->view->ultData = ($dtUltImp) ? $dtUltImp->format('d/m/Y') : 'S/ Registros';
    }
}