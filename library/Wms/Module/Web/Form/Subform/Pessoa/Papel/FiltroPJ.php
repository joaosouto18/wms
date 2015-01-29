<?php

namespace Wms\Module\Web\Form\Subform\Pessoa\Papel;

use Wms\Module\Web\Form;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class FiltroPJ extends Form
{

    public function init()
    {
        $this->setAttrib('method', 'get');


        $this->addElement('text', 'nome', array(
                    'class' => 'caixa-alta focus',
                    'size' => 50,
                    'label' => 'Razão Social',
                ))
                ->addElement('text', 'nomeFantasia', array(
                    'class' => 'caixa-alta',
                    'size' => 50,
                    'label' => 'Nome Fantasia',
                ))
                ->addElement('cnpj', 'cnpj', array(
                    'label' => 'CNPJ',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('nome', 'nomeFantasia', 'cnpj', 'submit'), 'identificacao', array('legend' => 'Filtros de Busca'));
    }

}
