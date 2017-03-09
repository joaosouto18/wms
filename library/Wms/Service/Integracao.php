<?php

namespace Wms\Service;


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

    public function processaAcao() {
        Try {
            switch ($this->getAcao()->getTipoAcao()->getId()) {
                case AcaoIntegracao::INTEGRACAO_PRODUTO:
                    return $this->processaProdutos($this->_dados);
                case AcaoIntegracao::INTEGRACAO_ESTOQUE:
                    return $this->processaEstoque($this->_dados);
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
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

    public function processaProdutos($dados){
        ini_set('memory_limit', '-1');
        try {

            $repositorios = array(
                'fabricanteRepo'        => $this->_em->getRepository('wms:Fabricante'),
                'classeRepo'            => $this->_em->getRepository('wms:Produto\Classe'),
                'parametroRepo'         => $this->_em->getRepository('wms:Sistema\Parametro'),
                'produtoAndamentoRepo'  => $this->_em->getRepository('wms:Produto\Andamento'),
                'produtoRepo'  => $this->_em->getRepository('wms:Produto'),
                'enderecoRepo'          => $this->_em->getRepository('wms:Deposito\Endereco'),
                'embalagemRepo'         => $this->_em->getRepository('wms:Produto\Embalagem')
            );

            $importacaoService = new Importacao(true);

            $arrayProdutos = array();
            $arrayFabricantes = array();
            $arrayClasses = array();

            /*
             * Reorganiza os arrays
             */
            foreach ($dados as $linha) {
                $codProduto = $linha['COD_PRODUTO'];
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

                $codClasseProduto = $codClasseNivel1;
                if (($codClasseNivel2 != null) AND ($codClasseNivel1 != null)) {
                    $codClasseProduto = $codClasseNivel2;
                }

                if (!array_key_exists($codProduto,$arrayProdutos)) {
                    $arrayProdutos[$codProduto] = array('codProduto'=>$codProduto,
                                                        'dscProduto'=>$dscProduto,
                                                        'codClasse'=>$codClasseProduto,
                                                        'codFabricante'=>$codFabricante,
                                                        'indPesoVariavel'=>$indPesoVariavel,
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
                $importacaoService->saveFabricante($this->_em,
                                                   $fabricante['codFabricante'],
                                                   $fabricante['dscFabricante'],
                                                   $repositorios);
            }
            $this->_em->flush();

            foreach ($arrayClasses as $classe) {
                $importacaoService->saveClasse($classe['codClasse'],
                                               $classe['dscClasse'],
                                               $classe['codClassePai'],
                                               $repositorios);
            }
            $this->_em->flush();
            ini_set('max_execution_time', '-1');
            foreach ($arrayProdutos as $produto) {
                $embalagensObj = array();
                foreach ($produto['embalagem'] as $embalagem) {
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
                                                  "UNICA",
                                                  $produto['codFabricante'],
                                                  '1',
                                                  $produto['codClasse'],
                                                  $embalagensObj,
                                                  '');

            }
            $this->_em->flush();
            return true;
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }

    }

}