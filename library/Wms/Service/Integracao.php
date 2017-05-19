<?php

namespace Wms\Service;


use Core\Util\String;
use Doctrine\ORM\EntityManager;
use Wms\Domain\Entity\Enderecamento\EstoqueErp;
use Wms\Domain\Entity\Integracao\AcaoIntegracao;

class embalagem {
    /** @var string */
    public $codBarras;
    /** @var int */
    public $qtdEmbalagem;
    /** @var string */
    public $descricao;
}

class Integracao
{
    protected $_acao;
    protected $_dados;
    protected $_options;

    /** @var EntityManager _em */
    protected $_em;

    public function __construct($em,$params)
    {
        $this->_em = $em;
        \Zend\Stdlib\Configurator::configure($this, $params);
    }

    /**
     * @return mixed
     */
    public function getAcao()
    {
        return $this->_acao;
    }

    /**
     * @param mixed $acao
     */
    public function setAcao($acao)
    {
        $this->_acao = $acao;
    }

    /**
     * @return mixed
     */
    public function getDados()
    {
        return $this->_dados;
    }

    /**
     * @param mixed $dados
     */
    public function setDados($dados)
    {
        $this->_dados = $dados;
    }

    /**
     * @return mixed
     */
    public function getOptions()
    {
        return $this->_options;
    }

    /**
     * @param mixed $options
     */
    public function setOptions($options)
    {
        $this->_options = $options;
    }

    public function getMaxDate() {
        if (!(($this->getAcao()->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_PRODUTO)
            ||($this->getAcao()->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_PEDIDOS)
            ||($this->getAcao()->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_NOTAS_FISCAIS))){
            return new \DateTime();
        }

        $maxDate = null;
        foreach ($this->_dados as $row) {

            $data = \DateTime::createFromFormat('d/m/Y H:i:s', $row['DTH']);
            $data = $data->format('Y-m-d H:i:s');
            if ($maxDate == null) {
                $maxDate = $data;
            }
            if (strtotime($data) > strtotime($maxDate)) {
                $maxDate = $data;
            }
        }
        $maxDate = new \DateTime($maxDate);
        return $maxDate;
    }


    public function processaAcao() {
        Try {
            switch ($this->getAcao()->getTipoAcao()->getId()) {
                case AcaoIntegracao::INTEGRACAO_PRODUTO:
                    return $this->processaProdutos($this->_dados);
                case AcaoIntegracao::INTEGRACAO_ESTOQUE:
                    return $this->processaEstoque($this->_dados);
                case AcaoIntegracao::INTEGRACAO_PEDIDOS:
                    return $this->processaPedido($this->_dados);
                case AcaoIntegracao::INTEGRACAO_RESUMO_CONFERENCIA:
                    return $this->comparaResumoConferenciaExpedicao($this->_dados, $this->_options);
                case AcaoIntegracao::INTEGRACAO_CONFERENCIA:
                    return $this->comparaConferenciaExpedicao($this->_dados, $this->_options);
                case AcaoIntegracao::INTEGRACAO_NOTAS_FISCAIS:
                    return $this->processaNotasFiscais($this->_dados);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function comparaConferenciaExpedicao ($dados, $options) {
        $idCarga = null;
        if (isset($options[0]) && ($options[0] != null)) {
            $idCarga = $options[0];
        } else {
            throw new \Exception("Carga não definida nos parametros da consulta");
        }

        $expedicaoRepo    = $this->_em->getRepository('wms:Expedicao');

        $idPedidoAnterior = null;
        $produtos = array();
        $pedidos = array();
        $cargas = array();
        foreach ($dados as $key => $row) {
            $idCarga = $row['CARGA'];
            $idPedido = $row['PEDIDO'];

            $produto = array(
                'idProduto' => $row['PRODUTO'],
                'grade' => $row['GRADE'],
                'qtd' =>$row['QTD']
            );
            $produtos[] = $produto;

            if ((count($dados) == $key-1) || (isset($dados[$key+1]) && ($dados[$key+1]['PEDIDO'] != $idPedido))) {
                $pedidos[$idPedido] = $produtos;
                unset($produtos);
                $produtos = array();
            }

            if ((count($dados) == $key-1) || (isset($dados[$key+1]) && ($dados[$key+1]['CARGA'] != $idCarga))) {
                $cargas[$idCarga] = $pedidos;
                unset($pedidos);
                $pedidos = array();
            }
        }

        return $expedicaoRepo->compareConferenciaByCarga($cargas,$idCarga);
    }

    public function comparaResumoConferenciaExpedicao ($dados, $options) {
        $expedicaoRepo    = $this->_em->getRepository('wms:Expedicao');

        $idCarga = null;
        if (isset($options[0]) && ($options[0] != null)) {
            $idCarga = $options[0];
        } else {
            throw new \Exception("Carga não definida nos parametros da consulta");
        }

        foreach ($dados as $row) {
            if ($row['CARGA'] == $idCarga) {
                return $expedicaoRepo->campareResumoConferenciaByCarga($row['QTD'], $idCarga);
            }
        }
        throw new \Exception("Carga não encontrada na consulta do ERP");
    }

    public function processaEstoque($dados){

        $produtoRepo    = $this->_em->getRepository('wms:Produto');

        /*
         * Removo os estoques antigos
         */
        $query = $this->_em->createQuery("DELETE FROM wms:Enderecamento\EstoqueErp");
        $query->execute();

        /*
         * Insiro o novo estoque retornado pela query
         */
        $qtdIteracoes = 0;
        foreach ($dados as $valorEstoque) {
            $qtdIteracoes = $qtdIteracoes + 1;

            $codProduto = $valorEstoque['COD_PRODUTO'];
            $grade= "UNICA";

            if (isset($valorEstoque['GRADE'])) {
                $grade = $valorEstoque['GRADE'];
            }
            $produtoEn = $produtoRepo->findOneBy(array('id'=>$codProduto,'grade'=>$grade));
            if ($produtoEn!= null) {
                $estoqueErp = new EstoqueErp();
                $estoqueErp->setProduto($produtoEn);
                $estoqueErp->setCodProduto($codProduto);
                $estoqueErp->setGrade($grade);
                $estoqueErp->setEstoqueDisponivel(str_replace(',','.',$valorEstoque['ESTOQUE_DISPONIVEL']));
                $estoqueErp->setEstoqueGerencial(str_replace(',','.',$valorEstoque['ESTOQUE_GERENCIAL']));
                $estoqueErp->setFatorUnVenda(str_replace(',','.',$valorEstoque['FATOR_UNIDADE_VENDA']));
                $estoqueErp->setUnVenda($valorEstoque['DSC_UNIDADE']);
                $estoqueErp->setVlrEstoqueTotal(str_replace(',','.',$valorEstoque['VALOR_ESTOQUE']));
                $estoqueErp->setVlrEstoqueUnitario(str_replace(',','.',$valorEstoque['CUSTO_UNITARIO']));
                $this->_em->persist($estoqueErp);
            }

            if ($qtdIteracoes == 100) {
                $this->_em->flush();
                $this->_em->clear();
                $qtdIteracoes = 0;
            }

        }

        $this->_em->flush();
        return true;
    }

    public function processaPedido($dados) {
        try {

            $cargas = array();
            $pedidos = array();
            $produtos = array();

            foreach ($dados as $key => $row) {
                $idPedido = $row['PEDIDO'];
                $idCarga = $row['CARGA'];

                $produto = array(
                    'codProduto' => $row['PRODUTO'],
                    'grade'      => $row['GRADE'],
                    'quantidade' => $row['QTD'],
                    'valorVenda' => $row['VLR_VENDA']
                );
                $produtos[] = $produto;

                if (($key == count($dados)-1) || (isset($dados[$key+1]) && ($idPedido != $dados[$key+1]['PEDIDO']))) {
                    $itinerario = array (
                        'idItinerario' => $row['COD_ROTA'],
                        'nomeItinerario' => $row['DSC_ROTA']
                    );

                    $cliente = array(
                        'codCliente'  => $row['COD_CLIENTE'],
                        'bairro'      => $row['BAIRRO'],
                        'cidade'      => $row['CIDADE'],
                        'complemento' => $row['COMPLEMENTO'],
                        'cpf_cnpj'    => $row['CPF_CNPJ'],
                        'logradouro'  => $row['LOGRADOURO'],
                        'nome'        => $row['NOME'],
                        'numero'      => $row['NUMERO'],
                        'referencia'  => $row['REFERENCIA'],
                        'tipoPessoa'  => $row['TIPO_PESSOA'],
                        'uf'          => $row['UF'],
                        'cep'         => $row['CEP']
                    );

                    $pedido = array(
                        'codPedido'    => $idPedido,
                        'cliente'      => $cliente,
                        'itinerario'   => $itinerario,
                        'produtos'     => $produtos,
                        'linhaEntrega' => $row['DSC_ROTA']
                    );

                    $pedidos[] = $pedido;

                    unset($produtos);
                    $produtos = array();
                }

                if (($key == count($dados)-1) || (isset($dados[$key+1]) && ($idCarga != $dados[$key+1]['CARGA']))) {
                    $carga = array(
                        'idCarga' => $idCarga,
                        'placaExpedicao' => $row['PLACA'],
                        'placa' => $row['PLACA'],
                        'pedidos' => $pedidos
                    );
                    $cargas[] = $carga;

                    unset($pedidos);
                    $pedidos = array();
                }
                if ($row['PRODUTO'] == 6 && $row['PEDIDO'] == '33001688') {
                    var_dump($pedido);
                    var_dump($produto); exit;
                }
            }

            $wsExpedicao = new \Wms_WebService_Expedicao();
            $wsExpedicao->enviar($cargas);
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

    }

    public function processaNotasFiscais($dados)
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        $em = $this->_em;
        $importacaoService = new Importacao(true);
        $fornecedores = array();
        $itens = array();
        $notasFiscais = array();

        foreach ($dados as $key => $notaFiscal) {

            $cpf_cnpj = String::retirarMaskCpfCnpj($notaFiscal['CPF_CNPJ']);
            if (strlen($cpf_cnpj) == 11) {
                $tipoPessoa = 'F';
            } else {
                $tipoPessoa = 'J';
            }
            if ($tipoPessoa == 'F') {
                continue;
            }
            if (!array_key_exists($notaFiscal['COD_FORNECEDOR'],$fornecedores)) {
                $fornecedores[$notaFiscal['COD_FORNECEDOR']] = array(
                    'idExterno' => $notaFiscal['COD_FORNECEDOR'],
                    'cpf_cnpj' => $cpf_cnpj,
                    'nome' => $notaFiscal['NOM_FORNECEDOR'],
                    'inscricaoEstadual' => $notaFiscal['INSCRICAO_ESTADUAL'],
                    'tipoPessoa' => $tipoPessoa
                );
            }




            $itens[] = array(
                'idProduto' => $notaFiscal['COD_PRODUTO'],
                'grade' => $notaFiscal['DSC_GRADE'],
                'quantidade' => $notaFiscal['QTD_ITEM'],
                'peso' => $notaFiscal['QTD_ITEM']
            );

            $numNfAtual = $notaFiscal['NUM_NOTA_FISCAL'];
            $serieNfAtual = $notaFiscal['COD_SERIE_NOTA_FISCAL'];
            $codFornecedorNfAtual = $notaFiscal['COD_FORNECEDOR'];

            $FimNotaAtual = false;
            if (isset($dados[$key+1])) {
                $numProxNfNota = $dados[$key+1]['NUM_NOTA_FISCAL'];
                $serieProxNfNota = $dados[$key+1]['COD_SERIE_NOTA_FISCAL'];
                $codFornecedorProxNf = $dados[$key+1]['COD_FORNECEDOR'];

                if (($numNfAtual != $numProxNfNota) || ($serieNfAtual != $serieProxNfNota) || ($codFornecedorNfAtual != $codFornecedorProxNf)) {
                    $FimNotaAtual = true;
                }
            } else {
                $FimNotaAtual = true;
            }

            if ($FimNotaAtual == true) {
                $notasFiscais[] = array(
                    'codFornecedor' => $notaFiscal['COD_FORNECEDOR'],
                    'numNota' => $notaFiscal['NUM_NOTA_FISCAL'],
                    'serie' => $notaFiscal['COD_SERIE_NOTA_FISCAL'],
                    'dtEmissao' => $notaFiscal['DAT_EMISSAO'],
                    'placaVeiculo' => $notaFiscal['DSC_PLACA_VEICULO'],
                    'itens'=> $itens
                );
                unset($itens);
                $itens = array();

            }
        }

        foreach ($fornecedores as $fornecedor){
            $importacaoService->saveFornecedor($em,$fornecedor);
        }
        $em->flush();

        $count = 0;
        foreach ($notasFiscais as $nf) {
            $importacaoService->saveNotaFiscal($em, $nf['codFornecedor'], $nf['numNota'], $nf['serie'], $nf['dtEmissao'], $nf['placaVeiculo'], $nf['itens'], 'N');
            if ($count == 50) {
                $count =0;
                $em->flush();
                $em->clear();
            } else {
                $count=$count +1;
            }
        }

        $em->flush();
        return true;
    }

    public function processaProdutos($dados){
        ini_set('memory_limit', '-1');
        try {

            $repositorios = array(
                'fabricanteRepo'        => $this->_em->getRepository('wms:Fabricante'),
                'classeRepo'            => $this->_em->getRepository('wms:Produto\Classe'),
                'parametroRepo'         => $this->_em->getRepository('wms:Sistema\Parametro'),
                'produtoAndamentoRepo'  => $this->_em->getRepository('wms:Produto\Andamento'),
                'produtoRepo'           => $this->_em->getRepository('wms:Produto'),
                'enderecoRepo'          => $this->_em->getRepository('wms:Deposito\Endereco'),
                'embalagemRepo'         => $this->_em->getRepository('wms:Produto\Embalagem')
            );

            $importacaoService = new Importacao(true);

            $arrayProdutos = array();
            $arrayFabricantes = array();
            $arrayClasses = array();
            $parametroEmbalagemAtiva = $repositorios['parametroRepo']->findOneBy(array('constante' => 'SALVAR_EMBALAGEM_COMO_ATIVA'));


            /*
             * Reorganiza os arrays
             */
            foreach ($dados as $linha) {
                $codProduto = $linha['COD_PRODUTO'];
                $dscGrade = (isset($linha['DSC_GRADE']))? $linha['DSC_GRADE'] : 'UNICA';
                $dscProduto = $linha['DESCRICAO_PRODUTO'];
                $codClasseNivel1 = $linha['CODIGO_CLASSE_NIVEL_1'];
                $dscClasseNivel1 = $linha['DSC_CLASSE_NIVEL_1'];
                $codClasseNivel2 = $linha['CODIGO_CLASSE_NIVEL_2'];
                $dscClasseNivel2 = $linha['DSC_CLASSE_NIVEL_2'];
                $codFabricante = $linha['CODIGO_FABRICANTE'];
                $dscFabricante = $linha['DESCRICAO_FABRICANTE'];
                $dscEmbalagem = $linha['DESCRICAO_EMBALAGEM'];
                $indPesoVariavel = $linha['PESO_VARIAVEL'];
                $qtdEmbalagem = $linha['QTD_EMBALAGEM'];
                $codBarras = $linha['COD_BARRAS'];
                $pesoEmbalagem = $linha['PESO_BRUTO_EMBALAGEM'];
                $alturaEmbalagem = $linha['ALTURA_EMBALAGEM'];
                $larguraEmbalagem = $linha['LARGURA_EMBALAGEM'];
                $profundidadeEmbalagem = $linha['PROFUNDIDADE_EMBALAGEM'];
                $cubagemEmbalagem = $linha['CUBAGEM_EMBALAGEM'];
                $embalagemAtiva = $linha['EMBALAGEM_ATIVA'];
                $possuiValidade = (isset($linha['POSSUI_VALIDADE']))? $linha['POSSUI_VALIDADE'] : null;
                $diasVidaUtil = (isset($linha['DIAS_VIDA_UTIL']))? (int)$linha['DIAS_VIDA_UTIL'] : null;

                $codClasseProduto = $codClasseNivel1;
                if (empty($codClasseNivel1) AND !empty($codClasseNivel2)) {
                    $codClasseProduto = $codClasseNivel2;
                }

                if (!array_key_exists($codProduto,$arrayProdutos)) {
                    $arrayProdutos[$codProduto] = array('codProduto'=>$codProduto,
                                                        'dscGrade' => $dscGrade,
                                                        'dscProduto'=>$dscProduto,
                                                        'codClasse'=>$codClasseProduto,
                                                        'codFabricante'=>$codFabricante,
                                                        'indPesoVariavel'=>$indPesoVariavel,
                                                        'possuiValidade'=>$possuiValidade,
                                                        'diasVidaUtil'=>$diasVidaUtil,
                                                        'embalagem'=>array());
                }

                $arrayProdutos[$codProduto]['embalagem'][] = array('dscEmbalagem'=>$dscEmbalagem,
                                                                   'qtdEmbalagem'=>$qtdEmbalagem,
                                                                   'codBarras'=>$codBarras,
                                                                   'peso'=>$pesoEmbalagem,
                                                                   'altura'=>$alturaEmbalagem,
                                                                   'largura'=>$larguraEmbalagem,
                                                                   'profundidade'=>$profundidadeEmbalagem,
                                                                   'cubagem'=>$cubagemEmbalagem,
                                                                   'ativa'=>$embalagemAtiva);

                if (!array_key_exists($codFabricante,$arrayFabricantes)) {
                    $arrayFabricantes[$codFabricante] = array('codFabricante'=>$codFabricante,
                                                              'dscFabricante'=>$dscFabricante);
                }

                if ($codClasseNivel1 != null) {
                    if (!array_key_exists($codClasseNivel1,$arrayClasses)) {
                        $arrayClasses[$codClasseNivel1] = array('codClasse'=>$codClasseNivel1,
                                                                'dscClasse'=>$dscClasseNivel1,
                                                                'codClassePai'=>null);
                    }
                }

                if ($codClasseNivel2 != null) {
                    if (!array_key_exists($codClasseNivel2,$arrayClasses)) {
                        $arrayClasses[$codClasseNivel2] = array('codClasse'=>$codClasseNivel2,
                            'dscClasse'=>$dscClasseNivel2,
                            'codClassePai'=>$codClasseNivel1);
                    }
                }
            }

            /*
             * Persiste no banco de dados
             */
            foreach ($arrayFabricantes as $fabricante) {
                if (!empty($fabricante['codFabricante']) and !empty($fabricante['dscFabricante']))
                    $importacaoService->saveFabricante($this->_em,
                                                       $fabricante['codFabricante'],
                                                       $fabricante['dscFabricante'],
                                                       $repositorios);
            }
            $this->_em->flush();
            $this->_em->clear();

            foreach ($arrayClasses as $classe) {
                if (!empty($classe['codClasse']) and !empty($classe['dscClasse']))
                    $importacaoService->saveClasse($classe['codClasse'],
                                                   $classe['dscClasse'],
                                                   $classe['codClassePai'],
                                                   $repositorios);
            }
            $this->_em->flush();
            $this->_em->clear();

            ini_set('max_execution_time', '-1');
            foreach ($arrayProdutos as $produto) {
                $embalagensObj = array();
                foreach ($produto['embalagem'] as $embalagem) {
                    if ($parametroEmbalagemAtiva == 'S') {
                        $embalagem['ativa'] = 'S';
                    }
                    if ($embalagem['ativa'] == 'S') {
                        $emb = new embalagem();
                        $emb->codBarras = $embalagem['codBarras'];
                        $emb->qtdEmbalagem = $embalagem['qtdEmbalagem'];
                        $emb->descricao = $embalagem['dscEmbalagem'];
                        $embalagensObj[] = $emb;
                    }
                }
                $importacaoService->saveProdutoWs($this->_em,
                                                  $repositorios,
                                                  $produto['codProduto'],
                                                  $produto['dscProduto'],
                                                  $produto['dscGrade'],
                                                  $produto['codFabricante'],
                                                  '1',
                                                  $produto['codClasse'],
                                                  $produto['indPesoVariavel'],
                                                  $embalagensObj,
                                                  '',
                                                  $produto['possuiValidade'],
                                                  $produto['diasVidaUtil']);

            }
            $this->_em->flush();
            $this->_em->clear();
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

}