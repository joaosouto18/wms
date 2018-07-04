<?php

namespace Wms\Module\Web\Form\Pessoa\Fisica;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Filtro extends \Wms\Module\Web\Form
{

    public function init()
    {
        $formIdentificacao = new \Core\Form\SubForm();

        $this->setAttrib('class', 'filtro');

        $this->addElement('text', 'nome', array(
                    'label' => 'Nome',
                    'size' => 60,
                    'maxlength' => 50,
                    'class' => 'focus',
                ))
                ->addElement('cpf', 'cpf', array(
                    'label' => 'CPF',
                ))
                ->addElement('submit', 'submit', array(
                    'label' => 'Buscar',
                    'class' => 'btn',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('nome', 'cpf', 'submit'), 'identificacao', array('legend' => 'Filtros de Busca'));
    }

    /**
     *
     * @param array $data
     * @return boolean 
     */
    public function isValid($data)
    {
        $valid = parent::isValid($data);

        if (!$data) {
            return false;
        }
        extract($data);

        if ($this->checkAllEmpty()) {
            $valid = false;
        }

        return $valid;
    }

}