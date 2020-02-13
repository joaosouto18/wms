<?php

namespace Wms\Module\Validade\Grid;

use Wms\Domain\Entity\Sistema\Parametro;
use Wms\Module\Web\Grid;

class Validade extends Grid
{

    public function init($produtos)
    {
        $em = \Zend_Registry::get('doctrine')->getEntityManager();
        $paramRepo = $em->getRepository('wms:Sistema\Parametro');
        /** @var Parametro $param */
        $param = $paramRepo->findOneBy(array('constante' => "UTILIZA_GRADE"));

        $this->setAttrib('title','Consulta');
        $this->setSource(new \Core\Grid\Source\ArraySource($produtos));
        $this->setShowExport(false);
        $this->addColumn(array(
                'label' => 'Cód. Produto',
                'index' => 'COD_PRODUTO'
            ));

        if ($param->getValor() === "S"){
            $this->addColumn(array(
                'label' => 'Grade',
                'index' => 'GRADE',
            ));
        }

        $this->addColumn(array(
                'label' => 'Descrição',
                'index' => 'DESCRICAO',
            ))
            ->addColumn(array(
                'label' => 'Linha de separação',
                'index' => 'LINHA_SEPARACAO',
            ))
            ->addColumn(array(
                'label' => 'Fabricante/Fornecedor',
                'index' => 'FABRICANTE',
            ))
            ->addColumn(array(
                'label' => 'Endereço',
                'index' => 'ENDERECO',
            ))
            ->addColumn(array(
                'label' => 'Picking',
                'index' => 'PICKING'
            ))
            ->addColumn(array(
                'label' => 'Validade',
                'index' => 'VALIDADE',
            ))
            ->addColumn(array(
                'label' => 'Dias P/ Vencer',
                'index' => 'DIASVENCER'
            ))
            ->addColumn(array(
                'label' => 'Quantidade',
                'index' => 'QTD_MAIOR',
                'width' => 'auto'
            ))
            ->addLogicalFeatured(
                function ($row){
                    $dt = date_create_from_format('d/m/Y', $row['VALIDADE']) ;
                    $now = date_create_from_format('d/m/Y', date('d/m/Y'));
                    return $dt <= $now;
                }
            )
        ;

        return $this;
    }
}
