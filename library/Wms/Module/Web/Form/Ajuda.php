<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form,
    Wms\Domain\Entity\Ajuda as AjudaEntity;

/**
 * Form do menu
 * @author Augusto Vespermann
 */
class Ajuda extends Form
{

    public function init()
    {
        $ajuda = $this->getEm()->getRepository('wms:Ajuda');
        $recursoAcao = $this->getEm()->getRepository('wms:Sistema\Recurso\Vinculo');

        // form's attr
        $this->setAttribs(array('id' => 'ajuda-form', 'class' => 'saveForm'));

        $this->addElement('text', 'dscAjuda', array(
                    'required' => true,
                    'label' => 'Título Ajuda',
                    'class' => 'focus',
                ))
                ->addElement('numeric', 'numPeso', array(
                    'required' => true,
                    'label' => 'Peso'
                ))
                ->addElement('textarea', 'dscConteudo', array(
                    'required' => true,
                    'label' => 'Texto da Ajuda',
                    'rows' => '15',
                    'cols' => '100',
                ))
                ->addElement('select', 'idRecursoAcao', array(
                    'label' => 'Recurso Ação',
                    'multiOptions' => $recursoAcao->getIdValue()
                ))
                ->addElement('select', 'idAjudaPai', array(
                    'label' => 'Ajuda Pai',
                    'multiOptions' => $ajuda->getIdValue()
                ))
                ->addDisplayGroup(
                        array('dscAjuda', 'numPeso', 'idRecursoAcao', 'idAjudaPai'), 'ajuda', array('legend' => 'Dados da Ajuda')
                )
                ->addDisplayGroup(
                        array('dscConteudo'), 'conteudo', array('legend' => 'Conteúdo da Ajuda')
        );
    }

    /**
     * Sets the values from entity
     * @param \Wms\Entity\Ajuda $ajuda
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Ajuda $ajuda)
    {

        $values = array(
            'id' => $ajuda->getId(),
            'dscAjuda' => $ajuda->getDscAjuda(),
            'numPeso' => $ajuda->getNumPeso(),
            'dscConteudo' => $ajuda->getDscConteudo(),
            'idAjudaPai' => $ajuda->getIdAjudaPai(),
            'idRecursoAcao' => $ajuda->getRecursoAcao()->getId(),
        );

        $this->setDefaults($values);
    }

}

