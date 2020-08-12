<?php

namespace Wms\Module\Web\Form\NotaFiscal;

use Wms\Domain\Entity\Pessoa\Papel\Emissor;
use Wms\Module\Web\Form;
use \Wms\Domain\Entity\NotaFiscal\Tipo as TipoNF;

class Tipo extends Form
{

    public function init()
    {
        // form's attr
        $this->setAttribs(array('class' => 'saveForm'));

        $this->addElement('hidden', 'id')
            ->addElement('text', 'descricao', array(
                'required' => true,
                'label' => 'Descrição',
                'class' => 'focus'
            ))
            ->addElement('text', 'codExterno', array(
                'required' => true,
                'label' => 'Cód. Externo'
            ))
            ->addElement('select', 'emissor', array(
                'required' => true,
                'mostrarSelecione' => false,
                'label' => 'Tipo Emissor',
                'multiOptions' => Emissor::$arrResponsaveis,
                'value' => Emissor::EMISSOR_FORNECEDOR,
            ))
            ->addDisplayGroup(
                array('descricao', 'emissor', 'codExterno'), 'group', array('legend' => 'Tipo de Nota de Entrada')
            );
    }

    /**
     * Sets the values from entity
     * @param TipoNF $tipo
     */
    public function setDefaultsFromEntity(TipoNF $tipo)
    {

        $values = array(
            'id' => $tipo->getId(),
            'descricao' => $tipo->getDescricao(),
            'codExterno' => $tipo->getCodExterno(),
            'emissor' => $tipo->getEmissor(),
        );

        $this->setDefaults($values);
    }

}

