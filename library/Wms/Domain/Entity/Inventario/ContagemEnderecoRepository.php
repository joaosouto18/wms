<?php

namespace Wms\Domain\Entity\Inventario;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Inventario;


class ContagemEnderecoRepository extends EntityRepository
{

    /**
     * @return ContagemOS
     * @throws \Exception
     */
    public function save($params, $flush = true)
    {
        if (empty($params['idContagemOs'])) {
            throw new \Exception("idContagemOs não pode ser vazio");
        }

        if (empty($params['idInventarioEnd'])) {
            throw new \Exception("idInventarioEnd não pode ser vazio");
        }

        $em = $this->getEntityManager();
        
        if ($flush == true) $em->beginTransaction();
        try {

            $contagemEndEn = new ContagemEndereco();

            if ($params['codProdutoVolume'] != null) {
                $enProdutoVolume = $this->getEntityManager()->getReference('wms:Produto\Volume', $params['codProdutoVolume']);
                $contagemEndEn->setProdutoVolume($enProdutoVolume);
                $contagemEndEn->setCodProdutoVolume($params['codProdutoVolume']);
            }

            if ($params['codProduto'] != null) {
                $enProduto = $this->getEntityManager()->getReference('wms:Produto', array('id' => $params['codProduto'], 'grade' => $params['grade']));
                $contagemEndEn->setProduto($enProduto);
            }

            $contagemEndEn->setCodProdutoEmbalagem($params['codProdutoEmbalagem']);

            $contagemEndEn->setNumContagem($params['numContagem']);
            $contagemEndEn->setQtdContada($params['qtd']);
            $contagemEndEn->setQtdAvaria($params['qtdAvaria']);
            $contagemEndEn->setCodProduto($params['codProduto']);
            $contagemEndEn->setGrade($params['grade']);
            $contagemEndEn->setValidade($params['validade']);

            $contagemOsEn = $em->getReference('wms:Inventario\ContagemOs',$params['idContagemOs']);
            $contagemEndEn->setContagemOs($contagemOsEn);

            $inventarioEn = $em->getReference('wms:Inventario\Endereco',$params['idInventarioEnd']);
            $contagemEndEn->setInventarioEndereco($inventarioEn);

            $em->persist($contagemEndEn);
            if ($flush == true) $em->commit();
            if ($flush == true) $em->flush();

        } catch(\Exception $e) {
            if ($flush == true) $em->rollback();
            throw new \Exception();
        }

        return $contagemEndEn;
    }

    public function edit($params)
    {
        $em = $this->getEntityManager();
        $inventarioContagemEnderecoEn = $em->getReference('wms:Inventario\ContagemEndereco', $params['contagemEnderecoId']);
        $inventarioContagemEnderecoEn->setQtdContada($params['qtd']);
        $inventarioContagemEnderecoEn->setNumContagem($inventarioContagemEnderecoEn->getNumContagem() + 1);
        $inventarioContagemEnderecoEn->setCodProduto($params['codProduto']);
        $inventarioContagemEnderecoEn->setCodProdutoEmbalagem();
        $inventarioContagemEnderecoEn->setCodProdutoVolume();
        $inventarioContagemEnderecoEn->setGrade($params['grade']);

        $em->persist($inventarioContagemEnderecoEn);
        $em->flush();
    }

    public function getContagens($params)
    {
        $idInventario   = $params['idInventario'];
        $sql = "SELECT 
                    DISTINCT IE.DIVERGENCIA, 
                    MIN(NVL(MAXCONT.ULTCONT,1)) as CONTAGEM
                FROM 
                    INVENTARIO_ENDERECO IE
                    LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = IE.COD_DEPOSITO_ENDERECO
                    LEFT JOIN (
                                SELECT 
                                    MIN (ULTCONT) AS ULTCONT, 
                                    COD_INVENTARIO_ENDERECO 
                                FROM 
                                (
                                    SELECT 
                                        MAX(ICE.NUM_CONTAGEM) as ULTCONT, 
                                        ICE.COD_PRODUTO,
                                        ICE.DSC_GRADE,
                                        ICE.COD_PRODUTO_VOLUME,
                                        ICE.COD_INVENTARIO_ENDERECO 
                                    FROM 
                                        INVENTARIO_CONTAGEM_ENDERECO ICE
                                        INNER JOIN INVENTARIO_ENDERECO IE2 ON ICE.COD_INVENTARIO_ENDERECO = IE2.COD_INVENTARIO_ENDERECO
                                    WHERE 
                                        NOT(IE2.INVENTARIADO = 1 AND IE2.DIVERGENCIA IS NULL)
                                    GROUP BY 
                                        ICE.COD_INVENTARIO_ENDERECO,
                                        ICE.COD_PRODUTO,ICE.DSC_GRADE,
                                        ICE.COD_PRODUTO_VOLUME
                                ) 
                                GROUP BY 
                                    COD_INVENTARIO_ENDERECO
                            )
            MAXCONT
                ON MAXCONT.COD_INVENTARIO_ENDERECO = IE.COD_INVENTARIO_ENDERECO
            WHERE 
                IE.COD_INVENTARIO = $idInventario
                AND IE.INVENTARIADO IS NULL
            GROUP BY 
                NVL(MAXCONT.ULTCONT,1),
                IE.DIVERGENCIA
            ORDER 
                BY CONTAGEM";
        
        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getDetalhesByInventarioEndereco($codInvEndereco)
    {
        $query = $this->_em->createQueryBuilder()
            ->select("ice.numContagem, pessoa.nome, p.id, p.grade, p.descricao, ice.qtdContada, ice.qtdDivergencia,
                      nvl(pv.descricao,'Embalagem') as volume")
            ->from("wms:Inventario\ContagemEndereco","ice")
            ->innerJoin("ice.inventarioEndereco",'ie')
            ->innerJoin("ice.contagemOs",'co')
            ->innerJoin("co.os",'o')
            ->leftJoin('wms:Produto\Volume','pv','WITH','pv.id = ice.codProdutoVolume')
            ->leftJoin("o.pessoa",'pessoa')
            ->leftJoin("ice.produto",'p')
            ->andWhere("ie.id = $codInvEndereco")
            ->orderBy('ice.numContagem, p.id, p.grade');

        return $query->getQuery()->getResult();
    }

    public function getEnderecosInventariados($idInventario)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('de.id AS endereco, ice.id AS contagemEndereco, de.descricao, ice.qtdContada, ice.codProduto, ice.grade')
            ->from('wms:Inventario', 'i')
            ->innerJoin('wms:Inventario\Endereco', 'ie', 'WITH', 'ie.inventario = i.id')
            ->innerJoin('wms:Inventario\ContagemEndereco', 'ice', 'WITH', 'ice.inventarioEndereco = ie.id')
            ->innerJoin('ie.depositoEndereco', 'de')
            ->where("i.id = $idInventario")
            ->orderBy('de.descricao', 'ASC');

        return $sql->getQuery()->getResult();

    }

    public function getProdutosInventariados($id)
    {

        $status = Inventario::STATUS_FINALIZADO;
        $sql = "SELECT SUM(ICE.QTD_CONTADA) QTD_INV, ICE.COD_PRODUTO, ICE.DSC_GRADE, NUM_CONTAGEM, NVL(PE.COD_BARRAS,0) COD_BARRAS
                FROM (SELECT COD_PRODUTO, DSC_GRADE, QTD_CONTADA, MAX(NUM_CONTAGEM) AS NUM_CONTAGEM, COD_INVENTARIO_CONTAGEM_OS 
                      FROM INVENTARIO_CONTAGEM_ENDERECO
                      GROUP BY COD_PRODUTO, DSC_GRADE, QTD_CONTADA, COD_PRODUTO_VOLUME, COD_INVENTARIO_CONTAGEM_OS) ICE
                INNER JOIN INVENTARIO_CONTAGEM_OS ICO ON ICE.COD_INVENTARIO_CONTAGEM_OS = ICO.COD_INVENTARIO_CONTAGEM_OS
                INNER JOIN INVENTARIO I ON I.COD_INVENTARIO = ICO.COD_INVENTARIO
                LEFT JOIN ( SELECT PE2.COD_PRODUTO_EMBALAGEM, PE2.COD_PRODUTO, PE2.DSC_GRADE, PE2.COD_BARRAS
                            FROM PRODUTO_EMBALAGEM PE2
                            INNER JOIN ( SELECT COD_PRODUTO, DSC_GRADE, MIN(QTD_EMBALAGEM) QTD_EMB
                                         FROM PRODUTO_EMBALAGEM
                                         GROUP BY COD_PRODUTO, DSC_GRADE
                                       ) PE3 ON PE3.COD_PRODUTO = PE2.COD_PRODUTO AND PE3.DSC_GRADE = PE2.DSC_GRADE AND PE3.QTD_EMB = PE2.QTD_EMBALAGEM
                          ) PE ON ICE.COD_PRODUTO = PE.COD_PRODUTO AND ICE.DSC_GRADE = PE.DSC_GRADE
                WHERE I.COD_INVENTARIO = $id AND I.COD_STATUS = $status
                GROUP BY ICE.COD_PRODUTO, ICE.DSC_GRADE, ICE.NUM_CONTAGEM, PE.COD_BARRAS";

        return $this->_em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }
}