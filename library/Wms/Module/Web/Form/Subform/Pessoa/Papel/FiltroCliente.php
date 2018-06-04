<?php

namespace Wms\Module\Web\Form\Subform\Pessoa\Papel;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class FiltroCliente extends \Wms\Module\Web\Form
{

    public function init()
    {
        $this->addElement('text', 'nome', array(
                    'class' => 'caixa-alta focus',
                    'size' => 60,
                    'label' => 'Nome',
                ))
                ->addElement('text', 'cpf', array(
                    'class' => 'medio',
                    'alt' => 'numero',
                    'label' => 'CNPJ/CPF',
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

        if (strlen($cpf) != 11 && strlen($cpf) != 14 && strlen($cpf) != 0) {
            $this->addError('Favor preencher o CPF/CNPJ completo.');
            $valid = false;
        }
        return $valid;
    }

}