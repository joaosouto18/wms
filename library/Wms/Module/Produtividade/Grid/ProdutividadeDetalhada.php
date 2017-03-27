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
                    'label' => utf8_encode('Usu�rio'),
                    'index' => 'NOM_PESSOA',
                ));
                $this->addColumn(array(
                    'label' => utf8_encode('Expedi��o'),
                    'index' => 'COD_EXPEDICAO',
                ));
                $this->addColumn(array(
                    'label' => utf8_encode('Mapa Separa��o'),
                    'index' => 'COD_MAPA_SEPARACAO',
                ));
                $this->addColumn(array(
                    'label' => 'Peso',
                    'index' => 'NUM_PESO',
                    'render' => 'N2'
                ));
                $this->addColumn(array(
                    'label' => 'Volumes',
                    'index' => 'VOLUMES',
                ));
                $this->addColumn(array(
                    'label' => 'Qtd. Produtos',
                    'index' => 'QTD_PRODUTOS',
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

