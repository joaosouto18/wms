<?php


namespace Wms\Domain\Entity\NotaFiscal;


use Doctrine\ORM\EntityRepository;
use Wms\Domain\Configurator;

class TipoRepository extends EntityRepository
{
    /**
     * @param array $data
     * @param bool $runFlush
     * @throws \Exception
     */
    public function save($data, $runFlush = false)
    {
        try {
            /** @var Tipo $entity */
            $entity = null;
            if (!empty($data['id'])) {
                $entity = $this->find($data['id']);
                if ($entity->isSystemDefault())
                    throw new \Exception("Este registro é padrão do sistema, usuários não tem permissão para alterar!");
            }

            $codEn = $this->findOneBy(['codExterno' => $data['codExterno']]);
            if (!empty($codEn) && $codEn->getId() !== (int)$data['id'])
                throw new \Exception("O código externo '$data[codExterno]' já está cadastrado");

            if (empty($entity)) $entity = new Tipo();

            $entity = Configurator::configure($entity, $data);

            $this->_em->persist($entity);
            if ($runFlush) $this->_em->flush($entity);

            return $entity;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}