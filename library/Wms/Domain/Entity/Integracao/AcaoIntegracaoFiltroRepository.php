<?php
/**
 * Created by PhpStorm.
 * User: Rodrigo
 * Date: 29/06/2017
 * Time: 15:12
 */

namespace Wms\Domain\Entity\Integracao;

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
    public function getQuery($acaoEn, $options, $filtro, $data)
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

        if (!is_null($options)) {
            foreach ($options as $key => $value) {
                $query = str_replace(':?' . ($key + 1), $value, $query);
            }
        }
        $query = str_replace(":codFilial", $this->getSystemParameterValue("WINTHOR_CODFILIAL_INTEGRACAO"), $query);

        return $query;

    }

}