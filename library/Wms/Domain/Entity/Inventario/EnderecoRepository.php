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

        if (empty($params['codInventario'])) {
            throw new \Exception("codInventario não pode ser vazio");
        }

        if (empty($params['codDepositoEndereco'])) {
            throw new \Exception("codDepositoEndereco não pode ser vazio");
        }

        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {

            $enInvEndereco = new Endereco();

            $inventarioEntity = $em->getReference('wms:Inventario',$params['codInventario']);
            $enInvEndereco->setInventario($inventarioEntity);
            $enderecoEntity = $em->getReference('wms:Deposito\Endereco',$params['codDepositoEndereco']);
            $enInvEndereco->setDepositoEndereco($enderecoEntity);

            $em->persist($enInvEndereco);
            $em->commit();

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
        if (empty($params['idInventario'])) {
            throw new \Exception('idInventario não pode ser vazio');
        }
        if (empty($params['limit'])) {
            $params['limit'] = 10;
        }

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("de.descricao, ie.divergencia, NVL(pe.codProduto, pv.codProduto) codProduto, NVL(pe.grade, pv.grade) grade")
            ->from("wms:Inventario\Endereco","ie")
            ->innerJoin('ie.depositoEndereco', 'de')
            ->leftJoin("wms:Inventario\ContagemEndereco", 'ce', 'WITH', 'ie.id = ce.inventarioEndereco')
            ->leftJoin('wms:Produto\Embalagem', 'pe', 'WITH', 'ce.codProdutoEmbalagem = pe.id')
            ->leftJoin('wms:Produto\Volume', 'pv', 'WITH', 'ce.codProdutoVolume = pv.id')
            ->andWhere("ie.inventario = :idInventario")
            ->setParameter('idInventario', $params['idInventario'])
            ->orderBy('de.descricao')
            ->distinct(true)
            ->setMaxResults($params['limit']);

        if (isset($params['rua']) && !empty($params['rua'])) {
            $query->andWhere("de.rua = :rua");
            $query->setParameter('rua', $params['rua']);
        }

        if (isset($params['numContagem']) && !empty($params['numContagem'])) {
            $query->andWhere("ce.numContagem = :numContagem");
            $query->setParameter('numContagem', $params['numContagem']);
        }

        if (isset($params['divergencia']) && !empty($params['divergencia'])) {
            $query->andWhere("ie.divergencia = 1");
        } else {
            $query->andWhere("ie.divergencia is null");
            $query->andWhere("ie.inventariado is null");
        }

        return $query->getQuery()->getResult();
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
        $idInvEnd = $enderecoEntity->getId();

        $query = $this->_em->createQueryBuilder()
            ->select('max(ce.id) id, ce.codProduto, ce.grade, ce.codProdutoEmbalagem, ce.codProdutoVolume')
            ->from("wms:Inventario\Endereco","ie")
            ->innerJoin("wms:Inventario\ContagemEndereco", 'ce', 'WITH', 'ie.id = ce.inventarioEndereco')
            ->andWhere("ie.id = $idInvEnd")
            ->groupBy('ce.codProduto, ce.grade, ce.codProdutoEmbalagem, ce.codProdutoVolume');

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
            ->andWhere("ie.inventario = $idInventario");

        return $query->getQuery()->getResult();
    }

}