<?php

namespace Wms\Domain\Entity\Inventario;

use Doctrine\ORM\EntityRepository;


class EnderecoRepository extends EntityRepository
{

    /**
     * @return Endereco
     * @throws \Exception
     */
    public function save($params)
    {

        if (isset($params['inventario']) && !empty($params['inventario'])) {
            $params['inventarioEn'] = $params['inventario'];
        }
        if (empty($params['codInventario']) and empty($params['inventarioEn'])) {
            throw new \Exception("O inventário não foi especificado");
        }

        if (isset($params['depositoEndereco']) && !empty($params['depositoEndereco'])) {
            $params['depositoEnderecoEn'] = $params['depositoEndereco'];
        }
        if (empty($params['codDepositoEndereco']) and empty($params['depositoEnderecoEn'])) {
            throw new \Exception("O endereço não foi especificado");
        }

        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {

            $enInvEndereco = new Endereco();

            if (isset($params['inventarioEn']) and !empty($params['inventarioEn'])) {
                $inventarioEntity = $params['inventarioEn'];
            } else {
                $inventarioEntity = $em->getReference('wms:Inventario',$params['codInventario']);
            }
            $enInvEndereco->setInventario($inventarioEntity);

            if (isset($params['depositoEnderecoEn']) and !empty($params['depositoEnderecoEn'])) {
                $enderecoEntity = $params['depositoEnderecoEn'];
            } else {
                $enderecoEntity = $em->getReference('wms:Deposito\Endereco', $params['codDepositoEndereco']);
            }
            $enInvEndereco->setDepositoEndereco($enderecoEntity);

            $em->persist($enInvEndereco);
            $em->commit();
//            $em->flush();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception();
        }

        return $enInvEndereco;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getByInventario($params)
    {
        $idInventario   = isset($params['idInventario']) ? $params['idInventario'] : null;
        $numContagem    = isset($params['numContagem']) ? $params['numContagem'] : null;
        $divergencia    = isset($params['divergencia']) ? $params['divergencia'] : null;
        $endereco    = isset($params['dscEndereco']) ? $params['dscEndereco'] : null;
        $rua            = isset($params['rua']) ? $params['rua'] : null;
        $andEndereco = '';
        if ($divergencia != null && $divergencia != 'todos') {
            $andDivergencia = " AND IE.DIVERGENCIA = 1 AND MAXCONT.DIVERGENCIA IS NOT NULL";
        } else if ($divergencia == 'todos') {
            $andDivergencia = null;
        } else {
            $andDivergencia = " AND IE.DIVERGENCIA IS NULL ";
        }

        $sqlWhereSubQuery = "";
        if ($numContagem == 0) {
            $sqlWhereSubQuery = "WHERE (CONTAGEM_INVENTARIADA IS NOT NULL OR DIVERGENCIA IS NOT NULL)";
        }
        $andRua = null;
        if ($rua != null) {
            $andRua = " AND DE.NUM_RUA = ".$rua." ";
        }
        if ($endereco != null) {
            $andEndereco = " AND DE.DSC_DEPOSITO_ENDERECO = '".$endereco."' ";
        }
        $andContagem = null;
        if (isset($numContagem)) {
            $andContagem = " AND NVL(MAXCONT.ULTCONT,0) = ".$numContagem." AND IE.INVENTARIADO IS NULL ";
        }

        $campos = "SELECT DISTINCT DE.DSC_DEPOSITO_ENDERECO, 
                            NVL(MAXCONT.ULTCONT,0) as ULTIMACONTAGEM, 
                            IE.DIVERGENCIA, 
                            IE.COD_INVENTARIO_ENDERECO AS codInvEndereco,
                            MAXCONT.DSC_PRODUTO, 
                            MAXCONT.DSC_GRADE, 
                            MAXCONT.COD_PRODUTO, 
                            MAXCONT.QTD_CONTADA, 
                            MAXCONT.COMERCIALIZACAO,
                            CASE WHEN IE.DIVERGENCIA = 1 THEN 'DIVERGENCIA' WHEN IE.INVENTARIADO = 1 THEN 'INVENTARIADO' ELSE 'PENDENTE' END SITUACAO ";
        if (isset($params['campos']) && $params['campos'] != null) {
            $campos = $params['campos'];
        }
        $sql = "$campos 
          FROM INVENTARIO_ENDERECO IE
          LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = IE.COD_DEPOSITO_ENDERECO
          LEFT JOIN (   SELECT ICE.QTD_CONTADA, 
                            ICE.DIVERGENCIA, 
                            MAX(NUM_CONTAGEM) as ULTCONT, 
                            ICE.COD_INVENTARIO_ENDERECO, 
                            P.DSC_PRODUTO,
                            P.COD_PRODUTO, 
                            P.DSC_GRADE, 
                            NVL(PV.DSC_VOLUME,'EMBALAGEM') COMERCIALIZACAO
                        FROM 
                            INVENTARIO_CONTAGEM_ENDERECO ICE
                            LEFT JOIN PRODUTO P ON ICE.COD_PRODUTO = P.COD_PRODUTO AND ICE.DSC_GRADE = P.DSC_GRADE
                            LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = ICE.COD_PRODUTO_EMBALAGEM
                            LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = ICE.COD_PRODUTO_VOLUME
                        INNER JOIN 
                            (  SELECT 
                                MAX(NUM_CONTAGEM) MAXC, 
                                COD_PRODUTO,
                                DSC_GRADE,
                                COD_PRODUTO_VOLUME,
                                COD_INVENTARIO_ENDERECO 
                              FROM 
                                INVENTARIO_CONTAGEM_ENDERECO
                                $sqlWhereSubQuery
                              GROUP BY COD_INVENTARIO_ENDERECO,
                              COD_PRODUTO,
                                DSC_GRADE,
                                COD_PRODUTO_VOLUME,
                                COD_INVENTARIO_ENDERECO
                            ) M ON M.COD_INVENTARIO_ENDERECO = ICE.COD_INVENTARIO_ENDERECO AND M.MAXC = ICE.NUM_CONTAGEM
                            AND ((M.COD_PRODUTO = ICE.COD_PRODUTO AND M.DSC_GRADE = ICE.DSC_GRADE) OR (ICE.COD_PRODUTO IS NULL AND ICE.DSC_GRADE IS NULL))
                            AND NVL(M.COD_PRODUTO_VOLUME,0) = NVL(ICE.COD_PRODUTO_VOLUME,0)
                        GROUP BY 
                            ICE.DIVERGENCIA, 
                            ICE.COD_INVENTARIO_ENDERECO, 
                            P.DSC_PRODUTO,
                            P.COD_PRODUTO, 
                            P.DSC_GRADE, 
                            PV.DSC_VOLUME,
                            PE.DSC_EMBALAGEM, 
                            ICE.QTD_CONTADA
                    ) MAXCONT
            ON MAXCONT.COD_INVENTARIO_ENDERECO = IE.COD_INVENTARIO_ENDERECO
         WHERE 
            IE.COD_INVENTARIO = ".$idInventario."
            $andContagem
            $andDivergencia
            $andRua
            $andEndereco
         ORDER BY 
            DE.DSC_DEPOSITO_ENDERECO";
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getRuasInventario($idInventario)
    {
        if (empty($idInventario)) {
            throw new \Exception('idInventario não pode ser vazio');
        }

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("de.rua")
            ->from("wms:Inventario\Endereco","ie")
            ->innerJoin('ie.depositoEndereco', 'de')
            ->andWhere("ie.inventario = $idInventario")
            ->orderBy('de.rua')
            ->distinct(true);

        return $query->getQuery()->getResult();
    }

    public function getUltimaContagem($enderecoEntity)
    {
        $idInvEnd = (is_object($enderecoEntity)) ? $enderecoEntity->getId() : $enderecoEntity;

        $query = $this->_em->createQueryBuilder()
            ->select('max(ce.id) id, ce.codProduto, ce.grade, ce.codProdutoEmbalagem, ce.codProdutoVolume')
            ->from("wms:Inventario\Endereco","ie")
            ->innerJoin("wms:Inventario\ContagemEndereco", 'ce', 'WITH', 'ie.id = ce.inventarioEndereco')
            ->andWhere("ie.id IN ($idInvEnd)")
            ->groupBy('ce.codProduto, ce.grade, ce.codProdutoEmbalagem, ce.codProdutoVolume, ie.id')
            ->orderBy('ce.codProduto, ce.grade');

        $results = $query->getQuery()->getResult();
        /** @var \Wms\Domain\Entity\Inventario\ContagemEndereco $invContagemEndRepo */
        $invContagemEndRepo = $this->_em->getRepository("wms:Inventario\ContagemEndereco");
        $produtosContagem = array();
        foreach($results as $result) {
            $produtosContagem[] = $invContagemEndRepo->find($result['id']);
        }
        return $produtosContagem;
    }

    public function getComContagem($idInventario)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('ie')
            ->from("wms:Inventario\Endereco","ie")
            ->andWhere("ie.inventariado = 1")
            ->andWhere("ie.atualizaEstoque = 1")
            ->andWhere("ie.inventario IN ($idInventario)");

        return $query->getQuery()->getResult();
    }

}
