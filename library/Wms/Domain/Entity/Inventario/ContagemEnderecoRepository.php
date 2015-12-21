<?php

namespace Wms\Domain\Entity\Inventario;

use Doctrine\ORM\EntityRepository;


class ContagemEnderecoRepository extends EntityRepository
{

    /**
     * @return ContagemOS
     * @throws \Exception
     */
    public function save($params)
    {
        if (empty($params['idContagemOs'])) {
            throw new \Exception("idContagemOs não pode ser vazio");
        }

        if (empty($params['idInventarioEnd'])) {
            throw new \Exception("idInventarioEnd não pode ser vazio");
        }

        $em = $this->getEntityManager();
        $em->beginTransaction();
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

            $contagemOsEn = $em->getReference('wms:Inventario\ContagemOs',$params['idContagemOs']);
            $contagemEndEn->setContagemOs($contagemOsEn);

            $inventarioEn = $em->getReference('wms:Inventario\Endereco',$params['idInventarioEnd']);
            $contagemEndEn->setInventarioEndereco($inventarioEn);

            $em->persist($contagemEndEn);
            $em->commit();
            $em->flush();

        } catch(\Exception $e) {
            $em->rollback();
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

        $sql = "SELECT DISTINCT IE.DIVERGENCIA, NVL(MAXCONT.ULTCONT,1) as CONTAGEM
                  FROM INVENTARIO_ENDERECO IE
                  LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = IE.COD_DEPOSITO_ENDERECO
                  LEFT JOIN (SELECT MAX(ICE.NUM_CONTAGEM) as ULTCONT, ICE.COD_INVENTARIO_ENDERECO FROM INVENTARIO_CONTAGEM_ENDERECO ICE
                        INNER JOIN INVENTARIO_ENDERECO IE2 ON ICE.COD_INVENTARIO_ENDERECO = IE2.COD_INVENTARIO_ENDERECO
                        WHERE NOT(IE2.INVENTARIADO = 1 AND IE2.DIVERGENCIA IS NULL)
                        GROUP BY ICE.COD_INVENTARIO_ENDERECO) MAXCONT
                    ON MAXCONT.COD_INVENTARIO_ENDERECO = IE.COD_INVENTARIO_ENDERECO
                WHERE IE.COD_INVENTARIO = ".$idInventario."
                ORDER BY CONTAGEM
         ";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getDetalhesByInventarioEndereco($codInvEndereco)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('ice.numContagem, pessoa.nome, p.id, p.grade, p.descricao, ice.qtdContada, ice.qtdDivergencia')
            ->from("wms:Inventario\ContagemEndereco","ice")
            ->innerJoin("ice.inventarioEndereco",'ie')
            ->innerJoin("ice.contagemOs",'co')
            ->innerJoin("co.os",'o')
            ->leftJoin("o.pessoa",'pessoa')
            ->innerJoin("ice.produto",'p')
            ->andWhere("ie.id = $codInvEndereco")
            ->orderBy('p.id, p.grade, ice.numContagem');

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

}