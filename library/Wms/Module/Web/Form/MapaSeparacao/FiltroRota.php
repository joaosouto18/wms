<?php

namespace Wms\Module\Web\Form\MapaSeparacao;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class FiltroRota extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'movimentacao-rota-filtro-form', 'class' => 'saveForm'));

        $em = $this->getEm();

        $this
                ->addElement('text', 'id', array(
                    'label' => 'Código Rota',
                    'class' => 'pequeno',
                    'class' => 'caixa-alta',
                    'alt' => 'id'
                ))
                ->addElement('text', 'nomeRota', array(
                    'label' => 'Nome Rota',
                    'class' => 'pequeno',
                    'class' => 'caixa-alta',
                    'alt' => 'nomeRota'
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array( 'id', 'nomeRota', 'submit'), 'veiculo', array('legend' => 'Filtros de Busca'));
    }



}
