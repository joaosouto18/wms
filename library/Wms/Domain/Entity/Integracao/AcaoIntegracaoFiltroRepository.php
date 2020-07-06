<?php
/**
 * Created by PhpStorm.
 * User: Rodrigo
 * Date: 29/06/2017
 * Time: 15:12
 */

namespace Wms\Domain\Entity\Integracao;

use Wms\Domain\Configurator;
use Wms\Domain\EntityRepository;

/**
 * Class AcaoIntegracaoFiltroRepository
 * @package Wms\Domain\Entity\Integracao
 */
class AcaoIntegracaoFiltroRepository extends EntityRepository
{
    /**
     * @param $acaoEn
     * @param $options
     */
    public function getQuery($acaoEn, $options, $filtro, $data, $insertAll = false)
    {
        $query = $acaoEn->getQuery();

        if ($filtro === AcaoIntegracaoFiltro::DATA_ESPECIFICA && empty($data)) {
            $query = str_replace(":where", '', $query);
        } else {
            $acaoIntegracaoFiltroEntity = $this->findOneBy(array('acaoIntegracao' => $acaoEn->getId(), 'tipoRegistro' => $filtro));
            if ($acaoIntegracaoFiltroEntity != null) {
                $query = str_replace(":where", $acaoIntegracaoFiltroEntity->getFiltro(), $query);
            }
        }

        if($insertAll === 'ORACLE'){
            foreach ($options as $keyOption => $option){
                foreach ($option as $key => $value) {
                    $queryAll[$keyOption] = str_replace(':?' . ($key + 1), $value, str_replace('INSERT','',$query));
                    $query = $queryAll[$keyOption];
                }
                $query = $acaoEn->getQuery();
            }
            $query = 'INSERT ALL '.implode(' ', $queryAll).' SELECT * FROM dual';
        }elseif($insertAll === 'MSSQL'){
            $vetQuery = explode('VALUES', $query);
            $query = $vetQuery[1];
            foreach ($options as $keyOption => $option){
                foreach ($option as $key => $value) {
                    $queryAll[$keyOption] = str_replace(':?' . ($key + 1), $value, $query);
                    $query = $queryAll[$keyOption];
                }
                $query = $vetQuery[1];
            }
            $query = $vetQuery[0].' VALUES '.implode(', ', $queryAll);
        }elseif($insertAll === 'SQLSRV'){
            $vetQuery = explode('VALUES', $query);
            $query = $vetQuery[1];
            foreach ($options as $keyOption => $option){
                foreach ($option as $key => $value) {
                    $queryAll[$keyOption] = str_replace(':?' . ($key + 1), $value, $query);
                    $query = $queryAll[$keyOption];
                }
                $query = $vetQuery[1];
            }
            $query = $vetQuery[0].' VALUES '.implode(', ', $queryAll);
        }else{
            if (!is_null($options)) {
                foreach ($options as $key => $value) {
                    $query = str_replace(':?' . ($key + 1), $value, $query);
                }
            }
        }
        $query = str_replace(":codFilial", $this->getSystemParameterValue("WINTHOR_CODFILIAL_INTEGRACAO"), $query);
        return $query;
    }

    public function save(array $params)
    {
        $entity = null;
        /** @var AcaoIntegracaoFiltro $entity */
        $entity = $this->findOneBy(['acaoIntegracao' => $params['acaoIntegracao'], 'tipoRegistro' => $params['tipoRegistro']]);
        if (empty($entity)) {
            $entity = new AcaoIntegracaoFiltro();
        }

        $entity = Configurator::configure($entity, $params);
        $this->_em->persist($entity);

        return $entity;
    }
}