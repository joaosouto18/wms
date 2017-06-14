<?php

namespace Wms\Module\Produtividade\Grid;

use Core\Grid\Pager;
use Wms\Module\Web\Grid;

class ProdutividadeDetalhada extends Grid
{
    public function init($params)
    {

        $this->setAttrib('title','produtividade-detalhada');
        $this->setSource(new \Core\Grid\Source\ArraySource($params));

                $this->addColumn(array(
                    'label' => utf8_encode('Usuario'),
                    'index' => 'NOM_PESSOA',
                ));
                $this->addColumn(array(
                    'label' => 'Atividade',
                    'index' => 'DSC_ATIVIDADE',
                ));
//                $this->addColumn(array(
//                    'label' => utf8_encode('Expedicao'),
//                    'index' => 'COD_EXPEDICAO',
//                ));
//                $this->addColumn(array(
//                    'label' => utf8_encode('Mapa Separacao'),
//                    'index' => 'COD_MAPA_SEPARACAO',
//                ));
                $this->addColumn(array(
                    'label' => 'Código',
                    'index' => 'IDENTIDADE',
                ));
                $this->addColumn(array(
                    'label' => 'Peso',
                    'index' => 'QTD_PESO',
                    'render' => 'N2'
                ));
                $this->addColumn(array(
                    'label' => 'Volumes',
                    'index' => 'QTD_VOLUMES',
                ));
                $this->addColumn(array(
                    'label' => 'Qtd. Produtos',
                    'index' => 'QTD_PRODUTOS',
                ));
                $this->addColumn(array(
                    'label' => 'Qtd. Cubagem',
                    'index' => 'QTD_CUBAGEM',
                ));
                $this->addColumn(array(
                    'label' => 'Qtd. Palete',
                    'index' => 'QTD_PALETES',
                ));
                $this->addColumn(array(
                    'label' => 'Qtd. Carga',
                    'index' => 'QTD_CARGA',
                ));
                $this->addColumn(array(
                    'label' => 'Data Inicio',
                    'index' => 'DTH_INICIO',
                ));
                $this->addColumn(array(
                    'label' => 'Data Fim',
                    'index' => 'DTH_FIM',
                ));
                $this->addColumn(array(
                    'label' => 'Tempo Gasto',
                    'index' => 'TEMPO_GASTO',
                ));
                

        $this->setShowExport(true);
        $pg = new Pager(count($params),0,count($params));
        $this->setPager($pg);
        return $this;
    }

}

