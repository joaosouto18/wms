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
    public function getQuery($acaoEn, $options, $filtro)
    {
        $query = $acaoEn->getQuery();

        $acaoIntegracaoFiltroEntity = $this->findOneBy(array('acaoIntegracao' => $acaoEn->getId(),'tipoRegistro' => $filtro));
        $query = str_replace(":where", $acaoIntegracaoFiltroEntity->getFiltro(), $query);

        if (!is_null($options)) {
            foreach ($options as $key => $value) {
                $query = str_replace(':?' . ($key + 1), $value, $query);
            }
        }
        $query = str_replace(":codFilial", $this->getSystemParameterValue("WINTHOR_CODFILIAL_INTEGRACAO"), $query);

        return $query;

    }

}