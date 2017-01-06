<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Fabricante as FabricanteEntity,
    \Doctrine\Common\Persistence\ObjectRepository;


class FabricanteRepository extends EntityRepository implements ObjectRepository
{

    public function save($idFabricante, $nome, $flush = true)
    {
        $idFabricante = trim($idFabricante);
        $nome = trim($nome);

        $em = $this->getEntityManager();
        if ($flush == true) {
            $em->beginTransaction();
        }

        try {
            $fabricanteEn = $this->findOneBy(array('id' => $idFabricante));
            $novo = false;
            if (!$fabricanteEn) {
                $novo = true;
                $fabricanteEn = new FabricanteEntity();
            }

            $fabricanteEn->setId($idFabricante);
            $fabricanteEn->setNome($nome);

            $em->persist($fabricanteEn);

            if ($flush == true) {
                $em->flush();
                $em->commit();
            } else {
                if ($novo == true) {
                    $em->flush();
                    $em->clear();
                }
            }
        } catch (\Exception $e) {
            if ($flush == true) {
                $em->rollback();
            }
            throw new \Exception($e->getMessage());
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