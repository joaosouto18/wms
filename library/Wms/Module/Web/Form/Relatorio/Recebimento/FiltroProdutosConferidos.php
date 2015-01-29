<?php

namespace Wms\Module\Web\Form\Relatorio\Recebimento;

use Wms\Domain\Entity\Recebimento,
    Wms\Module\Web\Form;

/**
 * Description of FiltroProdutosConferidos
 *
 * @author Adriano Uliana <adriano.uliana@rovereti.com.br>
 */
class FiltroProdutosConferidos extends Form
{

    public function init()
    {
        $em = $this->getEm();

        $this->setAttribs(array(
            'method' => 'get',
            'class' => 'filtro',
            'id' => 'filtro-produtos-conferidos-form',
        ));

        $this->addElement('text', 'idRecebimento', array(
                    'size' => 10,
                    'label' => 'Número do Recebimento',
                    'class' => 'focus',
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
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('idRecebimento', 'dataInicial1', 'dataInicial2', 'dataFinal1', 'dataFinal2', 'submit'), 'identificacao', array('legend' => 'Busca')
        );

        $this->setDecorators(array(array('ViewScript', array('viewScript' => 'relatorio/produtos-conferidos/filtro.phtml'))));
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