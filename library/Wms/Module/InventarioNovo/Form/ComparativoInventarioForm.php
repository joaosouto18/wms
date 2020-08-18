<?php
namespace Wms\Module\InventarioNovo\Form;
use Core\Form\SubForm;
use Wms\Module\InventarioNovo\Form\Subform\ComparativoInventario\Produtos;
use Wms\Module\Web\Form;

class ComparativoInventarioForm extends Form
{
    public function init($showExport = false, $showObs = false)
    {
        $this->addElement('text', 'codInventarioERP', array(
                'label' => 'Cód. Inventário (ERP)',
                'class' => 'focus'
            ))
            ->addElement('submit', 'btnSubmit', array(
                'class' => 'btn',
                'label' => 'Buscar',
                'decorators' => array('ViewHelper'),
                'attribs' => array('style' => 'margin-top:16px')
            ));
        if ($showExport == true) {
            $this->addElement('submit', 'btnExport', array(
                'class' => 'btn',
                'label' => 'Exportar',
                'decorators' => array('ViewHelper'),
                'attribs' => array('style' => 'margin-top:16px')
            ));
        }

        $obs = "";
        if ($showObs != false) {
            $obs = "Atenção: Serão exportados apenas os produtos presentes simultaneamente nos inventários do WMS e do ERP";
        }

        $this->addElement('hidden','obs', array(
            'value'=> $obs
        ));

        $this->addDisplayGroup($this->getElements(), 'Buscar', array('legend' => 'Inventário ERP'));
        $this->setDecorators(array(array('ViewScript', array('viewScript' => 'comparativo-inventario/filtro.phtml'))));
    }

}