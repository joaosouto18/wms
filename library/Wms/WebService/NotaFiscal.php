<?php

use Wms\Domain\Entity\NotaFiscal as NotaFiscalEntity,
    Wms\Domain\Entity\NotaFiscal\Item as ItemNF;


class Item {
    /** @var string */
    public $idProduto;
    /** @var string */
    public $grade;
    /** @var double */
    public $quantidade;
    /** @var double */
    public $peso;
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
    /** @var itensNf[] */
    public $itens = array();
}

class Wms_WebService_NotaFiscal extends Wms_WebService
{

    /**
     * Retorna uma Nota Fiscal específico no WMS pelo seu ID.
     *
     * @param string $idFornecedor Codigo do fornecedor
     * @param string $numero Numero da nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @param integer $idStatus Codigo do status da nota fiscal no wms
     * @return array
     * @throws Exception
     */
    public function buscar($idFornecedor, $numero, $serie, $dataEmissao, $idStatus)
    {

        $idFornecedor = trim($idFornecedor);
        $numero = trim($numero);
        $serie = trim($serie);
        $dataEmissao  = trim($dataEmissao);
        $idStatus = trim($idStatus);

        $em = $this->__getDoctrineContainer()->getEntityManager();

        $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $idFornecedor));
        if ($fornecedorEntity == null) {
            $novoIdFornecedor = $em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'COD_FORNECEDOR_DEVOLUCAO'))->getValor();
            $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $novoIdFornecedor));
            if ($fornecedorEntity == null) {
                throw new \Exception('Fornecedor código ' . $idFornecedor . ' não encontrado');
            }
            $idFornecedor = $novoIdFornecedor;
        }

        $notaFiscalEntity = $em->getRepository('wms:NotaFiscal')->findOneBy(array(
            'fornecedor' => $fornecedorEntity->getId(),
            'numero' => $numero,
            'serie' => $serie,
            //'dataEmissao' => \DateTime::createFromFormat('d/m/Y', $dataEmissao),
            'status' => $idStatus,
        ));

        if ($notaFiscalEntity == null)
            throw new \Exception('NotaFiscal não encontrada');

        $itemsNF = $em->getRepository('wms:NotaFiscal')->getConferencia($fornecedorEntity->getId(), $numero, $serie, $dataEmissao, $idStatus);

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
            'idFornecedor' => $notaFiscalEntity->getFornecedor()->getId(),
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
     * Retorna uma Nota Fiscal específico no WMS pelo seu ID.
     *
     * @param string $idFornecedor Codigo do fornecedor
     * @param string $numero Numero da nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @return notaFiscal
     * @throws Exception
     */
    public function buscarNf($idFornecedor, $numero, $serie, $dataEmissao)
    {

        $idFornecedor = trim($idFornecedor);
        $numero = trim($numero);
        $serie = trim($serie);
        $dataEmissao = trim($dataEmissao);

        $em = $this->__getDoctrineContainer()->getEntityManager();

        $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $idFornecedor));
        if ($fornecedorEntity == null) {
            $novoIdFornecedor = $em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'COD_FORNECEDOR_DEVOLUCAO'))->getValor();
            $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $novoIdFornecedor));
            if ($fornecedorEntity == null) {
                throw new \Exception('Fornecedor código ' . $idFornecedor . ' não encontrado');
            }
            $idFornecedor = $novoIdFornecedor;
        }
        /** @var \Wms\Domain\Entity\NotaFiscal $notaFiscalEntity */
        $notaFiscalEntity = $em->getRepository('wms:NotaFiscal')->findOneBy(array(
            'fornecedor' => $fornecedorEntity->getId(),
            'numero' => $numero,
            'serie' => $serie
        ));

        if ($notaFiscalEntity == null)
            throw new \Exception('NotaFiscal não encontrada');

        $itemsNF = $em->getRepository('wms:NotaFiscal')->getConferencia($fornecedorEntity->getId(), $numero, $serie, $dataEmissao, $notaFiscalEntity->getStatus()->getId());

        $clsNf = new notaFiscal();
        foreach ($itemsNF as $item) {
            $clsItensNf = new itensNf();
            $clsItensNf->idProduto = $item['COD_PRODUTO'];
            $clsItensNf->quantidade = $item['QTD_ITEM'];
            $clsItensNf->grade = $item['DSC_GRADE'];
            $clsItensNf->quantidadeConferida = $item['QTD_CONFERIDA'];
            $clsItensNf->quantidadeAvaria = $item['QTD_AVARIA'];
            $clsItensNf->motivoDivergencia = $item['DSC_MOTIVO_DIVER_RECEB'];
            $clsNf->itens[] = $clsItensNf;
        }

        //verifica se existe recebimento, senao seta 0 no codigo do recebimento
        $idRecebimento = ($notaFiscalEntity->getRecebimento()) ? $notaFiscalEntity->getRecebimento()->getId() : 0;

        $dataEntrada = ($notaFiscalEntity->getDataEntrada()) ? $notaFiscalEntity->getDataEntrada()->format('d/m/Y') : '';

        $clsNf->idRecebimeto = $idRecebimento;
        $clsNf->idFornecedor = $notaFiscalEntity->getFornecedor()->getIdExterno();
        $clsNf->numero = $notaFiscalEntity->getNumero();
        $clsNf->serie = $notaFiscalEntity->getSerie();
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
     * Salva uma Nota Fiscal no WMS
     *
     * @param string $idFornecedor Codigo do fornecedor
     * @param string $numero Numero da nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @param string $placa Placa do veiculo vinculado à nota fiscal formato esperado: XXX0000
     * @param array $itens
     * @param string $bonificacao Indica se a nota fiscal é ou não do tipo bonificação, Por padrão Não (N).
     * @param string $observacao Observações da Nota Fiscal
     * @param string $tipoNota Identifica se é uma nota de Bonificação(B), Compra(C), etc.
     * @param string $cnpjDestinatario CNPJ da filial dona da nota
     * @return boolean
     * @throws Exception
     */
    public function salvar($idFornecedor, $numero, $serie, $dataEmissao, $placa, $itens, $bonificacao, $observacao, $tipoNota, $cnpjDestinatario)
    {
        $em = $this->__getDoctrineContainer()->getEntityManager();
        try{
            $em->beginTransaction();

            //PREPARANDO AS INFORMAÇÔES PRA FORMATAR CORRETAMENTE
            //BEGIN
            $idFornecedor = trim($idFornecedor);
            $numero = (int) trim($numero);
            $serie = trim($serie);
            $dataEmissao = trim($dataEmissao);
            $placa = trim($placa);
            $bonificacao = trim ($bonificacao);

            if ($bonificacao == "E") {
                //NOTA DE ENTRADA NORMAL
            }
            if ($bonificacao == "D") {
                //NOTA DE DEVOLUÇÃO
            }
            $bonificacao = "N";

            $notaItensRepo = $em->getRepository('wms:NotaFiscal\Item');
            $recebimentoConferenciaRepo = $em->getRepository('wms:Recebimento\Conferencia');

            $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $idFornecedor));
            if ($fornecedorEntity == null) {
                $novoIdFornecedor = $em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'COD_FORNECEDOR_DEVOLUCAO'))->getValor();
                $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $novoIdFornecedor));
                if ($fornecedorEntity == null) {
                    throw new \Exception('Fornecedor código ' . $idFornecedor . ' não encontrado');
                }
                $idFornecedor = $novoIdFornecedor;
            }

            $tipoEnvio = 'array';

            //SE VIER O TIPO ITENS DEFINIDO ACIMA, ENTAO CONVERTE PARA ARRAY
            /*
            if ($tipoEnvio != "array") {
                $itensNf = array();
                foreach ($itens->itens as $itemNf) {
                    $itemWs['idProduto'] = trim($itemNf->idProduto);
                    $itemWs['grade'] = (empty($itemNf->grade) || $itemNf->grade === "?") ? "UNICA" : trim($itemNf->grade);
                    $itemWs['quantidade'] = str_replace(',','.',trim($itemNf->quantidade));

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
                        $itensNf[] = $itemWs;
                    }
                }
                $itens = $itensNf;
            }
            */

            $itensNf = array();
            foreach ($itens as $itemNf) {
                $itemWs['idProduto'] = $itemNf['idProduto'];
                $itemWs['peso'] =  $itemNf['quantidade'];
                $itemWs['grade'] = $itemNf['grade'];
                $itemWs['quantidade']= $itemNf['quantidade'];
                $itensNf[] = $itemWs;
            }
            $itens = $itensNf;


                if (count($itens) == 0) {
                throw new \Exception('A Nota fiscal deve ter ao menos um item');
            }

            //VERIFICO SE É UMA NOTA NOVA OU SE É ALTERAÇÃO DE ALGUMA NOTA JA EXISTENTE
            /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepo */
            $notaFiscalRepo = $em->getRepository('wms:NotaFiscal');
            /** @var NotaFiscalEntity $notaFiscalEn */
            $notaFiscalEn = $notaFiscalRepo->findOneBy(array('numero' => $numero, 'serie' => $serie, 'fornecedor' => $fornecedorEntity->getId()));

            if ($notaFiscalEn != null) {
                $statusNotaFiscal = $notaFiscalEn->getStatus()->getId();
                if ($statusNotaFiscal == \Wms\Domain\Entity\NotaFiscal::STATUS_RECEBIDA) {
                    throw new \Exception ("Não é possível alterar, NF ".$notaFiscalEn->getNumero()." já recebida");
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
                $notaFiscalRepo->salvarNota($idFornecedor,$numero,$serie,$dataEmissao,$placa,$itens,$bonificacao,$observacao);
            }

            $em->flush();
            $em->commit();
            return true;
        } catch (\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Salva uma Nota Fiscal no WMS atraves de Json para os itens
     *
     * @param string $idFornecedor Codigo do fornecedor
     * @param string $numero Numero da nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @param string $placa Placa do veiculo vinculado à nota fiscal formato esperado: XXX0000
     * @param string $itens Itens da Nota {Json}
     * @param string $bonificacao Indica se a nota fiscal é ou não do tipo bonificação, Por padrão Não (N).
     * @param string $observacao Observações da Nota Fiscal
     * @return boolean
     * @throws Exception
     */
    public function salvarJson($idFornecedor, $numero, $serie, $dataEmissao, $placa, $itens, $bonificacao, $observacao){
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
            return $this->salvar($idFornecedor,$numero,$serie,"",$dataEmissao,$placa,$arrayItens,$bonificacao, "", $observacao);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Retorna Nota fiscal ativa no WMS ( Integrada, Em Recebimento ou Recebida )
     *
     * @param string $idFornecedor Codigo externo do fornecedor
     * @param string $numero Numero da Nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'22/11/2010'
     * @return array
     * @throws Exception
     */
    public function status($idFornecedor, $numero, $serie, $dataEmissao)
    {
        $idFornecedor = trim($idFornecedor);
        $numero = trim($numero);
        $serie = trim($serie);
        $dataEmissao = trim($dataEmissao);

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $idFornecedor));
        if ($fornecedorEntity == null) {
            $novoIdFornecedor = $em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'COD_FORNECEDOR_DEVOLUCAO'))->getValor();
            $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $novoIdFornecedor));
            if ($fornecedorEntity == null) {
                throw new \Exception('Fornecedor código ' . $idFornecedor . ' não encontrado');
            }
            $idFornecedor = $novoIdFornecedor;
        }

        $notaFiscalEntity = $em->getRepository('wms:NotaFiscal')
            ->getAtiva($fornecedorEntity->getId(), $numero, $serie, $dataEmissao);

        if ($notaFiscalEntity == null)
            throw new \Exception('Nota Fiscal não encontrada');

        return array(
            'id' => $notaFiscalEntity->getStatus()->getId(),
            'descricao' => $notaFiscalEntity->getStatus()->getSigla()
        );
    }

    /**
     * Descarta uma nota desvinculando ela do recebimento.
     * <br />Ação pode ser executada em qualquer status em que a nota esteja.
     * <br />
     * <br /><b>(Obrigatório)</b> idFornecedor -> Código externo do fornecedor
     * <br />(Obrigatório) numero -> Número da Nota fiscal
     * <br />(Obrigatório) serie -> Série da nota fiscal
     * <br />(Opcional) dataEmissao -> Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'DD/MM/YYYY' -- Será descontinuado em futuras versões
     * <br />(Obrigatório) observacao -> Descrição do porquê da nota fiscal foi descartada
     * <br />
     * @param string $idFornecedor Codigo externo do fornecedor
     * @param string $numero Numero da Nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'DD/MM/YYYY'
     * @param string $observacao Descrição do porquê da nota fiscal descartada
     * @return boolean
     * @throws Exception
     */
    public function descartar($idFornecedor, $numero, $serie, $dataEmissao, $observacao)
    {
        $idFornecedor = trim ($idFornecedor);
        $numero = trim($numero);
        $serie = trim($serie);
        $dataEmissao = trim($dataEmissao);
        $observacao = trim($observacao);

        $dataEmissao = \DateTime::createFromFormat('d/m/Y', $dataEmissao);

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $idFornecedor));
        if ($fornecedorEntity == null) {
            $novoIdFornecedor = $em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'COD_FORNECEDOR_DEVOLUCAO'))->getValor();
            $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $novoIdFornecedor));
            if ($fornecedorEntity == null) {
                throw new \Exception('Fornecedor código ' . $idFornecedor . ' não encontrado');
            }
            $idFornecedor = $novoIdFornecedor;
        }

        $notaFiscalEntity = $this->__getServiceLocator()->getService('NotaFiscal')->findOneBy(array(
            'fornecedor' => $fornecedorEntity->getId(),
            'numero' => $numero,
            'serie' => $serie,
            //'dataEmissao' => $dataEmissao,
        ));

        $em->getRepository('wms:NotaFiscal')->descartar($notaFiscalEntity->getId(), $observacao);

        return true;
    }

    /**
     * Desfazer uma nota, basicamente ela é cancelada. Caso o recebimento não possua mais notas ele também é cancelado
     * Ação pode ser executada em qualquer status válido ( Integrada, Em Recebimento ou Recebida ) em que a nota esteja.
     *
     * @param string $idFornecedor Codigo externo do fornecedor
     * @param string $numero Numero da Nota fiscal
     * @param string $serie Serie da nota fiscal
     * @param string $dataEmissao Data de emissao da nota fiscal. Formato esperado (d/m/Y) ex:'DD/MM/YYYY'
     * @param string $observacao Descrição do porquê da nota fiscal foi desfeita
     * @return boolean
     * @throws Exception
     */
    public function desfazer($idFornecedor, $numero, $serie, $dataEmissao, $observacao)
    {
        $idFornecedor = trim($idFornecedor);
        $numero = trim($numero);
        $serie = trim($serie);
        $dataEmissao = trim($dataEmissao);
        $observacao = trim($observacao);

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $idFornecedor));
        if ($fornecedorEntity == null) {
            $novoIdFornecedor = $em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'COD_FORNECEDOR_DEVOLUCAO'))->getValor();
            $fornecedorEntity = $em->getRepository('wms:Pessoa\Papel\Fornecedor')->findOneBy(array('idExterno' => $novoIdFornecedor));
            if ($fornecedorEntity == null) {
                throw new \Exception('Fornecedor código ' . $idFornecedor . ' não encontrado');
            }
            $idFornecedor = $novoIdFornecedor;
        }

        $notaFiscalEntity = $em->getRepository('wms:NotaFiscal')
            ->getAtiva($fornecedorEntity->getId(), $numero, $serie, $dataEmissao);

        if (!$notaFiscalEntity)
            throw new \Exception('Não há Nota Fiscal válida para ser cancelada');

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

}

