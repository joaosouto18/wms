<?php

namespace Wms\Module\Mobile\Form;

class Nivel extends Mobile
{
    protected $_label = 'Endereço';
    protected $_labelCampo = 'Nível';
    protected $_nomeCampo = 'nivel';

    public function init()
    {
        $this->addElement('hidden', 'codigoBarras');
        parent::init();
    }

}