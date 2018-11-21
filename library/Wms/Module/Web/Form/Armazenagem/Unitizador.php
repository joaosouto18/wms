<?php

namespace Wms\Module\Web\Form\Armazenagem;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Unitizador extends \Wms\Module\Web\Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'armazenagem-unitizador-form', 'class' => 'saveForm calcular-medidas'));

        $formIdentificacao = new \Core\Form\SubForm();
        $formIdentificacao->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'class' => 'caixa-alta focus',
                    'maxlength' => 60,
                    'required' => true,
                    'size' => 67,
                ))
                ->addElement('text', 'largura', array(
                    'label' => 'Largura(m)',
                    'class' => 'parametro-cubagem parametro-area',
                    'id' => 'largura',
                    'alt' => 'centesimal',
                    'required' => true,
                    'size' => 15,
                ))
                ->addElement('text', 'altura', array(
                    'label' => 'Altura(m)',
                    'class' => 'parametro-cubagem',
                    'id' => 'altura',
                    'alt' => 'centesimal',
                    'required' => true,
                    'size' => 15,
                ))
                ->addElement('text', 'profundidade', array(
                    'label' => 'Profundidade(m)',
                    'class' => 'parametro-cubagem parametro-area',
                    'id' => 'profundidade',
                    'alt' => 'centesimal',
                    'required' => true,
                    'size' => 15,
                ))
                ->addElement('text', 'area', array(
                    'label' => 'Area(m²)',
                    'id' => 'area',
                    'alt' => 'centesimal',
                    'required' => true,
                    'readonly' => true,
                    'size' => 15,
                ))
                ->addElement('text', 'cubagem', array(
                    'label' => 'Cubagem(m³)',
                    'id' => 'cubagem',
                    'alt' => 'milesimal',
                    'required' => true,
                    'readonly' => true,
                    'size' => 15,
                ))
                ->addElement('text', 'capacidade', array(
                    'label' => 'Capacidade(kg)',
                    'id' => 'capacidade',
                    'alt' => 'centesimal',
                    'required' => true,
                    'size' => 15,
                ))
                ->addElement('text', 'qtdOcupacao', array(
                    'label' => 'End. Ocupados',
                    'id' => 'qtdOcupacao',
                    'required' => true,
                    'size' => 15,
                ))
                ->addDisplayGroup(array('descricao', 'largura', 'altura', 'profundidade', 'area', 'cubagem', 'capacidade', 'qtdOcupacao'), 'identificacao');

        $this->addSubFormTab('Identificação', $formIdentificacao, 'identificacao', 'unitizador/unitizador.phtml');
    }

    /**
     * Sets the values from entity
     * @param \Wms\Entity\Unitizador $tipo 
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Armazenagem\Unitizador $tipo)
    {
        $values = array(
            'identificacao' => array(
                'descricao' => $tipo->getDescricao(),
                'largura' => $tipo->getLargura(),
                'altura' => $tipo->getAltura(),
                'profundidade' => $tipo->getProfundidade(),
                'area' => $tipo->getArea(),
                'cubagem' => $tipo->getCubagem(),
                'capacidade' => $tipo->getCapacidade(),
                'qtdOcupacao' => $tipo->getQtdOcupacao(),
            )
        );

        $this->setDefaults($values);
    }

}

