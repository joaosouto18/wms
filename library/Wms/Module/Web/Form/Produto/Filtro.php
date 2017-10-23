<?php

namespace Wms\Module\Web\Form\Produto;

use Wms\Module\Web\Form;

/**
 * Description of Regra
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Filtro extends Form
{

    public function init()
    {
        $em = $this->getEm();
        
        //form's attr
        $this->setAttribs(array(
            'id' => 'produto-regra-form',
            'method' => 'get',
            'class' => 'filtro',
        ));

        $produtoClasses = $em->getRepository('wms:Produto\Classe')->getIdValue();
        
        $this->addElement('text', 'id', array(
                    'label' => 'Código',
                    'size' => 10,
                    'maxlength' => 100,
                    'class' => 'focus',
                ))
                ->addElement('text', 'grade', array(
                    'label' => 'Grade',
                    'size' => 13,
                    'maxlength' => 64,
                ))
                ->addElement('text', 'descricao', array(
                    'label' => 'Descrição',
                    'size' => 30,
                    'maxlength' => 40,
                ))
                ->addElement('text', 'fabricante', array(
                    'label' => 'Fabricante',
                    'size' => 30,
                    'maxlength' => 40,
                ))
                ->addElement('select', 'classe', array(
                    'label' => 'Classe',
                    'multiOptions' => array('firstOpt' => 'Selecione...', 'options' => $produtoClasses),
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('id', 'grade', 'descricao', 'fabricante', 'classe', 'submit'), 'identificacao', array('legend' => 'Filtros de Busca'));
    }

    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\Produto\Regra
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Produto\Regra $regra)
    {
        $values = array(
            'id' => $regra->getId(),
            'descricao' => $regra->getDescricao()
        );
        $this->setDefaults($values);
    }

    /**
     *
     * @param array $data
     * @return boolean 
     */
    public function isValid($data)
    {
        $valid = parent::isValid($data);

        if ($this->checkAllEmpty())
            $valid = false;

        return $valid;
    }

}