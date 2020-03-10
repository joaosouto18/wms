<?php

namespace Wms\Module\Web\Grid\Expedicao;


use Wms\Module\Web\Grid;

class Embalados extends Grid
{
    /**
     * @param $idExpedicao
     * @return $this|void
     */
    public function init($embalados)
    {
        /** @var \Wms\Domain\Entity\Expedicao\EtiquetaSeparacaoRepository $etiquetaRepo */
        $etiquetaRepo = $this->getEntityManager()->getRepository('wms:Expedicao\EtiquetaSeparacao');
        $this->showPager = true;
        $this->showExport = false;
        $this->setSource(new \Core\Grid\Source\ArraySource($embalados))
            ->setId('expedicao-mapas-grid')
            ->setAttrib('class', 'grid-expedicao-pendencias')
            ->setAttrib('caption', 'Embalados')
            ->addColumn(array(
                'label' => 'Mapa',
                'index' => 'COD_MAPA_SEPARACAO',
            ))
            ->addColumn(array(
                'label' => 'Cod. Embalados',
                'index' => 'COD_MAPA_SEPARACAO_EMB_CLIENTE',
            ))
            ->addColumn(array(
                'label' => 'Cliente',
                'index' => 'NOM_PESSOA',
            ))
            ->addColumn(array(
                'label' => 'Status',
                'index' => 'DSC_SIGLA',
            ))
            ->addAction(array(
                'label' => 'Produtos Conferidos',
                'moduleName' => 'expedicao',
                'controllerName' => 'os',
                'actionName' => 'produtos-volumes-embalados',
                'cssClass' => 'dialogAjax',
                'pkIndex' => 'COD_MAPA_SEPARACAO_EMB_CLIENTE'
            ))
            ->addAction(array(
                'label'=>'Reimprimir Volumes Embalado',
                'moduleName'=>'expedicao',
                'controllerName'=>'etiqueta',
                'actionName'=>'reimprimir-embalado-unico',
                'cssClass'=>'pdfAjax',
                'pkIndex'=>'COD_MAPA_SEPARACAO_EMB_CLIENTE'
            ));

        return $this;
    }

}

