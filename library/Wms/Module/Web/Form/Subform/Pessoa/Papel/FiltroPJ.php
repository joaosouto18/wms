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


        $this->addElement('text', 'codigo', array(
                    'class' => 'caixa-alta focus',
                    'size' => 5,
                    'label' => 'Código',
                ))
                ->addElement('text', 'nome', array(
                    'class' => 'caixa-alta focus',
                    'size' => 45,
                    'label' => 'Nome',
                ))
                ->addElement('document', 'cpfCnpj', array(
                    'label' => 'CPF/CNPJ',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('codigo', 'nome', 'cpfCnpj', 'submit'), 'identificacao', array('legend' => 'Filtros de Busca'));
    }

}
