<?php

namespace Wms\Module\Web\Form\MapaSeparacao;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class FiltroPraca extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'movimentacao-praca-filtro-form', 'class' => 'saveForm'));

        $em = $this->getEm();

        $this
                ->addElement('text', 'id', array(
                    'label' => 'Código Praça',
                    'class' => 'pequeno',
                    'class' => 'caixa-alta',
                    'alt' => 'id'
                ))
                ->addElement('text', 'nomePraca', array(
                    'label' => 'Nome Praça',
                    'class' => 'pequeno',
                    'class' => 'caixa-alta',
                    'alt' => 'nomePraca'
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array( 'id', 'nomePraca', 'submit'), 'veiculo', array('legend' => 'Filtros de Busca'));
    }



}
