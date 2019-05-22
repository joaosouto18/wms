<?php

namespace Wms\Module\Expedicao\Grid;

use Doctrine\ORM\EntityManager;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Expedicao as ExpedicaoEntity;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class AcompanhamentoSeparacao extends Grid
{
    /**
     *
     * @param array $params
     */

    public function init(array $params = array())
    {

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo = $this->getEntityManager()->getRepository('wms:Expedicao');
        $permissaoEn = $this->getEntityManager()->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'PERMITE_REALIZAR_CORTES_WMS'));
        $permite = (!empty($permissaoEn) && $permissaoEn->getValor() == "N") ? false : true;

        $sessao = new \Zend_Session_Namespace('deposito');
        $params['centrais'] = $sessao->centraisPermitidas;

        $result = $expedicaoRepo->buscar($params, $sessao->codFilialExterno);

        $this->setAttrib('title','Expedição');
        $source = $this->setSource(new \Core\Grid\Source\ArraySource($result));
        $source->setId('expedicao-index-grid')
            ->setAttrib('class', 'grid-expedicao')
            ->addColumn(array(
                'label' => 'Expedição',
                'index' => 'id',
            ))
            ->addColumn(array(
                'label' => 'Cargas',
                'index' => 'carga',
            ))
            ->addColumn(array(
                'label' => 'Tipo Pedido',
                'index' => 'tipopedido',
            ))
            ->addColumn(array(
                'label' => 'Cubagem',
                'index' => 'cubagem',
                'render' => 'N3'
            ))
            ->addColumn(array(
                'label' => 'Peso',
                'index' => 'peso',
                'render' => 'N3'
            ))
            ->addColumn(array(
                'label' => 'Qtd.Ped.',
                'index' => 'qtdPedidos',
                'render' => 'N0'
            ))
            ->addColumn(array(
                'label' => 'Placa',
                'index' => 'placaExpedicao'
            ))

            ->addColumn(array(
                'label' => 'Itinerarios',
                'index' => 'itinerario',
            ))
            ->addColumn(array(
                'label' => 'Motorista',
                'index' => 'motorista',
            ))
            ->addColumn(array(
                'label' => 'Data Inicial',
                'index' => 'dataInicio',
            ))
            ->addColumn(array(
                'label' => 'Data Final',
                'index' => 'dataFinalizacao',
            ))
            ->addColumn(array(
                'label' => 'Imprimir',
                'index' => 'imprimir',
            ))
            ->addColumn(array(
                'label' => '% Conferência',
                'index' => 'PercConferencia',
            ))
            ->addColumn(array(
                'label' => 'Status',
                'index' => 'status',
            ));

        $source->setShowExport(true)
            ->setShowMassActions($params);

        return $this;
    }

    /**
     * @param $result
     * @param $expedicaoRepo
     * @return mixed
     */
    public function formatItinerarios($result, $expedicaoRepo)
    {
        $colItinerario = array();
        foreach ($result as $key => $expedicao) {
            $itinerarios = $expedicaoRepo->getItinerarios($result[$key]['id']);
            foreach ($itinerarios as $itinerario) {
                if (!is_numeric($itinerario['id'])) {
                    $colItinerario[] = '(' . $itinerario['id'] . ')' . $itinerario['descricao'];
                } else {
                    $colItinerario[] = $itinerario['descricao'];
                }
            }
            $result[$key]['itinerario'] = implode(', ', $colItinerario);
            unset($colItinerario);
        }
        return $result;
    }

    /**
     * @param $result
     * @param $expedicaoRepo
     * @return mixed
     */
    public function formataCargas($result, $expedicaoRepo)
    {
        $colCarga = array();
        foreach ($result as $key => $expedicao) {
            $cargas = $expedicaoRepo->getCargas($result[$key]['id']);
            foreach ($cargas as $carga) {
                $colCarga[] = $carga->getCodCargaExterno();
            }
            $result[$key]['carga'] = implode(', ', $colCarga);
            unset($colCarga);
        }
        return $result;
    }

}

