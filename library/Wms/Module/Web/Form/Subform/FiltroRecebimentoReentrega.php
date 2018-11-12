<?php

namespace Wms\Module\Web\Form\Subform;

use Wms\Domain\Entity\Recebimento;

class FiltroRecebimentoReentrega extends \Wms\Module\Web\Form
{

    public function init()
    {
        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro',
            'id' => 'filtro-recebimento-reentrega-form',
        ));

        $this->addElement('text', 'notaFiscal', array(
            'size' => 10,
            'label' => 'Número da Nota Fiscal',
            'decorators' => array('ViewHelper'),
        ))
            ->addElement('date', 'dataInicial1', array(
                'size' => 20,
                'label' => 'Data Inicio do Recebimento',
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('date', 'dataInicial2', array(
                'size' => 20,
                'decorators' => array('ViewHelper'),
            ))
            ->addElement('submit', 'submit', array(
                'label' => 'Buscar',
                'class' => 'btn',
                'decorators' => array('ViewHelper'),
            ))
            ->addDisplayGroup(array('notaFiscal', 'dataInicial1', 'dataInicial2', 'submit'), 'identificacao', array('legend' => 'Busca')
            );

        $this->setDecorators(array(array('ViewScript', array('viewScript' => 'reentrega/filtro-reentrega.phtml'))));
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

        return true;
    }

}