<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;

class ItinerarioRepository extends EntityRepository
{

    public function save($itinerario, $runFlush) {

        $em = $this->getEntityManager();

        if ($runFlush)
            $em->beginTransaction();
        try {
            $enItinerario = new Itinerario;
            $enItinerario->setId($itinerario['idItinerario']);
            $enItinerario->setDescricao($itinerario['nomeItinerario']);

            $em->persist($enItinerario);
            if ($runFlush) {
                $em->flush();
                $em->commit();
            }

        } catch(\Exception $e) {
            if ($runFlush)
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