<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository,
    Core\Util\Produto as ProdutoUtil;
use Wms\Util\Endereco;

class EstoqueRepository extends EntityRepository
{
    /*
     $params = array();
     $params['produto'];      - obrigatorio, entidade de produto - wms:Produto
     $params['endereco'];     - obrigatorio, entidade de produto - wms:Deposito\Endereco
     $params['qtd'];          - obrigatorio, quantidade a movimentar
     $params['volume'];       - entidade do volume a movimentar - wms:Produto\Volume
     $params['embalagem'];    - entidade da embalagem a movimentar - wms:Produto\Embalagem
     $params['tipo']           - tipo de movimentação ('S'=> Sistema, 'M'=> Manual, 'I' => Inventario, 'RC' => Ressuprimento Corretivo
                               'RP' => 'Ressuprimento Preventivo, 'E' => Expedicao )
     $params['observacoes'];  - observações
     $params['unitizador'];   - entidade do unitizador a movimentar - wms:Armazenagem\Unitizador
     $params['os'];           - entidade de OS relacionada a movimentação - wms:OrdemServico
     $params['uma'];          - id da U.M.A
     $params['usuario'];      - entidade de usuario - wms:Usuario
     */
    public function movimentaEstoque($params, $runFlush = true, $saidaProduto = false, $dataValidade = null)
    {
        $em = $this->getEntityManager();

        if (!isset($params['produto']) or is_null($params['produto']))
            throw new \Exception("Produto não informado");
        if (!isset($params['endereco']) or is_null($params['endereco']))
            throw new \Exception("Endereço não informado");
        if (!isset($params['qtd']) or is_null($params['qtd']))
            throw new \Exception("Quantidade não informada");

        $enderecoEn = $params['endereco'];
        $produtoEn = $params['produto'];
        $qtd = $params['qtd'];

        if (isset($params['volume']) && !empty($params['volume']) ) {
            $volumeEn = $params['volume'];
        }

        $codProduto = $produtoEn->getId();
        $grade = $produtoEn->getGrade();
        $endereco = $enderecoEn->getId();


        $qtdReserva = 0;

        if ($saidaProduto == true) {
            $dql = "SELECT SUM(REP.QTD_RESERVADA) QTD_RESERVADA
                        FROM RESERVA_ESTOQUE RE
                        INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                        WHERE RE.IND_ATENDIDA = 'N' AND RE.TIPO_RESERVA = 'S'
                        AND REP.COD_PRODUTO = '$codProduto' AND REP.DSC_GRADE = '$grade' AND RE.COD_DEPOSITO_ENDERECO = $endereco";
                        if (isset($volumeEn) && !empty($volumeEn)) {
                            $idVolume = $volumeEn->getId();
                            $dql .= " AND REP.COD_PRODUTO_VOLUME = $idVolume";
                        }
            $dql .= " GROUP BY REP.COD_PRODUTO, REP.DSC_GRADE, RE.COD_DEPOSITO_ENDERECO, NVL(COD_PRODUTO_VOLUME,0)";

            $resultado = $this->getEntityManager()->getConnection()->query($dql)->fetchAll(\PDO::FETCH_ASSOC);

            if (count($resultado) > 0) {
                $qtdReserva = $resultado[0]['QTD_RESERVADA'];
            }
        }

        $usuarioEn = null;
        if (isset($params['usuario']) and !is_null($params['usuario'])) {
            $usuarioEn = $params['usuario'];
        } else {
            $auth = \Zend_Auth::getInstance();
            $usuarioSessao = $auth->getIdentity();
            $pessoaRepo = $this->getEntityManager()->getRepository("wms:Usuario");
            $usuarioEn = $pessoaRepo->find($usuarioSessao->getId());
        }

        $volumeEn = null;
        if (isset($params['volume']) and !is_null($params['volume']) && !empty($params['volume'])){
            $volumeEn = $params['volume'];
            $estoqueEn = $this->findOneBy(array('codProduto' => $codProduto, 'grade' => $grade, 'depositoEndereco' => $enderecoEn, 'produtoVolume'=>$volumeEn));
        }

        $embalagemEn = null;
        if (isset($params['embalagem']) and !is_null($params['embalagem']) && !empty($params['embalagem'])) {
            $embalagemEn = $params['embalagem'];
            $estoqueEn = $this->findOneBy(array('codProduto' => $codProduto, 'grade' => $grade, 'depositoEndereco' => $enderecoEn));
        }

        $tipo = "S";
        if (isset($params['tipo']) and !is_null($params['tipo'])){
            $tipo = $params['tipo'];
        }
        $observacoes = "";
        if (isset($params['observacoes']) and !is_null($params['observacoes'])) {
            $observacoes = $params['observacoes'];
        }

        $unitizadorEn = null;
        if (isset($params['unitizador']) and (!is_null($params['unitizador']))) {
            $unitizadorEn = $params['unitizador'];
        }

        $osEn = null;
        if (isset($params['os']) and !is_null($params['os'])) {
            $osEn = $params['os'];
        }

        $idUma = null;
        if (isset($params['uma']) and !is_null($params['uma'])) {
            $idUma = $params['uma'];
        }

        $validade = null;
        $validadeEsttoque = null;
        $validadeParam = null;
        if (isset($estoqueEn) && is_object($estoqueEn)) {
            $validadeEsttoque = $estoqueEn->getValidade();
        }
//        if ($qtd != 0 ) {
            if (isset($params['validade']) and !empty($params['validade'])) {
                $validadeParam = new \Zend_Date($params['validade']);
                $validadeParam = $validadeParam->toString('Y-MM-dd');
                $validadeParam = new \DateTime($validadeParam);
            } elseif (isset($dataValidade['dataValidade']) and !empty($dataValidade['dataValidade'])) {
                $validadeParam = (is_string($dataValidade['dataValidade']))? new \DateTime($dataValidade['dataValidade']) : $dataValidade['dataValidade'];
            }
//        }

        if (isset($validadeParam) && !empty($validadeParam)) {
            $validade = $validadeParam;
        } elseif (isset($validadeEsttoque) && !empty($validadeEsttoque)) {
            $validade = $validadeEsttoque;
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
            $estoqueEn->setProdutoEmbalagem($embalagemEn);
            $estoqueEn->setProdutoVolume($volumeEn);
            $estoqueEn->setValidade($validade);

            $dscEndereco = $enderecoEn->getDescricao();
            $dscProduto  = $produtoEn->getDescricao();
        } else {
            $idUma = $estoqueEn->getUma();
            $novaQtd = $estoqueEn->getQtd() + $qtd;
            $dscEndereco = $estoqueEn->getDepositoEndereco()->getDescricao();
            $dscProduto  = $estoqueEn->getProduto()->getDescricao();
            $estoqueEn->setQtd($novaQtd);
            $estoqueEn->setValidade($validade);
            if (isset($unitizadorEn)) {
                $estoqueEn->setUnitizador($unitizadorEn);
            }
        }

        if ($novaQtd > 0) {
            $em->persist($estoqueEn);
        } else if ($novaQtd + $qtdReserva < 0) {
            throw new \Exception("Não é permitido estoque negativo para o endereço $dscEndereco com o produto $codProduto / $grade - $dscProduto");
        } else {
            $em->remove($estoqueEn);
        }

        if ($runFlush == true)
            $em->flush();

        //CRIA UM HISTÓRICO DE MOVIMENTAÇÃO DE ESTOQUE
        $historico = new HistoricoEstoque();
        $historico->setQtd($qtd);
        $historico->setData(new \DateTime());
        $historico->setDepositoEndereco($enderecoEn);
        $historico->setObservacao($observacoes);
        $historico->setOrdemServico($osEn);
        $historico->setTipo($tipo);
        $historico->setUsuario($usuarioEn);
        $historico->setUma($idUma);
        $historico->setProduto($produtoEn);
        $historico->setUnitizador($unitizadorEn);
        $historico->setProdutoEmbalagem($embalagemEn);
        $historico->setProdutoVolume($volumeEn);
        $em->persist($historico);

        //VERIFICA SE O ENDERECO VAI ESTAR DISPONIVEL OU NÃO PARA ENDEREÇAMENTO
        if ($novaQtd > 0) {
            if ($enderecoEn->getDisponivel() == "S") {
                $enderecoEn->setDisponivel("N");
                $em->persist($enderecoEn);
            }
        } else {
            if ($enderecoEn->getDisponivel() == "N") {
                $enderecoEn->setDisponivel("S");
                $em->persist($enderecoEn);
            }
        }

        if ($runFlush == true)
            $em->flush();

        return true;
    }

    public function movimentaEstoqueInventario($params)
    {
        return $this->movimentaEstoque(
            $params['codProduto'], $params['grade'], $params['codProdutoVolume'], $params['codProdutoEmbalagem'], $params['idEndereco'], $params['qtd'], $params['idPessoa'] , $params['observacoes'],
            $params['tipo'], $params['idOs']);
    }

    /*
     * $params = array('idProduto' => '32123,
     *                 'grade' = > 'UNICA',
     *                 'idVolume' => 32312,
     *                 'maxResult' => 5, (Optional)
     *                 'idEnderecoIgnorar' => 321 (Optionoal)
     *                 'idCaracteristigaIgnorar' => 37 (Optional)
     */
    public function getEstoqueByParams ( $params)
    {
        $Sql = " SELECT
                    ESTQ.COD_DEPOSITO_ENDERECO,
                    DE.DSC_DEPOSITO_ENDERECO, 
                    ESTQ.QTD, 
                    NVL(RS.QTD_RESERVA,0) as QTD_RESERVA, 
                    ESTQ.QTD + NVL(RS.QTD_RESERVA,0) as SALDO, 
                    ESTQ.COD_PRODUTO_VOLUME, 
                    ESTQ.COD_PRODUTO, 
                    ESTQ.DSC_GRADE, 
                    ESTQ.DTH_PRIMEIRA_MOVIMENTACAO,
                    NVL(ESTQ.DTH_VALIDADE, TO_DATE(CONCAT(TO_CHAR(ESTQ.DTH_PRIMEIRA_MOVIMENTACAO,'DD/MM/YYYY'),' 00:00'),'DD/MM/YYYY HH24:MI')) as DT_MOVIMENTACAO
                   FROM ESTOQUE ESTQ
                   LEFT JOIN (SELECT RE.COD_DEPOSITO_ENDERECO, SUM(REP.QTD_RESERVADA) QTD_RESERVA, REP.COD_PRODUTO, REP.DSC_GRADE, NVL(REP.COD_PRODUTO_VOLUME,0) as VOLUME
                                FROM RESERVA_ESTOQUE RE
                           LEFT JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                               WHERE TIPO_RESERVA = 'S'
                                 AND IND_ATENDIDA = 'N'
                               GROUP BY RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO, REP.DSC_GRADE, REP.COD_PRODUTO_VOLUME) RS
                     ON RS.COD_PRODUTO = ESTQ.COD_PRODUTO
                    AND RS.DSC_GRADE = ESTQ.DSC_GRADE
                    AND RS.COD_DEPOSITO_ENDERECO = ESTQ.COD_DEPOSITO_ENDERECO
                    AND ((RS.VOLUME = ESTQ.COD_PRODUTO_VOLUME) OR (RS.VOLUME = 0 AND ESTQ.COD_PRODUTO_VOLUME IS NULL))
                   LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = ESTQ.COD_DEPOSITO_ENDERECO
                  WHERE ((ESTQ.QTD + NVL(RS.QTD_RESERVA,0)) >0)";

        $SqlOrder = " ORDER BY DT_MOVIMENTACAO , ESTQ.QTD";
        $SqlWhere = "";

        if ((isset($params['idProduto'])) && ($params['idProduto'] != null)) {
            $SqlWhere .= " AND ESTQ.COD_PRODUTO = '" . $params['idProduto'] . "'";
        }

        if ((isset($params['grade'])) && ($params['grade'] != null)) {
            $SqlWhere .= " AND ESTQ.DSC_GRADE = '" . $params['grade'] . "'";
        }

        if ((isset($params['idVolume'])) && ($params['idVolume'] != null)) {
            $idVolume = $params['idVolume'];
            if (is_array($params['idVolume']) == true) {
                $idVolume = implode(",",$params['idVolume']);
            }

            $SqlWhere .= " AND ESTQ.COD_PRODUTO_VOLUME IN (" . $idVolume . ")";
        }

        if ((isset($params['idCaracteristicaIgnorar'])) && ($params['idCaracteristicaIgnorar'] != null)) {
            $SqlWhere .= " AND DE.COD_CARACTERISTICA_ENDERECO <> " . $params['idCaracteristicaIgnorar'] . "";
        }

        if ((isset($params['idEnderecoIgnorar'])) && ($params['idEnderecoIgnorar'] != null)) {
            $SqlWhere .= " AND DE.COD_DEPOSITO_ENDERECO <> '" . $params['idEnderecoIgnorar'] . "'";
        }

        if ((isset($params['maxResult'])) && ($params['maxResult'] != null)) {
            $maxResult = $params['maxResult'];
            $resultado = $this->getEntityManager()->getConnection()->query($Sql . $SqlWhere . $SqlOrder)->fetchAll(\PDO::FETCH_ASSOC);

            $arrayResult = array();
            foreach ($resultado as $key => $line) {
                $arrayResult[] = $line;
                if (($key+1) >= $maxResult) break;
            }
            $result = $arrayResult;
        } else {
            $result = $this->getEntityManager()->getConnection()->query($Sql . $SqlWhere . $SqlOrder)->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $result;
    }


    public function getEstoqueGroupByVolumns($params) {
        $subQuery  = $this->getEstoqueAndVolumeByParams($params,null,true,null,true);
        $SQL = "
            SELECT ESTQ.ENDERECO,
                   ESTQ.TIPO,
                   ESTQ.COD_PRODUTO,
                   ESTQ.DSC_GRADE,
                   ESTQ.RESERVA_SAIDA,
                   ESTQ.RESERVA_ENTRADA,
                   LISTAGG(ESTQ.VOLUME,',') WITHIN GROUP (ORDER BY ESTQ.ENDERECO, ESTQ.TIPO, ESTQ. COD_PRODUTO, ESTQ.DSC_GRADE, ESTQ.RESERVA_SAIDA, ESTQ.RESERVA_ENTRADA,ESTQ.QTD,ESTQ.DTH_PRIMEIRA_MOVIMENTACAO) VOLUME,
                   ESTQ.QTD,
                   ESTQ.DTH_PRIMEIRA_MOVIMENTACAO,
                   ESTQ.DSC_PRODUTO
              FROM ($subQuery) ESTQ
             GROUP BY ESTQ.ENDERECO, ESTQ.TIPO, ESTQ. COD_PRODUTO, ESTQ.DSC_GRADE, ESTQ.RESERVA_SAIDA, ESTQ.RESERVA_ENTRADA,ESTQ.QTD,ESTQ.DTH_PRIMEIRA_MOVIMENTACAO, ESTQ.DSC_PRODUTO
             ORDER BY COD_PRODUTO, DSC_GRADE, VOLUME, ENDERECO, DTH_PRIMEIRA_MOVIMENTACAO
        ";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getEstoqueAndVolumeByParams($parametros, $maxResult = null,$showPicking = true, $orderBy = null, $returnQuery = false){
        $SQL = "SELECT DE.DSC_DEPOSITO_ENDERECO as ENDERECO,
                       DE.COD_DEPOSITO_ENDERECO as COD_ENDERECO,
                       C.DSC_CARACTERISTICA_ENDERECO as TIPO,
                       E.COD_PRODUTO,
                       E.DSC_GRADE,
                       E.NORMA,
                       E.COD_VOLUME,
                       E.VOLUME,
                       E.RESERVA_ENTRADA,
                       E.RESERVA_SAIDA,
                       E.QTD,
                       TO_CHAR(E.DTH_PRIMEIRA_MOVIMENTACAO,'dd/mm/yyyy hh:mi:ss') AS DTH_PRIMEIRA_MOVIMENTACAO,
                       P.DSC_PRODUTO,
                       E.UMA,
                       E.UNITIZADOR,
                       E.DTH_VALIDADE
                  FROM (SELECT NVL(NVL(RE.COD_DEPOSITO_ENDERECO, RS.COD_DEPOSITO_ENDERECO),ESTQ.COD_DEPOSITO_ENDERECO) as COD_DEPOSITO_ENDERECO,
                               NVL(NVL(RE.COD_PRODUTO, RS.COD_PRODUTO),ESTQ.COD_PRODUTO) as COD_PRODUTO,
                               NVL(NVL(RE.DSC_GRADE,RS.DSC_GRADE),ESTQ.DSC_GRADE) as DSC_GRADE,
                               CASE WHEN (ESTQ.VOLUME = '0' OR RE.VOLUME = '0' OR RS.VOLUME = '0') THEN 'PRODUTO UNITÁRIO'
                                    ELSE PV.DSC_VOLUME
                               END as VOLUME,
                               NVL(NVL(RS.VOLUME, RE.VOLUME),ESTQ.VOLUME) as COD_VOLUME,
                               NVL(RE.QTD_RESERVADA,0) as RESERVA_ENTRADA,
                               NVL(RS.QTD_RESERVADA,0) as RESERVA_SAIDA,
                               NVL(ESTQ.QTD,0) as QTD,
                               NVL(PV.COD_NORMA_PALETIZACAO,0) as NORMA,
                               ESTQ.DTH_PRIMEIRA_MOVIMENTACAO,
                               ESTQ.UMA,
                               UN.DSC_UNITIZADOR AS UNITIZADOR,
                               ESTQ.DTH_VALIDADE
                          FROM (SELECT DTH_PRIMEIRA_MOVIMENTACAO, QTD, UMA, COD_UNITIZADOR, DTH_VALIDADE,
                                       COD_DEPOSITO_ENDERECO, COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,'0') as VOLUME FROM ESTOQUE) ESTQ
                          LEFT JOIN UNITIZADOR UN ON UN.COD_UNITIZADOR = ESTQ.COD_UNITIZADOR
                          FULL OUTER JOIN (SELECT SUM(R.QTD_RESERVADA) as QTD_RESERVADA, R.COD_DEPOSITO_ENDERECO, R.COD_PRODUTO, R.DSC_GRADE, R.VOLUME
                                             FROM (SELECT REP.QTD_RESERVADA, RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO, REP.DSC_GRADE, NVL(REP.COD_PRODUTO_VOLUME,0) as VOLUME
                                                     FROM RESERVA_ESTOQUE RE
                                                    INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON RE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                                                    WHERE IND_ATENDIDA = 'N'
                                                      AND TIPO_RESERVA = 'E') R
                                            GROUP BY R.COD_DEPOSITO_ENDERECO,R.COD_PRODUTO, R.DSC_GRADE, R.VOLUME) RE
                                  ON ESTQ.COD_PRODUTO = RE.COD_PRODUTO
                                 AND ESTQ.DSC_GRADE = RE.DSC_GRADE
                                 AND ESTQ.VOLUME = RE.VOLUME
                                 AND ESTQ.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO
                          FULL OUTER JOIN (SELECT SUM(R.QTD_RESERVADA) as QTD_RESERVADA, R.COD_DEPOSITO_ENDERECO, R.COD_PRODUTO, R.DSC_GRADE, R.VOLUME
                                             FROM (SELECT REP.QTD_RESERVADA, RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO, REP.DSC_GRADE, NVL(REP.COD_PRODUTO_VOLUME,0) as VOLUME
                                                     FROM RESERVA_ESTOQUE RE
                                                    INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON RE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                                                    WHERE IND_ATENDIDA = 'N'
                                                      AND TIPO_RESERVA = 'S') R
                                            GROUP BY R.COD_DEPOSITO_ENDERECO,R.COD_PRODUTO, R.DSC_GRADE, R.VOLUME) RS
                                  ON ESTQ.COD_PRODUTO = RS.COD_PRODUTO
                                 AND ESTQ.DSC_GRADE = RS.DSC_GRADE
                                 AND ESTQ.VOLUME = RS.VOLUME
                                 AND ESTQ.COD_DEPOSITO_ENDERECO = RS.COD_DEPOSITO_ENDERECO
                          LEFT JOIN PRODUTO_VOLUME PV ON (PV.COD_PRODUTO_VOLUME = ESTQ.VOLUME) OR (PV.COD_PRODUTO_VOLUME = RE.VOLUME) OR (PV.COD_PRODUTO_VOLUME = RS.VOLUME)) E
                  LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                  LEFT JOIN CARACTERISTICA_ENDERECO C ON C.COD_CARACTERISTICA_ENDERECO = DE.COD_CARACTERISTICA_ENDERECO
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE";

        $SQLWhere = " WHERE 1 = 1 ";
        if (isset($parametros['idProduto']) && !empty($parametros['idProduto'])) {
            $parametros['idProduto'] = ProdutoUtil::formatar($parametros['idProduto']);
            $SQLWhere .= " AND E.COD_PRODUTO = '".$parametros['idProduto'] . "' ";
            if (isset($parametros['grade']) && !empty($parametros['grade'])) {
                $SQLWhere .= " AND E.DSC_GRADE = '".$parametros['grade']."'";
            } else {
                $SQLWhere .= " AND E.DSC_GRADE = 'UNICA'";
            }
        }

        if ($showPicking == false) {
            $caracteristicaPicking = \Wms\Domain\Entity\Deposito\Endereco::ENDERECO_PICKING;
            $SQLWhere .= " AND DE.COD_CARACTERISTICA_ENDERECO <> " . $caracteristicaPicking;
        }
        if (isset($parametros['rua']) && !empty($parametros['rua'])) {
            $SQLWhere .= " AND DE.NUM_RUA = " . $parametros['rua'];
        }
        if (isset($parametros['predio']) && !empty($parametros['predio'])) {
            $SQLWhere .= " AND DE.NUM_PREDIO = " . $parametros['predio'];
        }
        if (isset($parametros['nivel']) && !empty($parametros['nivel'])) {
            $SQLWhere .= " AND DE.NUM_NIVEL = " . $parametros['nivel'];
        }
        if (isset($parametros['apto']) && !empty($parametros['apto'])) {
            $SQLWhere .= " AND DE.NUM_APARTAMENTO = " . $parametros['apto'];
        }
        if (isset($parametros['volume']) && !empty($parametros['volume'])) {
            $SQLWhere .= " AND E.COD_VOLUME = " . $parametros['volume'];
        }

        if ($orderBy != null) {
            $SQLOrderBy = $orderBy;
        } else {
            $SQLOrderBy = " ORDER BY E.DTH_VALIDADE, E.COD_PRODUTO, E.DSC_GRADE, E.NORMA, E.VOLUME, C.COD_CARACTERISTICA_ENDERECO, E.DTH_PRIMEIRA_MOVIMENTACAO";
        }
        $result = $this->getEntityManager()->getConnection()->query($SQL . $SQLWhere . $SQLOrderBy)->fetchAll(\PDO::FETCH_ASSOC);

        if ($returnQuery == true) {
            return $SQL . $SQLWhere . $SQLOrderBy;
        }

        if (isset($maxResult) && !empty($maxResult)) {
            if ($maxResult != false) {

                $arrayResult = array();
                foreach ($result as $key => $line) {
                    $arrayResult[] = $line;
                    if (($key+1) >= $maxResult) break;
                }
                $result = $arrayResult;

            }
        }
        return $result;
    }

    public function getEstoquePulmao($parametros)
    {
        $tipoPicking = \Wms\Domain\Entity\Deposito\Endereco::ENDERECO_PICKING;

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
        $tipoPicking = \Wms\Domain\Entity\Deposito\Endereco::ENDERECO_PICKING;

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
        $tipoPicking = \Wms\Domain\Entity\Deposito\Endereco::ENDERECO_PICKING;
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
        $tipoPicking = \Wms\Domain\Entity\Deposito\Endereco::ENDERECO_PICKING;
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
        $SQL = 'SELECT LS.DSC_LINHA_SEPARACAO as "Linha Separacao",
                       E.COD_PRODUTO as "Codigo",
                       E.DSC_GRADE as "Grade",
                       SubSTR(P.DSC_PRODUTO,0,60) as "Descricao",
                       MIN(E.QTD) as "Qtd"
                  FROM (SELECT PROD.COD_PRODUTO,
                               PROD.DSC_GRADE,
                               NVL(QTD.QTD,0) as QTD
                          FROM (SELECT DISTINCT E.COD_PRODUTO, E.DSC_GRADE, NVL(PV.COD_PRODUTO_VOLUME,0) as VOLUME
                                  FROM ESTOQUE E
                                  LEFT JOIN PRODUTO_VOLUME PV ON E.COD_PRODUTO = PV.COD_PRODUTO AND E.DSC_GRADE = PV.DSC_GRADE) PROD
                          LEFT JOIN (SELECT SUM(E.QTD) as QTD, E.COD_PRODUTO, E.DSC_GRADE,
                                            NVL(E.COD_PRODUTO_VOLUME,0) as VOLUME
                                       FROM ESTOQUE E
                                      GROUP BY E.COD_PRODUTO, E.DSC_GRADE, NVL(E.COD_PRODUTO_VOLUME,0)) QTD
                            ON QTD.COD_PRODUTO = PROD.COD_PRODUTO
                           AND QTD.DSC_GRADE = PROD.DSC_GRADE
                           AND QTD.VOLUME = PROD.VOLUME) E
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE
                  LEFT JOIN LINHA_SEPARACAO LS ON LS.COD_LINHA_SEPARACAO = P.COD_LINHA_SEPARACAO
        ';
        $SQLGroup = " GROUP BY E.COD_PRODUTO,
                            E.DSC_GRADE,
                            P.DSC_PRODUTO,
                            LS.DSC_LINHA_SEPARACAO";

        $SQLOrder = " ORDER BY LS.DSC_LINHA_SEPARACAO, P.DSC_PRODUTO";

        $SQLWhere = "";
        if (isset($params['grandeza'])) {
            $grandeza = $params['grandeza'];
            if (!empty($grandeza)) {
                $grandeza = implode(',',$grandeza);
                $SQLWhere = " WHERE P.COD_LINHA_SEPARACAO IN ($grandeza) ";
            }
        }

        $result = $this->getEntityManager()->getConnection()->query($SQL . $SQLWhere . $SQLGroup . $SQLOrder)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }


    public function getSituacaoEstoque($params) {

        $tipoPicking = \Wms\Domain\Entity\Deposito\Endereco::ENDERECO_PICKING;

        $query = $this->getEntityManager()->createQueryBuilder()
        ->select("de.descricao,
                 NVL(NVL(NVL(e.codProduto, pp.codProduto),pv.codProduto),pe.codProduto) as codProduto,
                 NVL(NVL(NVL(e.grade, pp.grade),pv.grade),pe.grade) as grade,
                 NVL(e.qtd,pp.qtd) as qtd,
                 p.id as uma,
                 r.id as idRecebimento,
                 s.sigla as sigla
                 ")
        ->from("wms:Deposito\Endereco", 'de')
        ->leftJoin("wms:Enderecamento\Estoque", "e", "WITH", "e.depositoEndereco = de.id")
        ->leftJoin("wms:Enderecamento\Palete", "p", "WITH", "p.depositoEndereco = de.id  AND p.codStatus !=". Palete::STATUS_ENDERECADO . " AND p.codStatus !=" . Palete::STATUS_CANCELADO)
        ->leftJoin("p.produtos","pp")
        ->leftJoin("wms:Produto\Volume", "pv", "WITH", "de.id = pv.endereco")
        ->leftJoin("wms:Produto\Embalagem", "pe", "WITH", "de.id = pe.endereco")

        ->leftJoin("p.recebimento", "r")
        ->leftJoin("p.status", "s")
        ->distinct(true)
        ->orderBy("de.descricao");

        $query->andWhere('(pv.id is  null and pe.id is  null)');

        if (!empty($params['mostrarPicking']) && $params['mostrarPicking'] == 1) {
            $query->andWhere('de.idCaracteristica = :idCaracteristica');
            $query->setParameter('idCaracteristica',$tipoPicking);
        } else {
            $query->andWhere('de.idCaracteristica <> :idCaracteristica');
            $query->setParameter('idCaracteristica',$tipoPicking);
        }

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
            $query-> andWhere('((e.codProduto IS NULL) AND (pp.codProduto IS NULL))');
        }

        $result = $query->getQuery()->getResult();

        foreach ($result as $key => $endereco) {
            if ($endereco['codProduto'] == NULL) {
                $endereco['statusEndereco'] = "Endereço não utilizado";
                $endereco['tipo'] = "V";
            } else {
                if ($endereco['uma'] == NULL) {
                    $endereco['tipo'] = "E";
                    $endereco['statusEndereco'] = "Endereçado no estoque";
                } else {
                    $endereco['statusEndereco'] = "Reservado para o palete $endereco[uma] ($endereco[sigla]) no recebimento $endereco[idRecebimento]";
                    $endereco['tipo'] = "P";
                }
            }
            $result[$key] = $endereco;
        }


        return $result;
    }

    public function getProdutoByNivel($dscEndereco, $nivel) {

        if (is_null($nivel) || $nivel == '') {
            throw new \Exception('Nivel não foi informado');
        }

        $em = $this->getEntityManager();

        $endereco = Endereco::formatar($dscEndereco, null, null, $nivel);

        $dql = $em->createQueryBuilder()
            ->select('dep.rua, dep.nivel, dep.predio, dep.apartamento, e.uma, e.id, dep.id as idEndereco' )
            ->from("wms:Enderecamento\Estoque", "e")
            ->InnerJoin("e.depositoEndereco", "dep")
            ->where("dep.descricao = '$endereco'");

        return $dql->getQuery()->getArrayResult();
    }

    public function getProdutoByUMA($codigoBarrasUMA, $idEndereco)
    {
        $em = $this->getEntityManager();
        $sql = "SELECT p0_.DSC_PRODUTO AS descricao, p0_.COD_PRODUTO AS id, p0_.DSC_GRADE AS grade, NVL(e1_.QTD / NVL(p2_.QTD_EMBALAGEM, 1),'1') AS qtd, d3_.DSC_DEPOSITO_ENDERECO AS endereco, NVL(p2_.DSC_EMBALAGEM,'VOLUMES') as DSC_EMBALAGEM, NVL(p2_.COD_PRODUTO_EMBALAGEM,0) as COD_PRODUTO_EMBALAGEM, p2_.QTD_EMBALAGEM
                FROM ESTOQUE e1_
                INNER JOIN DEPOSITO_ENDERECO d3_ ON e1_.COD_DEPOSITO_ENDERECO = d3_.COD_DEPOSITO_ENDERECO
                INNER JOIN PRODUTO p0_ ON e1_.COD_PRODUTO = p0_.COD_PRODUTO AND e1_.DSC_GRADE = p0_.DSC_GRADE
                LEFT JOIN PRODUTO_EMBALAGEM p2_ ON (p2_.COD_PRODUTO = p0_.COD_PRODUTO AND p2_.DSC_GRADE = p0_.DSC_GRADE AND p2_.DTH_INATIVACAO is null)
                WHERE e1_.UMA = $codigoBarrasUMA AND d3_.COD_DEPOSITO_ENDERECO = $idEndereco
                ORDER BY p2_.QTD_EMBALAGEM";

        return $em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutoByCodBarrasAndEstoque($etiquetaProduto, $idEndereco)
    {
        $em = $this->getEntityManager();
        $dql = "SELECT p0_.DSC_PRODUTO AS descricao, p0_.COD_PRODUTO AS id, p0_.DSC_GRADE AS grade, e1_.QTD / NVL(p3_.QTD_EMBALAGEM,1) AS qtd, NVL(p3_.DSC_EMBALAGEM,'') DSC_EMBALAGEM, p3_.COD_PRODUTO_EMBALAGEM, d2_.DSC_DEPOSITO_ENDERECO AS ENDERECO
                    FROM ESTOQUE e1_ INNER JOIN PRODUTO p0_ ON e1_.COD_PRODUTO = p0_.COD_PRODUTO AND e1_.DSC_GRADE = p0_.DSC_GRADE
                    LEFT JOIN DEPOSITO_ENDERECO d2_ ON e1_.COD_DEPOSITO_ENDERECO = d2_.COD_DEPOSITO_ENDERECO
                    LEFT JOIN PRODUTO_EMBALAGEM p3_ ON (p3_.COD_PRODUTO = e1_.COD_PRODUTO AND p3_.DSC_GRADE = e1_.DSC_GRADE AND p3_.DTH_INATIVACAO is null)
                    LEFT JOIN PRODUTO_VOLUME p4_ ON p0_.COD_PRODUTO = p4_.COD_PRODUTO AND p0_.DSC_GRADE = p4_.DSC_GRADE
                    WHERE ((p3_.COD_BARRAS = '$etiquetaProduto' OR p4_.COD_BARRAS = '$etiquetaProduto')) AND d2_.COD_DEPOSITO_ENDERECO = $idEndereco";

        return $em->getConnection()->query($dql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getQtdProdutoByVolumesOrProduct($codProduto, $grade, $idEndereco, $volumes) {
        if (count($volumes) == 0) {
            $SQL = "SELECT CASE WHEN SUM(QTD) IS NULL THEN 0 ELSE SUM (QTD) END AS QTD
                      FROM ESTOQUE
                     WHERE COD_PRODUTO = '$codProduto'
                       AND DSC_GRADE = '$grade'
                       AND COD_DEPOSITO_ENDERECO = '$idEndereco'";
            $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
            return $result[0]['QTD'];
        } else {
            $menorQtd = null;
            foreach ($volumes as $volume) {
                $SQL = "SELECT CASE WHEN SUM(QTD) IS NULL THEN 0 ELSE SUM (QTD) END AS QTD
                          FROM ESTOQUE
                         WHERE COD_PRODUTO = '$codProduto'
                           AND DSC_GRADE = '$grade'
                           AND COD_DEPOSITO_ENDERECO = '$idEndereco'
                           AND COD_PRODUTO_VOLUME = '$volume'";
                $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
                $qtd = $result[0]['QTD'];
                if (is_null($menorQtd)) $menorQtd = $qtd;
                if ($qtd < $menorQtd) {
                    $menorQtd = $qtd;
                }
            }
            if (is_null($menorQtd)) {
                return 0;
            } else {
                return $menorQtd;
            }
        }
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
        LEFT JOIN ESTOQUE E ON E.COD_PRODUTO = PR.COD_PRODUTO AND E.DSC_GRADE = PR.DSC_GRADE
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

    public function getProdutosArmazenadosPickingErrado($params) {
        $SQLWhere = " WHERE ";
        $SQLOrder = " ORDER BY DE.DSC_DEPOSITO_ENDERECO ";
        $SQL = "SELECT DE.DSC_DEPOSITO_ENDERECO as ENDERECO,
                       PK.COD_PRODUTO as PRODUTO_PICKING,
                       PK.DSC_GRADE as GRADE_PICKING,
                       PK.VOLUMES as VOLUME_PICKING,
                       E.COD_PRODUTO as PRODUTO_ESTOQUE,
                       E.DSC_GRADE as GRADE_ESTOQUE,
                       E.VOLUMES as VOLUMES_ESTOQUE,
                       E.QTD,
                       E.PK_CORRETO as PICKING_CORRETO
                  FROM (SELECT E.COD_PRODUTO, E.DSC_GRADE, E.COD_DEPOSITO_ENDERECO, E.QTD, E.PK_CORRETO,
                               LISTAGG(E.VOLUME,',') WITHIN GROUP (ORDER BY E.VOLUME) VOLUMES
                          FROM (SELECT E.QTD,
                                       NVL(PE.COD_PRODUTO, PV.COD_PRODUTO) as COD_PRODUTO,
                                       NVL(PE.DSC_GRADE, PV.DSC_GRADE) as DSC_GRADE,
                                       NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) as VOLUME,
                                       E.COD_DEPOSITO_ENDERECO,
                                       DE2.DSC_DEPOSITO_ENDERECO AS PK_CORRETO
                                  FROM ESTOQUE E
                                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = E.COD_PRODUTO_VOLUME
                                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = E.COD_PRODUTO_EMBALAGEM
                                  LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                                  LEFT JOIN DEPOSITO_ENDERECO DE2 ON (DE2.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO OR DE2.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO)
                                 WHERE DE.COD_CARACTERISTICA_ENDERECO = 37
                                   AND (E.COD_DEPOSITO_ENDERECO <> PE.COD_DEPOSITO_ENDERECO
                                     OR E.COD_DEPOSITO_ENDERECO <> PV.COD_DEPOSITO_ENDERECO))E
                         GROUP BY E.QTD, E.COD_PRODUTO, E.DSC_GRADE, E.COD_DEPOSITO_ENDERECO, E.PK_CORRETO) E
                  LEFT JOIN (SELECT COD_PRODUTO,
                                    DSC_GRADE,
                                    COD_DEPOSITO_ENDERECO,
                                    LISTAGG (VOLUME,';') WITHIN GROUP (ORDER BY VOLUME) VOLUMES
                               FROM (SELECT P.COD_PRODUTO, P.DSC_GRADE, NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) as VOLUME,NVL(PE.COD_DEPOSITO_ENDERECO, PV.COD_DEPOSITO_ENDERECO) as COD_DEPOSITO_ENDERECO
                                       FROM PRODUTO P
                                       LEFT JOIN PRODUTO_VOLUME PV ON P.COD_PRODUTO = PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE
                                       LEFT JOIN PRODUTO_EMBALAGEM PE ON P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE)
                              GROUP BY COD_PRODUTO, DSC_GRADE, COD_DEPOSITO_ENDERECO) PK
                         ON PK.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                  LEFT JOIN DEPOSITO_ENDERECO DE ON E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                  LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = E.COD_PRODUTO AND PROD.DSC_GRADE = E.DSC_GRADE
                   ";

        if (isset($params['inicioRua']) && ($params['inicioRua'] != "")) {
            if ($SQLWhere != " WHERE ") $SQLWhere .= " AND ";
            $SQLWhere .= " DE.NUM_RUA >= " . $params['inicioRua'];
        }

        if (isset($params['fimRua']) && ($params['fimRua'] != "")) {
            if ($SQLWhere != " WHERE ") $SQLWhere .= " AND ";
            $SQLWhere .= " DE.NUM_RUA <= " . $params['fimRua'];
        }

        if (isset($params['grandeza']) && (count($params['grandeza']) >0)) {
            if ($SQLWhere != " WHERE ") $SQLWhere .= " AND ";
            $grandezas = implode(",",$params['grandeza']);
            $SQLWhere .= " PROD.COD_LINHA_SEPARACAO IN ($grandezas)";
        }

        $array = $this->getEntityManager()->getConnection()->query($SQL . $SQLWhere . $SQLOrder)->fetchAll(\PDO::FETCH_ASSOC);
        return $array;

    }

    public function getProdutosVolumesDivergentes()
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
            ->select('e.codProduto as Codigo, e.grade as Grade, p.descricao as Produto', 'v.descricao as Volume, SUM(e.qtd) as Qtd')
            ->from("wms:Enderecamento\Estoque", "e")
            ->innerJoin("e.produto", 'p')
            ->innerJoin("e.produtoVolume", 'v')
            ->where('e.produtoVolume IS NOT NULL')
            ->groupBy('e.codProduto ','e.grade', 'p.descricao', 'v.id', 'v.descricao')
            ->orderBy('e.codProduto, e.grade', 'ASC');

        $result = $dql->getQuery()->getArrayResult();

        $prodAnterior = "";
        $prodAtual = "";
        $qtdVolumes = 1;

        $produtosDivergentes = array();

        foreach ($result as $produto) {
            $prodAtual = $produto;

            if ($prodAnterior == "") {
                $prodAnterior = $produto;
            } else {
                if (($prodAnterior['Codigo'] == $produto['Codigo']) && ($prodAnterior['Grade'] == $produto['Grade'])) {
                    $qtdVolumes = $qtdVolumes + 1;
                    if ($prodAnterior['Qtd'] != $produto['Qtd']) {
                        array_push($produtosDivergentes, $produto);
                    }
                } else {
                    $produtoEn = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id' => $prodAnterior['Codigo'], 'grade' => $prodAnterior['Grade']));
                    if ($produtoEn->getNumVolumes() != $qtdVolumes) {
                        $produtoFaltante = $prodAnterior;
                        $produtoFaltante['Volume'] = 'Faltando Volume';
                        $produtoFaltante['Qtd'] = "-";
                        array_push($produtosDivergentes, $produtoFaltante);
                    }

                    $qtdVolumes = 1;
                }
            }

            $prodAnterior = $prodAtual;
        }

        return $produtosDivergentes;
    }

    public function getEstoqueByProduto ($produtos = null) {
        $SQL = "SELECT P.COD_PRODUTO,
                       P.DSC_GRADE,
                       NVL(E.QTD_ESTOQUE,0) as QTD_ESTOQUE_TOTAL,
                       NVL(E.QTD_ESTOQUE,0) + NVL(R.QTD_RESERVADA,0) as QTD_ESTOQUE_DISPONIVEL
                  FROM PRODUTO P
                  LEFT JOIN (SELECT COD_PRODUTO, DSC_GRADE, SUM(QTD) as QTD_ESTOQUE
                               FROM ESTOQUE E
                              GROUP BY COD_PRODUTO, DSC_GRADE) E
                    ON E.COD_PRODUTO = P.COD_PRODUTO AND E.DSC_GRADE = P.DSC_GRADE
                  LEFT JOIN (SELECT COD_PRODUTO, DSC_GRADE, SUM(QTD_RESERVADA) as QTD_RESERVADA
                               FROM (SELECT DISTINCT REP.COD_PRODUTO, REP.DSC_GRADE, REP.COD_RESERVA_ESTOQUE, REP.QTD_RESERVADA
                                       FROM RESERVA_ESTOQUE_EXPEDICAO REE
                                      INNER JOIN RESERVA_ESTOQUE RE ON RE.COD_RESERVA_ESTOQUE = REE.COD_RESERVA_ESTOQUE
                                       LEFT JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                                      WHERE RE.IND_ATENDIDA = 'N')
                              GROUP BY COD_PRODUTO, DSC_GRADE) R
                   ON R.COD_PRODUTO = P.COD_PRODUTO AND R.DSC_GRADE = P.DSC_GRADE";

        if ($produtos != null) {
            $SQL = $SQL . " WHERE P.COD_PRODUTO IN (". $produtos . ")";            
        }

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);        
        return $result;
    }

}
