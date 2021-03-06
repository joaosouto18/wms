<?php

namespace Wms\Module\Expedicao\Form;

use Wms\Module\Web\Form;

class CheckoutExpedicao extends Form {

    public function init() {
        $this->setAttribs(array(
            'method' => 'post',
            'class' => 'filtro',
            'id' => 'checkout-expedicao',
        ));
        $this->addElement('text', 'codigoBarrasMapa', array(
                'size' => 25,
                'label' => 'Código de Barras Mapa',
                'class' => 'ctrSize',
                'alt' => 'number',
            ))
            ->addElement('text', 'cpfEmbalador', array(
                'size' => 14,
                'alt' => 'cpf',
                'label' => 'CPF Embalador',
                'class' => 'ctrSize',
            ))
            ->addElement('submit', 'completar', array(
                'label' => 'Buscar',
                'class' => 'btn buscar-mapa',
                'decorators' => array('ViewHelper')
            ))
            ->addDisplayGroup(array('codigoBarrasMapa', 'cpfEmbalador', 'completar'), 'identificacao', array('legend' => ''));
    }

}
