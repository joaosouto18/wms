<?php

namespace Wms\Module\Web\Form;

use Wms\Module\Web\Form,
    Core\Form\SubForm,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity;

/**
 * Description of SystemContextParam
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class OrdemServico extends Form
{

    public function init()
    {
        $em = $this->getEm();

        $this->addElement('hidden', 'idRecebimento')
                ->addElement('submit', 'btnSubmit', array(
                    'class' => 'btn',
                    'label' => 'Gerar Ordem de Serviço para Conferência Cega',
                    'decorators' => array('ViewHelper'),
                ))
                ->addDisplayGroup(array('btnSubmit'), 'identificacao', array('legend' => 'Clique abaixo para gerar a conferencia cega'));
        
        $this->setDecorators(array(array('ViewScript', array('viewScript' => 'recebimento/ordem-servico-form.phtml'))));
    }
    
    /**
     * Sets the values from entity
     * @param \Wms\Domain\Entity\OrdemServico
     */
    public function setDefaultsFromEntity(OrdemServicoEntity $ordemServico)
    {
        $values = array(
            'identificacao' => array(
                'id' => $ordemServico->getId(),
            )
        );

        $this->setDefaults($values);
    }

}
