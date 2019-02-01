<?php
/**
 * Created by PhpStorm.
 * User: Joaby
 * Date: 26/11/2018
 * Time: 11:00
 */

namespace Wms\Domain\Entity;


use Wms\Domain\Entity\Inventario;
use Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovo;
use Wms\Domain\EntityRepository;

class InventarioNovoRepository extends EntityRepository
{

    public function getInventarios($returnType = 'entity', $findBy = [], $orderBy = null)
    {
        $return = [];
        /** @var InventarioNovo[] $inventarios */
        if (!empty($findBy)) {
            $inventarios = $this->findBy($findBy,$orderBy);
        }
        else {
            $inventarios = $this->findAll();
        }
        if ($returnType === 'stdClass') {
            foreach ($inventarios as $inventario) {
                $obj = new \stdClass;
                $obj->id                    = $inventario->getId();
                $obj->descricao             = $inventario->getDescricao();
                $obj->criterio              = $inventario->getCriterio();
                $obj->dthCriacao            = $inventario->getDthCriacao(true);
                $obj->dthLiberacao          = $inventario->getDthInicio(true);
                $obj->dthFinalizacao        = $inventario->getDthFinalizacao(true);
                $obj->codErp                = $inventario->getCodErp();
                $obj->status                = $inventario->getStatus();
                $obj->dscStatus             = $inventario->getDscStatus();
                $obj->modeloInventario      = $inventario->getModeloInventario()->toArray();
                $obj->itemAItem             = $inventario->confereItemAItem();
                $obj->controlaValidade      = $inventario->getControlaValidade();
                $obj->controlaValidadeLbl   = $inventario->controlaValidade();
                $obj->exigeUMA              = $inventario->exigeUma();
                $obj->numContagens          = $inventario->getNumContagens();
                $obj->comparaEstoque        = $inventario->comparaEstoque();
                $obj->usuarioNContagens     = $inventario->permiteUsuarioNContagens();
                $obj->contarTudo            = $inventario->forcarContarTudo();
                $obj->volumesSeparadamente  = $inventario->confereVolumesSeparadamente();
                $return[] = $obj;
            }
        } else if ($returnType === 'entity') {
            $return = $inventarios;
        } else if ($returnType === 'array') {
            foreach ($inventarios as $inventario) {
                $return[] = $inventario->toArray();
            }
        }

        return $return;
    }

    public function listInventarios($args)
    {
        $arrWhere = [];
        $where = "";
        if (!empty($args)) {
            if (isset($args['rua']) && !empty($args['rua'])) {
                $arrWhere[] = "DE.NUM_RUA >= $args[rua]";
            }

            if (isset($args['ruaFinal']) && !empty($args['ruaFinal'])) {
                $arrWhere[] = "DE.NUM_RUA <= $args[ruaFinal]";
            }

            if (isset($args['predio']) && !empty($args['predio'])) {
                $arrWhere[] = "DE.NUM_PREDIO >= $args[predio]";
            }

            if (isset($args['predioFinal']) && !empty($args['predioFinal'])) {
                $arrWhere[] = "DE.NUM_PREDIO <= $args[predioFinal]";
            }

            if (isset($args['nivel'])) {
                $arrWhere[] = "DE.NUM_NIVEL >= $args[nivel]";
            }

            if (isset($args['nivelFinal'])) {
                $arrWhere[] = "DE.NUM_NIVEL <= $args[nivelFinal]";
            }

            if (isset($args['apto']) && !empty($args['apto'])) {
                $arrWhere[] = "DE.NUM_APARTAMENTO >= $args[apto]";
            }

            if (isset($args['aptoFinal']) && !empty($args['aptoFinal'])) {
                $arrWhere[] = "DE.NUM_APARTAMENTO <= $args[aptoFinal]";
            }

            if (isset($args['dataInicial1']) && !empty($args['dataInicial1'])) {
                $arrWhere[] = "INVN.DTH_INICIO >= (TO_DATE('$args[dataInicial1] 00:00:00', 'DD/MM/YYYY HH24:MI:SS')";
            }

            if (isset($args['dataInicial2']) && !empty($args['dataInicial2'])) {
                $arrWhere[] = "INVN.DTH_INICIO <= (TO_DATE('$args[dataInicial2] 23:59:59', 'DD/MM/YYYY HH24:MI:SS')";
            }

            if (isset($args['dataFinal1']) && !empty($args['dataFinal1'])) {
                $arrWhere[] = "INVN.DTH_FINALIZACAO >= (TO_DATE('$args[dataFinal1] 00:00:00', 'DD/MM/YYYY HH24:MI:SS')";
            }

            if (isset($args['dataFinal2']) && !empty($args['dataFinal2'])) {
                $arrWhere[] = "INVN.DTH_FINALIZACAO <= (TO_DATE('$args[dataFinal2] 23:59:59', 'DD/MM/YYYY HH24:MI:SS')";
            }

            if (isset($args['status'])) {
                $arrWhere[] = "INVN.COD_STATUS = $args[status]";
            }

            if (isset($args['produto']) && !empty($args['produto'])) {
                $arrWhere[] = "(IEP.COD_PRODUTO = '$args[produto]' OR ICEP.COD_PRODUTO = '$args[produto]')";
            }

            if (isset($args['grade']) && !empty($args['grade'])) {
                $arrWhere[] = "(IEP.DSC_GRADE = '$args[grade]' OR ICEP.DSC_GRADE = '$args[grade]')";
            }

            if (isset($args['inventario']) && !empty($args['inventario'])) {
                $arrWhere[] = "INVN.COD_INVENTARIO = $args[inventario]";
            }

            if (isset($args['descricao']) && !empty($args['descricao'])) {
                $descricao = strtolower($args['descricao']);
                $arrWhere[] = "LOWER(INVN.DSC_INVENTARIO) like '%$descricao%'";
            }

            $where = "WHERE 1 = 1 AND IEN.IND_ATIVO = 'S' AND " . implode(" AND ", $arrWhere);
        }


        $sql = "SELECT
                  INVN.COD_INVENTARIO AS \"id\",
                  INVN.COD_STATUS \"status\",
                  INVN.DSC_INVENTARIO \"descricao\",
                  COUNT( DISTINCT IEN.COD_DEPOSITO_ENDERECO ) \"qtdEndereco\",
                  COUNT( DISTINCT CASE WHEN ICE.IND_CONTAGEM_DIVERGENCIA = 'S' THEN IEN.COD_INVENTARIO_ENDERECO END ) \"qtdDivergencia\",
                  COUNT( DISTINCT CASE WHEN IEN.COD_STATUS = 3 THEN IEN.COD_INVENTARIO_ENDERECO END ) \"qtdInventariado\",
                  TO_CHAR(INVN.DTH_CRIACAO, 'DD/MM/YYYY HH24:MI:SS') \"dataCriacao\",
                  TO_CHAR(INVN.DTH_INICIO, 'DD/MM/YYYY HH24:MI:SS') \"dataInicio\",
                  INVN.COD_INVENTARIO_ERP \"codInvERP\",
                  TO_CHAR(INVN.DTH_FINALIZACAO, 'DD/MM/YYYY HH24:MI:SS') \"dataFinalizacao\",
                  CASE WHEN SUM( CASE WHEN IEN.COD_STATUS = 3 THEN 1 ELSE 0 END ) > 0
                         THEN ROUND(((COUNT( DISTINCT CASE WHEN IEN.COD_STATUS = 3 THEN IEN.COD_INVENTARIO_ENDERECO END ) / COUNT( DISTINCT IEN.COD_DEPOSITO_ENDERECO )) * 100), 2)
                       ELSE 0 END AS \"andamento\"
                FROM INVENTARIO_NOVO INVN
                INNER JOIN INVENTARIO_ENDERECO_NOVO IEN ON INVN.COD_INVENTARIO = IEN.COD_INVENTARIO
                LEFT JOIN INVENTARIO_CONT_END ICE ON IEN.COD_INVENTARIO_ENDERECO = ICE.COD_INVENTARIO_ENDERECO AND ICE.IND_CONTAGEM_DIVERGENCIA = 'S'
                LEFT JOIN INVENTARIO_END_PROD IEP ON IEN.COD_INVENTARIO_ENDERECO = IEP.COD_INVENTARIO_ENDERECO
                LEFT JOIN INVENTARIO_CONT_END_PROD ICEP ON ICE.COD_INV_CONT_END = ICEP.COD_INV_CONT_END
                INNER JOIN DEPOSITO_ENDERECO DE ON IEN.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                $where
                GROUP BY INVN.COD_INVENTARIO, INVN.COD_STATUS, INVN.DTH_INICIO, INVN.DTH_CRIACAO, INVN.COD_INVENTARIO_ERP, INVN.DTH_FINALIZACAO, INVN.DSC_INVENTARIO";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }

    public function getEnderecosCriarNovoInventario($params)
    {
        $query = $this->_em->createQueryBuilder()
            ->select("
                de.id,
                de.descricao as dscEndereco, 
                c.descricao as caracEnd,
                aa.descricao as dscArea,
                ea.descricao as dscEstrutura,
                de.rua, de.predio, de.nivel, de.apartamento")
            ->from('wms:Deposito\Endereco', 'de')
            ->innerJoin('de.caracteristica', 'c')
            ->innerJoin('de.estruturaArmazenagem', 'ea')
            ->innerJoin('de.areaArmazenagem', 'aa')
        ;

        $query->distinct(true);

        if (!empty($params['ruaInicial']) || !empty($params['ruaFinal'])) {
            $condition = [];
            if (!empty($params['ruaInicial'])) {
                $condition[] = "de.rua >= $params[ruaInicial]";
            }
            if (!empty($params['ruaFinal'])) {
                $condition[] = "de.rua <= $params[ruaFinal]";
            }
            $query->andWhere(implode(" AND ", $condition));
        }

        if (!empty($params['predioInicial']) || !empty($params['predioFinal'])) {
            $condition = [];
            if (!empty($params['predioInicial'])) {
                $condition[] = "de.predio >= $params[predioInicial]";
            }
            if (!empty($params['predioFinal'])) {
                $condition[] = "de.predio <= $params[predioFinal]";
            }
            $query->andWhere(implode(" AND ", $condition));
        }

        if (!empty($params['nivelInicial']) || !empty($params['nivelFinal'])) {
            $condition = [];
            if (!empty($params['nivelInicial'])) {
                $condition[] = "de.nivel >= $params[nivelInicial]";
            }
            if (!empty($params['nivelFinal'])) {
                $condition[] = "de.nivel <= $params[nivelFinal]";
            }
            $query->andWhere(implode(" AND ", $condition));
        }

        if (!empty($params['aptoInicial']) || !empty($params['aptoFinal'])) {
            $condition = [];
            if (!empty($params['aptoInicial'])) {
                $condition[] = "de.apartamento >= $params[aptoInicial]";
            }
            if (!empty($params['aptoFinal'])) {
                $condition[] = "de.apartamento <= $params[aptoFinal]";
            }
            $query->andWhere(implode(" AND ", $condition));
        }

        if (!empty($params['lado'])) {
            if ($params['lado'] == "P")
                $query->andWhere("MOD(de.predio,2) = 0");
            if ($params['lado'] == "I")
                $query->andWhere("MOD(de.predio,2) = 1");
        }

        if (!empty($params['situacao']))
            $query->andWhere("de.situacao = :situacao")
                ->setParameter('situacao', $params['situacao']);

        if (!empty($params['status']))
            $query->andWhere("de.status = :status")
                ->setParameter('status', $params['status']);

        if (!empty($params['idCarac']))
            $query->andWhere("de.idCaracteristica = ?1")
                ->setParameter(1, $params['idCarac']);

        if (!empty($params['estrutArmaz']))
            $query->andWhere("de.idEstruturaArmazenagem = ?2")
                ->setParameter(2, $params['estrutArmaz']);

        if (!empty($params['areaArmaz']))
            $query->andWhere("de.idAreaArmazenagem = ?3")
                ->setParameter(3, $params['areaArmaz']);

        if (!empty($params['tipoEnd']))
            $query->andWhere("de.idTipoEndereco = ?4")
                ->setParameter(4, $params['tipoEnd']);

        if (!empty($params['ativo']))
            $query->andWhere("de.ativo = ?5")
                ->setParameter(5, $params['ativo']);

        $query->orderBy('de.rua, de.predio, de.nivel, de.apartamento');

        return $query->getQuery()->getResult();
    }

    public function getProdutosCriarNovoInventario($params)
    {
        $query = $this->_em->createQueryBuilder()
            ->select("
                de.id,
                de.descricao as dscEndereco, 
                c.descricao as caracEnd,
                p.id as codProduto,
                p.grade,
                p.descricao as dscProduto,
                de.rua, de.predio, de.nivel, de.apartamento")
            ->from('wms:Enderecamento\Estoque', 'e')
            ->innerJoin('e.depositoEndereco', 'de')
            ->innerJoin('e.produto', 'p')
            ->innerJoin('p.classe', 'cl')
            ->innerJoin('p.fabricante', 'f')
            ->innerJoin('de.caracteristica', 'c')
        ;

        $query->distinct(true);

        if (!empty($params['fabricante']))
            $query->andWhere("f.id = ?6")
                ->setParameter(6, $params['fabricante']);

        if (!empty($params['descricao']))
            $query->andWhere("p.descricao like ?7")
                ->setParameter(7, "%$params[descricao]%");

        if (!empty($params['codProduto']))
            $query->andWhere("p.id = ?8")
                ->setParameter(8, $params['codProduto']);

        if (!empty($params['grade']))
            $query->andWhere("p.grade = ?9")
                ->setParameter(9, $params['grade']);

        if (!empty($params['classe']))
            $query->andWhere("cl.id = ?10")
                ->setParameter(10, $params['classe']);

        if (!empty($params['linhaSep']))
            $query->andWhere("ls.id = ?11")
                ->setParameter(11, $params['linhaSep']);

        $query->orderBy('p.id, p.descricao, p.grade, de.rua, de.predio, de.nivel, de.apartamento');

        return $query->getQuery()->getResult();
    }

    public function findImpedimentosLiberacao($id)
    {
        $statusLiberado = InventarioNovo::STATUS_LIBERADO;
        $statusConcluido = InventarioNovo::STATUS_CONCLUIDO;

        $sql = "SELECT
                    DISTINCT
                    DE.COD_DEPOSITO_ENDERECO \"idEndereco\",
                    IEP.COD_INVENTARIO_ENDERECO \"idInventarioEndereco\",                       
                    NVL(REP.COD_PRODUTO, IEP.COD_PRODUTO) \"produto\",
                    NVL(REP.DSC_GRADE, IEP.DSC_GRADE) \"grade\",
                    TO_CHAR(NVL(RE.DTH_RESERVA, INVATV.DTH_INICIO), 'DD/MM/YYYY HH24:MI:SS') \"dataOperacao\",
                    DE.DSC_DEPOSITO_ENDERECO \"descricao\",
                    CASE WHEN IEP.COD_INV_END_PROD IS NULL THEN
                            CASE
                                WHEN REEXP.COD_EXPEDICAO IS NOT NULL THEN CONCAT('Expedição número: ', REEXP.COD_EXPEDICAO)
                                WHEN REOND.COD_ONDA_RESSUPRIMENTO_OS IS NOT NULL THEN CONCAT('Ressuprimento OS: ', REOND.COD_ONDA_RESSUPRIMENTO_OS)
                                WHEN REEND.UMA IS NOT NULL THEN CONCAT('Endereçamento Palete:', REEND.UMA)
                                WHEN INVATV.COD_INVENTARIO IS NOT NULL THEN CONCAT('Inventário em andamento: ', INVATV.COD_INVENTARIO)
                                ELSE 'Não foi possível identificar a operação do endereço'
                            END
                        WHEN (IEP.COD_PRODUTO = REP.COD_PRODUTO AND IEP.DSC_GRADE = REP.DSC_GRADE) 
                                OR 
                             (IEP.COD_PRODUTO = INVATV.COD_PRODUTO AND IEP.DSC_GRADE = INVATV.DSC_GRADE) THEN
                            CASE 
                                WHEN REEXP.COD_EXPEDICAO IS NOT NULL THEN CONCAT('Expedição Código: ', REEXP.COD_EXPEDICAO)
                                WHEN REOND.COD_ONDA_RESSUPRIMENTO_OS IS NOT NULL THEN CONCAT('Ressuprimento OS: ', REOND.COD_ONDA_RESSUPRIMENTO_OS)
                                WHEN IEP.COD_INV_END_PROD IS NOT NULL AND (IEP.COD_PRODUTO = REP.COD_PRODUTO AND IEP.DSC_GRADE = REP.DSC_GRADE) AND REEND.UMA IS NOT NULL THEN CONCAT('Palete:', REEND.UMA)
                                WHEN IEP.COD_INV_END_PROD IS NOT NULL AND (IEP.COD_PRODUTO = INVATV.COD_PRODUTO AND IEP.DSC_GRADE = INVATV.DSC_GRADE) AND INVATV.COD_INVENTARIO IS NOT NULL THEN CONCAT('Inventário: ', INVATV.COD_INVENTARIO)
                                ELSE 'Não foi possível identificar a operação do produto'
                            END 
                        END as \"origemImpedimento\",
                    CASE WHEN IEP.COD_INV_END_PROD IS NULL THEN 'E' ELSE 'P' END \"criterio\"
                FROM INVENTARIO_NOVO INV 
                INNER JOIN INVENTARIO_ENDERECO_NOVO IEN ON INV.COD_INVENTARIO = IEN.COD_INVENTARIO AND IEN.IND_ATIVO = 'S'
                INNER JOIN DEPOSITO_ENDERECO DE ON IEN.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                LEFT JOIN RESERVA_ESTOQUE RE ON DE.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO AND RE.IND_ATENDIDA = 'N'
                LEFT JOIN RESERVA_ESTOQUE_EXPEDICAO REEXP ON RE.COD_RESERVA_ESTOQUE = REEXP.COD_RESERVA_ESTOQUE
                LEFT JOIN RESERVA_ESTOQUE_ENDERECAMENTO REEND ON RE.COD_RESERVA_ESTOQUE = REEND.COD_RESERVA_ESTOQUE
                LEFT JOIN RESERVA_ESTOQUE_ONDA_RESSUP REOND ON RE.COD_RESERVA_ESTOQUE = REOND.COD_RESERVA_ESTOQUE
                LEFT JOIN RESERVA_ESTOQUE_PRODUTO REP ON RE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                LEFT JOIN INVENTARIO_END_PROD IEP ON IEN.COD_INVENTARIO_ENDERECO = IEP.COD_INVENTARIO_ENDERECO AND IEP.IND_ATIVO = 'S'
                LEFT JOIN (
                            SELECT INVN.COD_INVENTARIO, INVN.DTH_INICIO, IEN2.COD_DEPOSITO_ENDERECO, IEP2.COD_PRODUTO, IEP2.DSC_GRADE
                            FROM INVENTARIO_NOVO INVN
                            INNER JOIN INVENTARIO_ENDERECO_NOVO IEN2 ON INVN.COD_INVENTARIO = IEN2.COD_INVENTARIO  AND IEN2.IND_ATIVO = 'S'
                            LEFT JOIN INVENTARIO_END_PROD IEP2 ON IEN2.COD_INVENTARIO_ENDERECO = IEP2.COD_INVENTARIO_ENDERECO AND IEP2.IND_ATIVO = 'S'
                            WHERE INVN.COD_STATUS IN ($statusLiberado, $statusConcluido)
                  ) INVATV ON INVATV.COD_DEPOSITO_ENDERECO = IEN.COD_DEPOSITO_ENDERECO OR (INVATV.COD_PRODUTO = IEP.COD_PRODUTO AND INVATV.DSC_GRADE = IEP.DSC_GRADE)
                WHERE IEN.COD_INVENTARIO = $id AND 
                      CASE WHEN ( IEP.COD_PRODUTO IS NULL) OR (
                          (IEP.COD_PRODUTO = REP.COD_PRODUTO AND IEP.DSC_GRADE = REP.DSC_GRADE) 
                                 OR (IEP.COD_PRODUTO = INVATV.COD_PRODUTO AND IEP.DSC_GRADE = INVATV.DSC_GRADE))
                      THEN NVL(RE.COD_RESERVA_ESTOQUE, INVATV.COD_INVENTARIO)
                      ELSE NULL
                      END IS NOT NULL
                ORDER BY DE.DSC_DEPOSITO_ENDERECO";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }

    /**
     * @param InventarioEnderecoNovo $invEnd
     * @return array
     */
    public function getEnderecosPendentes($invEnd)
    {
        $dql = $this->_em->createQueryBuilder();
        $dql->select("ien")
            ->from("wms:InventarioNovo\InventarioEnderecoNovo", 'ien')
            ->innerJoin("ien.inventario", "ivn")
            ->where("ivn = :inventario")
            ->andWhere("ien != :invEnd")
            ->andWhere("ien.finalizado = 'N'")
            ->andWhere("ien.ativo = 'S'")
            ->setParameters(["inventario" => $invEnd->getInventario(), "invEnd" => $invEnd]);

        return $dql->getQuery()->getResult();
    }

    public function getResultInventario($idInventario)
    {
        $sql = "
            SELECT DISTINCT
                ICEP.COD_PRODUTO,
                ICEP.DSC_GRADE,
                ICEP.COD_PRODUTO_VOLUME,
                ICEP.DSC_LOTE,
                IEN.COD_DEPOSITO_ENDERECO,
                ICEP.DTH_VALIDADE,
                SUM(ICEP.QTD_EMBALAGEM * ICEP.QTD_CONTADA) - NVL(E.QTD,0) QTD,
                NVL(E.QTD, 0) POSSUI_SALDO
            FROM INVENTARIO_NOVO INV
            INNER JOIN INVENTARIO_ENDERECO_NOVO IEN on INV.COD_INVENTARIO = IEN.COD_INVENTARIO AND IEN.IND_ATIVO = 'S'
            INNER JOIN INVENTARIO_CONT_END ICE on IEN.COD_INVENTARIO_ENDERECO = ICE.COD_INVENTARIO_ENDERECO
            INNER JOIN INVENTARIO_CONT_END_PROD ICEP on ICE.COD_INV_CONT_END = ICEP.COD_INV_CONT_END AND ICEP.IND_DIVERGENTE = 'N'
             LEFT JOIN ESTOQUE E 
               ON E.COD_DEPOSITO_ENDERECO = IEN.COD_DEPOSITO_ENDERECO 
              AND E.COD_PRODUTO = ICEP.COD_PRODUTO 
              AND E.DSC_GRADE = ICEP.DSC_GRADE 
              AND NVL(E.DSC_LOTE, 0) = NVL(ICEP.DSC_LOTE, 0)
              AND NVL(E.COD_PRODUTO_VOLUME, 0) = NVL(ICEP.COD_PRODUTO_VOLUME, 0)
            WHERE INV.COD_INVENTARIO = $idInventario 
                  AND NOT EXISTS(
                  SELECT 'x' FROM INVENTARIO_END_PROD IEP 
                  WHERE IEP.COD_INVENTARIO_ENDERECO = IEN.COD_INVENTARIO_ENDERECO 
                    AND IEP.COD_PRODUTO = ICEP.COD_PRODUTO 
                    AND IEP.DSC_GRADE = ICEP.DSC_GRADE 
                    AND IEP.IND_ATIVO = 'N'
            )
            GROUP BY ICEP.COD_PRODUTO, ICEP.DSC_GRADE, ICEP.COD_PRODUTO_VOLUME, ICEP.DSC_LOTE, IEN.COD_DEPOSITO_ENDERECO, ICEP.DTH_VALIDADE, E.QTD";

        return $this->_em->getConnection()->query($sql)->fetchAll();
    }

    public function getSumarioByRua($idInventario) {

        $sql = "
            SELECT 
                   DE.NUM_RUA, 
                   DE.DSC_DEPOSITO_ENDERECO, 
                   IEN.IND_ATIVO, 
                   IEN.COD_STATUS,
                   ICE.NUM_SEQUENCIA,
                   ICE.NUM_CONTAGEM,
                   ICE.IND_CONTAGEM_DIVERGENCIA,
                   PES.NOM_PESSOA,
                   ICEP.COD_PRODUTO,
                   P.DSC_PRODUTO,
                   ICEP.DSC_GRADE,
                   NVL(ICEP.DSC_LOTE, '-') DSC_LOTE,
                   NVL(PE.DSC_EMBALAGEM || '(' || ICEP.QTD_EMBALAGEM || ')' , PV.DSC_VOLUME) UNID,
                   ICEP.QTD_CONTADA,
                   NVL(TO_CHAR(ICEP.DTH_VALIDADE, 'DD/MM/YYYY'), '-') DTH_VALIDADE,
                   TO_CHAR(ICEP.DTH_CONTAGEM, 'DD/MM/YYYY HH24:MI:SS') DTH_CONFERENCIA
              FROM INVENTARIO_NOVO INVN
        INNER JOIN INVENTARIO_ENDERECO_NOVO IEN ON IEN.COD_INVENTARIO = INVN.COD_INVENTARIO
        INNER JOIN INVENTARIO_CONT_END ICE ON IEN.COD_INVENTARIO_ENDERECO = ICE.COD_INVENTARIO_ENDERECO
        INNER JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = IEN.COD_DEPOSITO_ENDERECO
         LEFT JOIN INVENTARIO_CONT_END_OS ICEO ON ICE.COD_INV_CONT_END = ICEO.COD_INV_CONT_END
         LEFT JOIN ORDEM_SERVICO OS ON OS.COD_OS = ICEO.COD_OS
         LEFT JOIN PESSOA PES ON PES.COD_PESSOA = OS.COD_PESSOA
         LEFT JOIN INVENTARIO_CONT_END_PROD ICEP ON ICE.COD_INV_CONT_END = ICEP.COD_INV_CONT_END
         LEFT JOIN INVENTARIO_END_PROD IEP ON IEN.COD_INVENTARIO_ENDERECO = IEP.COD_INVENTARIO_ENDERECO
         LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = ICEP.COD_PRODUTO_VOLUME
         LEFT JOIN PRODUTO_EMBALAGEM PE ON ICEP.COD_PRODUTO_EMBALAGEM = PE.COD_PRODUTO_EMBALAGEM
         LEFT JOIN PRODUTO P ON P.COD_PRODUTO = ICEP.COD_PRODUTO AND P.DSC_GRADE = ICEP.DSC_GRADE
            
             WHERE INVN.COD_INVENTARIO = $idInventario
          ORDER BY DE.NUM_RUA, ICE.NUM_SEQUENCIA, ICEP.COD_PRODUTO, ICEP.DSC_GRADE";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}
