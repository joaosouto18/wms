<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Empresa as EmpresaEntity;


class EmpresaRepository extends EntityRepository
{
    /**
     * Retorna um array id => valor do Ajuda
     * @return array
     */
    public function getIdValue()
    {
        $valores = array();

        foreach ($this->findBy(array(), array('nomEmpresa' => 'ASC')) as $item) {
            $valores[$item->getId()] = $item->getDscAjuda();
        }

        return $valores;
    }

    /**
     * @param EmpresaEntity $empresa
     * @param array $values
     * @throws \Exception
     */
    public function save(EmpresaEntity $empresa, array $values){

        extract($values);
        $em = $this->getEntityManager();

        // request
        $empresa->setNomEmpresa($nomEmpresa);
        $empresa->setIdentificacao(str_replace(array('.','-','/'), '',$identificacao));
        $empresa->setPrioridadeEstoque($prioridadeEstoque);

        $em->persist($empresa);
    }

    public function remove($id){
        $em = $this->getEntityManager();
        $proxy = $em->getReference('wms:Empresa', $id);
        $em->remove($proxy);
    }
}
