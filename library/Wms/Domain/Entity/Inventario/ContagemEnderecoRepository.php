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

        if (empty($params['codProduto'])) {
            throw new \Exception("codProduto não pode ser vazio");
        }

        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {

            $contagemEndEn = new ContagemEndereco();

            if ($params['codProdutoVolume'] != null) {
                $enProdutoVolume = $this->getEntityManager()->getReference('wms:Produto\Volume', $params['codProdutoVolume']);
                $contagemEndEn->setProdutoVolume($enProdutoVolume);
            }

            $enProduto = $this->getEntityManager()->getReference('wms:Produto', array('id' => $params['codProduto'], 'grade' => $params['grade']));
            $contagemEndEn->setProduto($enProduto);

            $contagemEndEn->setQtdContada($params['qtd']);
            $contagemEndEn->setNumContagem($params['numContagem']);
            $contagemEndEn->setQtdContada($params['qtd']);
            $contagemEndEn->setQtdAvaria($params['qtdAvaria']);
            $contagemEndEn->setCodProduto($params['codProduto']);
            $contagemEndEn->setGrade($params['grade']);
            $contagemEndEn->setCodProdutoEmbalagem($params['codProdutoEmbalagem']);
            $contagemEndEn->setCodProdutoVolume($params['codProdutoVolume']);

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

    public function getContagens($params)
    {
        $query = $this->_em->createQueryBuilder()
            ->select('ce.numContagem')
            ->from("wms:Inventario\Endereco","ie")
            ->innerJoin("wms:Inventario\ContagemEndereco", 'ce', 'WITH', 'ie.id = ce.inventarioEndereco')
            ->andWhere("ie.inventario = :idInventario")
            ->setParameter('idInventario', $params['idInventario'])
            ->groupBy('ce.numContagem');

        if (isset($params['divergencia']) && $params['divergencia'] == 1) {
            $query->andWhere('ce.divergencia = 1');
        }  else {
            $query->andWhere('(ie.divergencia is null or ie.inventariado is null)');
        }
        return $query->getQuery()->getResult();
    }

}