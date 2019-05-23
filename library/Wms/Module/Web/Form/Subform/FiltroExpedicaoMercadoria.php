<?php

namespace Wms\Module\Web\Form\Subform;

use Wms\Domain\Entity\Util\SiglaRepository,
    Wms\Domain\Entity\Expedicao;

/**
 * Description of FiltroRecebimentoMercadoria
 *
 * @author Lucas Chinelate
 */
class FiltroExpedicaoMercadoria extends \Wms\Module\Web\Form
{

    public function init($action = "/expedicao")
    {
        //$s = new Zend_Session_Namespace('sessionUrl');
        $label=$action;
       //if ( !empty($s->action))
           //$label=$s->action;
        $em = $this->getEm();
        /** @var SiglaRepository $repoSigla */
        $repoSigla = $em->getRepository('wms:Util\Sigla');

        $notStatus = array(Expedicao::STATUS_CANCELADO, Expedicao::STATUS_FINALIZADO);

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
                ->addElement('text', 'pedido', array(
                    'size' => 10,
                    'label' => 'Pedido',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('text', 'produto', array(
                    'size' => 10,
                    'label' => 'Produto',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('hidden', 'control', array(
                    'value' => 'roll',
                    'label' => $label,
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ));

        $this->addElement('text', 'produtividade', array(
            'size' => 10,
            'label' => 'produtividade',
            'decorators' => array('ViewHelper'),
        ));

        $this->addDisplayGroup($this->getElements(), 'identificacao', array('legend' => 'Busca'));

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