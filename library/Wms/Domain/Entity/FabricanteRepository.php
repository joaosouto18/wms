<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Fabricante as FabricanteEntity,
    \Doctrine\Common\Persistence\ObjectRepository;


class FabricanteRepository extends EntityRepository implements ObjectRepository
{

    public function save($idFabricante, $nome)
    {
        $idFabricante = trim($idFabricante);
        $nome = trim($nome);

        $em = $this->getEntityManager();
        $em->beginTransaction();

        try {
            $fabricanteEn = $em->getRepository('wms:Fabricante')->findOneBy(array('id' => $idFabricante));

            if (!$fabricanteEn)
                $fabricanteEn = new FabricanteEntity();

            $fabricanteEn->setId($idFabricante);
            $fabricanteEn->setNome($nome);

            $em->persist($fabricanteEn);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }
    
    public function remove($id)
    {
	$em = $this->getEntityManager();
	$proxy = $em->getReference('wms:Fabricante', $id);
	$em->remove($proxy);
    }

    
     /**
     * Retorna um array id => valor do
     * @return array
     */
    public function getIdValue()
    {
        $valores = array();

        foreach ($this->findBy(array(), array('nome' => 'ASC')) as $item) {
            $valores[$item->getId()] = $item->getNome();
        }

        return $valores;
    }
}