<?php

namespace Wms\Module\Web\Form\Relatorio\Ressuprimento;

use Wms\Module\Web\Form;

/**
 * Description of FiltroDadosOnda
 *
 * @author Michel Castro <mlaguardia@gmail.com>
 */
class FiltroDadosReservaInativa extends Form
{

    public function init()
    {


        $botao = $this->createElement('button','submitButton');
        $botao->setLabel('Gerar Relatório')
            ->setAttribs(array(
                'id' => 'gerar',
                'data-relatorio' => 'relatorio-ondas',
                'data-tipo' => 'pdf',
                'class'=>'btn'
            ));

        //form's attr
        $this->setAttribs(array(
            'method' => 'post',
            'class' => 'filtro',
            //'target' => '_blank',
            'action' => '/relatorios-simples/imprimir',
            'id' => 'relatorios-form',
        ));

        $this->addElement('date', 'dataInicial', array(
                'size' => 20,
                'label' => 'Data Início'
            ))
            ->addElement('date', 'dataFinal', array(
                'label' => 'Data Fim',
                'size' => 10
            ))
                ->addElement($botao)
                ->addDisplayGroup($this->getElements(), array('legend' => 'Busca')
        );
    }

}