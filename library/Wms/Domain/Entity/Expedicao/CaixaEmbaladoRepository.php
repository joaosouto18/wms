<?php


namespace Wms\Domain\Entity\Expedicao;


use Wms\Domain\Configurator;
use Wms\Domain\EntityRepository;

class CaixaEmbaladoRepository extends EntityRepository
{
    /**
     * @param CaixaEmbalado $entity
     * @param array $params
     * @param bool $flush
     * @return CaixaEmbalado
     * @throws \Exception
     */
    public function save(CaixaEmbalado $entity, array $params, $flush = true)
    {
        try {
            /** @var CaixaEmbalado $entity */
            $entity = Configurator::configure($entity, $params['identificacao']);

            if ($entity->isDefault() == null) {
                $default = $this->findOneBy(['isDefault' => true]);
                $entity->setIsDefault(empty($default));
            }

            $this->_em->persist($entity);
            if ($flush) $this->_em->flush();

            return $entity;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id int
     * @param $flush bool
     * @throws \Exception
     */
    public function exclusaoLogica($id, $flush = true)
    {
        try{
            /** @var CaixaEmbalado $entity */
            $entity = $this->find($id);
            $entity->setIsAtiva(false);
            $entity->setIsDefault(false);

            if ($entity->isDefault())
            {
                $sql = "SELECT MIN(COD_CAIXA) ID FROM CAIXA_EMBALADO WHERE IS_ATIVA > 0 AND COD_CAIXA != $id";
                $result = $this->_em->getConnection()->query($sql)->fetch();
                if (!empty($result)) {
                    self::changeDefault($result['ID'], $entity, false);
                } else {
                    $entity->setIsDefault(false);
                }
            }

            $this->_em->persist($entity);
            if ($flush) $this->_em->flush();

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $id int
     * @param $flush bool
     * @param CaixaEmbalado $oldDefault
     * @throws \Exception
     */
    public function changeDefault($id, CaixaEmbalado $oldDefault = null, $flush = true)
    {
        try{
            /** @var CaixaEmbalado $newDefault */
            $newDefault = $this->find($id);
            $newDefault->setIsDefault(true);
            $this->_em->persist($newDefault);

            if (empty($oldDefault)) $oldDefault = $this->findOneBy(['isDefault' => true]);

            $oldDefault->setIsDefault(false);
            $this->_em->persist($oldDefault);

            if ($flush) $this->_em->flush();

        } catch (\Exception $e) {
            throw $e;
        }
    }
}