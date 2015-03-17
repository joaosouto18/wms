<?php

namespace Wms\Module\Web\Form\Relatorio\Ressuprimento;

use Wms\Module\Web\Form;

/**
 * Description of FiltroDadosOnda
 *
 * @author Michel Castro <mlaguardia@gmail.com>
 */
class FiltroDadosOnda extends Form
{

    public function init()
    {

        $em = $this->getEm();
        $repoSigla = $em->getRepository('wms:Util\Sigla');

        $this->addElement('date', 'dataInicial', array(
            'size' => 20,
            'label' => 'Data Início'
        ))
        ->addElement('date', 'dataFinal', array(
            'label' => 'Data Fim',
            'size' => 10
        ))
        ->addElement('select', 'status', array(
            'label' => 'Status das OS',
            'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoSigla->getIdValue(74)),
            'decorators' => array('ViewHelper'),
        ))
        ->addElement('submit', 'submit', array(
            'label' => 'Buscar',
            'class' => 'btn',
            'decorators' => array('ViewHelper'),
        ))
        ->addElement('hidden', 'actionParams', array(
            'values'=>false
        ))

                    ->addDisplayGroup(array('dataInicial','dataFinal', 'status', 'submit','actionParams'),'filtro', array('legend' => 'Busca')
        );
    }

}