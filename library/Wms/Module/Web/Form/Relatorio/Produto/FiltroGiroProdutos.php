<?php

namespace Wms\Module\Web\Form\Relatorio\Produto;

use Wms\Module\Web\Form;

class FiltroGiroProdutos extends Form
{

    public function init()
    {
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
            ->addElement('text', 'linhaSeparacao', array(
                'label' => 'Linha Separação',
                'size' => 10,
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('codProduto', 'dataInicio', 'dataFinal', 'quebra', 'linhaSeparacao', 'submit'), 'identificacao', array('legend' => 'Busca')
        );

//        $this->setDecorators(array(array('ViewScript', array('viewScript' => 'relatorio/produtos-conferidos/filtro.phtml'))));
    }

    /**
     *
     * @param array $params
     * @return boolean 
     */
    public function isValid($params)
    {
        extract($params);

        if (!parent::isValid($params))
            return false;

        if ($this->checkAllEmpty())
            return false;

        if (($dataInicial1 && !$dataInicial2) || ($dataFinal1 && !$dataFinal2) || (!$dataInicial1 && $dataInicial2) || (!$dataFinal1 && $dataFinal2)) {
            $this->addError('Favor preencher corretamente o intervalo de datas');
            return false;
        }

        return true;
    }

}