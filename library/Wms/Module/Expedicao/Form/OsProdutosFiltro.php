<?php
namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class OsProdutosFiltro extends Form
{

    public function init($volumes = array())
    {
        $this->setAttribs(array('id' => 'os-volume-filtro-form', 'class' => 'saveForm'))
            ->setMethod('get');
        $this->addElement('select', 'volumes', array(
                'label' => 'Volume Patrimonio',
                'multiOptions' => $volumes,
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('submit', 'exportarpdf', array(
                'label' => 'Exportar PDF',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('volumes', 'submit', 'exportarpdf'), 'identificacao', array('legend' => 'Busca')
        );
    }

}