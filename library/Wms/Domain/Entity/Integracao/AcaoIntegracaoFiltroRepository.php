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












/*
        if ($conexaoEn->getProvedor() == ConexaoIntegracao::PROVEDOR_ORACLE) {
            //PARAMETRIZA A DATA DE ULTIMA EXECUÇÃO DA QUERY
            if ($acaoEn->getDthUltimaExecucao() == null) {
                $dthExecucao = "TO_DATE('01/01/1900 01:01:01','DD/MM/YY HH24:MI:SS')";
                if ($acaoEn->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_PRODUTO) {
                    $query = str_replace("and p.dtcadastro > :dthExecucao", "", $query);
                    $query = str_replace("AND (log.datainicio > :dthExecucao OR p.dtultaltcom > :dthExecucao)", "", $query);

                }
            } else {
                $dthExecucao = "TO_DATE('" . $acaoEn->getDthUltimaExecucao()->format("d/m/Y H:i:s") . "','DD/MM/YYYY HH24:MI:SS')";
            }

            $query = str_replace(":dthExecucao", $dthExecucao, $query);

            //PARAMETRIZA O COD_FILIAL PELO CODIGO DA FILIAL DE INTEGRAÇAO PARA INTEGRAÇÕES NO WINTHOR
            $query = str_replace(":codFilial", $this->getSystemParameterValue("WINTHOR_CODFILIAL_INTEGRACAO"), $query);

            //DEFINI OS PARAMETROS PASSADOS EM OPTIONS
            if (!is_null($options)) {
                foreach ($options as $key => $value) {
                    $query = str_replace(":?" . ($key + 1), $value, $query);
                }
            }
        } else if ($conexaoEn->getProvedor() == ConexaoIntegracao::PROVEDOR_MYSQL) {
            $dthExecucao = null;
            if ($acaoEn->getDthUltimaExecucao() == null) {
                if ($acaoEn->getTipoAcao()->getId() == AcaoIntegracao::INTEGRACAO_PRODUTO) {
                    $query = str_replace("and a.es1_dtalteracao > :dthExecucao", "", $query);
                } else {
                    $hoje = new \DateTime();
                    $dthExecucao = $hoje->format("Y-m-d");
                }
            } else {
                $dthExecucao = $acaoEn->getDthUltimaExecucao()->format("Y-m-d");
            }
            $query = str_replace(":dthExecucao", "'$dthExecucao'", $query);
        }
*/
    }

}