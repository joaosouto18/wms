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
                WHERE CONF_CARREG.COD_CONF_CARREG = $confCarreg AND CCV.COD_VOLUME = '$volume'";

        return !empty($this->_em->getConnection()->query($sql)->fetchAll());
    }

    public function checkVolumeInvalidoConfCarreg($confCarreg, $idVolume)
    {
        $etiquetaConferida = EtiquetaSeparacao::STATUS_CONFERIDO;
        $sql = "SELECT *
                FROM CONFERENCIA_CARREGAMENTO CC
                INNER JOIN CONF_CARREG_CLIENTE CCC on CC.COD_CONF_CARREG = CCC.COD_CONF_CARREG
                INNER JOIN (
                    SELECT EM.COD_EXPEDICAO, ES.COD_ETIQUETA_SEPARACAO AS ID_VOLUME, PED2.COD_PESSOA
                    FROM ETIQUETA_MAE EM
                    INNER JOIN ETIQUETA_SEPARACAO ES on EM.COD_ETIQUETA_MAE = ES.COD_ETIQUETA_MAE AND ES.COD_STATUS = $etiquetaConferida
                    INNER JOIN PEDIDO PED2 ON PED2.COD_PEDIDO = ES.COD_PEDIDO
                    UNION
                    SELECT MS.COD_EXPEDICAO, MSEC.COD_MAPA_SEPARACAO_EMB_CLIENTE AS ID_VOLUME, MSEC.COD_PESSOA
                    FROM MAPA_SEPARACAO MS
                    INNER JOIN MAPA_SEPARACAO_EMB_CLIENTE MSEC on MS.COD_MAPA_SEPARACAO = MSEC.COD_MAPA_SEPARACAO
                ) VOLS ON VOLS.COD_PESSOA = CCC.COD_CLIENTE AND VOLS.COD_EXPEDICAO = CC.COD_EXPEDICAO
                WHERE CC.COD_CONF_CARREG = $confCarreg AND VOLS.ID_VOLUME = '$idVolume'";

        return empty($this->_em->getConnection()->query($sql)->fetchAll());
    }

    public function checkVolumagemConferida($confCarreg)
    {
        $etiquetaConferida = EtiquetaSeparacao::STATUS_CONFERIDO;
        $sql = "SELECT *
                FROM CONFERENCIA_CARREGAMENTO CC
                INNER JOIN CONF_CARREG_CLIENTE CCC ON CC.COD_CONF_CARREG = CCC.COD_CONF_CARREG
                INNER JOIN (
                    SELECT EM.COD_EXPEDICAO, ES.COD_ETIQUETA_SEPARACAO AS ID_VOLUME, PED2.COD_PESSOA, 'ES' TIPO
                    FROM ETIQUETA_MAE EM
                    INNER JOIN ETIQUETA_SEPARACAO ES ON EM.COD_ETIQUETA_MAE = ES.COD_ETIQUETA_MAE AND ES.COD_STATUS = $etiquetaConferida
                    INNER JOIN PEDIDO PED2 ON PED2.COD_PEDIDO = ES.COD_PEDIDO
                    UNION
                    SELECT MS.COD_EXPEDICAO, MSEC.COD_MAPA_SEPARACAO_EMB_CLIENTE AS ID_VOLUME, MSEC.COD_PESSOA, 'VE' TIPO
                    FROM MAPA_SEPARACAO MS
                    INNER JOIN MAPA_SEPARACAO_EMB_CLIENTE MSEC ON MS.COD_MAPA_SEPARACAO = MSEC.COD_MAPA_SEPARACAO
                ) VOLS ON VOLS.COD_PESSOA = CCC.COD_CLIENTE AND VOLS.COD_EXPEDICAO = CC.COD_EXPEDICAO
                INNER JOIN CONF_CARREG_OS CCO ON CC.COD_CONF_CARREG = CCO.COD_CONF_CARREG
                LEFT JOIN CONF_CARREG_VOLUME CCV ON CCO.COD_CONF_CARREG_OS = CCV.COD_CONF_CARREG_OS AND CCV.IND_TIPO_VOLUME = VOLS.TIPO AND CCV.COD_VOLUME = VOLS.ID_VOLUME
                WHERE CC.COD_CONF_CARREG = $confCarreg AND CCV.COD_VOLUME IS NULL";

        return empty($this->_em->getConnection()->query($sql)->fetchAll());
    }
}