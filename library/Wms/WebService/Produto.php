<?php

use Wms\Domain\Entity\Produto as ProdutoEntity,
    Core\Util\Produto as ProdutoUtil;

class embalagem {
    /** @var string */
    public $codBarras;
    /** @var int */
    public $qtdEmbalagem;
    /** @var string */
    public $descricao;
    /** @var double */
    public $altura;
    /** @var double */
    public $largura;
    /** @var double */
    public $comprimento;
    /** @var double */
    public $peso;
    /** @var double */
    public $cubagem;
}

class volume {
    /** @var string */
    public $codBarras;
    /** @var string */
    public $descricao;
    /** @var double */
    public $altura;
    /** @var double */
    public $largura;
    /** @var double */
    public $comprimento;
    /** @var double */
    public $peso;
    /** @var double */
    public $cubagem;
}


class produto {
    /** @var string */
    public $idProduto;
    /** @var string */
    public $descricao;
    /** @var string */
    public $grade;
    /** @var string */
    public $idFabricante;
    /** @var string */
    public $tipo;
    /** @var string */
    public $idClasse;
    /** @var string */
    public $nomeFabricante;
    /** @var integer */
    public $estoqueArmazenado;
    /** @var integer */
    public $estoqueDisponivel;
    /** @var double */
    public $peso;
    /** @var double */
    public $cubagem;
    /** @var embalagem[] */
    public $embalagens = array();
    /** @var volume[] */
    public $volumes = array();

}

class produtos {
    /** @var produto[] */
    public $produtos = array();
}

class grade {
    /** @var string */
    public $grade;
}

class grades {
    /** @var grade[] */
    public $grades = array();
}

class Wms_WebService_Produto extends Wms_WebService {

    private function removeCaracteres($value) {
        return strtr(utf8_decode($value), utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
    }

    /**
     * Retorna um Produto específico no WMS pelo seu ID
     *
     * @param string $idProduto ID do Produto
     * @param string $grade Grade do Produto
     * @return produto|Exception
     */
    public function buscar($idProduto, $grade) {
        try {
            $idProduto = trim ($idProduto);
            $grade = (empty($grade) || $grade === "?") ? "UNICA" : trim($grade);

            $em = $this->__getDoctrineContainer()->getEntityManager();
            $produtoRepo = $em->getRepository('wms:Produto');
            $dadosProduto = $produtoRepo->getDadosLogisticos($idProduto,$grade);

            $produtoService = $this->__getServiceLocator()->getService('Produto');
            $produto = $produtoService->findOneBy(array('id' => $idProduto, 'grade'=> $grade));

            if ($produto == null) {
                throw new \Exception("Produto $idProduto grade $grade não encontrado");
            }

            $prod = new produto();
            $prod->idProduto = $idProduto;
            $prod->descricao = $this->removeCaracteres($produto->getDescricao());
            $prod->grade = $produto->getGrade();
            $prod->idFabricante = $produto->getFabricante()->getId();
            $prod->tipo = $produto->getTipoComercializacao()->getId();
            $prod->idClasse = $produto->getClasse()->getId();
            $prod->nomeFabricante = $this->removeCaracteres($produto->getFabricante()->getNome());
            $prod->estoqueArmazenado = 0;
            $prod->estoqueDisponivel = 0;
            $prod->cubagem = $dadosProduto['NUM_CUBAGEM'];
            $prod->peso = $dadosProduto['NUM_PESO'];

            foreach ($dadosProduto['EMBALAGENS'] as $embalagem) {
                $emb = new embalagem();
                $emb->altura = $embalagem['NUM_ALTURA'];
                $emb->largura = $embalagem['NUM_LARGURA'];
                $emb->comprimento = $embalagem['NUM_PROFUNDIDADE'];
                $emb->codBarras = $embalagem['COD_BARRAS'];
                $emb->peso = $embalagem['NUM_PESO'];
                $emb->qtdEmbalagem = $embalagem['QTD_EMBALAGEM'];
                $emb->descricao = $embalagem['DSC_EMBALAGEM'];
                $emb->cubagem = $embalagem['NUM_CUBAGEM'];
                $prod->embalagens[] = $emb;
            }

            foreach ($dadosProduto['VOLUMES'] as $volume) {
                $vol = new volume();
                $vol->altura = $volume['NUM_ALTURA'];
                $vol->largura = $volume['NUM_LARGURA'];
                $vol->comprimento = $volume['NUM_PROFUNDIDADE'];
                $vol->codBarras = $volume['COD_BARRAS'];
                $vol->peso = $volume['NUM_PESO'];
                $vol->descricao = $volume['DSC_VOLUME'];
                $vol->cubagem = $volume['NUM_CUBAGEM'];
                $prod->volumes[] = $vol;
            }
            return $prod;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Checa se um fabricante existe
     *
     * @param string $idFabricante
     * @return boolean|Wms\Domain\Entity\Fabricante
     */
    private function getFabricante($idFabricante) {
        $fabricante = $this->__getServiceLocator()
            ->getService('Fabricante')
            ->find($idFabricante);

        return ($fabricante == null) ? false : $fabricante;
    }

    /**
     * Checa se uma classe existe
     *
     * @param string $idClasse
     * @return boolean|Wms\Domain\Entity\Produto\Classe
     */
    private function getClasse($idClasse) {
        $classe = $this->__getServiceLocator()
            ->getService('Produto\Classe')
            ->find($idClasse);

        return ($classe == null) ? false : $classe;
    }

    /**
     * Salva um Produto no WMS. Se o Produto não existe, insere, senão, altera
     *
     * @param string $idProduto ID do produto
     * @param string $descricao Descrição
     * @param string $grade Grade
     * @param string $idFabricante ID do fabricante
     * @param string $tipo 1 => Unitário, 2 => Composto, 3 => Kit | Hoje não está sendo utilizado
     * @param string $idClasse ID da classe do produto
     * @param embalagem[] $embalagens Embalagens
     * @param string $referencia Código de Referencia do produto no fornecedor
     * @param string $possuiPesoVariavel 'N' , 'S'
     * @throws Exception
     * @return boolean Se o produto foi inserido com sucesso ou não
     */
    public function salvar($idProduto, $descricao, $grade, $idFabricante, $tipo, $idClasse, $embalagens, $referencia, $possuiPesoVariavel) {

        $idProduto = trim ($idProduto);
        $descricao = trim ($descricao);

        $idProduto = ProdutoUtil::formatar($idProduto);

        $grade = (empty($grade) || $grade === "?") ? "UNICA" : trim($grade);
        $idFabricante = trim ($idFabricante);
        $tipo = trim ($tipo);
        $idClasse = trim($idClasse);

        if ($referencia === "?" || empty($referencia)) $referencia = "";
        if ($possuiPesoVariavel === "?" || empty($possuiPesoVariavel)) $possuiPesoVariavel = "N";

        $service = $this->__getServiceLocator()->getService('Produto');
        $em = $this->__getDoctrineContainer()->getEntityManager();
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $em->getRepository('wms:Produto');

        $em->beginTransaction();

        try {
            $produto = $service->findOneBy(array('id' => $idProduto, 'grade' => $grade));

            if (!$produto) {
                $produtoNovo = true;
            } else {
                $produtoNovo = false;
            }

            if (!$produto)
                $produto = new ProdutoEntity;

            $fabricante = $this->getFabricante($idFabricante);

            if (!$fabricante)
                throw new \Exception('Fabricante inexistente');

            $classe = $this->getClasse($idClasse);

            if (!$classe)
                throw new \Exception('Classe do produto de codigo ' . $idClasse . ' inexistente');

            // define numero de volume e tipo de comercializacao do produto
            /** @var ProdutoEntity\TipoComercializacao $tipoComercializacaoEntity */
            $tipoComercializacaoEntity = $em->getReference('wms:Produto\TipoComercializacao', $tipo);
            $numVolumes = ($produto->getNumVolumes()) ? $produto->getNumVolumes() : 1;

            $produto->setId($idProduto)
                ->setDescricao($descricao)
                ->setGrade($grade)
                ->setFabricante($fabricante)
                ->setClasse($classe)
                ->setReferencia($referencia)
                ->setPossuiPesoVariavel($possuiPesoVariavel);

            if ($produtoNovo == true) {
                $produto
                    ->setTipoComercializacao($tipoComercializacaoEntity)
                    ->setNumVolumes($numVolumes)
                    ->setIndFracionavel('N')
                    ->setIndControlaLote((isset($indControlaLote) && !empty($indControlaLote)) ? $indControlaLote : 'N');
            } else {
                $valorAtual = $produto->getIndControlaLote();
                $produto->setIndControlaLote((isset($indControlaLote) && !empty($indControlaLote)) ? $indControlaLote : $valorAtual);
            }

            $em->persist($produto);

            $parametroRepo = $em->getRepository('wms:Sistema\Parametro');
            $parametro = $parametroRepo->findOneBy(array('constante' => 'INTEGRACAO_CODIGO_BARRAS'));

            //VERIFICA SE VAI RECEBER AS EMBALAGENS OU NÃO
            if ($parametro->getValor() == 'S') {

                $embalagensArray = array();

                //PRIMEIRO INATIVA AS EMBALAGENS NÃO ENVIADAS
                /** @var ProdutoEntity\Embalagem $embalagemCadastrada */
                foreach ($produto->getEmbalagens() as $embalagemCadastrada) {
                    $descricaoEmbalagem = null;
                    $encontrouEmbalagem = false;

                    $fator = $embalagemCadastrada->getQuantidade();
                    foreach ($embalagens as $embalagemWs) {

                        if (trim($embalagemWs->codBarras) == trim($embalagemCadastrada->getCodigoBarras())) {
                            $encontrouEmbalagem = true;
                            $descricaoEmbalagem =  $embalagemWs->descricao;
                            $fator = $embalagemWs->qtdEmbalagem;

                            //if ($embalagemWs->qtdEmbalagem != $embalagemCadastrada->getQuantidade()) {
                            //    throw new \Exception ("Não é possivel trocar a quantidade por embalagem da unidade " . $embalagemWs->descricao . " para " . $embalagemWs->qtdEmbalagem);
                            //}

                            continue;
                        }
                    }
                    $endPicking = null;
                    if ($embalagemCadastrada->getEndereco() != null ) {
                        $endPicking = $embalagemCadastrada->getEndereco()->getDescricao();
                    }

                    $embalagemArray = array(
                        'acao'=> 'alterar',
                        'quantidade' => $fator ,
                        'id' =>$embalagemCadastrada->getId(),
                        'endereco' => $endPicking,
                        'codigoBarras' => $embalagemCadastrada->getCodigoBarras(),
                        'CBInterno' => $embalagemCadastrada->getCBInterno(),
                        'embalado' => $embalagemCadastrada->getEmbalado(),
                        'capacidadePicking' =>$embalagemCadastrada->getCapacidadePicking(),
                        'pontoReposicao' =>$embalagemCadastrada->getPontoReposicao(),
                        'descricao' => $descricaoEmbalagem,
                        'isEmbExpDefault' => $embalagemCadastrada->isEmbExpDefault(),
                        'isEmbFracionavelDefault' => $embalagemCadastrada->isEmbFracionavelDefault()
                    );

                    if ($encontrouEmbalagem == false) {
                        $embalagemArray['ativarDesativar'] = false;
                    } else {
                        $embalagemArray['ativarDesativar'] = true;
                    }

                    $embalagensArray[] = $embalagemArray;

                }

                //throw new \Exception(count($embalagemArray));

                //DEPOIS INCLUO AS NOVAS EMBALAGENS
                foreach ($embalagens as $embalagemWs) {

                    $encontrouEmbalagem = false;
                    foreach ($produto->getEmbalagens() as $embalagemCadastrada) {
                        if (trim($embalagemWs->codBarras) == trim($embalagemCadastrada->getCodigoBarras())) {
                            $encontrouEmbalagem = true;
                            continue;
                        }
                    }

                    if ($encontrouEmbalagem == false) {

                        $embalagemArray = array (
                            'acao' => 'incluir',
                            'descricao' => $embalagemWs->descricao,
                            'quantidade' => $embalagemWs->qtdEmbalagem,
                            'isPadrao' => 'N',
                            'CBInterno' => 'N',
                            'imprimirCB' => 'N',
                            'codigoBarras' => $embalagemWs->codBarras,
                            'embalado' => 'N',
                            'capacidadePicking' => 0,
                            'pontoReposicao' => 0,
                            'endereco' => null,
                            'isEmbExpDefault' => 'N',
                            'isEmbFracionavelDefault' => 'N'
                        );
                        $embalagensArray[] = $embalagemArray;
                    }
                }

                $embalagensPersistir = array('embalagens'=>$embalagensArray);
                $produtoRepo->persistirEmbalagens($produto, $embalagensPersistir,true);
            }

            $em->flush();
            $produtoRepo->atualizaPesoProduto($idProduto,$grade);
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }
        return true;
    }

    private function verificaCodigoBarrasDuplicado($codBarras, $idProduto, $grade) {
        $SQL = "SELECT P.COD_PRODUTO, P.DSC_PRODUTO
                  FROM PRODUTO P
                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE
                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
                 WHERE NVL(PE.COD_BARRAS, PV.COD_BARRAS) = '$codBarras'
                   AND P.COD_PRODUTO <> '$idProduto'
                   AND P.DSC_GRADE <> '$grade'";

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $produtos =  $em->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        if (count($produtos) >0) {
            $prod = $produtos[0];
            throw new \Exception("A Embalagem " . $codBarras ." se encontra em uso no sistema para o produto " . $prod['COD_PRODUTO'] . "/" . $prod['DSC_PRODUTO']);
        }
        return true;
    }

    /**
     * Exclui um Produto do WMS
     *
     * @param string $idProduto ID do produto
     * @param string $grade Grade
     * @return boolean|Exception
     */
    public function excluir($idProduto, $grade) {

        $idProduto = trim ($idProduto);
        $grade = (empty($grade) || $grade === "?") ? "UNICA" : trim($grade);

        $em = $this->__getDoctrineContainer()->getEntityManager();
        $service = $this->__getServiceLocator()->getService('Produto');
        $em->beginTransaction();

        try {
            $produto = $service->findOneBy(array('id' => $idProduto, 'grade' => $grade));

            if (!$produto)
                throw new \Exception('Não existe produto com esse codigo no sistema');

            $em->remove($produto);
            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Lista todos os Produtos cadastrados no sistema
     *
     * @return produtos|Exception
     */
    public function listar() {
        $em = $this->__getDoctrineContainer()->getEntityManager();

        $result = $em->createQueryBuilder()
            ->select('p.id as idProduto, p.descricao, p.grade, f.id as idFabricante, t.id as tipo, c.id as idClasse, f.nome as nomeFabricante')
            ->from('wms:Produto', 'p')
            ->innerJoin('p.fabricante', 'f')
            ->innerJoin('p.classe', 'c')
            ->innerJoin('p.tipoComercializacao', 't')
            ->orderBy('p.descricao')->getQuery()
            ->getArrayResult();
        $produtos = new produtos();
        $arrayProdutos = array();

        $produtoRepo = $em->getRepository('wms:Produto');


        foreach ($result as $line) {

            $dadosProduto = $produtoRepo->getDadosLogisticos($line['idProduto'],$line['grade']);

            $produto = new produto();
            $produto->idProduto = $line['idProduto'];
            $produto->descricao = $this->removeCaracteres($line['descricao']);
            $produto->grade = $line['grade'];
            $produto->idFabricante = $line['idFabricante'];
            $produto->tipo = $line['tipo'];
            $produto->idClasse = $line['idClasse'];
            $produto->nomeFabricante = $this->removeCaracteres($line['nomeFabricante']);
            $produto->estoqueArmazenado = 0;
            $produto->estoqueDisponivel = 0;
            $produto->cubagem = $dadosProduto['NUM_CUBAGEM'];
            $produto->peso = $dadosProduto['NUM_PESO'];
            foreach ($dadosProduto['EMBALAGENS'] as $embalagem) {
                $emb = new embalagem();
                $emb->altura = $embalagem['NUM_ALTURA'];
                $emb->largura = $embalagem['NUM_LARGURA'];
                $emb->comprimento = $embalagem['NUM_PROFUNDIDADE'];
                $emb->codBarras = $embalagem['COD_BARRAS'];
                $emb->peso = $embalagem['NUM_PESO'];
                $emb->qtdEmbalagem = $embalagem['QTD_EMBALAGEM'];
                $emb->descricao = $embalagem['DSC_EMBALAGEM'];
                $emb->cubagem = $embalagem['NUM_CUBAGEM'];
                $produto->embalagens[] = $emb;
            }

            foreach ($dadosProduto['VOLUMES'] as $volume) {
                $vol = new volume();
                $vol->altura = $volume['NUM_ALTURA'];
                $vol->largura = $volume['NUM_LARGURA'];
                $vol->comprimento = $volume['NUM_PROFUNDIDADE'];
                $vol->codBarras = $volume['COD_BARRAS'];
                $vol->peso = $volume['NUM_PESO'];
                $vol->descricao = $volume['DSC_VOLUME'];
                $vol->cubagem = $volume['NUM_CUBAGEM'];
                $produto->volumes[] = $vol;
            }
            $arrayProdutos[] = $produto;
        }
        $produtos->produtos = $arrayProdutos;

        return $produtos;
    }

    /**
     * @param string $idProduto
     * @param string $descricao
     * @param int $idFabricante
     * @param int $tipo
     * @param int $idClasse
     * @param array $grades
     * @param array $classes
     * @param array $fabricante
     * @return array
     */
    public function salvarCompleto($idProduto, $descricao, $idFabricante, $tipo, $idClasse, array $grades, array $classes, array $fabricante)
    {
        try {
            $idProduto = trim ($idProduto);
            $idProduto = ProdutoUtil::formatar($idProduto);
            $descricao = trim ($descricao);
            $idFabricante = trim($idFabricante);
            $tipo = trim($tipo);

            $wsClasse = new Wms_WebService_ProdutoClasse();
            foreach($classes as $classe)
                $wsClasse->salvar(trim($classe['idClasse']), trim($classe['nome']), $classe['idClassePai']);
            unset($wsClasse);

            $wsFabricante  = new Wms_WebService_Fabricante();
            $wsFabricante->salvar(trim($fabricante['idFabricante']), trim($fabricante['nome']));
            unset($wsFabricante);

            if( empty($grades) )
                throw new \Exception('O array de grades deve ser informado.');
            foreach($grades as $grade)
                $this->salvar($idProduto, $descricao, trim($grade), $idFabricante, $tipo, $idClasse,null,'','N');
        } catch(\Exception $e) {
            throw new \Exception($e->getMessage());
        }

        return true;
    }

}