<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository,
    Core\Util\Produto;

class EstoqueRepository extends EntityRepository
{

    public function movimentaEstoque($codProduto, $grade, $idEndereco, $qtd, $idPessoa , $observacoes = "", $tipo = "S", $idOs = NULL, $codUnitizador = NULL, $idUma = NULL)
    {

        $em = $this->getEntityManager();

        $enderecoRepo = $em->getRepository('wms:Deposito\Endereco');

        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoEn = $em->getRepository('wms:Deposito\Endereco')->findOneBy(array('id'=>$idEndereco));
        $produtoEn = $em->getRepository("wms:Produto")->findOneBy(array('id'=>$codProduto, 'grade'=>$grade));
        $pessoaEn = $em->getRepository("wms:Pessoa")->find($idPessoa);
        $estoqueEn = $em->getRepository('wms:Enderecamento\Estoque')->findOneBy(array('codProduto' => $codProduto, 'grade' => $grade, 'depositoEndereco' => $enderecoEn));

        if($enderecoEn== null)
            throw new \Exception("Endereço $idEndereco não encontrado");
        if($produtoEn== null)
            throw new \Exception("Produto $codProduto / $grade não encontrado");

        //IGNORO QUALQUER MOVIMENTAÇÃO NO ENDEREÇO DE PICKING
        //RETIRAR QUANDO IMPLANTAR RESSUPRIMENTO
        $tipoPicking = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'ID_CARACTERISTICA_PICKING'))->getValor();
        if ($enderecoEn->getIdCaracteristica() == $tipoPicking)
            return true;

        $osEn = NULL;
        $qtdAdjacentes = 1;
        $unitizadorEn = NULL;
        $paleteEn = NULL;

        if ($idOs != NULL) {
            $osEn = $em->getRepository("wms:OrdemServico")->find($idOs);
        }
        if ($codUnitizador != NULL) {
            $unitizadorEn = $em->getRepository("wms:Armazenagem\Unitizador")->find($codUnitizador);
            $qtdAdjacentes = $unitizadorEn->getQtdOcupacao();
        }

        //ATUALIZA A TABELA ESTOQUE COM O SALDO DE ESTOQUE
            if ($estoqueEn == NULL) {
                $novaQtd = $qtd;
                $estoqueEn = new Estoque();
                $estoqueEn->setDepositoEndereco($enderecoEn);
                $estoqueEn->setProduto($produtoEn);
                $estoqueEn->setDtPrimeiraEntrada(new \DateTime());
                $estoqueEn->setQtd($qtd);
                $estoqueEn->setUma($idUma);
                $estoqueEn->setUnitizador($unitizadorEn);

                $dscEndereco = $enderecoEn->getDescricao();
                $dscProduto  = $produtoEn->getDescricao();
            } else {
                $idUma = $estoqueEn->getUma();
                $novaQtd = $estoqueEn->getQtd() + $qtd;
                $dscEndereco = $estoqueEn->getDepositoEndereco()->getDescricao();
                $dscProduto  = $estoqueEn->getProduto()->getDescricao();

                if ($estoqueEn->getUnitizador() != NULL) {
                    $qtdAdjacentes = $estoqueEn->getUnitizador()->getQtdOcupacao();
                }
                $estoqueEn->setQtd($novaQtd);
            }

            if ($novaQtd < 0) {
                throw new \Exception("Não é permitido estoque negativo para o endereço $dscEndereco com o produto $codProduto / $grade - $dscProduto");
            } else if ($novaQtd > 0) {
                $em->persist($estoqueEn);
            } else {
                $em->remove($estoqueEn);
            }

            if ($idUma != NULL) {
                $umaEn = $em->getRepository("wms:Enderecamento\Palete")->findOneBy(array('id'=>$idUma));
                $umaEn->setQtdEnderecada($novaQtd);
                $em->persist($umaEn);
            }

        $em->flush();

        //CRIA UM HISTÓRICO DE MOVIMENTAÇÃO DE ESTOQUE
            $historico = new HistoricoEstoque();
                $historico->setQtd($qtd);
                $historico->setData(new \DateTime());
                $historico->setDepositoEndereco($enderecoEn);
                $historico->setObservacao($observacoes);
                $historico->setOrdemServico($osEn);
                $historico->setTipo($tipo);
                $historico->setPessoa($pessoaEn);
                $historico->setProduto($produtoEn);
                $historico->setUnitizador($unitizadorEn);
            $em->persist($historico);

        //VERIFICA SE O ENDERECO VAI ESTAR DISPONIVEL OU NÃO PARA ENDEREÇAMENTO
        if ($novaQtd >0) {
            $enderecoRepo->ocuparLiberarEnderecosAdjacentes($idEndereco, $qtdAdjacentes, "OCUPAR");
        } else {
            $enderecoRepo->ocuparLiberarEnderecosAdjacentes($idEndereco, $qtdAdjacentes, "LIBERAR");
        }

        $em->flush();

        return true;
    }

    public function getEstoquePulmaoByProduto ($codProduto, $grade, $maxResult = 5)
    {
        $tipoPicking = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'ID_CARACTERISTICA_PICKING'))->getValor();

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e.descricao, estq.qtd, estq.dtPrimeiraEntrada, e.id")
            ->from("wms:Enderecamento\Estoque",'estq')
            ->innerJoin("estq.depositoEndereco", "e")
            ->where("estq.codProduto = '$codProduto'")
            ->andWhere("estq.grade = '$grade'")
            ->andWhere("e.idCaracteristica != '$tipoPicking'")
            ->orderBy("estq.dtPrimeiraEntrada");
        if ($maxResult != false) {
            $result = $query->getQuery()->setMaxResults($maxResult)->getArrayResult();
        } else {
            $result = $query->getQuery()->getArrayResult();
        }
        return $result;
    }

    public function getEstoquePulmao($parametros)
    {
        $tipoPicking = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'ID_CARACTERISTICA_PICKING'))->getValor();

        $and="";
        $cond="";
        if (isset($parametros['uma']) && !empty($parametros['uma'])) {
            $cond.=$and.'E.UMA = \''.$parametros['uma'].'\'';
            $and=" and ";
        } else {
            if (isset($parametros['idProduto']) && !empty($parametros['idProduto'])) {
                $cond.=$and.'P.COD_PRODUTO = '.$parametros['idProduto'];
                $and=" and ";
                if (isset($parametros['grade']) && !empty($parametros['grade'])) {
                    $cond.=$and.'P.DSC_GRADE = \''.$parametros['grade'].'\'';
                    $and=" and ";
                } else {
                    $cond.=$and.'P.DSC_GRADE = \'UNICA\'';
                    $and=" and ";
                }
            }

            if (isset($parametros['idNormaPaletizacao']) && !empty($parametros['idNormaPaletizacao'])) {
                $cond.=$and.'U.COD_UNITIZADOR = '.$parametros['idNormaPaletizacao'];
                $and=" and ";
            }

            if (isset($parametros['rua']) && !empty($parametros['rua'])) {
                $cond.=$and.'DE.NUM_RUA = '.$parametros['rua'];
                $and=" and ";
            }
            if (isset($parametros['predio']) && !empty($parametros['predio'])) {
                $cond.=$and.'DE.NUM_PREDIO = '.$parametros['predio'];
                $and=" and ";
            }
            if (isset($parametros['nivel']) && !empty($parametros['nivel'])) {
                $cond.=$and.'DE.NUM_NIVEL = '.$parametros['nivel'];
                $and=" and ";
            }
            if (isset($parametros['apto']) && !empty($parametros['apto'])) {
                $cond.=$and.'DE.NUM_APARTAMENTO = '.$parametros['apto'];
                $and=" and ";
            }
        }

        $condPicking=str_replace("E.","P.",$cond);

        $SQL="
            SELECT * FROM
                (
                    SELECT
                      NULL as \"descricao\",
                      U.DSC_UNITIZADOR as \"unitizador\",
                      DE.COD_DEPOSITO_ENDERECO as \"id\",
                      E.QTD as \"qtd\",
                      E.DTH_PRIMEIRA_MOVIMENTACAO as \"dtPrimeiraEntrada\",
                      P.COD_PRODUTO as \"codProduto\",
                      P.DSC_GRADE as \"grade\",
                      P.DSC_PRODUTO as \"produto\",
                      DE.DSC_DEPOSITO_ENDERECO as \"enderecoPicking\"
                    FROM
                      PRODUTO P
                    LEFT JOIN PRODUTO_VOLUME  PV
                      ON P.COD_PRODUTO=PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE
                    LEFT JOIN PRODUTO_EMBALAGEM  PE
                      ON P.COD_PRODUTO=PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE
                    LEFT JOIN DEPOSITO_ENDERECO  DE
                      ON PV.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                      OR PE.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                    LEFT JOIN ESTOQUE E
                      ON E.COD_PRODUTO = P.COD_PRODUTO AND E.DSC_GRADE = P.DSC_GRADE AND E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                    LEFT JOIN UNITIZADOR  U
                      ON E.COD_UNITIZADOR=U.COD_UNITIZADOR
                    WHERE
                      DE.COD_CARACTERISTICA_ENDERECO=".$tipoPicking." and ".$cond."
                    GROUP BY DE.DSC_DEPOSITO_ENDERECO,
                             U.DSC_UNITIZADOR,
                             DE.COD_DEPOSITO_ENDERECO,
                             E.QTD,
                             E.DTH_PRIMEIRA_MOVIMENTACAO,
                             P.COD_PRODUTO,
                             P.DSC_GRADE,
                             P.DSC_PRODUTO
                    ORDER BY P.COD_PRODUTO,
                             P.DSC_GRADE,
                             E.QTD,
                             E.DTH_PRIMEIRA_MOVIMENTACAO
                )
                 UNION ALL

            SELECT * FROM
                (
                SELECT
                  DE.DSC_DEPOSITO_ENDERECO as \"descricao\",U.DSC_UNITIZADOR as \"unitizador\", E.COD_DEPOSITO_ENDERECO as \"id\", E.QTD as \"qtd\", E.DTH_PRIMEIRA_MOVIMENTACAO as \"dtPrimeiraEntrada\", E.COD_PRODUTO as \"codProduto\", E.DSC_GRADE as \"grade\", P.DSC_PRODUTO as \"produto\", NULL as \"enderecoPicking\"
                FROM
                  ESTOQUE E
                INNER JOIN DEPOSITO_ENDERECO DE
                  ON E.COD_DEPOSITO_ENDERECO=DE.COD_DEPOSITO_ENDERECO
                INNER JOIN PRODUTO  P
                  ON E.COD_PRODUTO=P.COD_PRODUTO AND E.DSC_GRADE=P.DSC_GRADE
                LEFT JOIN UNITIZADOR  U
                  ON E.COD_UNITIZADOR=U.COD_UNITIZADOR
                LEFT JOIN PRODUTO_VOLUME  PV
                  ON P.COD_PRODUTO=PV.COD_PRODUTO
                LEFT JOIN DEPOSITO_ENDERECO  PVE
                  ON PV.COD_DEPOSITO_ENDERECO=PVE.COD_DEPOSITO_ENDERECO
                LEFT JOIN PRODUTO_EMBALAGEM  PE
                  ON P.COD_PRODUTO=PE.COD_PRODUTO
                LEFT JOIN DEPOSITO_ENDERECO  PEE
                  ON PE.COD_DEPOSITO_ENDERECO=PEE.COD_DEPOSITO_ENDERECO
                WHERE
                  DE.COD_CARACTERISTICA_ENDERECO<>".$tipoPicking." and ".$cond."
                GROUP BY
                  DE.DSC_DEPOSITO_ENDERECO,U.DSC_UNITIZADOR, E.COD_DEPOSITO_ENDERECO, E.QTD, E.DTH_PRIMEIRA_MOVIMENTACAO, E.COD_PRODUTO, E.DSC_GRADE, P.DSC_PRODUTO
                ORDER BY
                  E.COD_PRODUTO,E.DSC_GRADE,E.QTD,E.DTH_PRIMEIRA_MOVIMENTACAO
                )
        ";

        $resultado = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $groupByProduto = array();
        foreach ($resultado as $chv => $data) {

            $id = $data['codProduto'].$data['grade'];
            $data['dtPrimeiraEntrada']=new \DateTime($data['dtPrimeiraEntrada']);
            if (isset($groupByProduto[$id])) {
                $groupByProduto[$id][] = $data;
            } else {
                $groupByProduto[$id] = array($data);
            }

        }
		return $groupByProduto;

    }

    public function getEstoqueByRua($inicioRua, $fimRua, $grandeza = null,$exibePicking = 1, $exibePulmao = 1)
    {
        $tipoPicking = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'ID_CARACTERISTICA_PICKING'))->getValor();

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("e.descricao, estq.codProduto, estq.grade, p.descricao nomeProduto")
            ->from("wms:Enderecamento\Estoque",'estq')
            ->innerJoin("estq.depositoEndereco", "e")
            ->innerJoin("estq.produto", "p")
            ->orderBy("e.descricao, p.id, p.grade, estq.dtPrimeiraEntrada");

        if ($inicioRua) {
            $query->andWhere('e.rua >= :inicioRua');
            $query->setParameter('inicioRua',$inicioRua);
        }

        if ($fimRua) {
            $query->andWhere('e.rua <= :fimRua');
            $query->setParameter('fimRua',$fimRua);
        }

        if (!empty($grandeza)) {
            $grandeza = implode(',',$grandeza);
            $query->andWhere("p.linhaSeparacao in ($grandeza)");
        }

        if (($exibePulmao == 1) && ($exibePicking == 0)) {
            $query->andWhere("e.nivel != '" . $tipoPicking . "'");
        }

        if (($exibePulmao == 0) && ($exibePicking == 1)) {
            $query->andWhere("e.idCaracteristica = '" . $tipoPicking . "'");
        }

        return $query->getQuery()->getResult();
    }

    public function saldo($params)
    {
        $tipoPicking = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'ID_CARACTERISTICA_PICKING'))->getValor();
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('estq.codProduto, estq.grade, ls.descricao, sum(estq.qtd) qtdestoque, NVL(depv.descricao, depe.descricao) enderecoPicking')
            ->from("wms:Enderecamento\Estoque",'estq')
            ->innerJoin("estq.produto", "p")
            ->leftJoin("p.volumes", 'pv')
            ->leftJoin("pv.endereco", 'depv')
            ->leftJoin("p.embalagens", 'pe')
            ->leftJoin("pe.endereco", 'depe')
            ->innerJoin("p.linhaSeparacao", "ls")
            ->innerJoin("estq.depositoEndereco", "e")
            ->groupBy('estq.codProduto, estq.grade, ls.descricao, depv.descricao, depe.descricao');

        if(!empty($params['grandeza']))
        {
           $grandeza = $params['grandeza'];
           $grandeza = implode(',',$grandeza);
           $query->andWhere("p.linhaSeparacao in ($grandeza)");
        }

        if (!empty($params['inicioRua'])) {
            $query->andWhere('e.rua >= :inicioRua');
            $query->setParameter('inicioRua',$params['inicioRua']);
        }

        if (!empty($params['fimRua'])) {
            $query->andWhere('e.rua <= :fimRua');
            $query->setParameter('fimRua',$params['fimRua']);
        }

        if (($params['pulmao'] == 1) && ($params['picking'] == 0)) {
            $query->andWhere("e.nivel !=  '". $tipoPicking . "'");
        }

        if (($params['pulmao'] == 0) && ($params['picking'] == 1)) {
            $query->andWhere("e.idCaracteristica = '" . $tipoPicking ."'");
        }

        return $query->getQuery()->getResult();
    }

    public function getExisteEnderecoPulmao ($codProduto, $grade)
    {
        $tipoPicking = $this->_em->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'ID_CARACTERISTICA_PICKING'))->getValor();
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('estq.codProduto, estq.grade')
            ->from("wms:Enderecamento\Estoque",'estq')
            ->innerJoin("estq.depositoEndereco", "dep")
            ->where("dep.idCaracteristica != '$tipoPicking'")
            ->andWhere("estq.codProduto = '$codProduto'")
            ->andWhere("estq.grade = '$grade'");

        $estoque = $query->getQuery()->getResult();

        if (count($estoque) == 0) {
            return false;
        } else {
            return true;
        }
    }

    public function imprimeMovimentacaoAvulsa($codProduto, $grade, $quantidade, $endereco)
    {
        $dadosPalete = array();
        $dadosRelatorio = array();
        $paletes = array();

        $dadosPalete['idUma'] = 0;
        $dadosPalete['endereco'] = $endereco;
        $dadosPalete['qtd'] = $quantidade;
        $paletes[] = $dadosPalete;

        $dadosRelatorio['idRecebimento'] = 0;
        $dadosRelatorio['codProduto'] = $codProduto;
        $dadosRelatorio['grade'] = $grade;
        $dadosRelatorio['paletes'] = $paletes;

        $Uma = new \Wms\Module\Enderecamento\Printer\UMA('L');
        $Uma->imprimir($dadosRelatorio,$this->getSystemParameterValue("MODELO_RELATORIOS"));

    }

    public function getEstoqueConsolidado($params)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('estq.codProduto, estq.grade,  ls.descricao, sum(estq.qtd) qtdestoque, p.descricao nomeProduto')
            ->from("wms:Enderecamento\Estoque",'estq')
            ->innerJoin("estq.produto", "p")
            ->innerJoin("p.linhaSeparacao", "ls")
            ->innerJoin("estq.depositoEndereco", "e")
            ->groupBy('estq.codProduto, estq.grade, ls.descricao, p.descricao');

        $grandeza = $params['grandeza'];
        if (!empty($grandeza)) {
            $grandeza = implode(',',$grandeza);
            $query->andWhere("p.linhaSeparacao in ($grandeza)");
        }

        return $query->getQuery()->getResult();
    }


    public function getSituacaoEstoque($params) {

        $query = $this->getEntityManager()->createQueryBuilder()
        ->select("de.descricao,
                 NVL(NVL(NVL(e.codProduto, p.codProduto),pv.codProduto),pe.codProduto) as codProduto,
                 NVL(NVL(NVL(e.grade, p.grade),pv.grade),pe.grade) as grade,
                 NVL(e.qtd,p.qtd) as qtd,
                 p.id as uma,
                 r.id as idRecebimento,
                 s.sigla as sigla
                 ")
        ->from("wms:Deposito\Endereco", 'de')
        ->leftJoin("wms:Enderecamento\Estoque", "e", "WITH", "e.depositoEndereco = de.id")
        ->leftJoin("wms:Enderecamento\Palete", "p", "WITH", "p.depositoEndereco = de.id  AND p.codStatus !=". Palete::STATUS_ENDERECADO . " AND p.codStatus !=" . Palete::STATUS_CANCELADO)
        ->leftJoin("wms:Produto\Volume", "pv", "WITH", "de.id = pv.codEndereco ")
        ->leftJoin("wms:Produto\Embalagem", "pe", "WITH", "de.id = pe.codEndereco ")
        ->leftJoin("p.recebimento", "r")
        ->leftJoin("p.status", "s")
        ->distinct(true)
        ->orderBy("de.descricao");

        $query->andWhere('(pv.id is  null and pe.id is  null)');

        if (!empty($params['rua'])) {
            $query->andWhere('de.rua = :rua');
            $query->setParameter('rua',$params['rua']);
        }

        if (!empty($params['predio'])) {
            $query->andWhere('de.predio = :predio');
            $query->setParameter('predio',$params['predio']);
        }

        if (!empty($params['nivel'])) {
            $query->andWhere('de.nivel = :nivel');
            $query->setParameter('nivel',$params['nivel']);
        }

        if (!empty($params['apartamento'])) {
            $query->andWhere('de.apartamento = :apartamento');
            $query->setParameter('apartamento',$params['apartamento']);
        }

        if (($params['mostraOcupado'])== 0 ){
            $query-> andWhere('((e.codProduto IS NULL) AND (p.codProduto IS NULL))');
        }

        $result =$query->getQuery()->getResult();
        return $result;
    }

    public function getProdutoByNivel($dscEndereco, $nivel, $unico = true) {
        $em = $this->getEntityManager();
        $tempEndereco = "a";

        if (strlen($dscEndereco) < 8) {
            $rua = 0;
            $predio = 0;
            $nivel = 0;
            $apartamento = 0;
        } else {
            $dscEndereco = str_replace('.','',$dscEndereco);
            if (strlen($dscEndereco) == 8){
                $tempEndereco = "0" . $dscEndereco;
            } else {
                $tempEndereco = $dscEndereco;
            }
            $rua = intval( substr($tempEndereco,0,2));
            $predio = intval(substr($tempEndereco,2,3));
            $apartamento = intval(substr($tempEndereco,7,2));
        }

        $dql = $em->createQueryBuilder()
            ->select('dep.rua, dep.nivel, dep.predio, dep.apartamento, e.uma, e.id, dep.id as idEndereco' )
            ->from("wms:Enderecamento\Estoque", "e")
            ->InnerJoin("e.depositoEndereco", "dep")
            ->where("dep.rua = $rua")
            ->andWhere("dep.predio = $predio")
            ->andWhere("dep.nivel = $nivel")
            ->andWhere("dep.apartamento =  $apartamento");
            $result = $dql->getQuery()->getArrayResult();
        return $result;
    }

    public function getProdutoByUMA($codigoBarrasUMA, $idEndereco)
    {
        $em = $this->getEntityManager();
        $sql=$em->createQueryBuilder()
            ->select('p.descricao, p.id, p.grade, e.qtd, de.descricao as endereco')
            ->from("wms:Enderecamento\Estoque", "e")
            ->innerJoin("e.depositoEndereco", "de")
            ->innerJoin("e.produto", "p")
            ->where("e.uma = $codigoBarrasUMA")
            ->andWhere("de.id = $idEndereco");

        $result = $sql->getQuery()->getArrayResult();
      return $result;
    }

    public function getProdutoByCodBarrasAndEstoque($etiquetaProduto, $idEndereco)
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('p.descricao, p.id, p.grade, e.qtd, de.descricao as endereco')
            ->from("wms:Enderecamento\Estoque","e")
            ->innerJoin("e.produto","p")
            ->leftJoin("e.depositoEndereco", "de")
            ->leftJoin('p.embalagens', 'pe')
            ->leftJoin('p.volumes', 'pv')
            ->where('(pe.codigoBarras = :codigoBarras OR pv.codigoBarras = :codigoBarras)')
            ->andWhere("de.id = :idEndereco")
            ->setParameters(
                array(
                    'codigoBarras' => $etiquetaProduto,
                    'idEndereco' => $idEndereco,
                )
            );
        $result = $dql->getQuery()->setMaxResults(1)->getArrayResult();
        return $result;
    }

    public function getQtdEstoqueByProdutoAndEndereco($codProduto,$grade,$idEndereco) {
        $SQL = "SELECT CASE WHEN SUM(QTD) IS NULL THEN 0 ELSE SUM (QTD) END AS QTD
                  FROM ESTOQUE
                 WHERE COD_PRODUTO = '$codProduto' AND DSC_GRADE = '$grade' AND COD_DEPOSITO_ENDERECO = '$idEndereco'";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result[0]['QTD'];
    }

    public function getEstoqueProdutosSemPicking($params){

        $SQL = "
                SELECT P.COD_PRODUTO, P.DSC_GRADE, P.DSC_PRODUTO,  SUM(E.QTD) as QTD FROM
        (SELECT DISTINCT P.COD_PRODUTO,
               P.DSC_GRADE,
               NVL(PE.COD_DEPOSITO_ENDERECO, PV.COD_DEPOSITO_ENDERECO) AS COD_DEPOSITO_ENDERECO
          FROM PRODUTO P
          LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE
          LEFT JOIN PRODUTO_VOLUME    PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
          WHERE PE.COD_DEPOSITO_ENDERECO IS NULL AND PV.COD_DEPOSITO_ENDERECO IS NULL)PR
        INNER JOIN ESTOQUE E ON E.COD_PRODUTO = PR.COD_PRODUTO AND E.DSC_GRADE = PR.DSC_GRADE
        LEFT JOIN PRODUTO P ON P.COD_PRODUTO = PR.COD_PRODUTO AND P.DSC_GRADE = PR.DSC_GRADE";

        if (isset($params['grandeza'])) {
            $grandeza = implode(',',$params['grandeza']);
            $SQL = $SQL . " WHERE P.COD_LINHA_SEPARACAO IN ($grandeza)";
        }
        $SQL = $SQL . " GROUP BY P.COD_PRODUTO, P.DSC_GRADE, P.DSC_PRODUTO";
        $SQL = $SQL . " ORDER BY P.DSC_PRODUTO";

        $array = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $array;

    }

}
