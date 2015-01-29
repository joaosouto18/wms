<?php

namespace Wms\Module\Web\Grid\Expedicao;

use Wms\Domain\Entity\Expedicao\EtiquetaSeparacao;
use Wms\Domain\Entity\Expedicao;
use Wms\Module\Web\Grid,
    Wms\Domain\Entity\Recebimento;

/**
 * Grid da Página Inicial da Expedição
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class CortesPendentes extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($idExpedicao)
    {
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $result = $etiquetaRepo->getPendenciasByExpedicaoAndStatus($idExpedicao, EtiquetaSeparacao::STATUS_PENDENTE_CORTE,"DQL");

        $grid = new \Core\Grid(new \Core\Grid\Source\Doctrine($result));
        $this->setSource(new \Core\Grid\Source\Doctrine($result))
                ->setId('expedicao-cortes-grid')
                ->setAttrib('class', 'grid-expedicao-cortes')
                ->setAttrib('caption', 'Pendencias de corte na expedição')
                ->addColumn(array(
                    'label' => 'Etiqueta',
                    'index' => 'codBarras',
                ))
                ->addColumn(array(
                    'label' => 'Produto',
                    'index' => 'codProduto',
                ))
                ->addColumn(array(
                    'label' => 'Descricao',
                    'index' => 'produto',
                ))                
                ->addColumn(array(
                    'label' => 'Grade',
                    'index' => 'grade',
                ))
                ->addColumn(array(
                    'label' => 'Volume',
                    'index' => 'embalagem',
                ))
                ->addColumn(array(
                    'label' => 'Cliente',
                    'index' => 'cliente',
                ))
                ->addColumn(array(
                    'label' => 'Data Conferência',
                    'index' => 'dataConferencia',
                    'render' => 'DataTime'
                ))                
                ->addAction(array(
                    'label' => 'Cortar',
                    'moduleName' => 'expedicao',
                    'controllerName' => 'corte',
                    'actionName' => 'index',
                    'pkIndex' => 'codBarras',
                    'cssClass' => 'dialogAjax'
                ))
                ->setShowExport(false);

        return $this;
    }

}

