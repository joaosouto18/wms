<?php
namespace Wms\Module\Armazenagem\Form\ProdutosPickingIncorreto;

use Wms\Module\Web\Form;
use Wms\Util\Endereco;

class Filtro extends Form
{

    public function init()
    {
        $repoLinhaSeparacao = $this->getEm()->getRepository('wms:Armazenagem\LinhaSeparacao');

        $this
                ->setAttribs(array(
                    'method' => 'get',
                    'class' => 'filtro',
                    'id' => 'filtro-por-rua',
                ))
                ->addElement('text', 'inicioRua', array(
                    'size' => 4,
                    'alt' => 'enderecoRua',
                    'label' => 'Inicio Rua',
                    'class' => 'focus',
                ))
                ->addElement('text', 'fimRua', array(
                    'size' => 4,
                    'alt' => 'enderecoRua',
                    'label' => 'Fim Rua',
                ))
                ->addElement('multiselect', 'grandeza', array(
                    'label' => 'Linha Separação',
                    'style' => 'height:auto; width:100%',
                     'multiOptions' => $repoLinhaSeparacao->getIdValue()
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))

            ->addDisplayGroup(array('inicioRua', 'fimRua', 'grandeza', 'submit'), 'identificacao', array('legend' => 'Busca'));
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

        return true;
    }

}