<?php
namespace Wms\Module\InventarioNovo\Form;
use Core\Form\SubForm;
use Wms\Module\InventarioNovo\Form\Subform\ComparativoInventario\Produtos;
use Wms\Module\Web\Form;

class ResultadoComparativoInventarioForm extends Form
{
    public function init()
    {
        $this->setAttribs(array('id' => 'inventario-comparativo-form', 'class' => 'saveForm'));
        $this->addSubFormTab('Resultado Inventário WMS', new Produtos(), 'resultado-inventario','comparativo-inventario/produtos.phtml');
        $this->addSubFormTab('Inventário Gerado ERP', new Produtos(), 'inventario-erp','comparativo-inventario/produtos.phtml');
        $this->addSubFormTab('ERP e WMS Simultaneamente', new Produtos(), 'inventario-erp-wms','comparativo-inventario/produtos.phtml');
        $this->addSubFormTab('Somente WMS', new Produtos(), 'apenas-wms','comparativo-inventario/produtos.phtml');
        $this->addSubFormTab('Somente ERP', new Produtos(), 'apenas-erp','comparativo-inventario/produtos.phtml');
    }

    public function setDefaultsGrid($values) {
        $this->getSubForm('resultado-inventario')->setDefaultsGrid($values['resultado-inventario']);
        $this->getSubForm('inventario-erp')->setDefaultsGrid($values['inventario-erp']);
        $this->getSubForm('inventario-erp-wms')->setDefaultsGrid($values['inventario-erp-wms']);
        $this->getSubForm('apenas-wms')->setDefaultsGrid($values['apenas-wms']);
        $this->getSubForm('apenas-erp')->setDefaultsGrid($values['apenas-erp']);
    }

}