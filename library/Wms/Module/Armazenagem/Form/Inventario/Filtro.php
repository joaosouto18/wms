<?php
namespace Wms\Module\Armazenagem\Form\Inventario;

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
                    'id' => 'filtro-inventario-por-rua',
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
                ->addElement('checkbox', 'picking', array(
                    'label' => 'Picking',
                    'checked' => true
                ))
                ->addElement('checkbox', 'pulmao', array(
                    'label' => 'Pulmão',
                    'checked' => true
                ))
                ->addElement('select', 'tipo', array(
                    'label' => 'Tipo de Inventário',
					'mostrarSelecione' => false,
                    'style' => 'height:auto; width:100%',
                     'multiOptions' => array('C'=> 'Inventário Completo','P'=>'Inventário Parcial')
                ))
                ->addElement('checkbox', 'mostraEstoque', array(
                    'label' => 'Imprimir o Estoque',
                    'checked' => false
                ))

            ->addDisplayGroup(array('inicioRua', 'fimRua', 'grandeza','mostraEstoque','tipo', 'submit'), 'identificacao', array('legend' => 'Busca'));
            $subGroup = $this->addDisplayGroup(array('picking','pulmao'),'tipoEndereco',array('legend'=>'Tipo de Endereço'));
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