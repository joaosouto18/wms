<?php

namespace Wms\Module\Web\Form\Relatorio\Recebimento;

use Wms\Domain\Entity\Recebimento,
    Wms\Module\Web\Form;

/**
 * Descrição de FiltroProdutoRecebido
 *
 * @author Adriano Uliana <adriano.uliana@rovereti.com.br>
 */
class FiltroProdutoRecebido extends Form
{

    public function init($utilizaGrade = 'S')
    {
        $em = $this->getEm();

        $this->setAction($this->getView()->url(array('controller' => 'relatorio_produto-recebido', 'action' => 'index')))
                ->setAttribs(array(
                    'method' => 'get',
                    'class' => 'filtro',
                    'id' => 'filtro-produtos-conferidos-form',
                ))
                ->addElement('text', 'idRecebimento', array(
                    'size' => 10,
                    'label' => 'Cod. Recebimento',
                    'class' => 'focus'
                ))
                ->addElement('text', 'idProduto', array(
                    'size' => 12,
                    'label' => 'Cod. produto',
                ));
                if ($utilizaGrade == "S") {
                    $this->addElement('text', 'grade', array(
                        'size' => 12,
                        'label' => 'Grade',
                    ));
                } else {
                    $this->addElement('hidden', 'grade', array(
                        'label' => 'Grade',
                        'value' => 'UNICA'
                    ));
                }
                $this->addElement('text', 'descricao', array(
                    'size' => 30,
                    'label' => 'Descrição',
                ))
                ->addElement('date', 'dataFinal1', array(
                    'label' => 'Data Inicial do Recebimento',
                    'size' => 10,
                ))
                ->addElement('date', 'dataFinal2', array(
                    'label' => 'Data Final de Recebimento',
                    'size' => 10,
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('idRecebimento', 'idProduto', 'grade', 'descricao', 'dataFinal1', 'dataFinal2', 'submit'), 'identificacao', array('legend' => 'Busca')
        );
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