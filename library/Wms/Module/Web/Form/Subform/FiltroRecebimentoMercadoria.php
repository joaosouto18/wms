<?php

namespace Wms\Module\Web\Form\Subform;

use Wms\Domain\Entity\Recebimento;

/**
 * Description of FiltroRecebimentoMercadoria
 *
 * @author Augusto Vespermann 
 */
class FiltroRecebimentoMercadoria extends \Wms\Module\Web\Form
{

    public function init()
    {
        $em = $this->getEm();
        $repoSigla = $em->getRepository('wms:Util\Sigla');

        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro',
            'id' => 'filtro-recebimento-mercadoria-form',
        ));
        
        $this->addElement('text', 'idRecebimento', array(
                    'size' => 10,
                    'label' => 'Número do Recebimento',
                    'class' => 'focus',
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('text', 'uma', array(
                    'size' => 10,
                    'label' => 'UMA',
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
                ->addElement('date', 'dataFinal1', array(
                    'label' => 'Data Final do Recebimento',
                    'size' => 10,
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('date', 'dataFinal2', array(
                    'size' => 10,
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('select', 'status', array(
                    'label' => 'Status do Recebimento',
                    'multiOptions' => array('firstOpt' => 'Todos', 'options' => $repoSigla->getIdValue(50)),
                    'decorators' => array('ViewHelper'),
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('idRecebimento', 'uma', 'dataInicial1','dataInicial2', 'dataFinal1', 'dataFinal2', 'status', 'submit'), 'identificacao', array('legend' => 'Busca')
        );

        $this->setDecorators(array(array('ViewScript', array('viewScript' => 'recebimento/filtro.phtml'))));
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