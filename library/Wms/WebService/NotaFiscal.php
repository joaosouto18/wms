<?php

use Wms\Domain\Entity\NotaFiscal as NotaFiscalEntity;
use \Wms\Domain\Entity\Pessoa\Papel as Papel;

class TypeOfParam
{

    public static function getType($destination)
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $paramRepo = $em->getRepository('wms:Sistema\Parametro');
        /** @var Wms\Domain\Entity\Sistema\Parametro $parametro */
        $parametro = $paramRepo->findOneBy(array('constante' => $destination));

        if (!empty($parametro))
            return $parametro->getValor();

        throw new Exception("Não foi definido o parâmetro $destination");
    }
}
class Item {
    /** @var string */
    public $idProduto;
    /** @var string */
    public $grade;
    /** @var double */
    public $quantidade;
    /** @var double */
    public $peso;
    /** @var string */
    public $lote;
}

class Itens {
    /** @var Item[] */
    public $itens = array();
}

class itensNf {
    /** @var string */
    public $idProduto;
    /** @var string */
    public $quantidade;
    /** @var string */
    public $grade;
    /** @var string */
    public $quantidadeConferida;
    /** @var string */
    //public $peso;
    /** @var string */
    public $quantidadeAvaria;
    /** @var string */
    public $motivoDivergencia;
    /** @var string */
    public $lote;
}

class notaFiscal {
    /** @var string */
    public $idRecebimeto;
    /** @var string */
    public $idFornecedor;
    /** @var string */
    public $numero;
    /** @var string */
    public $serie;
    /** @var string */
    public $dataEmissao;
    /** @var string */
    public $placa;
    /** @var string */
    public $status;
    /** @var string */
    public $dataEntrada;
    /** @var string */
    public $bonificacao;
    /** @var string */
    public $peso;
    /** @var string */
    public $tipoNota;
    /** @var itensNf[] */
    public $itens = array();
}

class Wms_WebService_NotaFiscal extends Wms_WebService
{

    /**
     * <h3>=========================== ATENÇÃO ===========================</h3>
     * <h3>Este método é depreciado e será removido em futuras versões</h3>
     * <h3>==============================================================</h3>
     *
     * Método para consultar no WMS uma Nota Fiscal específica
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idFornecedor</b> - OBRIGATÓRIO Código do Emissor da nota (Préviamente cadastrado, como Fornecedor ou Cliente caso devolução) <br>
     * <b>numero</b> - OBRIGATÓRIO Número Nota Fiscal<br>
     * <b>serie</b> - OBRIGATÓRIO Serie da Nota Fiscal<br>
     * <b>dataEmissao</b> - OPCIONAL Data de emissao da nota fiscal. Formato esperado (DD/MM/AAAA) ex:'22/11/2010'<br>
     * <b>tipoNota</b> - OPCIONAL Caso não especificado, o sistema atribuirá como uma nota de compra.
     * Os códigos identificadores dos tipos de nota deve ser definidos previamente entre ERP e WMS Imperium<br>
     * <b>idStatus</b> - OBRIGATÓRIO Código do status da nota à consultar.
     * </p>
     *
     * @param string $idFornecedor Codigo do fornecedor
     * @param string $tipoNota Tipo da Nota Fiscal
     * @param string $numero Numero da nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @param integer $idStatus Codigo do status da nota fiscal no wms
     * @return array
     * @throws Exception
     */
    public function buscar($idFornecedor, $numero, $serie, $dataEmissao, $idStatus, $tipoNota)
    {

        $idEmissor = trim($idFornecedor);
        $numero = trim($numero);
        $serieTrim = trim($serie);
        $serie = (!empty($serieTrim))? $serieTrim : "0";
        $dataEmissao  = trim($dataEmissao);
        $idStatus = trim($idStatus);
        $tipoNota = trim($tipoNota);

        $em = $this->__getDoctrineContainer()->getEntityManager();

        list($emissorEn, $tipoNotaEn) = self::getEmissorAndTipo($em, $idEmissor, $tipoNota);

        /** @var \Wms\Domain\Entity\NotaFiscal $notaFiscalEntity */
        $notaFiscalEntity = $em->getRepository('wms:NotaFiscal')->findOneBy(array(
            'emissor' => $emissorEn,
            'numero' => $numero,
            'serie' => $serie,
            'tipo' => $tipoNotaEn,
            'status' => $idStatus
        ));

        if ($notaFiscalEntity == null)
            throw new \Exception('NotaFiscal não encontrada');

        $itemsNF = $em->getRepository('wms:NotaFiscal')->getConferencia($emissorEn->getId(), $numero, $serie, $dataEmissao, $idStatus);

        $itens = array();
        foreach ($itemsNF as $item) {
            $itens[] = array(
                'idProduto' => $item['COD_PRODUTO'],
                'quantidade' => $item['QTD_ITEM'],
                'grade' => $item['DSC_GRADE'],
                'quantidadeConferida' => $item['QTD_CONFERIDA'],
                'quantidadeAvaria' => $item['QTD_AVARIA'],
                'motivoDivergencia' => $item['DSC_MOTIVO_DIVER_RECEB'],
                'peso' => $item['PESO_ITEM']
            );
        }

        //verifica se existe recebimento, senao seta 0 no codigo do recebimento
        $idRecebimento = ($notaFiscalEntity->getRecebimento()) ? $notaFiscalEntity->getRecebimento()->getId() : 0;

        $dataEntrada = ($notaFiscalEntity->getDataEntrada()) ? $notaFiscalEntity->getDataEntrada()->format('d/m/Y') : '';

        return $result =  array(
            'idRecebimento' => $idRecebimento,
            'idFornecedor' => $notaFiscalEntity->getEmissor()->getId(),
            'numero' => $notaFiscalEntity->getNumero(),
            'serie' => $notaFiscalEntity->getSerie(),
            'dataEmissao' => $notaFiscalEntity->getDataEmissao()->format('d/m/Y'),
            'placa' => $notaFiscalEntity->getPlaca(),
            'status' => $notaFiscalEntity->getStatus()->getSigla(),
            'pesoTotal' => $notaFiscalEntity->getPesoTotal(),
            'dataEntrada' => $dataEntrada,
            'bonificacao' => $notaFiscalEntity->getBonificacao(),
            'itens' => $itens
        );
    }

    /**
     * Método para consultar no WMS uma Nota Fiscal específica
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idFornecedor</b> - OBRIGATÓRIO Código do Emissor da nota (Préviamente cadastrado, como Fornecedor ou Cliente caso devolução) <br>
     * <b>numero</b> - OBRIGATÓRIO Número Nota Fiscal<br>
     * <b>serie</b> - OBRIGATÓRIO Serie da Nota Fiscal<br>
     * <b>dataEmissao</b> - OPCIONAL Data de emissao da nota fiscal. Formato esperado (DD/MM/AAAA) ex:'22/11/2010'<br>
     * <b>tipoNota</b> - OPCIONAL Caso não especificado, o sistema atribuirá como uma nota de compra.
     * Os códigos identificadores dos tipos de nota deve ser definidos previamente entre ERP e WMS Imperium<br>
     * </p>
     *
     * @param string $idFornecedor Codigo do fornecedor
     * @param string $numero Numero da nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @param string $tipoNota Tipo de nota
     * @return notaFiscal
     * @throws Exception
     */
    public function buscarNf($idFornecedor, $numero, $serie, $dataEmissao, $tipoNota = null)
    {

        $idEmissor = trim($idFornecedor);
        $numero = trim($numero);
        $serieTrim = trim($serie);
        $serie = (!empty($serieTrim))? $serieTrim : "0";
        $dataEmissao = trim($dataEmissao);
        $tipoNota = trim ($tipoNota);

        $em = $this->__getDoctrineContainer()->getEntityManager();

        list($emissorEn, $tipoNotaEn) = self::getEmissorAndTipo($em, $idEmissor, $tipoNota);

        /** @var \Wms\Domain\Entity\NotaFiscal $notaFiscalEntity */
        $notaFiscalEntity = $em->getRepository('wms:NotaFiscal')->findOneBy(array(
            'emissor' => $emissorEn,
            'numero' => $numero,
            'serie' => $serie,
            'tipo' => $tipoNotaEn
        ));

        if ($notaFiscalEntity == null)
            throw new \Exception('NotaFiscal não encontrada');

        $itemsNF = $em->getRepository('wms:NotaFiscal')->getConferencia($emissorEn->getId(), $numero, $serie, $dataEmissao, $notaFiscalEntity->getStatus()->getId());

        $clsNf = new notaFiscal();
        foreach ($itemsNF as $item) {
            $clsItensNf = new itensNf();
            $clsItensNf->idProduto = $item['COD_PRODUTO'];
            $clsItensNf->quantidade = $item['QTD_ITEM'];
            $clsItensNf->grade = $item['DSC_GRADE'];
            $clsItensNf->quantidadeConferida = $item['QTD_CONFERIDA'];
            $clsItensNf->quantidadeAvaria = $item['QTD_AVARIA'];
            $clsItensNf->motivoDivergencia = $item['DSC_MOTIVO_DIVER_RECEB'];
            $clsItensNf->lote = $item['LOTE'];
            $clsNf->itens[] = $clsItensNf;
        }

        //verifica se existe recebimento, senao seta 0 no codigo do recebimento
        $idRecebimento = ($notaFiscalEntity->getRecebimento()) ? $notaFiscalEntity->getRecebimento()->getId() : 0;

        $dataEntrada = ($notaFiscalEntity->getDataEntrada()) ? $notaFiscalEntity->getDataEntrada()->format('d/m/Y') : '';

        $clsNf->idRecebimeto = $idRecebimento;
        $clsNf->idFornecedor = $notaFiscalEntity->getEmissor()->getCodExterno();
        $clsNf->numero = $notaFiscalEntity->getNumero();
        $clsNf->serie = $notaFiscalEntity->getSerie();
        $clsNf->tipoNota = $notaFiscalEntity->getTipo()->getCodExterno();
        $clsNf->pesoTotal = $notaFiscalEntity->getPesoTotal();
        $clsNf->dataEmissao = $notaFiscalEntity->getDataEmissao()->format('d/m/Y');
        $clsNf->placa = $notaFiscalEntity->getPlaca();
        $clsNf->dataEntrada = $dataEntrada;
        $clsNf->bonificacao = $notaFiscalEntity->getBonificacao();
        $clsNf->status = $notaFiscalEntity->getStatus()->getSigla();

        if ($notaFiscalEntity->getStatus()->getId() == \Wms\Domain\Entity\NotaFiscal::STATUS_RECEBIDA) {
            /** @var \Wms\Domain\Entity\Sistema\Parametro $retornaEnderecado */
            $retornaEnderecado = $em->getRepository("wms:Sistema\Parametro")->findOneBy(array('constante' => "STATUS_RECEBIMENTO_ENDERECADO"));
            if (!empty($retornaEnderecado) && $retornaEnderecado->getValor() == "S") {
                /** @var \Wms\Domain\Entity\RecebimentoRepository $recebimentoRepo */
                $recebimentoRepo = $em->getRepository("wms:Recebimento");
                $result = $recebimentoRepo->checkRecebimentoEnderecado($idRecebimento);
                if (empty($result)) {
                    $clsNf->status = "ENDERECADO";
                }
            }
        }

        return $clsNf;
    }


    /**
     * Se a Nota Fiscal já existe e já foi recebida (conferida e recebimento finalizado), retornará uma <b>Exception</b>
     *
     * <p>Se a Nota Fiscal já existe e está em recebimento, caso um dos itens alterados já tiver sido conferido por completo, retornará uma <b>Exception</b></p>
     *
     * <p>Se a Nota Fiscal não existe ou já existe mas não está em recebimento, o sistema irá atualizar as informações</p>
     * 
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idFornecedor</b> - OBRIGATÓRIO Código do Emissor da nota (Préviamente cadastrado, como Fornecedor ou Cliente caso devolução) <br>
     * <b>numero</b> - OBRIGATÓRIO Número Nota Fiscal<br>
     * <b>serie</b> - OBRIGATÓRIO Serie da Nota Fiscal<br>
     * <b>dataEmissao</b> - OBRIGATÓRIO Data de emissao da nota fiscal. Formato esperado (DD/MM/AAAA) ex:'22/11/2010'<br>
     * <b>placa</b> - OPCIONAL Se não especificado será atribuido o valor padrão configurado pelo usuário do WMS<br>
     * <b>itens</b> - OBRIGATÓRIO<br>
     * <b>bonificacao</b> - DEPRECIADO Este campo será removido em versões futuras <br>
     * <b>observacao</b> - OPCIONAL Campo para informações pertinentes à nota<br>
     * <b>tipoNota</b> - OPCIONAL Caso não especificado, o sistema atribuirá como uma nota de compra.
     * Os códigos identificadores dos tipos de nota deve ser definidos previamente entre ERP e WMS Imperium<br>
     * <b>cnpjDestinatario</b> - CONDICIONAL Caso a empresa use a mesma instalação do WMS para múltiplas filiais é OBRIGATORIO o CNPJ da filial que fará o recebimento<br>
     * <b>cnpjProprietario</b> - CONDICIONAL Caso a empresa use uma mesma filial para controlar o saldo virtual de multiplas é OBRIGATORIO o CNPJ da filial que receberá o saldo
     * </p>
     *
     * @param string $idFornecedor Codigo do fornecedor
     * @param string $numero Numero da nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @param string $placa Placa do veiculo vinculado à nota fiscal formato esperado: XXX0000
     * @param TypeOfParam::getType(NOTA_FISCAL_SALVAR_ITENS) $itens
     * @param string $bonificacao Indica se a nota fiscal é ou não do tipo bonificação, Por padrão Não (N).
     * @param string $observacao Observações da Nota Fiscal
     * @param string $tipoNota Identifica se é uma nota de Bonificação(B), Compra(C), etc.
     * @param string $cnpjDestinatario CNPJ da filial que irá receber a nota
     * @param string $cnpjProprietario CNPJ da filial dona da nota
     * @return boolean
     * @throws Exception
     */
    public function salvar($idFornecedor, $numero, $serie, $dataEmissao, $placa, $itens, $bonificacao, $observacao, $tipoNota, $cnpjDestinatario, $cnpjProprietario)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        try{
            $em->beginTransaction();

            //PREPARANDO AS INFORMAÇÔES PRA FORMATAR CORRETAMENTE
            //BEGIN
            $idEmissor = trim($idFornecedor);
            $numero = trim($numero);
            $serieTrim = trim($serie);
            $serie = (!empty($serieTrim))? $serieTrim : "0";
            $dataEmissao = trim($dataEmissao);
            $placa = trim($placa);
            $cnpjDestinatario = trim ($cnpjDestinatario);
            $cnpjProprietario = trim ($cnpjProprietario);
            $bonificacao = trim ($bonificacao);
            $tipoNota = trim ($tipoNota);

            if ($bonificacao == "E") {
                //NOTA DE ENTRADA NORMAL
            }
            if ($bonificacao == "D") {
                //NOTA DE DEVOLUÇÃO
            }
            $bonificacao = "N";

            /** @var $emissorEn Papel\Fornecedor|Papel\Cliente */
            /** @var $tipoNotaEn NotaFiscalEntity\Tipo */
            list($emissorEn, $tipoNotaEn) = self::getEmissorAndTipo($em, $idEmissor, $tipoNota);

            //SE VIER O TIPO ITENS DEFINIDO ACIMA, ENTAO CONVERTE PARA ARRAY
            if (gettype($itens) != "array") {
                $itensNf = array();
                foreach ($itens->itens as $itemNf) {
                    $itemWs['idProduto'] = trim($itemNf->idProduto);
                    $itemWs['grade'] = (empty($itemNf->grade) || $itemNf->grade === "?") ? "UNICA" : trim($itemNf->grade);
                    $itemWs['quantidade'] = str_replace(',','.',trim($itemNf->quantidade));
                    $itemWs['lote'] = (isset($itemNf->lote) && $itemNf->lote != "?" && !empty($itemNf->lote)) ? trim($itemNf->lote) : null;

                    if (isset($itemNf->peso)) {
                        if (trim(is_null($itemNf->peso) || !isset($itemNf->peso) || empty($itemNf->peso) || $itemNf->peso == 0)) {
                            $itemWs['peso'] = trim($itemNf->quantidade);
                        } else {
                            $itemWs['peso'] = trim(str_replace(',','.',$itemNf->peso));
                        }
                    } else {
                        $itemWs['peso'] = trim($itemNf->quantidade);
                    }

                    $itensNf[] = $itemWs;
                }
                $itens = $itensNf;
            } else {
                $itensNf = array();
                foreach ($itens as $itemNf) {
                    if (is_object($itemNf)) {
                        $itemWs['idProduto'] = trim($itemNf->idProduto);
                        $itemWs['grade'] = (empty($itemNf->grade) || $itemNf->grade === "?") ? "UNICA" : trim($itemNf->grade);
                        $itemWs['quantidade'] = str_replace(',', '.', trim($itemNf->quantidade));
                        $itemWs['lote'] = (isset($itemNf->lote) && $itemNf->lote != "?" && !empty($itemNf->lote)) ? trim($itemNf->lote) : null;

                        if (isset($itemNf->peso)) {
                            if (trim(is_null($itemNf->peso) || !isset($itemNf->peso) || empty($itemNf->peso) || $itemNf->peso == 0)) {
                                $itemWs['peso'] = trim($itemNf->quantidade);
                            } else {
                                $itemWs['peso'] = trim(str_replace(',', '.', $itemNf->peso));
                            }
                        } else {
                            $itemWs['peso'] = trim($itemNf->quantidade);
                        }

                        $itensNf[] = $itemWs;
                    } else {
                        $itemWs['idProduto'] = $itemNf['idProduto'];
                        $itemWs['peso'] =  $itemNf['quantidade'];
                        $itemWs['grade'] = $itemNf['grade'];
                        $itemWs['quantidade']= $itemNf['quantidade'];
                        $itemWs['lote']= (isset($itemNf['lote']) && $itemNf['lote'] != "?" && !empty($itemNf['lote'])) ? trim($itemNf['lote']) : null;
                        $itensNf[] = $itemWs;
                    }
                }
                $itens = $itensNf;
            }

            if (count($itens) == 0) {
                throw new \Exception('A Nota fiscal deve ter ao menos um item');
            }

            //VERIFICO SE É UMA NOTA NOVA OU SE É ALTERAÇÃO DE ALGUMA NOTA JA EXISTENTE
            /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
            $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');
            /** @var NotaFiscalEntity $notaFiscalEn */
            $notaFiscalEn = $notaFiscalRepo->findOneBy(['numero' => $numero, 'serie' => $serie, 'emissor' => $emissorEn, 'tipo' => $tipoNotaEn]);

            if ($notaFiscalEn != null) {
                $recebimentoConferenciaRepo = $em->getRepository('wms:Recebimento\Conferencia');
                $notaItensRepo = $em->getRepository('wms:NotaFiscal\Item');
                $statusNotaFiscal = $notaFiscalEn->getStatus()->getId();
                if ($statusNotaFiscal == \Wms\Domain\Entity\NotaFiscal::STATUS_RECEBIDA) {
                    $nomEmissor = $emissorEn->getNome();
                    $dscTipo = $tipoNotaEn->getDescricao();
                    $msg = "NF de $dscTipo de $nomEmissor, nº $numero e série $serie, já foi conferida";
                    throw new \Exception ("Não é possível alterar, $msg");
                }

                if ($notaFiscalEn->getStatus()->getId() == NotaFiscalEntity::STATUS_CANCELADA) {
                    $statusEntity = $em->getReference('wms:Util\Sigla', NotaFiscalEntity::STATUS_INTEGRADA);
                    $notaFiscalEn->setRecebimento(null);
                    $notaFiscalEn->setStatus($statusEntity);
                    $em->persist($notaFiscalEn);
                }

                //VERIFICA TODOS OS ITENS DO BANCO DE DADOS E COMPARA COM WS
                $this->compareItensBancoComArray($itens, $notaItensRepo, $recebimentoConferenciaRepo, $notaFiscalEn, $em);

                //VERIFICA TODOS OS ITENS DO WS E COMPARA COM BANCO DE DADOS
                $this->compareItensWsComBanco($itens, $notaItensRepo, $notaFiscalRepo, $notaFiscalEn, $em);


            } else {
                $notaFiscalRepo->salvarNota($emissorEn, $tipoNotaEn, $numero,$serie,$dataEmissao,$placa,$itens,$bonificacao,$observacao,$cnpjDestinatario, $cnpjProprietario, false);
            }

            $em->flush();
            $em->commit();
            return true;
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    /**
     * <h3>=========================== ATENÇÃO ===========================</h3>
     * <h3>Este método é semelhante ao método <b>"salvar"</b>, porém,
     * <br>o atributo <b>"itens"</b> deve ser enviado como uma string no formato JSON.
     * <br><u>Recomendamos fortemente que seja utilizado o método <b>"salvar"</b>!</u></h3>
     * <h3>==============================================================</h3>
     *
     * <p>Se a Nota Fiscal já existe e já foi recebida (conferida e recebimento finalizado), retornará uma <b>Exception</b></p>
     *
     * <p>Se a Nota Fiscal já existe e está em recebimento, caso um dos itens alterados já tiver sido conferido por completo, retornará uma <b>Exception</b></p>
     *
     * <p>Se a Nota Fiscal não existe ou já existe mas não está em recebimento, o sistema irá atualizar as informações</p>
     *
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idFornecedor</b> - OBRIGATÓRIO Código do Emissor da nota (Préviamente cadastrado, como Fornecedor ou Cliente caso devolução) <br>
     * <b>numero</b> - OBRIGATÓRIO Número Nota Fiscal<br>
     * <b>serie</b> - OBRIGATÓRIO Serie da Nota Fiscal<br>
     * <b>dataEmissao</b> - OBRIGATÓRIO Data de emissao da nota fiscal. Formato esperado (DD/MM/AAAA) ex:'22/11/2010'<br>
     * <b>placa</b> - OPCIONAL Se não especificado será atribuido o valor padrão configurado pelo usuário do WMS<br>
     * <b>itens</b> - OBRIGATÓRIO<br>
     * <b>bonificacao</b> - DEPRECIADO Este campo será removido em versões futuras <br>
     * <b>observacao</b> - OPCIONAL Campo para informações pertinentes à nota<br>
     * <b>cnpjDestinatario</b> - CONDICIONAL Caso a empresa use a mesma instalação do WMS para múltiplas filiais é OBRIGATORIO o CNPJ da filial que fará o recebimento<br>
     * </p>
     *
     * @param string $idFornecedor Codigo do fornecedor
     * @param string $numero Numero da nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @param string $placa Placa do veiculo vinculado à nota fiscal formato esperado: XXX0000
     * @param string $itens Itens da Nota {Json}
     * @param string $bonificacao Indica se a nota fiscal é ou não do tipo bonificação, Por padrão Não (N).
     * @param string $observacao Observações da Nota Fiscal
     * @param string $cnpjDestinatario CNPJ da filial que irá receber a nota
     * @return boolean
     * @throws Exception
     */
    public function salvarJson($idFornecedor, $numero, $serie, $dataEmissao, $placa, $itens, $bonificacao, $observacao, $cnpjDestinatario){
        /*
        $jsonMockSample ='{"produtos": [';
        $jsonMockSample .='     {"idProduto": "999", ';
        $jsonMockSample .='      "grade": "UNICA",' ;
        $jsonMockSample .='      "quantidade": "50"}, ';
        $jsonMockSample .='     {"idProduto": "888", ';
        $jsonMockSample .='      "grade": "UNICA2",' ;
        $jsonMockSample .='      "quantidade": "55"}]} ';
        */
        try {
            $array = json_decode($itens, true);
            $arrayItens = $array['produtos'];
            return $this->salvar($idFornecedor,$numero,$serie,$dataEmissao,$placa, $arrayItens,$bonificacao, $observacao, null,$cnpjDestinatario, null);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Método para consultar no WMS o status da Nota fiscal( Integrada, Em Recebimento ou Recebida )
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idFornecedor</b> - OBRIGATÓRIO Código do Emissor da nota (Préviamente cadastrado, como Fornecedor ou Cliente caso devolução) <br>
     * <b>numero</b> - OBRIGATÓRIO Número Nota Fiscal<br>
     * <b>serie</b> - OBRIGATÓRIO Serie da Nota Fiscal<br>
     * <b>dataEmissao</b> - OPCIONAL Data de emissao da nota fiscal. Formato esperado (DD/MM/AAAA) ex:'22/11/2010'<br>
     * <b>tipoNota</b> - OPCIONAL Caso não especificado, o sistema atribuirá como uma nota de compra.
     * Os códigos identificadores dos tipos de nota deve ser definidos previamente entre ERP e WMS Imperium<br>
     * </p>
     *
     *
     * @param string $idFornecedor Codigo externo do fornecedor
     * @param string $numero Numero da Nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @param string $tipoNota Tipo de Nota Fiscal
     * @return array
     * @throws Exception
     */
    public function status($idFornecedor, $numero, $serie, $dataEmissao, $tipoNota = null)
    {
        $idEmissor = trim($idFornecedor);
        $numero = trim($numero);
        $serieTrim = trim($serie);
        $serie = (!empty($serieTrim))? $serieTrim : "0";
        $dataEmissao = trim($dataEmissao);
        $tipoNota = trim($tipoNota);

        $em = $this->__getDoctrineContainer()->getEntityManager();
        list($emissorEn, $tipoNotaEn) = self::getEmissorAndTipo($em, $idEmissor, $tipoNota);

        $notaFiscalEntity = $em->getRepository('wms:NotaFiscal')
            ->getAtiva($emissorEn, $numero, $serie, $dataEmissao, $tipoNotaEn);

        if ($notaFiscalEntity == null)
            throw new \Exception('Nota Fiscal não encontrada');

        return array(
            'id' => $notaFiscalEntity->getStatus()->getId(),
            'descricao' => $notaFiscalEntity->getStatus()->getSigla()
        );
    }

    /**
     * Descarta uma nota desvinculando ela do recebimento.
     *
     * <p>Ação só pode ser executada quando a nota estiver no status <b>"Em Recebimento"</b></p>
     *
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idFornecedor</b> - OBRIGATÓRIO Código do Emissor da nota (Préviamente cadastrado, como Fornecedor ou Cliente caso devolução) <br>
     * <b>numero</b> - OBRIGATÓRIO Número Nota Fiscal<br>
     * <b>serie</b> - OBRIGATÓRIO Serie da Nota Fiscal<br>
     * <b>dataEmissao</b> - OPCIONAL Data de emissao da Nota Fiscal. Formato esperado (DD/MM/AAAA) ex:'22/11/2010'<br>
     * <b>observacao</b> - OBRIGATÓRIO Descrição do porquê da nota fiscal foi descartada<br>
     * <b>tipoNota</b> - OPCIONAL Caso não especificado, o sistema atribuirá como uma nota de compra.
     * Os códigos identificadores dos tipos de nota deve ser definidos previamente entre ERP e WMS Imperium<br>
     * </p>
     *
     * @param string $idFornecedor Codigo externo do fornecedor
     * @param string $numero Numero da Nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'DD/MM/YYYY'
     * @param string $observacao Descrição do porquê da nota fiscal descartada
     * @param string $tipoNota Tipo de Nota Fiscal
     * @return boolean
     * @throws Exception
     */
    public function descartar($idFornecedor, $numero, $serie, $dataEmissao, $observacao, $tipoNota = null)
    {
        $idEmissor = trim ($idFornecedor);
        $numero = trim($numero);
        $serieTrim = trim($serie);
        $serie = (!empty($serieTrim))? $serieTrim : "0";
        $observacao = trim($observacao);

        $em = $this->__getDoctrineContainer()->getEntityManager();
        /** @var $emissorEn Papel\EmissorInterface */
        /** @var $tipoNotaEn NotaFiscalEntity\Tipo */
        list($emissorEn, $tipoNotaEn) = self::getEmissorAndTipo($em, $idEmissor, $tipoNota);

        $notaFiscalEntity = $this->__getServiceLocator()->getService('NotaFiscal')->findOneBy(array(
            'emissor' => $emissorEn,
            'numero' => $numero,
            'serie' => $serie,
            'tipo' => $tipoNotaEn
        ));

        if (empty($notaFiscalEntity)){
            $tipoDsc = $tipoNotaEn->getDescricao();
            throw new \Exception("Nota fiscal $numero tipo '$tipoDsc' do emissor de código $idEmissor e série $serie não encontrada");
        }

        $em->getRepository('wms:NotaFiscal')->descartar($notaFiscalEntity->getId(), $observacao);

        return true;
    }

    /**
     * Desfazer uma nota, basicamente ela é cancelada. Caso a Nota Fiscal esteja em recebimento e o mesmo não possua mais notas ele também é cancelado!
     *
     * <p>Ação só pode ser executada quando a nota estiver no status <b>"Integrada"</b> ou <b>"Em Recebimento"</b></p>
     *
     * <p>Este método pode retornar uma <b>Exception</b></p>
     *
     * <p>
     * <b>idFornecedor</b> - OBRIGATÓRIO Código do Emissor da nota (Préviamente cadastrado, como Fornecedor ou Cliente caso devolução) <br>
     * <b>numero</b> - OBRIGATÓRIO Número Nota Fiscal<br>
     * <b>serie</b> - OBRIGATÓRIO Serie da Nota Fiscal<br>
     * <b>dataEmissao</b> - OPCIONAL Data de emissao da Nota Fiscal. Formato esperado (DD/MM/AAAA) ex:'22/11/2010'<br>
     * <b>observacao</b> - OBRIGATÓRIO Descrição do porquê da nota fiscal foi descartada<br>
     * <b>tipoNota</b> - OPCIONAL Caso não especificado, o sistema atribuirá como uma nota de compra.
     * Os códigos identificadores dos tipos de nota deve ser definidos previamente entre ERP e WMS Imperium<br>
     * </p>
     *
     * @param string $idFornecedor Codigo externo do fornecedor
     * @param string $numero Numero da Nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'DD/MM/YYYY'
     * @param string $observacao Descrição do porquê da nota fiscal foi desfeita
     * @param string $tipoNota Tipo de Nota Fiscal
     * @return boolean
     * @throws Exception
     */
    public function desfazer($idFornecedor, $numero, $serie, $dataEmissao, $observacao, $tipoNota = null)
    {
        $idEmissor = trim ($idFornecedor);
        $numero = trim($numero);
        $serieTrim = trim($serie);
        $serie = (!empty($serieTrim))? $serieTrim : "0";
        $dataEmissao = trim($dataEmissao);
        $observacao = trim($observacao);

        $em = $this->__getDoctrineContainer()->getEntityManager();
        /** @var $emissorEn Papel\EmissorInterface */
        /** @var $tipoNotaEn NotaFiscalEntity\Tipo */
        list($emissorEn, $tipoNotaEn) = self::getEmissorAndTipo($em, $idEmissor, $tipoNota);

        $notaFiscalEntity = $em->getRepository('wms:NotaFiscal')
            ->getAtiva($emissorEn, $numero, $serie, $dataEmissao, $tipoNotaEn);

        if (empty($notaFiscalEntity)){
            $tipoDsc = $tipoNotaEn->getDescricao();
            throw new \Exception("Nota fiscal $numero tipo '$tipoDsc' do emissor de código $idEmissor e série $serie não encontrada");
        }

        $em->getRepository('wms:NotaFiscal')->desfazer($notaFiscalEntity->getId(), $observacao);

        return true;
    }

    /**
     * @param $itens
     * @param $recebimentoConferenciaRepo
     * @param $notaFiscalEn
     * @param $em
     * @return bool
     * @throws Exception
     */
    private function compareItensBancoComArray($itens, $notaItensRepo, $recebimentoConferenciaRepo, $notaFiscalEn, $em)
    {
        //VERIFICA TODOS OS ITENS DO BD
        $notaItensBDEn = $notaItensRepo->findBy(array('notaFiscal' => $notaFiscalEn->getId()));
        if (count($itens) <= 0) {
            throw new \Exception("Nenhum item informado na nota");
        }

        if ($notaItensBDEn <= 0) {
            return false;
        }

        try {
            $notaFiscalItemLoteRepository = $em->getRepository('wms:NotaFiscal\NotaFiscalItemLote');
            foreach ($notaItensBDEn as $itemBD) {
                $matemItem = false;
                //VERIFICA TODOS OS ITENS DA NF
                foreach ($itens as $itemNf) {
                    //VERIFICA SE PRODUTO DO BANCO AINDA EXISTE NA NF
                    if ($itemBD->getProduto()->getId() == trim($itemNf['idProduto']) && $itemBD->getGrade() == trim($itemNf['grade'])) {
                        //VERIFICA SE A QUANTIDADE É A MESMA
                        if ($itemBD->getQuantidade() == trim($itemNf['quantidade'])) {
                            //SE TODOS OS DADOS FOREM IGUAIS, NAO FAZ NADA
                            $matemItem = true;
                            break;
                        } else {
                            //VERIFICA SE EXISTE CONFERENCIA DO PRODUTO
                            $recebimentoConferenciaEn = $recebimentoConferenciaRepo->findOneBy(array('codProduto' => $itemBD->getProduto()->getId(), 'grade' => $itemBD->getGrade(), 'recebimento' => $notaFiscalEn->getRecebimento()));
                            //SE EXISTIR CONFERENCIA E A QUANTIDADE FOR DIFERENTE FINALIZA O PROCESSO
                            if ($recebimentoConferenciaEn)
                                throw new \Exception ("Não é possível sobrescrever a NF com itens já conferidos!");
                        }
                    }
                }
                if ($matemItem == false) {
                    // SE PRODUTO EXISTIR NO BD, NAO EXISTIR NO WS E NAO TIVER CONFERENCIA REMOVE O PRODUTO
                    $em->remove($itemBD);
                    $notaFiscalItemLoteRepository->removeNFitem($itemBD->getId());
                }
            }
            $em->flush();
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

    /**
     * @param $itens
     * @param $notaFiscalRepo
     * @param $notaFiscalEn
     * @param $em
     * @return bool
     * @throws Exception
     */
    private function compareItensWsComBanco($itens, $notaItensRepo, $notaFiscalRepo, $notaFiscalEn, $em)
    {
        /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
        if ($itens <= 0) {
            throw new \Exception("Nenhum item informado na nota");
        }

        //VERIFICA TODOS OS ITENS DO BD
        $notaItensBDEn = $notaItensRepo->findBy(array('notaFiscal' => $notaFiscalEn->getId()));

        try {
            $itensNf = array();
            $pesoTotal = 0;
            foreach ($itens as $itemNf) {
                $pesoTotal = trim((float)$itemNf['peso']) + $pesoTotal;
                $encontrouItemNF = false;
                foreach ($notaItensBDEn as $itemBD) {
                    //VERIFICA SE PRODUTO DA NF JÁ EXISTE NO BD
                    if ($itemBD->getProduto()->getId() == trim($itemNf['idProduto']) && $itemBD->getGrade() == trim($itemNf['grade'])) {
                        $encontrouItemNF = true;
                        break;
                    }
                }
                //INSERE SE O PRODUTO NÃO EXISTIR NO BD
                if ($encontrouItemNF == false) {
                    $itemWs['idProduto'] = trim($itemNf['idProduto']);
                    $itemWs['grade'] = trim($itemNf['grade']);
                    $itemWs['quantidade'] = trim(str_replace(',','.',$itemNf['quantidade']));
                    $itemWs['peso'] = trim(str_replace(',','.',$itemNf['peso']));
                    if (is_null($itemNf['peso']) || strlen(trim($itemNf['peso'])) == 0) {
                        $itemWs['peso'] = trim(str_replace(',','.',$itemNf['quantidade']));
                    }
                    if(isset($itemNf['lote'])){
                        $itemWs['lote'] = trim($itemNf['lote']);
                    }


                    $itensNf[] = $itemWs;
                }
            }
            if (count($itensNf) > 0) {
                $notaFiscalRepo->salvarItens($itensNf, $notaFiscalEn);
                $notaFiscalEn->setPesoTotal($pesoTotal);
                $em->persist($notaFiscalEn);
                $em->flush($notaFiscalEn);
            }
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * @param $em
     * @param $idEmissor
     * @param $tipoNota
     * @return array
     * @throws Exception
     */
    private function getEmissorAndTipo($em, $idEmissor, $tipoNota) {
        if (!empty($tipoNota)) {
            $tipoNotaEn = $em->getRepository(NotaFiscalEntity\Tipo::class)->findOneBy(['codExterno' => $tipoNota]);
            if (empty($tipoNota))
                throw new Exception("Tipo de nota '$tipoNota' não identificado");
        } else {
            $tipoNotaEn = $em->getRepository(NotaFiscalEntity\Tipo::class)->findOneBy(['recebimentoDefault' => true]);
        }

        /** @var $tipoNotaEn NotaFiscalEntity\Tipo */
        if ($tipoNotaEn->getEmissor() === Papel\EmissorInterface::EMISSOR_FORNECEDOR) {
            /** @var Papel\Fornecedor $emissorEntity */
            $emissorEntity = $em->getRepository(Papel\Fornecedor::class)->findOneBy(['codExterno' => $idEmissor]);
        } else {
            /** @var Papel\Cliente $emissorEntity */
            $emissorEntity = $em->getRepository(Papel\Cliente::class)->findOneBy(['codExterno' => $idEmissor]);
        }

        if (empty($emissorEntity))
            throw new Exception("Nenhum emissor foi encontrado com o código $idEmissor do tipo " . NotaFiscalEntity\Tipo::$arrResponsaveis[$tipoNotaEn->getEmissor()]);

        return [$emissorEntity, $tipoNotaEn];
    }

}

