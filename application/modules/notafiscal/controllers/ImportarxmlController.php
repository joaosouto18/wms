<?php

use \Wms\Domain\Entity\NotaFiscal,
    \Wms\Domain\Entity\NotaFiscal\Item,
    \Wms\Domain\Entity\NotaFiscalRepository,
    \Wms\Module\Web\Controller\Action\Crud,
    Zend\File\Transfer,
    Zend\Dom\Query,
    Zend\Config\Xml,
    \Wms\Module\Web\Page;

/**
 * Description of Notafiscal_ImportarxmlController
 *
 * @author: Michel Castro <mlaguardia@gmail.com>
 */
class Notafiscal_ImportarxmlController extends Crud
{

    protected $entityName = 'NotaFiscal';
    //@isValid: Boolean variável isValid = true, caso encontre divergências valor = false
    private $isValid = true;
    private $falhas = array();

    /**
     * Configura a Classe
     * Botões Padrões
     *
     */
    private function configure(){

        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Voltar',
                    'cssClass' => 'btnBack',
                    'urlParams' => array(
                        'action' => 'index',
                        'id' => null
                    ),
                    'tag' => 'a'
                )
            )
        ));

    }

    public function indexAction()
    {
        //Seta os botões padrões
        $this->configure();

        $this->isValid = true;
        $this->falhas = array();

        $post = $this->getRequest()->getPost();

        if ( !empty($post) ) {

            if( $_POST['tipo'] != 1 && $_POST['placa'] == "")
                $this->addFlashMessage("error","Importação do tipo SAÍDA ou ENTRADA/SAÍDA, informe também a placa.");
            else {

                /*
                 * tipo 1 = ENTRADA
                 * tipo 2 = SAÍDA
                 * tipo 3 = ENTRADA/SAÍDA
                 */
                $tipo  = $_POST['tipo'];
                $placa = $_POST['placa'];

                // Define um método de transporte
                $upload = new Zend_File_Transfer_Adapter_Http();
                $upload->setDestination(APPLICATION_PATH . '/../data/xml/');

                try {
                    // Recebe o arquivo de upload
                    $upload->receive();
                    $result = $this->validarNota($upload);

                    if ($this->isValid) {
                        if ($tipo == "1" || $tipo == "3")
                            $this->salvarNotaEntrada($result);
                        if ($tipo == "2" || $tipo == "3")
                            $this->salvarNotaSaida($result, $placa);
                    } else {
                        $this->addFlashMessage("error", "Existe(m) problema(s) na importação da nota fiscal: ");
                    }
                } catch (Exception $e) {
                    $this->addFlashMessage("error", "Existe(m) problema(s) na importação da nota fiscal: ");
                    $this->isValid = false;
                    $this->falhas[] = $e->getMessage();
                }
            }
        }
        $this->view->isValid = $this->isValid;
        if(!empty($this->falhas)){
            foreach ($this->falhas as $falha) {
                //echo $falha;
                $this->addFlashMessage("error", $falha);
            }
        }
            //$this->addFlashMessage("error", $this->falhas);
            //$this->view->falhas = $this->falhas;
    }

    private function salvarNotaEntrada($result){
        try {
            /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
            $notaFiscalRepo = $this->_em->getRepository('wms:NotaFiscal');
            $idFornecedor   = trim($result['NotaFiscal']['COD_FORNECEDOR']);
            $numero         = trim($result['NotaFiscal']['NUM_NOTA_FISCAL']);
            $serie          = trim($result['NotaFiscal']['COD_SERIE_NOTA_FISCAL']);
            $dataEmissao    = trim($result['NotaFiscal']['DTH_ENTRADA']);
            $placa          = trim($result['NotaFiscal']['DSC_PLACA_VEICULO']);
            $bonificacao    = 'N';
            $itens          = $result['NotaFiscalItem'];
            $notaFiscalRepo->salvarNota($idFornecedor, $numero, $serie, $dataEmissao, $placa, $itens, $bonificacao, null, 0);
            $this->addFlashMessage("success", "Nota Fiscal $numero / $serie importada com sucesso");

        } catch (Exception $e) {
            $this->addFlashMessage("error", "Não foi possível importar a nota fiscal");
            $this->isValid = false;
            $this->falhas[] = $e->getMessage();
        }
    }

    private function salvarNotaSaida($result, $placaExpedicao){
        try {
            $expedicao = new Wms_WebService_Expedicao();

            $cliente = new cliente();
            $cliente->bairro      = $result['NotaFiscal']['BAIRRO_CLIENTE'];
            $cliente->cidade      = $result['NotaFiscal']['CIDADE_CLIENTE'];
            $cliente->codCliente  = $result['NotaFiscal']['CNPJ_CPF_CLIENTE'];
            $cliente->complemento = $result['NotaFiscal']['COMPLEMENTO_CLIENTE'];
            $cliente->cpf_cnpj    = $result['NotaFiscal']['CNPJ_CPF_CLIENTE'];
            $cliente->logradouro  = $result['NotaFiscal']['LOGRADOURO_CLIENTE'];
            $cliente->nome        = $result['NotaFiscal']['NOME_CLIENTE'];
            $cliente->referencia  = $result['NotaFiscal']['REFERENCIA_CLIENTE'];
            $cliente->numero      = $result['NotaFiscal']['NUMERO_CLIENTE'];
            $cliente->tipoPessoa  = $result['NotaFiscal']['TIPO_CLIENTE'];
            $cliente->uf          = $result['NotaFiscal']['UF_CLIENTE'];
            $cliente->insc        = $result['NotaFiscal']['INSC_CLIENTE'];

            $itinerario = new itinerario();
            $itinerario->idItinerario   = 99;
            $itinerario->nomeItinerario = $result['NotaFiscal']['UF_CLIENTE'];

            $codPedido = $result['NotaFiscal']['NUM_NOTA_FISCAL'];

            $pedido = new pedido();
            $pedido->codPedido = $codPedido;
            $pedido->cliente = $cliente;
            $pedido->itinerario = $itinerario;
            $pedido->linhaEntrega = "";

            $itens    = $result['NotaFiscalItem'];
            $produtos = array();
            foreach ($itens as $item) {
                $produto = new produto();
                $produto->codProduto = $item['idProduto'];
                $produto->grade = $item['grade'];
                $produto->quantidade = $item['quantidade'];
                $produtos[] = $produto;
            }

            $pedido->produtos = $produtos;

            $arrPed = array();
            $arrPed[] = $pedido;

            $pedidos  = new pedidos();
            $pedidos->pedidos = $arrPed;

            $expedicao->enviarPedidos($codPedido, $placaExpedicao, $placaExpedicao, $pedidos);
        } catch (Exception $e) {
            $this->addFlashMessage("error", "Não foi possível importar a nota fiscal");
            $this->isValid = false;
            $this->falhas[] = $e->getMessage();
        }
    }

    /*
     * validarNota: Valida se uma nota é válida ou não
     * @upload: Arquivo de XML
     * return: arrayRetorno: array para cadastro ou validação dos dados
     */
    private function validarNota($upload){

            //define um array para retorno
            $arrayRetorno = array();

            // Pega o cabeçalho de informações do arquivo
            $arquivo = $upload->getFileInfo();

            /*
            //Pega o conteúdo do Arquivo
            $conteudo=file_get_contents($arquivo['arquivo_xml']['tmp_name']);
            */

            //Converte o arquivo XML para um array encadeado
            $config = new Zend_Config_Xml($arquivo['arquivo_xml']['tmp_name']);
            $dados  = $config->toArray();

            /*
            * testa a variável dados
            print "<pre>";
            print_r($dados); die();
            */

            if (!isset($dados["NFe"])){
                if (isset($dados["infNFe"])) {
                    $temp["NFe"] = $dados;
                    $dados = $temp;
                } else {
                    throw new Exception("O arquivo não corresponde à uma Nota Fiscal de Recebimento");
                }
            }

            $versao = $dados["NFe"]["infNFe"]['versao'];

            //verificação se o XML é uma NF-e
            if ( !empty($versao) ){

                //dados de identificação
                if ( !empty ($dados["NFe"]["infNFe"]['ide']) ){
                    $arrayRetorno = array_merge($arrayRetorno,$this->getDadosNota($dados));

                    //dados do produto
                    if ( !empty($dados["NFe"]["infNFe"]['det']) ){
                        $arrayRetorno = array_merge($arrayRetorno,$this->getDadosProduto($dados));
                    }
                }
            }
            return $arrayRetorno;
    }

    /*
     * getDadosNota: Pega os dados de identificação da nota para inserção na tabela NOTA_FISCAL
     *
     * @dados: Array do XML Serializado
     *
     * return: arrayRetorno: array para cadastro ou validação dos dados
     */
    private function getDadosNota($dados){

        //Dados para a Tabela NOTA_FISCAL

        if ( !empty($dados["NFe"]["infNFe"]['ide']['serie']) )
            $arrayRetorno['NotaFiscal']['COD_SERIE_NOTA_FISCAL']=$dados["NFe"]["infNFe"]['ide']['serie'];
        else {
            $arrayRetorno['NotaFiscal']['COD_SERIE_NOTA_FISCAL'] = mt_rand(5, 15);
        }

        if ( !empty($dados["NFe"]["infNFe"]['ide']['dEmi']) || !empty($dados["NFe"]["infNFe"]['ide']['dhEmi']) ){
            $dEmi = !empty($dados["NFe"]["infNFe"]['ide']['dEmi']) ? $dados["NFe"]["infNFe"]['ide']['dEmi'] : $dados["NFe"]["infNFe"]['ide']['dhEmi'];
            $dataEmissao=  date_create_from_format('Y-m-d', $dEmi);
            $arrayRetorno['NotaFiscal']['DAT_EMISSAO'] = $dataEmissao->format('d/m/Y');
        }
        else {
            $this->isValid=false;
            $arrayRetorno['NotValid']['tags'][]='dEmi';
            $this->falhas[]['Data de Emissão inválida | Data: '] = null;
        }

        $arrayRetorno['NotaFiscal']['COD_STATUS']=NotaFiscal::STATUS_INTEGRADA;
        $arrayRetorno['NotaFiscal']['COD_RECEBIMENTO']="NULL";

        if ( !empty($dados["NFe"]["infNFe"]['emit']['CNPJ']) ){

            $sql = "
                SELECT f.COD_EXTERNO AS COD_FORNECEDOR
                    FROM fornecedor f
                    INNER JOIN pessoa_juridica p ON (f.COD_FORNECEDOR = p.COD_PESSOA)
                    WHERE (
                          p.NUM_CNPJ='".$dados["NFe"]["infNFe"]['emit']['CNPJ']."'
                        )
                   GROUP BY f.COD_EXTERNO";

            $array = $this->em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

            if ( !empty($array[0]['COD_FORNECEDOR']) ){
                $arrayRetorno['NotaFiscal']['COD_FORNECEDOR']=$array[0]['COD_FORNECEDOR'];
            } else {
                $this->isValid=false;
                $arrayRetorno['NotValid']['tags'][]='CNPJ';
                $arrayRetorno['NotValid']['valores']['CNPJ']=$dados["NFe"]["infNFe"]['emit']['CNPJ'];
                $this->falhas[] = "Não foi possível encontrar nenhum fornecedor com o CNPJ informado | CNPJ :" . $dados["NFe"]["infNFe"]['emit']['CNPJ'];
            }

        } else {
            $this->isValid=false;
            $arrayRetorno['NotValid']['tags'][]='CNPJ';
            $arrayRetorno['NotValid']['valores']['CNPJ']=$dados["NFe"]["infNFe"]['emit']['CNPJ'];
            $this->falhas[] = "CNPJ Inválido | CNPJ: " . $dados["NFe"]["infNFe"]['emit']['CNPJ'];
        }


        // pega as informações do destinatário para notas do tipo SAÍDA
        if ( !empty($dados["NFe"]["infNFe"]['dest']['CNPJ']) ){

            if( (isset($dados["NFe"]["infNFe"]['dest']['CNPJ'])) && (!empty($dados["NFe"]["infNFe"]['dest']['CNPJ']))) {
                $documento = $dados["NFe"]["infNFe"]['dest']['CNPJ'];
                $tipoPessoa = 'J';
            }
            elseif( (isset($dados["NFe"]["infNFe"]['dest']['CPF'])) && (!empty($dados["NFe"]["infNFe"]['dest']['CPF']))) {
                $documento = $dados["NFe"]["infNFe"]['dest']['CNPJ'];
                $tipoPessoa = 'F';
            }

            $arrayRetorno['NotaFiscal']['NOME_CLIENTE']        = $dados["NFe"]["infNFe"]['dest']['xNome'];
            $arrayRetorno['NotaFiscal']['LOGRADOURO_CLIENTE']  = $dados["NFe"]["infNFe"]['dest']['enderDest']['xLgr'];
            $arrayRetorno['NotaFiscal']['NUMERO_CLIENTE']      = $dados["NFe"]["infNFe"]['dest']['enderDest']['nro'];
            $arrayRetorno['NotaFiscal']['COMPLEMENTO_CLIENTE'] = $dados["NFe"]["infNFe"]['dest']['enderDest']['xCpl'];
            $arrayRetorno['NotaFiscal']['BAIRRO_CLIENTE']      = $dados["NFe"]["infNFe"]['dest']['enderDest']['xBairro'];
            $arrayRetorno['NotaFiscal']['CIDADE_CLIENTE']      = $dados["NFe"]["infNFe"]['dest']['enderDest']['xMun'];
            $arrayRetorno['NotaFiscal']['UF_CLIENTE']          = $dados["NFe"]["infNFe"]['dest']['enderDest']['UF'];
            $arrayRetorno['NotaFiscal']['CEP_CLIENTE']         = $dados["NFe"]["infNFe"]['dest']['enderDest']['CEP'];
            $arrayRetorno['NotaFiscal']['PAIS_CLIENTE']        = $dados["NFe"]["infNFe"]['dest']['enderDest']['xPais'];
            $arrayRetorno['NotaFiscal']['EMAIL_CLIENTE']       = $dados["NFe"]["infNFe"]['dest']['email'];
            $arrayRetorno['NotaFiscal']['INSC_CLIENTE']        = $dados["NFe"]["infNFe"]['dest']['IE'];
            $arrayRetorno['NotaFiscal']['TIPO_CLIENTE']        = $tipoPessoa;
            $arrayRetorno['NotaFiscal']['CNPJ_CPF_CLIENTE']    = $documento;
            $arrayRetorno['NotaFiscal']['REFERENCIA_CLIENTE']  = '';

        } else {
            $this->isValid=false;
            $arrayRetorno['NotValid']['tags'][]='CNPJ';
            $arrayRetorno['NotValid']['valores']['CNPJ']=$dados["NFe"]["infNFe"]['dest']['CNPJ'];
            $this->falhas[] = "CNPJ Inválido | CNPJ: " . $dados["NFe"]["infNFe"]['dest']['CNPJ'];
        }

        $arrayRetorno['NotaFiscal']['NUM_NOTA_FISCAL']=$dados["NFe"]["infNFe"]['ide']['nNF'];
        $arrayRetorno['NotaFiscal']['COD_NOTA_FISCAL']="SQ_NOTA_FISCAL_01.NEXTVAL";

        if ( !empty($dados["NFe"]["infNFe"]['transp']['veicTransp']['placa']) )
            $arrayRetorno['NotaFiscal']['DSC_PLACA_VEICULO']=$dados["NFe"]["infNFe"]['transp']['veicTransp']['placa'];
        else
            $arrayRetorno['NotaFiscal']['DSC_PLACA_VEICULO']="AAA0000";

        $arrayRetorno['NotaFiscal']['COD_NOTA_FISCAL']="SQ_NOTA_FISCAL_01.NEXTVAL";


        $arrayRetorno['NotaFiscal']['DTH_ENTRADA']=date("d/m/Y");
        $arrayRetorno['NotaFiscal']['IND_BONIFICACAO']="NULL";
        $arrayRetorno['NotaFiscal']['COD_FILIAL']="NULL";


        return $arrayRetorno;

    }

    /*
     * getDadosProduto: Pega os dados do produto para inserção na tabela NOTA_FISCAL_ITEM
     *
     * @dados: Array do XML Serializado
     * return: arrayRetorno: array para cadastro ou validação dos dados
     */
    private function getDadosProduto($dados){

        //Dados para a Tabela NOTA_FISCAL_ITEM

        if ( empty($dados["NFe"]["infNFe"]['det'][0]) ){

            $detalhes=$dados["NFe"]["infNFe"]['det'];
            unset($dados["NFe"]["infNFe"]['det']);
            $dados["NFe"]["infNFe"]['det'][0]=$detalhes;

        }

        $numProdutos=count($dados["NFe"]["infNFe"]['det']);

        for ($qtdProduto=0; $qtdProduto<$numProdutos; $qtdProduto++){

            $filiaLInterna = $this->filialInterna($dados);

            if ( !empty($dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cEAN']) ){

                //pega o produto pelo código de barras
                $sql = "
                SELECT prd.COD_PRODUTO,prd.DSC_GRADE, NVL(prde.qtd_embalagem,1) QTD_EMBALAGEM
                    FROM produto prd
                    LEFT JOIN produto_embalagem prde ON (prd.COD_PRODUTO = prde.COD_PRODUTO AND prd.DSC_GRADE = prde.DSC_GRADE)
                    LEFT JOIN produto_volume prdv ON (prd.COD_PRODUTO = prdv.COD_PRODUTO AND prd.DSC_GRADE = prdv.DSC_GRADE)
                    WHERE (
                          (
                            prde.COD_BARRAS='".$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cEAN']."' OR
                            prdv.COD_BARRAS='".$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cEAN']."'
                          )
                        )
                   ";

                $codigoProdutoNF = $dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cProd'];
                list($arrayReferencia, $arrayCodFornecedores) = $this->getCodigoFornecedorEReferencia($codigoProdutoNF);

                $array = $this->em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                if ( !empty($array[0]['COD_PRODUTO']) ){
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['idProduto']=$array[0]['COD_PRODUTO'];
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['grade']=$array[0]['DSC_GRADE'];
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['qtdEmbalagem'] = $array[0]['QTD_EMBALAGEM'];
                } elseif ( !empty($arrayReferencia[0]['COD_PRODUTO'])){
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['idProduto'] = $arrayReferencia[0]['COD_PRODUTO'];
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['grade'] = $arrayReferencia[0]['DSC_GRADE'];
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['qtdEmbalagem'] = $arrayReferencia[0]['QTD_EMBALAGEM'];
                } elseif (!empty($arrayCodFornecedores[0]['COD_PRODUTO'])) {
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['idProduto'] = $arrayCodFornecedores[0]['COD_PRODUTO'];
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['grade'] = $arrayCodFornecedores[0]['DSC_GRADE'];
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['qtdEmbalagem'] = $arrayCodFornecedores[0]['QTD_EMBALAGEM'];
                } else {
                    $this->isValid=false;
                    $ean = $dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cEAN'];
                    $dscProduto = $dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['xProd'];
                    $qtd = (int)$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['qCom'];
                    $arrayRetorno['NotValid']['tags'][]='cEAN';
                    $arrayRetorno['NotValid']['valores']['cEAN'][]=$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cEAN'];
                    $arrayRetorno['NotValid']['valores']['DSC_PRODUTO'][]=$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['xProd'];
                    $arrayRetorno['NotValid']['valores']['Grade'][]='UNICA';
                    $arrayRetorno['NotValid']['valores']['QTD_ITEM'][]=(int)$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['qCom'];
                    $this->falhas[] = "Produto não encontrado | EAN: " .$ean . "   DESCRIÇÃO: " . $dscProduto ."   QTD: " . $qtd;
                }
            }
            else if ($filiaLInterna == true) {

                $codigoProdutoNF = $dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cProd'];
                $sql = "
                SELECT COD_PRODUTO, DSC_GRADE
                    FROM PRODUTO P
                    WHERE P.COD_PRODUTO = '$codigoProdutoNF'
                ";
                $array = $this->em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

                if ( !empty($array[0]['COD_PRODUTO']) ){
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['idProduto']=$array[0]['COD_PRODUTO'];
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['grade']=$array[0]['DSC_GRADE'];
                } else {
                    $this->isValid=false;
                    $ean = $dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cEAN'];
                    $dscProduto = $dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['xProd'];
                    $qtd = (int)$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['qCom'];
                    $arrayRetorno['NotValid']['tags'][]='cEAN';
                    $arrayRetorno['NotValid']['valores']['cEAN'][]=$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cEAN'];
                    $arrayRetorno['NotValid']['valores']['DSC_PRODUTO'][]=$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['xProd'];
                    $arrayRetorno['NotValid']['valores']['Grade'][]='UNICA';
                    $arrayRetorno['NotValid']['valores']['QTD_ITEM'][]=(int)$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['qCom'];
                    $this->falhas[] = "Produto não encontrado | EAN: " .$ean . "   DESCRIÇÃO: " . $dscProduto ."   QTD: " . $qtd;
                }

            }
            else if ($filiaLInterna == false) {

                $codigoProdutoNF = $dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cProd'];
                list($arrayReferencia, $arrayCodFornecedores) = $this->getCodigoFornecedorEReferencia($codigoProdutoNF);

                if ( !empty($arrayReferencia[0]['COD_PRODUTO'])){
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['idProduto'] = $arrayReferencia[0]['COD_PRODUTO'];
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['grade'] = $arrayReferencia[0]['DSC_GRADE'];
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['qtdEmbalagem'] = $arrayReferencia[0]['QTD_EMBALAGEM'];
                } elseif (!empty($arrayCodFornecedores[0]['COD_PRODUTO'])) {
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['idProduto'] = $arrayCodFornecedores[0]['COD_PRODUTO'];
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['grade'] = $arrayCodFornecedores[0]['DSC_GRADE'];
                    $arrayRetorno['NotaFiscalItem'][$qtdProduto]['qtdEmbalagem'] = $arrayCodFornecedores[0]['QTD_EMBALAGEM'];
                }
                else {
                    $this->isValid=false;
                    $ean = $dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cEAN'];
                    $dscProduto = $dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['xProd'];
                    $qtd = (int)$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['qCom'];
                    $arrayRetorno['NotValid']['tags'][]='cEAN';
                    $arrayRetorno['NotValid']['valores']['cEAN'][]=$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cEAN'];
                    $arrayRetorno['NotValid']['valores']['DSC_PRODUTO'][]=$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['xProd'];
                    $arrayRetorno['NotValid']['valores']['Grade'][]='UNICA';
                    $arrayRetorno['NotValid']['valores']['QTD_ITEM'][]=(int)$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['qCom'];
                    $this->falhas[] = "Produto não encontrado | EAN: " .$ean . "   DESCRIÇÃO: " . $dscProduto ."   QTD: " . $qtd;
                }
            }
            else {
                $this->isValid=false;
                $ean = $dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cEAN'];
                $dscProduto = $dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['xProd'];
                $qtd = (int)$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['qCom'];

                $arrayRetorno['NotValid']['tags'][]='cEAN';
                $arrayRetorno['NotValid']['valores']['cEAN'][]=$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['cEAN'];
                $arrayRetorno['NotValid']['valores']['DSC_PRODUTO'][]=$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['xProd'];
                $arrayRetorno['NotValid']['valores']['Grade'][]='UNICA';
                $arrayRetorno['NotValid']['valores']['QTD_ITEM'][]=(int)$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['qCom'];
                $this->falhas[] = "Dados do Produto Inválidos | EAN: " .$ean . "   DESCRIÇÃO: " . $dscProduto ."   QTD: " . $qtd;
            }

            if ( !empty($dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['qCom']) )
                $arrayRetorno['NotaFiscalItem'][$qtdProduto]['quantidade']=(int)$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['qCom'];
            else {
                $this->isValid=false;
                $arrayRetorno['NotValid']['tags'][]='qCom';
                $arrayRetorno['NotValid']['valores']['qCom'][]=$dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['qCom'];
                $this->falhas[] = "Quantidade não informada | Qtd: " . $dados["NFe"]["infNFe"]['det'][$qtdProduto]['prod']['qCom'];
            }
        }

        return $arrayRetorno;
    }

    public function filialInterna($dados)
    {
        //se o CNPJ do emitente da nota fiscal for uma das filiais do sistema, pegar pelo código referente do sistema
        $cnpjEmitenteNF = $dados["NFe"]["infNFe"]['emit']['CNPJ'];
        $sql = "
                SELECT COD_FILIAL
                    FROM FILIAL F INNER JOIN PESSOA_JURIDICA PJ ON F.COD_FILIAL = PJ.COD_PESSOA
                    WHERE PJ.NUM_CNPJ = '$cnpjEmitenteNF'
           ";
        $resultado = $this->em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($resultado) > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param $codigoProdutoNF
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    private function getCodigoFornecedorEReferencia($codigoProdutoNF)
    {
        $sql = "
                SELECT P.COD_PRODUTO, P.DSC_GRADE, PE.QTD_EMBALAGEM
                    FROM PRODUTO P
                    LEFT JOIN FORNECEDOR_REFERENCIA FR ON FR.ID_PRODUTO = P.ID_PRODUTO
                    LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = FR.COD_PRODUTO_EMBALAGEM
                    WHERE P.DSC_REFERENCIA = '$codigoProdutoNF'
                ";
        $arrayReferencia = $this->em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        $sql2 = "SELECT P.COD_PRODUTO, P.DSC_GRADE, PE.QTD_EMBALAGEM
                        FROM PRODUTO P INNER JOIN FORNECEDOR_REFERENCIA FR ON FR.ID_PRODUTO = P.ID_PRODUTO
                        LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = FR.COD_PRODUTO_EMBALAGEM
                        WHERE FR.DSC_REFERENCIA = '$codigoProdutoNF' ";

        $arrayCodFornecedores = $this->em->getConnection()->query($sql2)->fetchAll(\PDO::FETCH_ASSOC);

        return array($arrayReferencia, $arrayCodFornecedores);
    }

}