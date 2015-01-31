<?php

namespace Wms\Module\Web\Form\Subform;

//use Wms\Domain\Entity\Recebimento;

/**
 * Description of FiltroRecebimentoMercadoria
 *
 * @author Lucas Chinelate
 */
class FiltroExpedicaoMercadoria extends \Wms\Module\Web\Form
{

    public function init()
    {
        $em = $this->getEm();
        $repoSigla = $em->getRepository('wms:Util\Sigla');

        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro',
            'id' => 'filtro-expedicao-mercadoria-form',
        ));
        
        $this->addElement('text', 'idExpedicao', array(
                    'size' => 10,
                    'label' => 'Código da Expedicao',
                    'class' => 'focus',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('date', 'dataInicial1', array(
                    'size' => 20,
                    'label' => 'Data Inicio da Expedição',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('date', 'dataInicial2', array(
                    'size' => 20,
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('date', 'dataFinal1', array(
                    'label' => 'Data Final da Expedição',
                    'size' => 10,
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('date', 'dataFinal2', array(
                    'size' => 10,
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('select', 'status', array(
                    'label' => 'Status da Expedição',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoSigla->getIdValue(53)),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('text', 'codCargaExterno', array(
                    'size' => 10,
                    'label' => 'Carga',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('text', 'placa', array(
                    'size' => 10,
                    'label' => 'Placa',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('hidden', 'control', array(
                    'value' => 'roll',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup($this->getElements(), 'identificacao', array('legend' => 'Busca')
        );

        $this->setDecorators(array(array('ViewScript', array('viewScript' => 'index/filtro.phtml'))));
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