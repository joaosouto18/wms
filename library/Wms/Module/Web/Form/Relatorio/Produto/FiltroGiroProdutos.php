<?php

namespace Wms\Module\Web\Form\Relatorio\Produto;

use Wms\Module\Web\Form;

class FiltroGiroProdutos extends Form
{

    public function init()
    {
        $repoLinhaSeparacao = $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao');
        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro',
            'id' => 'filtro--giro-produto-form',
        ));

        $this
            ->addElement('text', 'codProduto', array(
                'size' => 10,
                'label' => 'Código Produto',
                'class' => 'focus',
            ))
            ->addElement('date', 'dataInicio', array(
                'size' => 20,
                'label' => 'Data Inicio',
            ))
            ->addElement('date', 'dataFinal', array(
                'size' => 20,
                'label' => 'Data Fim'
            ))
            ->addElement('text', 'quebra', array(
                'label' => 'Tipo Quebra Mapa',
                'size' => 10,
            ))
            ->addElement('select','linhaSeparacao', array(
                'label' => 'Linha Separação',
                'multiOptions' => array('firstOpt' => ' Todos', 'options' => $repoLinhaSeparacao->getIdValue()),
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('codProduto', 'dataInicio', 'dataFinal', 'quebra', 'linhaSeparacao', 'submit'), 'identificacao', array('legend' => 'Busca')
        );

    }

}