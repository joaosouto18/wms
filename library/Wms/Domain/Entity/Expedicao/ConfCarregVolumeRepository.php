<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Configurator;

class ConfCarregVolumeRepository extends EntityRepository
{
    /**
     * @param $params
     * @param bool $executeFlush
     * @return ConfCarregVolume
     * @throws \Exception
     */
    public function save($params, $executeFlush = true)
    {
        try {
            /** @var ConfCarregVolume $entity */
            $entity = Configurator::configure(new $this->_entityName, $params);

            $this->_em->persist($entity);
            if ($executeFlush) $this->_em->flush();

            return $entity;

        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function checkConferido($confCarreg, $volume)
    {
        $sql = "SELECT * 
                FROM CONFERENCIA_CARREGAMENTO CONF_CARREG
                INNER JOIN CONF_CARREG_OS CCO on CONF_CARREG.COD_CONF_CARREG = CCO.COD_CONF_CARREG
                INNER JOIN CONF_CARREG_VOLUME CCV on CCO.COD_CONF_CARREG_OS = CCV.COD_CONF_CARREG_OS
                WHERE CONF_CARREG.COD_CONF_CARREG = $confCarreg AND CCV.COD_VOLUME = $volume";

        return !empty($this->_em->getConnection()->query($sql)->fetchAll());
    }
}