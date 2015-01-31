<?php

namespace Wms\Module\Web\Form\MapaSeparacao;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class FiltroModeloSeparacao extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'movimentacao-modelo-separacao-filtro-form', 'class' => 'saveForm'));

        $em = $this->getEm();

        $this
                ->addElement('text', 'id', array(
                    'label' => 'Código Modelo de Separação',
                    'class' => 'pequeno',
                    'class' => 'caixa-alta',
                    'alt' => 'placaVeiculo'
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array( 'id', 'submit'), 'veiculo', array('legend' => 'Filtros de Busca'));
    }



}
