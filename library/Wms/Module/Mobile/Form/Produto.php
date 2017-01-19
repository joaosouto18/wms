<?php

namespace Wms\Module\Mobile\Form;

class Produto extends Mobile
{
    protected $_label = 'Busca por produto';
    protected $_labelCampo = 'Busca por produto';

    public function init()
    {
        $this->addElement('hidden', 'idEndereco');
        $this->addElement('hidden', 'codigoBarrasEndereco');
        $this->addElement('hidden', 'idContagemOs');
        $this->addElement('hidden', 'idInventarioEnd');
        $this->addElement('hidden', 'numContagem');
        $this->addElement('hidden', 'contagemEndId');
        parent::init();
    }

}