<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
   Wms\Domain\Entity\Expedicao\Itinerario;

class ItinerarioRepository extends EntityRepository
{

    public function save($itinerario) {

        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {
            $enItinerario = new Itinerario;
            $enItinerario->setId($itinerario['idItinerario']);
            $enItinerario->setDescricao($itinerario['nomeItinerario']);

            $em->persist($enItinerario);
            $em->flush();
            $em->commit();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }

        return $enItinerario;
    }

    public function getIdValue()
    {
        $itinerarios = array();

        foreach ($this->findAll() as $itinerario) {
            $itinerarios[$itinerario->getId()] = $itinerario->getDescricao();
        }

        return $itinerarios;
    }

}