<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository;


class EquipamentoRepository extends EntityRepository
{

    public function save($entity, $params)
    {
        $usuarioRepo = $this->getEntityManager()->getRepository("wms:Usuario");
        $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();
        $usuarioEn = $usuarioRepo->find($idUsuario);
        $entity->setDescricao($params['descricao']);
        $entity->setModelo($params['modelo']);
        $entity->setMarca($params['marca']);
        $entity->setPatrimonio($params['patrimonio']);
        $entity->setUsuario($usuarioEn);

        $this->getEntityManager()->persist($entity);
    }

    public function remove($id)
    {
        $equipamentoEn = $this->getEntityManager()->getReference('wms:Equipamento', $id);

        $this->getEntityManager()->remove($equipamentoEn);

    }

    public function buscar($params)
    {
        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('e.id, e.descricao, e.modelo, e.marca, e.patrimonio')
            ->from('wms:Equipamento', 'e');
        if (isset($params['idEquipamento']) && !empty($params['idEquipamento'])) {
            $sql->andWhere("e.id = '$params[idEquipamento]'");
        }
        if (isset($params['descricao']) && !empty($params['descricao'])) {
            $sql->andWhere("e.descricao = '$params[descricao]'");
        }
        if (isset($params['modelo']) && !empty($params['modelo'])) {
            $sql->andWhere("e.modelo = '$params[modelo]'");
        }
        if (isset($params['marca']) && !empty($params['marca'])) {
            $sql->andWhere("e.marca = '$params[marca]'");
        }
        if (isset($params['patrimonio']) && !empty($params['patrimonio'])) {
            $sql->andWhere("e.patrimonio = '$params[patrimonio]'");
        }

        return $sql->getQuery()->getResult();
    }

}