<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form,
    Wms\Domain\Entity\Ajuda as AjudaEntity;

/**
 * Form do menu
 * @author Augusto Vespermann
 */
class Empresa extends Form
{

    public function init()
    {
        $ajuda = $this->getEm()->getRepository('wms:Empresa');
        $recursoAcao = $this->getEm()->getRepository('wms:Sistema\Recurso\Vinculo');

        // form's attr
        $this->setAttribs(array('id' => 'empresa-form', 'class' => 'saveForm'));

        $this->addElement('text', 'nomEmpresa', array(
            'required' => true,
            'label' => 'Nome da Empresa',
            'class' => 'focus',
        ))
            ->addElement('cnpj', 'identificacao', array(
                'required' => true,
                'label' => 'CNPJ'
            ))

            ->addElement('numeric', 'prioridadeEstoque', array(
                'required' => true,
                'label' => 'Prioridade de Estoque'
            ))
            ->addDisplayGroup(
                array('nomEmpresa', 'identificacao', 'prioridadeEstoque'), 'empresa', array('legend' => 'Dados da Empresa')
            );
    }

    /**
     * Sets the values from entity
     * @param \Wms\Entity\Empresa $empresa
     */
    public function setDefaultsFromEntity(\Wms\Domain\Entity\Empresa $empresa)
    {

        $values = array(
            'id' => $empresa->getId(),
            'nomEmpresa' => $empresa->getNomEmpresa(),
            'identificacao' => $empresa->getIdentificacao(),
            'prioridadeEstoque' => $empresa->getPrioridadeEstoque(),
        );

        $this->setDefaults($values);
    }

}

